<?php

namespace controller;

class dashboard extends controller {
    
    use \traits\remote;

    function __construct ($container) {
        parent::__construct($container);
    }

    function __invoke ($request, $response, $args) {
        if ($request->isGet()) {
            
            if (!is_null($this->container->db)) {
                $args['studentsList'] = $this->getUserLists(STUDENT);
                $args['teachersList'] = $this->getUserLists(TEACHER);
            } else {
                $args['studentsList'] = null;
                $args['teachersList'] = null;
            }
            
            //only for testing so we don't bother LDAP and MSSQL
            /*
            require __DIR__ . "/../../conf/test-data.php";
            $args['studentsList'] = \testData\students();
            $args['teachersList'] = \testData\teachers();
            //*/

            return $this->sendResponse($request, $response, 'dashboard.phtml', $args);
        }
        return $response;
    }
}