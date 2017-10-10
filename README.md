# WordPress GitLab Issue Board

This WordPress plugin aims to show a Trello-like issue board in the WordPress dashboard.

The motivation and process for creating this plugin are explained [in this article](https://developpeur-web-toulouse.fr/2017/10/08/a-trello-like-board-for-gitlab-issues-in-wordpress-with-angularjs/).

The latest plugin archive can be downloaded [here](https://developpeur-web-toulouse.fr/wordpress/gitlab-issue-board/).

This is mainly an educational project, currently in progress. Contributions and feedback are welcome!

## Installation

If you simply plan to use the plugin, please use the packaged version available through the above link.

If you want to look at the code and play around with it, please go to any WordPress instance's `plugins` folder, and do the following:

    git clone https://github.com/bhubr/wordpress-gitlab-issue-board.git gitlab-issue-board
    composer install
    npm install

## Changelog

### 0.0.1

- Setup basic plugin PHP&AngularJS structure
- Setup Grunt&Webpack build chain

### 0.0.2

- Implement GitLab OAuth2 authorization process

### 0.0.3

- Register Custom Post Types for `project` and `issue` types.
- Setup interface with the [GitLab PHP API](https://github.com/m4tthumphrey/php-gitlab-api)
- Import projects and issues obtained from the GitLab API, as Custom Posts, into the WordPress database
- Setup custom REST routes to trigger project and issue synchronization from the AngularJS app.
- Show the imported/updated items in the AngularJS app's Tools page.