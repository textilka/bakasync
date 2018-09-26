<?php

namespace controller;

abstract class controller {
    use \traits\sendResponse;

    protected $container;
    function __construct (\Slim\Container $container) {
        $this->container = $container;
    }

    abstract function __invoke (\Slim\Http\Request $request, \Slim\Http\Response $response, array $args);
}