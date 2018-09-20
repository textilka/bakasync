<?php

use Slim\Http\Request;
use Slim\Http\Response;

require __DIR__ . "/lib.php";

// logged in
$app->group('', function() {
    $this->get('/dashboard', function (Request $request, Response $response, array $args) {

        $ignore = [
            "CN=maturita,OU=Students,DC=textilniskola,DC=cz",
            "CN=Tester Šiška,OU=Students,DC=textilniskola,DC=cz",
            "CN=Prozapas,OU=Teachers,DC=textilniskola,DC=cz"
        ];
        
        $studentsList = lib\getUserLists(
            $this,
            $this->get('settings')['priv']['ldap']['search']['students'],
            "zaci",
            $ignore
        );

        $teachersList = lib\getUserLists(
            $this,
            $this->get('settings')['priv']['ldap']['search']['teachers'],
            "ucitele",
            $ignore
        );

        $args['studentsList'] = $studentsList;
        $args['teachersList'] = $teachersList;
        

        //only for testing so we don't bother LDAP and MSSQL
        /*
        require __DIR__ . "/../conf/test-data.php";
        $args['studentsList'] = testData\students();
        $args['teachersList'] = testData\teachers();
        */

        return $this->view->render($response, 'dashboard.phtml', $args);
    })->setName('dashboard');
})->add(\middleware\auth::class);

// not logged in
$app->group('', function() {
    
    $this->get('/', function (Request $request, Response $response, array $args) {
        //$this->logger->info("Slim-Skeleton '/' route");
        return $response->withRedirect($this->router->pathFor('login'), 301);
    });

    $this->get('/login', function (Request $request, Response $response, array $args) {
        return $this->view->render($response, 'login.phtml', $args);
    })->setName('login');

})->add(\middleware\login::class);
