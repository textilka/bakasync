<?php

namespace controller;

class config {

    protected const FIELDS = [
        'db' => [
            '_desc'  => 'Databáze Bakalářů',
            'remote' => 'IP adresa nebo (N)FQDN',
            'user'   => 'Uživatelské jméno',
            '_pass'  => 'Heslo',
            'db'     => 'Databáze',
            'roles'  => ['Názvy funkcí učitelů,<br /><small>každá na nový řádek</small>']
        ],
        'ldap' => [
            '_desc'   => 'LDAP server',
            'remote'  => 'IP adresa nebo (N)FQDN',
            'port'    => 'Port',
            'bind_dn' => 'DN účtu BakaSync',
            '_pass'   => 'Heslo',
            'domain'  => 'FQDN kořene domény',
            'ignore'  => ['DN ignorovaných účtů,<br /><small>každý na nový řádek</small>'],
            'search'  => [
                '_desc'    => 'DN uzlu pro vyhledávání',
                'students' => 'Studenti',
                'teachers' => 'Učitelé',
                'admins'   => 'Administrátoři'
            ]
        ],
        'url' => [
            '_desc'    => 'Aplikace',
            'app_base' => 'URL adresa (př.: /bakasync/)'
        ]
    ];

    use \traits\sendResponse;

    protected $container;
    function __construct(\Slim\Container $container) {
        $this->container = $container;
        $this->seed($this::FIELDS);
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
        $args['schema'] = $this::FIELDS;
    }

    private function setValue($key, $val) {
        if ($this->container->conf->has('conf', ['field' => $key])) {
            return $this->container->conf->update('conf', ['val' => $val], ['field' => $key]);
        } else {
            return $this->container->conf->insert('conf', ['field' => $key, 'val' => $val]);
        }
    }

    private function seed($tree, $path = '') {
        foreach ($tree as $key => $field) {
            if (is_array($field)) {
                if (count($field) == 1 && array_key_exists(0, $field)) {
                    if (!$this->container->conf->has('conf', ['field' => $path . "/" . $key]))
                        $this->container->conf->insert('conf', ['field' => $path . "/" . $key, 'val' => '']);
                } else $this->seed($field, $path . "/" . $key);
            } else {
                if ($key == '_desc')
                    continue;
                if (!$this->container->conf->has('conf', ['field' => $path . "/" . $key]))
                    $this->container->conf->insert('conf', ['field' => $path . "/" . $key, 'val' => '']);
            }
        }
    }
}
