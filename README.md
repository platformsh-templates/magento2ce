# Magento 2 Community Edition for Platform.sh

<p align="center">
<a href="https://console.platform.sh/projects/create-project?template=https://raw.githubusercontent.com/platformsh/template-builder/master/templates/magento2ce/.platform.template.yaml&utm_content=magento2ce&utm_source=github&utm_medium=button&utm_campaign=deploy_on_platform">
    <img src="https://platform.sh/images/deploy/lg-blue.svg" alt="Deploy on Platform.sh" width="180px" />
</a>
</p>

This template builds Magento 2 CE on Platform.sh.  It includes additional scripts to customize Magento to run effectively in a build-and-deploy environment.  A MariaDB database and Redis cache server come pre-configured and work out of the box.  The installer has been modified to not ask for database information.  Background workers are run using a worker container rather than via cron.

Magento is a fully integrated ecommerce system and web store written in PHP.  This is the Open Source version.

## Features

* PHP 7.4
* MariaDB 10.3
* Redis 6.0
* OpenSearch 1.2
* Dedicated worker instance for background processing
* Automatic TLS certificates
* Composer-based build

## Platform.sh Requirements

* Medium plan or greater.
* Magento 2.3.7 - This Magento 2.4 requires different services versions and slightly modified setup/install commands.

## Post-install

1. The site comes pre-configured with an admin account, with username/password of `admin`/`admin123`.  Login at `/admin` in your browser.  **You will be required to update the password the first time you log in**.

## Customizations

The following changes have been made relative to Magento 2 as it is downloaded from Magento.com.  If using this project as a reference for your own existing project, replicate the changes below to your project.

* The `.platform.app.yaml`, `.platform/services.yaml`, and `.platform/routes.yaml` files have been added.  These provide Platform.sh-specific configuration and are present in all projects on Platform.sh.  You may customize them as you see fit.
* A custom deploy script is provided in the `deploy.php` file and called from the deploy hook in `.platform.app.yaml`.  The `deploy` script handles installing Magento on first run, including populating the administrator account.  It also handles Magento self-updates on normal point release updates.
* The installer has been patched to not ask for information that is already provided by Platform.sh, such as database credentials, file paths, or the initial administrator account.  These changes should have no impact post-installation.  See the [patch file](https://github.com/platformsh/template-builder/blob/master/templates/magento2ce/platformsh.patch) for details.
* An additional step has been added to the `deploy.php` file to force the cron process to not start background workers. See [disable-cron-workers.php](disable-cron-workers.php) for details. It runs on deploy and modifies the `.config/env.php` file.
* A worker container is also created to handle background processing. That means that Magento cannot be run on a production plan smaller than Medium.

## References

* [Magento](https://magento.com/)
* [PHP on Platform.sh](https://docs.platform.sh/languages/php.html)
