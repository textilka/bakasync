<?php

namespace controller;

class logout {

    protected $container;
    function __construct(\Slim\Container $container) {
        $this->container = $container;
    }
    function __invoke($request, $response, $args) {
        if ($request->isGet()) {
            $this->container->auth->logout();
            return $response->withRedirect($this->container->router->pathFor("root", $args), 301);
        }
        return $request;
    }
}
