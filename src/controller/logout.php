<?php

namespace controller;

class logout extends controller {

    function __construct ($container) {
        parent::__construct($container);
    }

    function __invoke ($request, $response, $args) {
        if ($request->isGet()) {
            $this->container->auth->logout();
            return $response->withRedirect($this->container->router->pathFor("root", $args), 301);
        }
        return $request;
    }
}
