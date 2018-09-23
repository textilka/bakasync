<?php

namespace controller;

class config {
    
    use \traits\sendResponse;

    protected $container;
    function __construct(\Slim\Container $container) {
        $this->container = $container;
    }
    
    function __invoke($request, $response, $args) {
        if ($request->isGet()) {
            $this->fetch($args);
            
            return $this->sendResponse($request, $response, 'config.phtml', $args);

        } else if ($request->isPut()) {
            $postVars = $request->getParsedBody();
            
            foreach ($this->container->conf->select("conf", "*") as $data) {
                if (array_key_exists($data['field'], $postVars)) {
                    if (strpos(strrev($data['field']), 'ssap_') === 0) {
                        if ($postVars[$data['field']] == str_repeat('*', 15))
                            continue;
                    }
                    $this->container->conf->update('conf', ["val" => $postVars[$data['field']]], ['field' => $data['field']]);
                }
            }
            $this->fetch($args);
            $args['success'] = [["message" => "Uloženo"]];
            return $this->sendResponse($request, $response, 'config.phtml', $args);
        }
        return $response;
    }

    private function fetch(&$args) {
        foreach ($this->container->conf->select("conf", "*") as $data) {
            $args['conf'][$data['field']] = $data['val'];
        }
        $args['schema'] = setup::FIELDS;
    }
}
