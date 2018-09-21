<?php

namespace controller;

class login {

    use \traits\sendResponse;

    protected $container;
    function __construct(\Slim\Container $container) {
        $this->container = $container;
    }
    function __invoke($request, $response, $args) {
        if ($request->isGet()) {
            return $this->sendResponse($request, $response, 'login.phtml', $args);
        } else if ($request->isPost()) {
            $postVars = $request->getParsedBody();

            if (!\array_key_exists("uname", $postVars) || !\array_key_exists("passw", $postVars) || empty($postVars['uname']) || empty($postVars['passw'])) {
                $args['error'] = [["message" => "Vyplňte všechny údaje"]];
                return $this->sendResponse($request, $response, 'login.phtml', $args);
            }

            /*
            $settings = $c->get('settings')['priv']['ldap'];
            $ldap = ldap_connect($settings['remote'], $settings['port']);

            if (!$ldap)
                throw new \Exception('LDAP invalid');
            
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            if (!ldap_bind($ldap, $settings['bind_dn'], $settings['pass']))
                throw new \Exception('LDAP creds invalid');
                
            */
            var_dump($request->getParsedBody());
        }
        return $request;
    }
}
