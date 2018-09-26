<?php

namespace controller;

class login extends controller {

    function __construct ($container) {
        parent::__construct($container);
    }

    function __invoke ($request, $response, $args) {
        if ($request->isGet()) {
            return $this->sendResponse($request, $response, 'login.phtml', $args);
        } else if ($request->isPost()) {
            $postVars = $request->getParsedBody();
            if (!$this->container->auth->login($postVars['uname'], $postVars['passw'])) {
                sleep(2);
                $args['error'] = [['message' => $this->container->auth->getMessage()]];
                return $this->sendResponse($request, $response, 'login.phtml', $args);
            }
            return $response->withRedirect($this->container->router->pathFor("dashboard", $args), 301);
        }
        return $request;
    }
}
