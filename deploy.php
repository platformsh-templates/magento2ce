<?php

/**
 * MagentoDeployer makes it easy to manage Magento deployments on Platform.sh
 */
class MagentoDeployer
{
    public static array $FILE_PATHS = [
        'installed' => 'app/etc/installed',
        'env.php' => 'app/etc/env.php',
        'config.php' => 'app/etc/config.php',
    ];

    /**
     * Fetch $PLATFORM_RELATIONSHIPS environment variable to array
     *
     * @return array
     */
    public static function getRelationships(): array
    {
        return (array) json_decode(base64_decode($_ENV['PLATFORM_RELATIONSHIPS']), true);
    }

    /**
     * Pluck Platform service details from $PLATFORM_RELATIONSHIPS as an array
     *
     * @param  string  $serviceName the name of the service as defined in .platform/services.yaml
     * @return array
     */
    public static function getRelationship(string $serviceName): array
    {
        return (array) static::getRelationships()[$serviceName][0];
    }

    /**
     * Fetch $PLATFORM_ROUTES environment variable as an array
     *
     * @return array
     */
    public static function getRoutes(): array
    {
        return (array) json_decode(base64_decode($_ENV['PLATFORM_ROUTES']), true);
    }

    /**
     * Pluck route details with the ID "main" from $PLATFORM_ROUTES environment variable as an array
     *
     * @return array
     */
    public static function getMainRouteInfo(): array
    {
        return array_filter(static::getRoutes(), function ($value) {
            return array_key_exists('id', $value) && strtolower($value['id']) === 'main';
        });
    }

    /**
     * Fetch the URL assigned to the route with ID of "main" from $PLATFORM_ROUTES environment variable
     *
     * @return string
     */
    public static function getMainRoute(): string
    {
        return (string) key(static::getMainRouteInfo());
    }

    /**
     * Run a command and get its exit status. An abstraction of passthru() with built-in error handling.
     *
     * @param  string  $cmd The command to run in terminal
     * @param  bool  $exitOnFailure
     * @return int
     */
    public static function run(string $cmd, bool $exitOnFailure = true): int
    {
        passthru($cmd, $exitStatus);
        if ($exitOnFailure && $exitStatus !== 0) {
            self::abortBuild("Build failed w/ command: {$cmd}", $exitStatus);
        }

        return (int) $exitStatus;
    }

    /**
     * Validate that the environment has the expected main route and required services to install Magento.
     * Exit if it does not.
     *
     * @return void
     */
    public static function ValidateEnvironment(): void
    {
        if (! static::getMainRouteInfo()) {
            self::abortBuild('Cannot find the main route for Magento. Please add `id: main` to your routes.yaml.', 1);
        }

        if (! array_key_exists('database', static::getRelationships())) {
            self::abortBuild('Cannot find the database service for Magento. Please update your .platform.app.yaml or .platform/applications.yaml to use the relationship name "database" or modify deploy.php to use the new name.', 1);
        }
        if (! array_key_exists('redis', static::getRelationships())) {
            self::abortBuild('Cannot find the main redis service for Magento. Please update your .platform.app.yaml or .platform/applications.yaml to use the relationship name "redis" or modify deploy.php to use the new name.', 1);
        }
    }

    /**
     * Check to see if Magento has already been installed based on the existence of the install file.
     *
     * @return bool
     */
    public static function isMagentoInstalled(): bool
    {
        return file_exists(self::$FILE_PATHS['installed']);
    }

    /**
     * The inverse of self::isMagentoInstalled(). Check to see if Magento has not yet been installed
     * based on the existence of the install file.
     *
     * @return bool
     */
    public static function isFreshInstall(): bool
    {
        return ! static::isMagentoInstalled();
    }

    /**
     * Check to see if Magento's env.php has been created.
     *
     * @return bool
     */
    public static function isEnvConfigured(): bool
    {
        return file_exists(self::$FILE_PATHS['env.php']);
    }

