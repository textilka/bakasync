<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

define("STUDENT", 0);
define("TEACHER", 1);

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

$app->add(\middleware\csrf::class);
$app->add($container->csrf);
define("URLBASE", strlen(@$container->conf->data['/url/app_base']) > 0 ? substr($container->conf->data['/url/app_base'], 0, -1) : '');

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
