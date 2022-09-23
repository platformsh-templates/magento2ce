<?php

/**
 * Platform.sh creates a read-only environment. Magento needs to write to config files from time to time, so let's
 * place those files in a Platform.sh mount and tell this deploy script where to find them.
 */

class MagentoDeployer
{
    static $FILE_PATHS = [
        'installed' => 'app/etc/installed',
        'env.php' => 'app/etc/env.php',
        'config.php' => 'app/etc/config.php',
    ];

    static function getRelationships()
    {
        return json_decode(base64_decode($_ENV['PLATFORM_RELATIONSHIPS']), true);
    }

    static function getRelationship(string $serviceName)
    {
        return static::getRelationships()[$serviceName][0];
    }

    static function getRoutes()
    {
        return json_decode(base64_decode($_ENV['PLATFORM_ROUTES']), true);
    }

    static function getMainRouteInfo()
    {
        return array_filter(static::getRoutes(), function ($value) {
            return array_key_exists('id', $value) && strtolower($value['id']) === 'main';
        });
    }

    static function getMainRoute()
    {
        return key(static::getMainRouteInfo());
    }

    static function run($cmd, $exitOnFailure = true)
    {
        passthru($cmd, $exitStatus);
        if ($exitOnFailure && $exitStatus !== 0) {
            self::AbortBuild("Build failed w/ command: {$cmd}", $exitStatus);
        }

        return $exitStatus;
    }

    static function ValidateEnvironment()
    {
        if (!static::getMainRouteInfo()) {
            echo 'Cannot find the main route for Magento. Please add `id: main` to your routes.yaml.' . PHP_EOL;
            exit(1);
        }

        if (!array_key_exists('database', static::getRelationships())) {
            echo 'Cannot find the database service for Magento. Please update your .platform.app.yaml or .platform/applications.yaml to use the relationship name "database" or modify deploy.php to use the new name.' . PHP_EOL;
            exit(1);
        }
        if (!array_key_exists('redis', static::getRelationships())) {
            echo 'Cannot find the main redis service for Magento. Please update your .platform.app.yaml or .platform/applications.yaml to use the relationship name "redis" or modify deploy.php to use the new name.' . PHP_EOL;
            exit(1);
        }
    }

    static function isMagentoInstalled()
    {
        return file_exists(self::$FILE_PATHS['installed']);
    }

    static function isFreshInstall()
    {
        return !static::isMagentoInstalled();
    }

    static function isEnvConfigured()
    {
        return file_exists(self::$FILE_PATHS['env.php']);
    }