    /**
     * @deprecated
     *
     * Workaround for Magento 2.4 bug that expects a DB to be defined before setup:install.
     * Marking as deprecated as we should be confirming if this is still required with
     * each Magento version update.
     *
     * @return void
     */
    public static function applyMagento24SetupInstallPatch(): void
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
            self::resetFileFileContent(self::$FILE_PATHS['env.php'], $magento24SetupPatch);
        }
    }

    /**
     * Defines a sequential list of commands to run and the conditions required to run them.
     *
     * @return array[]
     */
    public static function installSteps(): array
    {
        $redis = self::getRelationship('redis');
        $database = self::getRelationship('database');
        $search = self::getRelationship('search');
        $installFile = self::$FILE_PATHS['installed'];

        return [
            'Installing Magento 2.4 setup:install bugfix' => [
                'only_if' => static::isFreshInstall(),
                'cmd' => function () {
                    self::applyMagento24SetupInstallPatch();
                },
            ],
            'Installing Magento 2.4' => [
                'only_if' => static::isFreshInstall(),
                'cmd' => 'php bin/magento setup:install',
                'args' => [
                    '--ansi',
                    '--no-interaction',
                    '--skip-db-validation',
                    '--base-url='.self::getMainRoute(),
                    '--db-host='.$database['host'],
                    '--db-name='.$database['path'],
                    '--db-user='.$database['username'],
                    '--backend-frontname=admin',
                    '--language=en_US',
                    '--currency=USD',
                    '--timezone=Europe/Paris',
                    '--use-rewrites=1',
                    '--session-save=redis',
                    '--session-save-redis-host='.$redis['host'],
                    '--session-save-redis-port='.$redis['port'],
                    '--session-save-redis-db=0',
                    '--cache-backend=redis',
                    '--cache-backend-redis-server='.$redis['host'],
                    '--cache-backend-redis-port='.$redis['port'],
                    '--cache-backend-redis-db=1',
                    '--page-cache=redis',
                    '--page-cache-redis-server='.$redis['host'],
                    '--page-cache-redis-port='.$redis['port'],
                    '--page-cache-redis-db=2',
                    '--search-engine=elasticsearch7',
                    '--elasticsearch-host='.$search['host'],
                    '--elasticsearch-port='.$search['port'],
                    '--admin-firstname=admin',
                    '--admin-lastname=admin',
                    '--admin-email=admin@admin.com',
                    '--admin-user=admin',
                    '--admin-password=admin123',
                ],
            ],
            'Requiring admin user to reset password by setting it to expired' => [
                'only_if' => self::isFreshInstall(),
                'cmd' => 'mysql',
                'args' => [
                    "-h{$database['host']}",
                    "-u{$database['username']}",
                    $database['password'] ?? "-p{$database['password']}", // Password, but only if there is one
                    $database['path'],
                    "-e \"insert into admin_passwords (user_id, password_hash, expires, last_updated) values (1, '123456789:2', 1, 1435156243);\"",
                ],
                'custom_fail_message' => 'WARNING! Failed to expire admin password. Please login to /admin and reset the password.',
            ],
            'Ensuring latest deployed services are applied to app/etc/env.php' => [
                'only_if' => static::isMagentoInstalled(),
                'cmd' => 'php bin/magento setup:config:set',
                'args' => [
                    '--ansi',
                    '--no-interaction',
                    '--db-host='.$database['host'],
                    '--db-name='.$database['path'],
                    '--db-user='.$database['username'],
                    '--session-save=redis',
                    '--session-save-redis-host='.$redis['host'],
                    '--session-save-redis-port='.$redis['port'],
                    '--session-save-redis-db=0',
                    '--cache-backend=redis',
                    '--page-cache-redis-server='.$redis['host'],
                    '--page-cache-redis-port='.$redis['port'],
                    '--cache-backend-redis-db=1',
                    '--page-cache=redis',
                    '--page-cache-redis-server='.$redis['host'],
                    '--page-cache-redis-port='.$redis['port'],
                    '--page-cache-redis-db=2',
                ],
            ],
            'Purging cache to ensure the latest services are used' => [
                'only_if' => static::isMagentoInstalled(),
                'cmd' => 'php bin/magento cache:flush',
            ],
            'Ensuring Magento deployment uses the latest configured main route' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento config:set',
                'args' => ['web/unsecure/base_url', self::getMainRoute()],
            ],
            'Ensuring Magento deployment uses the latest configured Elasticsearch service' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento config:set catalog/search/engine elasticsearch7'
                    ."  && php bin/magento config:set catalog/search/elasticsearch7_server_hostname {$search['host']}"
                    ."  && php bin/magento config:set catalog/search/elasticsearch7_server_port {$search['port']}",
            ],
            'Clearing Magento\'s cache to ensure we use the values that were just set' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento cache:flush',
            ],
            'Ensuring Magento is in production mode' => [
                'only_if' => true, // This will run every time.
                'cmd' => 'php bin/magento deploy:mode:set production',
                'args' => ['--skip-compilation'],
            ],
            'Entering maintenance mode' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento maintenance:enable',
            ],
            'Installing & configuring Magento modules' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento setup:upgrade',
            ],
            'Compiling Magento\'s Di config' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento setup:di:compile',
            ],
            'Deploying Magento\'s static files' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento setup:static-content:deploy',
            ],
            'Exiting maintenance mode' => [
                'only_if' => self::isMagentoInstalled(),
                'cmd' => 'php bin/magento maintenance:disable',
            ],
            'Ensuring that Magento is marked as configured.' => [
                'only_if' => true, // any time this setup runs successfully we should make sure this file is in place
                'cmd' => "echo \"Welcome to Platform.sh! Your Magento site has been deployed!\" >> {$installFile}",
            ],
        ];
    }

    /**
     * Analyzes and runs a self::InstallStep() if conditions are met.
     *
     * @param  string  $summary Summary of the intall step being executed
     * @param  array  $installStep Install step configuration
     * @return void
     */
    public static function executeInstallStep(string $summary, array $installStep): void
    {
        $shouldRun = $installStep['only_if'];

        if (! $shouldRun) {
            return;
        }

        echo "{$summary}".PHP_EOL;

        if (self::isPHPFunction($installStep['cmd'])) {
            $installStep['cmd']();

            return;
        }

        $hasCustomError = array_key_exists('custom_fail_message', $installStep);
        $exitStatus = self::run(self::installCommand($installStep), ! $hasCustomError);

        if ($exitStatus !== 0 && $hasCustomError) {
            self::abortBuild($installStep['custom_fail_message'], $exitStatus);
        }
    }

    /**
     * Validates that the environment is ready to deploy and triggers the deployment if so.
     *
     * @return void
     */
    public static function deploy(): void
    {
        static::ValidateEnvironment();

        self::executeInstallSteps();
    }

    /**
     * Determines if the $cmd argument is a PHP callable function.
     *
     * @param $cmd
     * @return bool
     */
    private static function isPHPFunction($cmd): bool
    {
        return is_callable($cmd);
    }

    /**
     * Builds a terminal command from the provided $installStep configuration
     *
     * @param  array  $installStep Install step configuration as provided by self::InstallSteps()
     * @return string
     */
    private static function installCommand(array $installStep): string
    {
        $hasArgs = array_key_exists('args', $installStep) && is_array($installStep['args']);
        $args = implode(' ', $hasArgs ? $installStep['args'] : []);

        return "{$installStep['cmd']} {$args}";
    }

     /**
      * Ends the PHP process that launched the Magento::Deploy() command with the provided message and exit status.
      *
      * @param  string  $message Message to output to PHP process launcher
      * @param  int  $exitStatus Exit status to end the PHP process with
      * @return void
      */
     private static function abortBuild(string $message, int $exitStatus): void
     {
         $setBoldOutputCmd = 'echo $(tput -T "xterm-256color" bold)';
         $resetOutputCmd = 'echo $(tput -T "xterm-256color" sgr0)';

         self::run("figlet -f standard 'DEPLOYMENT ABORTED'");
         self::run($setBoldOutputCmd);
         echo $message . PHP_EOL;
         self::run($resetOutputCmd);

         exit($exitStatus);
     }

    /**
     * Execute each of the install steps provided by self::InstallSteps()
     *
     * @return void
     */
    private static function executeInstallSteps(): void
    {
        foreach (static::installSteps() as $summary => $installStep) {
            static::executeInstallStep($summary, $installStep);
        }
    }

    /**
     * Replace file content with the provided content.
     *
     * @param  mixed  $filePath
     * @param  string  $fileContent
     * @return void
     */
    private static function resetFileFileContent(string $filePath, string $fileContent): void
    {
        unlink($filePath);

        file_put_contents($filePath, $fileContent);
    }
}

MagentoDeployer::deploy();

if (MagentoDeployer::isFreshInstall()) {
    echo "\t* You can login at ".MagentoDeployer::getMainRoute().'/admin using the username "admin" and the password "admin123".'.PHP_EOL;
    echo "\t* This password will only work once. You will be prompted to update it once logged in.".PHP_EOL;
}
