<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// auth
$container['auth'] = function ($c) {
    $settings = $c->get('settings')['auth'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// database
$container['db'] = function ($c) {
    $settings = $c->get('settings')['priv']['db'];
    $db = new Medoo\Medoo([
        'server' => $settings['remote'],
        'database_type' => 'mssql',
        'database_name' => $settings['db'],
        'username' => $settings['user'],
        'password' => $settings['pass']
    ]);
    return $db;
};

// ldap
$container['ldap'] = function ($c) {
    $settings = $c->get('settings')['priv']['ldap'];
    $ldap = ldap_connect($settings['remote'], $settings['port']);

    if (!$ldap)
        throw new \Exception('LDAP invalid');
    
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if (!ldap_bind($ldap, $settings['bind_dn'], $settings['pass']))
        throw new \Exception('LDAP creds invalid');
    
    return $ldap;
};

// OpenID Connect
$container['iodc'] = function ($c) {
    $settings = $c->get('settings')['priv']['oidc'];
    $oidc = new OpenIDConnectClient($settings['remote'], $settings['app_id'], $settings['secret']);
    $oidc->addAuthParam(array('response_mode' => 'form_post'));
    $oidc->setRedirectURL($settings['redirect']);
    return $oidc;
};
