<?php

use Slim\Http\Request;
use Slim\Http\Response;

// logged in
$app->group(URLBASE, function() {
    $this->get('/dashboard', \controller\dashboard::class)->setName('dashboard');
    $this->get('/logout', \controller\logout::class)->setName('logout');
    $this->map(['get', 'put'], "/config", \controller\config::class)->setName('config');
    $this->map(['put'], "/action/{id}", \controller\action::class)->setName('action');
})->add(\middleware\auth::class);

// not logged in
$app->group(URLBASE, function() {
    $this->map(['get', 'put'], '/setup', controller\setup::class)->setName('setup');
    $this->get('', controller\index::class)->setName('root');
    $this->get('/', controller\index::class)->setName('root');
    $this->map(['get', 'post'], '/login', controller\login::class)->setName('login');
    $this->get('/test', function(request $request, response $response, $args) {
        return $response->write("OK");
    });
})->add(\middleware\login::class);
