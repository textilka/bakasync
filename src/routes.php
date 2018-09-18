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

    
    $URLBASE = $this['environment']['REDIRECT_BASE'] . "/";
    // Sample log message
    //$this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    //var_dump($this->db->select('zaci', ['JMENO', 'PRIJMENI', 'TRIDA']));

    $args['URLBASE'] = $URLBASE;
    return $this->renderer->render($response, 'index.phtml', $args);
});//->add( new ExampleMiddleware());
