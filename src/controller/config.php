<?php

namespace controller;

class config {
    protected $container;
    function __construct(\Slim\Container $container) {
        $this->container = $container;
    }
    function __invoke($request, $response, $args) {
        if ($request->isGet()) {
            $args['conf'] = $this->container->conf->select("conf", "*");
            return $this->container->view->render($response, 'config.phtml', $args);
        } else if ($request->isPut()) {
            if (\array_key_exists('fields', $args)) {
                /*
                foreach ($args['fields'] as $field) {
                    $this->setValue($field['key'], $field['val']);
                }*/
                var_dump($args);
            }
        }
        return $response;
    }

    private function setValue($key, $val) {
        if ($this->container->conf->has('conf', ['field' => $key])) {
            return $this->container->conf->update('conf', ['val' => $val], ['field' => $key]);
        } else {
            return $this->container->conf->insert('conf', ['field' => $key, 'val' => $val]);
        }
    }
}