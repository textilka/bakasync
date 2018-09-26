<?php

namespace controller;

class index extends controller {

    function __construct ($container) {
        parent::__construct($container);
    }

    function __invoke ($request, $response, $args) {
        if ($request->isGet()) {
            return $this->sendResponse($request, $response, 'index.phtml', $args);
        }
        return $request;
    }
}
