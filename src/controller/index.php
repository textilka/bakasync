<?php

namespace controller;

class index {

    use \traits\sendResponse;

    protected $container;
    function __construct(\Slim\Container $container) {
        $this->container = $container;
    }
    function __invoke($request, $response, $args) {
        if ($request->isGet()) {
            return $this->sendResponse($request, $response, 'index.phtml', $args);
        }
        return $request;
    }
}
