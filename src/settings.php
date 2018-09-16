<?php
if (!is_file(__DIR__ . '../../conf/private_settings.php'))
    throw new Exception("Private settings not defined");

$private_settings = require __DIR__ . '../../conf/private_settings.php';
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'priv' => $private_settings['settings'],
        
    ],
];
