<?php

use Slim\Http\Request;
use Slim\Http\Response;

// logged in
$app->group(URLBASE, function() {
    $this->get('/dashboard', \controller\dashboard::class)->setName('dashboard');
    $this->get('/logout', \controller\logout::class)->setName('logout');
    $this->map(['get', 'put', 'delete'], "/user[/{id}]", \controller\user::class);
    $this->map(['get', 'put'], "/config", \controller\config::class)->setName('config');
})->add(\middleware\auth::class);

// not logged in
$app->group(URLBASE, function() {
    $this->get('', controller\index::class)->setName('root');
    $this->get('/', controller\index::class)->setName('root');
    $this->map(['get', 'post'], '/login', controller\login::class)->setName('login');
})->add(\middleware\login::class);

$app->map(['get', 'put'], '/setup', controller\setup::class)->setName('setup');
