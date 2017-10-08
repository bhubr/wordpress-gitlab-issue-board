<?php
class WP_Gitlab_Issue_Board_Configurator {

	const ACCOUNT_NOT_READY_NO_CONFIG = 0;
	const ACCOUNT_NOT_READY_HAS_CONFIG = 1;
	const ACCOUNT_READY = 2;


	/**
	 * Reference to the OAuth2 Provider
	 */
	private $oauth_provider;


	/**
	 * Currently logged-in user
	 */
	private $user = null;


	/**
	 * Account status (internal). Can take one of the 3 const values above
	 */
	private $account_status;


	/**
	 * Account data
	 */
	private $gitlab_account_data = array();


	/**
	 * @var Singleton
	 * @access private
	 * @static
	 */
	 private static $_instance = null;


	/**
	 * Constructor: plug WordPress hooks
	 */
	private function __construct() {

		// This one has to be called first, because it sets the $user property...
		add_action( 'init', array( $this, 'check_user_config_and_account' ), 1 );

		// ... that the second needs
		add_action( 'init', array( $this, 'catch_get_post_requests' ), 5 );

	}


	/**
	 * Create the unique class instance on first call, return it always
	 *
	 * @param void
	 * @return Singleton
	 */
	public static function get_instance() {
	  if( is_null( self::$_instance ) ) {
		  self::$_instance = new WP_Gitlab_Issue_Board_Configurator();
	  }
	  return self::$_instance;
	}


	/**
	 * Shorthand for getting the callback URL
	 */
	private function get_cb_url() {
		return admin_url( 'admin.php' );
	}


	/**
	 * Shorthand for this page URL
	 */
	private function get_page_url() {
		return admin_url( 'admin.php?page=gitlab-issue-board' );
	}


