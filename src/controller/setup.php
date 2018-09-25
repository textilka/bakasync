<?php

namespace controller;

class setup {

    public const FIELDS = [
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
            'app_base' => 'URL adresa (obvykle již nastaveno instalátorem)'
        ]
    ];

    use \traits\sendResponse;

    protected $container;
    function __construct(\Slim\Container $container) {
        $this->container = $container;
        $this->seed($this::FIELDS);
    }
    function __invoke($request, $response, $args) {
        if ($this->container->conf->get('conf', 'val', ['field' => 'setup']) == "true")
            return $response->withRedirect($this->container->router->pathFor("root", $args), 301);

        if ($request->isGet()) {
            $this->fetch($args);
            
            return $this->sendResponse($request, $response, 'setup.phtml', $args);

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
            return $response->withRedirect($this->container->router->pathFor("setup", $args), 301);
        }
        return $response;
    }

    private function fetch(&$args) {
        foreach ($this->container->conf->select("conf", "*") as $data) {
            $args['conf'][$data['field']] = $data['val'];
        }
        $args['schema'] = $this::FIELDS;
        $args['tests'] = [];

        if (is_null($this->container->ldap)) {
            $args['error'] = [["message" => "Test se nezdařil"]];
            $args['tests']['ldap'] = 0;
        } else {
            $args['tests']['ldap'] = 1;
        }

        if (is_null($this->container->db)) {
            $args['error'] = [["message" => "Test se nezdařil"]];
            $args['tests']['db'] = 0;
        } else {
            $args['tests']['db'] = 1;
        }

        if (!is_array(@$args['error'])) {
            $this->container->conf->update('conf', ['val' => 'true'], ['field' => 'setup']);
            $args['success'] = [["message" => "Nastavení uloženo"]];
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
        if (!$this->container->conf->has('conf', ['field' => 'setup']))
            $this->container->conf->insert('conf', ['field' => 'setup', 'val' => 'false']);
    }
}
