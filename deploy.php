<?php

/**
 * Platform.sh creates a read-only environment. Magento needs to write to config files from time to time, so let's
 * place those files in a Platform.sh mount and tell this deploy script where to find them.
 */

$filePaths = [
    'installed' => 'app/etc/installed',
    'env.php' => 'app/etc/env.php',
    'config.php' => 'app/etc/config.php',
];

$relationships = json_decode(base64_decode($_ENV['PLATFORM_RELATIONSHIPS']), true);
$routes = json_decode(base64_decode($_ENV['PLATFORM_ROUTES']), true);

$mainRouteInfo = array_filter($routes, function($value) {
    return array_key_exists('id', $value) && strtolower($value['id']) === 'main';
});

if(!$mainRouteInfo) {
    echo 'Cannot find the main route for Magento. Please add `id: main` to your routes.yaml.'.PHP_EOL;
    exit(1);
}

if(!array_key_exists('database', $relationships)) {
    echo 'Cannot find the database service for Magento. Please update your .platform.app.yaml or .platform/applications.yaml to use the relationship name "database" or modify deploy.php to use the new name.'.PHP_EOL;
    exit(1);
}
if(!array_key_exists('redis', $relationships)) {
    echo 'Cannot find the main redis service for Magento. Please update your .platform.app.yaml or .platform/applications.yaml to use the relationship name "redis" or modify deploy.php to use the new name.'.PHP_EOL;
    exit(1);
}
/** @var $mainRoute Let's get our real world route to Magento */
$mainRoute = key($mainRouteInfo);

/** Now, collect our services data */
$database = $relationships['database'][0];
$redis = $relationships['redis'][0];
$search = $relationships['search'][0];

/** Check the state of our Magento installation */
$isMagentoInstalled = file_exists($filePaths['installed']);
$isFreshInstall = !$isMagentoInstalled;
$isEnvConfigured = file_exists($filePaths['env.php']);

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
if($isEnvConfigured) {
    unlink($filePaths['env.php']);
    /** A Magento 2.4.4 bug requires the database to be defined before running setup:install */
    file_put_contents($filePaths['env.php'], $magento24SetupPatch);
}

/** Now, we're going to define our Magento CLI setup command */
$setupCommand = ["php bin/magento setup:install"];
$deploymentArgs = [
    "--ansi",
    "--no-interaction",
    "--base-url={$mainRoute}",
    "--db-host={$database["host"]}",
    "--db-name={$database["path"]}",
    "--db-user={$database["username"]}",
    "--backend-frontname=admin",
    "--language=en_US",
    "--currency=USD",
    "--timezone=Europe/Paris",
    "--use-rewrites=1",
    "--session-save=redis",
    "--session-save-redis-host={$redis["host"]}",
    "--session-save-redis-port={$redis["port"]}",
    "--session-save-redis-db=0",
    "--cache-backend=redis",
    "--cache-backend-redis-server={$redis["host"]}",
    "--cache-backend-redis-port={$redis["port"]}",
    "--cache-backend-redis-db=1",
    "--page-cache=redis",
    "--page-cache-redis-server={$redis["host"]}",
    "--page-cache-redis-port={$redis["port"]}",
    "--page-cache-redis-db=2",
    "--search-engine=elasticsearch7",
    "--elasticsearch-host={$search['host']}",
    "--elasticsearch-port={$search['port']}",
];

$initialSetupArgs = [
    "--admin-firstname=admin",
    "--admin-lastname=admin",
    "--admin-email=admin@admin.com",
    "--admin-user=admin",
    "--admin-password=admin123",
];

/** If Magento hasn't been installed, let's set a few more options. */
if(!$isMagentoInstalled)
    $deploymentArgs = array_merge($deploymentArgs, $initialSetupArgs);

$deployCommand = join(' ', array_merge($setupCommand, $deploymentArgs));

/** Execute the setup command and output to deploy log */
passthru($deployCommand, $exitStatus);

if($exitStatus !== 0) {
    echo "Build failed w/ command: {$deployCommand}".PHP_EOL;
    exit($exitStatus);
}

if($isFreshInstall) {
    echo "Forcing admin password to expire after first login.".PHP_EOL;

    /** Force the admin user to reset their password after their first login */
    $expirePasswordCommand = [
        "mysql",
        "-h{$database['host']}",
        "-u{$database['username']}",
        $database['password'] ?? "-p{$database['password']}",
        $database['path'],
        '-e',
        "\"insert into admin_passwords (user_id, password_hash, expires, last_updated) values (1, '123456789:2', 1, 1435156243);\"",
    ];

    passthru(join(' ', $expirePasswordCommand), $exitStatus);

    if ($exitStatus !== 0) {
        echo 'WARNING! Failed to expire admin password. Please login to /admin and reset the password.' . PHP_EOL;
        exit($exitStatus);
    }
}

echo PHP_EOL.PHP_EOL."Deployment complete! Your Magento site is accessible at {$mainRoute}".PHP_EOL;

if($isFreshInstall) {
    echo "\t* You can login at ${mainRoute}admin using the username \"admin\" and the password \"admin123\".".PHP_EOL;
    echo "\t* This password will only work once. You will be prompted to update it once logged in.".PHP_EOL;
}

/** Mark Magento as installed. */
file_put_contents($filePaths['installed'], "Welcome to Platform.sh! Your Magento site has been deployed!");