    /**
     * Magento 2.4 has a bug where it expects a DB to be defined before setup:install
     */
    static function CreatePreInstallEnvFile()
    {
        $database = self::getRelationship('database');
        $magento24SetupPatch = "<?php return ['db' => [
        'table_prefix' => '',
        'connection' => [
            'default' => [
                'host' => '{$database['host']}',
                'dbname' => '{$database['path']}',
                'username' => '{$database['username']}',
                'password' => '{$database['password']}',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
                'driver_options' => [
                    1014 => false
                ]
            ]
        ]
    ],];";

        /** Let's reset our Magento env.php since we are redeploying */
        if (self::isEnvConfigured()) {
            $envPath = self::$FILE_PATHS['env.php'];
            unlink($envPath);
            /** A Magento 2.4.4 bug requires the database to be defined before running setup:install */
            file_put_contents($envPath, $magento24SetupPatch);
        }
    }

    /**
     * Defines a sequential list of commands to run and the conditions to run them.
     * @return array[]
     */
    static function InstallSteps()
    {
        $redis = self::getRelationship('redis');
        $database = self::getRelationship('database');
        $search = self::getRelationship('search');
        $installFile = self::$FILE_PATHS['installed'];

        return [
            'Installing Magento 2.4 setup:install bugfix' => [
                'only_if' => static::isFreshInstall(),
                'cmd' => function () {
                    self::CreatePreInstallEnvFile();
                },
            ],
            'Installing Magento 2.4' => [
                'only_if' => static::isFreshInstall(),
                'cmd' => 'php bin/magento setup:install',
                'args' => [
                    "--ansi",
                    "--no-interaction",
                    "--skip-db-validation",
                    "--base-url=" . self::getMainRoute(),
                    "--db-host=" . $database["host"],
                    "--db-name=" . $database["path"],
                    "--db-user=" . $database["username"],
                    "--backend-frontname=admin",
                    "--language=en_US",
                    "--currency=USD",
                    "--timezone=Europe/Paris",
                    "--use-rewrites=1",
                    "--session-save=redis",
                    "--session-save-redis-host=" . $redis["host"],
                    "--session-save-redis-port=" . $redis["port"],
                    "--session-save-redis-db=0",
                    "--cache-backend=redis",
                    "--cache-backend-redis-server=" . $redis["host"],
                    "--cache-backend-redis-port=" . $redis["port"],
                    "--cache-backend-redis-db=1",
                    "--page-cache=redis",
                    "--page-cache-redis-server=" . $redis["host"],
                    "--page-cache-redis-port=" . $redis["port"],
                    "--page-cache-redis-db=2",
                    "--search-engine=elasticsearch7",
                    "--elasticsearch-host=" . $search["host"],
                    "--elasticsearch-port=" . $search["port"],
                    "--admin-firstname=admin",
                    "--admin-lastname=admin",
                    "--admin-email=admin@admin.com",
                    "--admin-user=admin",
                    "--admin-password=admin123",
                ],
            ],
            'Requiring admin user to reset password by setting it to expired' => [
                'only_if' => self::isFreshInstall(),
                'cmd' => "mysql",
                'args' => [
                    "-h{$database['host']}",
                    "-u{$database['username']}",
                    $database['password'] ?? "-p{$database['password']}", // Password, but only if there is one
                    $database['path'],
                    "-e \"insert into admin_passwords (user_id, password_hash, expires, last_updated) values (1, '123456789:2', 1, 1435156243);\""
                ],
                'custom_fail_message' => 'WARNING! Failed to expire admin password. Please login to /admin and reset the password.'
            ],
            'Ensuring latest deployed services are applied to app/etc/env.php' => [
                'only_if' => static::isMagentoInstalled(),
                'cmd' => 'php bin/magento setup:config:set',
                'args' => [
                    "--ansi",
                    "--no-interaction",
                    "--db-host=" . $database["host"],
                    "--db-name=" . $database["path"],
                    "--db-user=" . $database["username"],
                    "--session-save=redis",
                    "--session-save-redis-host=" . $redis["host"],
                    "--session-save-redis-port=" . $redis["port"],
                    "--session-save-redis-db=0",
                    "--cache-backend=redis",
                    "--page-cache-redis-server=" . $redis["host"],
                    "--page-cache-redis-port=" . $redis["port"],
                    "--cache-backend-redis-db=1",
                    "--page-cache=redis",
                    "--page-cache-redis-server=" . $redis["host"],
                    "--page-cache-redis-port=" . $redis["port"],
                    "--page-cache-redis-db=2",
                ],
            ],
            'Purging cache to ensure the latest services are used' => [
                'only_if' => static::isMagentoInstalled(),
                'cmd' => 'php bin/magento cache:flush',
            ],
            'Ensuring Magento deployment uses the latest configured main route' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento config:set',
                'args' => ['web/unsecure/base_url', self::getMainRoute()]
            ],
            'Ensuring Magento deployment uses the latest configured Elasticsearch service' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento config:set catalog/search/engine elasticsearch7'
                    . "  && php bin/magento config:set catalog/search/elasticsearch7_server_hostname {$search['host']}"
                    . "  && php bin/magento config:set catalog/search/elasticsearch7_server_port {$search['port']}"
            ],
            'Clearing Magento\'s cache to ensure we use the values that were just set' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento cache:flush'
            ],
            'Ensuring Magento is in production mode' => [
                'only_if' => true, // This will run every time.
                'cmd' => 'php bin/magento deploy:mode:set production',
                'args' => ['--skip-compilation']
            ],
            'Entering maintenance mode' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento maintenance:enable'
            ],
            'Installing & configuring Magento modules' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento setup:upgrade'
            ],
            'Compiling Magento\'s Di config' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento setup:di:compile'
            ],
            'Deploying Magento\'s static files' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento setup:static-content:deploy'
            ],
            'Exiting maintenance mode' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento maintenance:disable'
            ],
            'Ensuring that Magento is marked as configured.' => [
                'only_if' => true, // any time this setup runs successfully we should make sure this file is in place
                'cmd' => "echo \"Welcome to Platform.sh! Your Magento site has been deployed!\" >> {$installFile}",
            ]
        ];
    }

    static function RunInstallStep(string $summary, array $installStep){
        $shouldRun = $installStep['only_if'];

        if (!$shouldRun) return;

        echo "{$summary}" . PHP_EOL;

        if (self::isPHPFunction($installStep['cmd'])) {
            $installStep['cmd']();
            return;
        }

        $hasCustomError = array_key_exists('custom_fail_message', $installStep);
        $exitStatus = self::run(self::installCommand($installStep), !$hasCustomError);

        if ($exitStatus !== 0 && $hasCustomError) {
            self::AbortBuild($installStep['custom_fail_message'], $exitStatus);
        }
    }
    static function Deploy()
    {
        static::ValidateEnvironment();

        self::ExecuteInstallSteps();
    }

    /**
     * @param $cmd
     * @return bool
     */
    private static function isPHPFunction($cmd): bool
    {
        return is_callable($cmd);
    }

    private static function installCommand(array $installStep): string
    {
        $hasArgs = array_key_exists('args', $installStep) && is_array($installStep['args']);
        $args = join(' ', $hasArgs ? $installStep['args'] : []);
        return "{$installStep['cmd']} {$args}";
    }

    private static function AbortBuild($failMessage, int $exitStatus): void
    {
        echo $failMessage . PHP_EOL;
        exit($exitStatus);
    }

    /**
     * @return void
     */
    private static function ExecuteInstallSteps(): void
    {
        foreach (static::InstallSteps() as $summary => $installStep) {
            static::runInstallStep($summary, $installStep);
        }
    }
}

MagentoDeployer::Deploy();

if (MagentoDeployer::isFreshInstall()) {
    echo "\t* You can login at " . MagentoDeployer::getMainRoute() . "/admin using the username \"admin\" and the password \"admin123\"." . PHP_EOL;
    echo "\t* This password will only work once. You will be prompted to update it once logged in." . PHP_EOL;
}
