<?php
/**
 * Entry point for static resources (JS, CSS, etc.)
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Begin Platform.sh Patch
 * @deprecated this can be handled in .platform.app.yaml:locations
 */
$parts = explode('/', $_SERVER['REQUEST_URI']);
// $parts is ['', 'static', 'version<\d+>', ...], and only '...' is
// what we're interested in.
array_shift($parts);
array_shift($parts);
array_shift($parts);

$_GET['resource'] = implode('/', $parts);
/** End Platform.sh Patch */

require realpath(__DIR__) . '/../app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
/** @var \Magento\Framework\App\StaticResource $app */
$app = $bootstrap->createApplication(\Magento\Framework\App\StaticResource::class);
$bootstrap->run($app);
