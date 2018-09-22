<?php

namespace controller;

class dashboard {
    
    use \traits\sendResponse;

    protected $container;
    function __construct(\Slim\Container $container) {
        $this->container = $container;
    }
    function __invoke($request, $response, $args) {
        if ($request->isGet()) {
            
            $args['studentsList'] = $this->getUserLists(STUDENT);
            $args['teachersList'] = $this->getUserLists(TEACHER);
            
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

    private function getUserLists($type = STUDENT) {
        if ($type == STUDENT) {
            $db = array_map(function($e) {
                return [
                    'dn' => null,
                    'gn' => trim(@$e['JMENO']),
                    'sn' => trim(@$e['PRIJMENI']),
                    'dp' => trim(@$e['TRIDA']),
                    'id' => trim(@$e['INTERN_KOD'])
                ];
            }, $this->container->db->select('zaci', ['JMENO', 'PRIJMENI', 'TRIDA', 'INTERN_KOD']));

        } else if ($type == TEACHER) {
            $db = array_map(function($e) {
                return [
                    'dn' => null,
                    'gn' => trim(@$e['JMENO']),
                    'sn' => trim(@$e['PRIJMENI']),
                    'dp' => trim(@$e['FUNKCE']),
                    'id' => trim(@$e['INTERN_KOD'])
                ];
            }, $this->container->db->select(
                'ucitele',
                ['JMENO', 'PRIJMENI', 'FUNKCE', 'INTERN_KOD'],
                ["FUNKCE[~]" => array_map(function ($e) {return $e . "%";}, $this->container->get('settings')['priv']['db']['te_role'])]
            ));
        } else {
            return false;
        }

        $sr = \ldap_search(
            $this->container->ldap,
            $this->container->get('settings')['priv']['ldap']['search'][['students', 'teachers'][$type]],
            "(&(objectclass=user)(objectcategory=person))",
            ['givenName', 'sn', 'department', 'bakaID']
        );
    
        $ldap = \ldap_get_entries($this->container->ldap, $sr);

        unset($ldap['count']);

        $ldap = array_map(function($e) {
            return [
                'dn' => $e['dn'],
                'gn' => @$e['givenname'][0],
                'sn' => @$e['sn'][0],
                'dp' => @$e['department'][0],
                'id' => @$e['bakaid'][0]
            ];
        }, $ldap);

        $ldapIgnore = $this->container->get('settings')['priv']['ldap']['ignoreDN'];

        $bakaRem = $db;
        $ldapRem = $ldap;
    
        $ldapChange = [];
        $ldapCorrect = [];

        foreach($ldap as $ldapKey => $ldapVal) {
            
            if (in_array($ldapVal['dn'], $ldapIgnore)) {
                unset($ldapRem[$ldapKey]);
                continue;
            }
            
            if (!array_key_exists('id', $ldapVal))
                continue;
            
            $bakaKey = array_search($ldapVal['id'], array_column($db, 'id'));
            if ($bakaKey !== false) {
                $dbVal = $db[$bakaKey];
                if (
                    $ldapVal['gn'] == $dbVal['gn'] &&
                    $ldapVal['sn'] == $dbVal['sn'] &&
                    $ldapVal['dp'] == $dbVal['dp']
                ) {
                    array_push($ldapCorrect, $ldapVal);
                } else {
                    array_push($ldapChange, [
                        "ldap" => $ldapVal,
                        "baka" => $dbVal
                    ]);
                }
                unset($ldapRem[$ldapKey]);
                unset($bakaRem[$bakaKey]);
            }
        }

        return [
            "correct" => $ldapCorrect,
            "different" => $ldapChange,
            "onlyLDAP" => $ldapRem,
            "onlyBaka" => $bakaRem
        ];
    }
}