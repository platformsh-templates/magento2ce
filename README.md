# Magento 2 Community Edition for Platform.sh/Upsun

<p align="center">
<a href="https://console.platform.sh/projects/create-project?template=https://raw.githubusercontent.com/platformsh/template-builder/master/templates/magento2ce/.platform.template.yaml&utm_content=magento2ce&utm_source=github&utm_medium=button&utm_campaign=deploy_on_platform">
    <img src="https://platform.sh/images/deploy/lg-blue.svg" alt="Deploy on Platform.sh" width="180px" />
</a>
</p>

This template builds Magento 2 CE on Platform.sh and Upsun.  It includes the Magento ECE-Tools to run effectively in a build-and-deploy environment.  A MariaDB Database, Elasticsearch Indexer, RabbitMQ Message Queue and Redis Cache server come pre-configured and work out of the box. 

Magento is a fully integrated ecommerce system and web store written in PHP.  This is the Open Source version of Magento.

## Features

* PHP 8.3
* MariaDB 10.6
* Redis 7.2
* Opensearch 2
* RabbitMQ 3.13
* Automatic TLS certificates
* Composer-based build

## Composer Authentication and Post Installation Setup

1. Get your Magento Repository authentication keys https://devdocs.magento.com/guides/v2.4/install-gde/prereq/connect-auth.html if you want to adjust the composer repo to https://repo.magento.com/
2. Add your keys as a project level variable `platform variable:create -p <your Platform.sh projectID> --level project --name env:COMPOSER_AUTH --json true --visible-runtime false --sensitive true --visible-build true  --value '{"http-basic":{"repo.magento.com":{"username":"<your public key>","password":"<your private key>"}}}'`
3. Replace https://mirror.mage-os.org/ in the composer.json with the https://repo.magento.com/
4. Please disable Magento two factor auth for admin logins on development enviroments with mail disabled, please SSH into your application and run `bin/magento config:set twofactorauth/general/enable 0` 
5. Please add an admin user using `php bin/magento admin:user:create`.  Login at `/admin` in your browser. 

## Customizations

If using this project as a reference for your own existing project, replicate the changes below to your project.

* The `.platform.app.yaml`, `.platform/services.yaml`, and `.platform/routes.yaml` files have been added.  These provide Platform.sh-specific configuration and are present in all projects on Platform.sh.  You may customize them as you see fit.
* The `.upsun/config.yaml` files has been added. This provides Upsun-specific configuration which can also be reviewed.
* The `composer.json` file has had the ECE-Tools package and its dependencies added.
* We are using https://mirror.mage-os.org/ and GitHub for the Magento sources.
* Magento crons have been setup to ensure they are run sequentially to ensure there is availible memory
* A logrotate and report housekeeping cron have been added.
* A module which allows two factor authentication to be disabled has been added to `composer.json`.

## References

* [Adobe Commerce Docs](https://experienceleague.adobe.com/en/docs/commerce)
* [PHP on Platform.sh](https://docs.platform.sh/languages/php.html)