	/**
	 * Check if the user has an app set up and token/user data
	 */
	public function check_user_config_and_account() {
		if( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$this->user = wp_get_current_user();

		// We store all the user's config in a single, serialized user meta
		// Its `config` field holds the parameters for the GitLab app
		// Its `data` field holds the access token and user data
		// In the initial state (config 1/2) it's all empty. Then (step 2/2) it has the `config` key.
		// Then we request authorization from GitLab, and the `data` field is set.
		$this->gitlab_account_data = get_user_meta( $this->user->ID, 'wpglib_account', true );
		if( empty( $this->gitlab_account_data ) ) {
			$this->account_status = self::ACCOUNT_NOT_READY_NO_CONFIG;
		}
		else {
			$this->account_status = isset( $this->gitlab_account_data['data'] ) ?
				$this->account_status = self::ACCOUNT_READY :
				$this->account_status = self::ACCOUNT_NOT_READY_HAS_CONFIG;
		}
	}


	/**
	 * Tells the main class whether or not we're ready
	 */
	public function is_ready() {
		return $this->account_status === self::ACCOUNT_READY;
	}

	/**
	 * Catch the incoming request, if it is one of the three that we need to catch.
	 * If it is, call the appropriate handler
	 */
	public function catch_get_post_requests() {
		if( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$actions = array(
			'post_app_settings', 'post_request_auth', 'get_auth_callback'
		);
		foreach( $actions as $action ) {
			$check_this_action = array( $this, 'check_if_' . $action );
			if( $check_this_action() ) {
				$this->$action();
			}
		}
	}


	/**
	 * Check if this request is a submission from the config form at step 1.
	 */
	public function check_if_post_app_settings() {
		return $_SERVER['REQUEST_METHOD'] === 'POST' &&
			isset( $_GET['page'] ) &&
			$_GET['page'] === 'gitlab-issue-board' &&
			isset( $_POST['_gitlab_app_settings'] ) &&
			$_POST['_gitlab_app_settings'] === '1';
	}


	/**
	 * Store the GitLab app parametters in the `config` field of the user's account meta
	 */
	public function post_app_settings() {
		if( ! wp_verify_nonce( $_POST['_wpnonce'], 'configure-account_'. $this->user->ID ) ) {
			 wp_nonce_ays( 'configure-account_'. $this->user->ID );
		}
		if( empty( $this->gitlab_account_data ) ) {
			$this->gitlab_account_data = array( 'config' => array() );
		}
		$this->gitlab_account_data['config'] = array(
			'clientId' => $_POST['client-id'],
			'clientSecret' => $_POST['client-secret'],
			'domain' => $_POST['domain'],
			'redirectUri' => $this->get_cb_url()
		);
		add_user_meta( $this->user->ID, 'wpglib_account', $this->gitlab_account_data, true );
		wp_redirect( $this->get_page_url() . '&success=1' );
		exit;
	}

	/**
	 * Check if this request is a submission from the config form at step 2.
	 */
	public function check_if_post_request_auth() {
		return $_SERVER['REQUEST_METHOD'] === 'POST' &&
			isset( $_GET['page'] ) &&
			$_GET['page'] === 'gitlab-issue-board' &&
			isset( $_POST['_gitlab_auth_request'] ) &&
			$_POST['_gitlab_auth_request'] === '1';
	}


	/**
	 * Initiate authorization request to GitLab
	 */
	public function post_request_auth() {
		session_start();
		$this->init_gitlab_oauth_provider();

		$authUrl = $this->oauth_provider->getAuthorizationUrl();
		$_SESSION['oauth2state'] = $this->oauth_provider->getState();
		header('Location: '.$authUrl);
		exit;
	}


	/**
	 * Check if this request is a callback request from GitLab after authorization
	 */
	public function check_if_get_auth_callback() {
		return $_SERVER['REQUEST_METHOD'] === 'GET' &&
			isset( $_GET['code'] ) &&
			isset( $_GET['state'] );
	}


	/**
	 * Handle return from GitLab authorization request
	 */
	public function get_auth_callback() {
		session_start();
		$this->init_gitlab_oauth_provider();

		// Check given state against previously stored one to mitigate CSRF attack
		if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

			unset($_SESSION['oauth2state']);
			exit('Invalid state');

		} else {

			// Optional: Now you have a token you can look up a users profile data
			try {

				// Try to get an access token (using the authorization code grant)
				$token = $this->oauth_provider->getAccessToken('authorization_code', [
					'code' => $_GET['code'],
				]);

				// We got an access token, let's now get the user's details
				$user = $this->oauth_provider->getResourceOwner($token);

				$this->gitlab_account_data['data'] = array(
					'accessToken'  => $token->getToken(),
					'user' => array(
						'name'      => $user->getName(),
						'username'  => $user->getUsername(),
						'avatarUrl' => $user->getAvatarUrl()
					)
				);

				update_user_meta( $this->user->ID, 'wpglib_account', $this->gitlab_account_data );
				wp_redirect( $this->get_page_url() . '&success=2' );

			} catch (Exception $e) {
				wp_redirect( $this->get_page_url() . '&error=1' );
			}
		}
	}


	/**
	 * Instantiate the OAuth provider
	 */
	public function init_gitlab_oauth_provider() {
		$this->oauth_provider = new Omines\OAuth2\Client\Provider\Gitlab(
			$this->gitlab_account_data['config']
		);
	}


	/**
	 * Show the settings form
	 */
	public function display_config_form() {
		$values = isset( $this->gitlab_account_data['config'] ) ?
			$this->gitlab_account_data['config'] :
			array(
				'clientId' => '',
				'clientSecret' => '',
				'domain'       => 'https://gitlab.com',
				'redirectUri'  => $this->get_cb_url()
			);
		$params = (
			$this->account_status === self::ACCOUNT_NOT_READY_NO_CONFIG ||
			( isset( $_GET['step'] ) && $_GET['step'] === '1' )
		) ?
			array(
				'page_title'  => 'Setup 1/2 &mdash; Set GitLab app settings',
				'form_key'    => '_gitlab_app_settings',
				'form_label'  => 'Save settings',
				'field_attr'  => 'required',
				'back_class'  => 'hidden',
				'v'           => $values
			) :
			array(
				'page_title'  => 'Setup 2/2 &mdash; Request GitLab authorization ',
				'form_key'    => '_gitlab_auth_request',
				'form_label'  => 'Request authorization',
				'field_attr'  => 'readonly',
				'help_class'  => 'hidden',
				'v'           => $values
			);
		?>
		<h1><?php echo $params['page_title']; ?></h1>
		<?php if( $this->account_status === self::ACCOUNT_NOT_READY_HAS_CONFIG ): ?>
			<p><a href="<?php echo $this->get_page_url(); ?>&amp;step=1">Back to step 1</a></p>
		<?php else: ?>
		<div class="<?php echo $params['help_class']; ?>">
			<p>
				Please fill in your GitLab application settings. You must first create an application in GitLab (<a href="https://gitlab.com/profile/applications">here if you are using gitlab.com</a>).
			</p>
			<p>
				When you create your application, <strong>you must copy-paste the "callback URL" value below</strong> to the corresponding field in the GitLab form.
			</p>
			<p>
				The other fields will be provided to you when you save your application.
			</p>
		</div>
		<?php endif; ?>
		<form method="post" action="" validate>
			<input type="hidden" name="<?php echo $params['form_key']; ?>" value="1" />
			<?php wp_nonce_field( 'configure-account_'. $this->user->ID ); ?>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="callback-url">Callback URL</label></th>
						<td>
							<input readonly name="callback-url" type="text" id="callback-url" aria-describedby="tagline-callback-url" value="<?php echo $params['v']['redirectUri']; ?>" class="regular-text">
							<p class="description <?php echo $params['help_class']; ?>" id="tagline-callback-url">Paste this into the GitLab application form.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="client-id">Client ID</label></th>
						<td><input <?php echo $params['field_attr']; ?> value="<?php echo $params['v']['clientId']; ?>" name="client-id" type="text" id="client-id" placeholder="Your app id" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="client-secret">Client Secret</label></th>
						<td><input <?php echo $params['field_attr']; ?> value="<?php echo $params['v']['clientId']; ?>" name="client-secret" type="text" id="client-secret" placeholder="Your app secret" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="domain">Domain</label></th>
						<td>
							<input <?php echo $params['field_attr']; ?> name="domain" type="text" id="domain" aria-describedby="tagline-domain" placeholder="https://gitlab.com" value="<?php echo $params['v']['domain']; ?>" class="regular-text">
							<p class="description <?php echo $params['help_class']; ?>" id="tagline-domain">This should be https://gitlab.com, unless you use a self-hosted GitLab.</p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $params['form_label']; ?>"></p>
		</form>
	<?php }

}