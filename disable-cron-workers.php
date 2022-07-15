<?php

/**
 * Disable cron-spawned worker processes.
 *
 * Magento's cron task will automatically start a series of worker processes, on the assumption that
 * they are not started elsewhere.  Those processes block redeployment, so must be disabled.
 * The only way to do so is with a flag in the .config/env.php file, which must be writeable, not
 * committed to Git.  That leaves us with the need to manually regenerate it with that flag added
 * on deploy, which is what this script does.
 *
 */

if (!$_ENV['PLATFORM_APP_DIR']) {
    exit;
}

$file = "{$_ENV['PLATFORM_APP_DIR']}/.config/env.php";

if (!file_exists($file)) {
    exit;
}

$config = include $file;

$config['cron_consumers_runner'] = [
    'cron_run' => false,
];

$export = var_export($config, 1);

$newFile = <<<END
<?php

return $export;
END;

file_put_contents($file, $newFile);
