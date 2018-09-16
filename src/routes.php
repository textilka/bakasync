<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/login', function (Request $request, Response $response, array $args) {
    // auth user using OIDC

    if ($this->auth->valid()) {
        
    }
});

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
