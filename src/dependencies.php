<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['view'] = function ($c) {
    global $container;
    $templateVariables = [
        "router" => $container->router,
        /*"auth" => $container->auth*/
    ];
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path'], $templateVariables);
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
$container['auth'] = function($c) {
    $auth = new \auth($c);
    return $auth;
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

// config database
$container['conf'] = function ($c) {
    $db = new Medoo\Medoo([
        'database_type' => 'sqlite',
	    'database_file' => __DIR__ . "/../db/config.db"
    ]);
    
    // seed db
    $db->query("CREATE TABLE IF NOT EXISTS conf (id INTEGER PRIMARY KEY AUTOINCREMENT, field TEXT NOT NULL, val TEXT NOT NULL);");
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

// flash messages
$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

$container['csrf'] = function ($c) {
    $guard = new \Slim\Csrf\Guard();
    $guard->setFailureCallable(function ($request, $response, $next) {
        $request = $request->withAttribute("csrf_status", false);
        return $next($request, $response);
    });
    return $guard;
};
