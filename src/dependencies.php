<?php
// DIC configuration

$container = $app->getContainer();

// config database
$container['conf'] = function ($c) {
    $db = new Medoo\Medoo([
        'database_type' => 'sqlite',
	    'database_file' => __DIR__ . "/../db/config.db"
    ]);
    
    // seed db after updates
    $db->query("CREATE TABLE IF NOT EXISTS conf (id INTEGER PRIMARY KEY AUTOINCREMENT, field TEXT NOT NULL, val TEXT NOT NULL);");
    $db->exec("CREATE TABLE IF NOT EXISTS ldap_log (id INTEGER PRIMARY KEY AUTOINCREMENT, source TEXT NOT NULL, msg TEXT NOT NULL, t TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");

    $confDB = [];
    foreach ($db->select('conf', '*') as $data) {
        $confDB[$data['field']] = $data['val'];
    }

    $db->data = $confDB;
    
    return $db;
};

// view renderer
$container['view'] = function ($c) {
    global $container;
    $templateVariables = [
        "router" => $container->router,
        "auth" => $container->auth
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
    $settings = $c->conf->data;

    $op = @fsockopen($settings['/db/remote'], 1433, $errno, $errstr, 2);
    if (!$op)
        return null;
    else fclose($op);

    try {
        $db = new Medoo\Medoo([
            'server' => $settings['/db/remote'],
            'database_type' => 'mssql',
            'database_name' => $settings['/db/db'],
            'username' => $settings['/db/user'],
            'password' => $settings['/db/_pass']
        ]);
        return $db;
    } catch (PDOException $e) {
        return null;
    }
};

// ldap
$container['ldap'] = function ($c) {
    $settings = $c->conf->data;

    $op = @fsockopen($settings['/ldap/remote'], $settings['/ldap/port'], $errno, $errstr, 2);
    if (!$op)
        return null;
    else fclose($op);

    $ldap = ldap_connect($settings['/ldap/remote'], $settings['/ldap/port']);

    if (!$ldap)
        return null;
    
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if (!ldap_bind($ldap, $settings['/ldap/bind_dn'], $settings['/ldap/_pass']))
        return null;
    
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
