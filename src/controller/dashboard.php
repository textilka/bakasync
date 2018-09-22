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
            
            $ignore = [
                //will be gathered from internal config DB
            ];
            
            $studentsList = $this->getUserLists(
                $this->container->get('settings')['priv']['ldap']['search']['students'],
                "zaci",
                $ignore
            );
    
            $teachersList = $this->getUserLists(
                $this->container->get('settings')['priv']['ldap']['search']['teachers'],
                "ucitele",
                $ignore
            );
    
            $args['studentsList'] = $studentsList;
            $args['teachersList'] = $teachersList;
            
    
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

    private function getUserLists(string $searchDN, string $searchTable, array $ldapIgnore) {
        $sr = \ldap_search(
            $this->container->ldap,
            $searchDN,
            "(&(objectclass=user)(objectcategory=person))",
            ['givenName', 'sn', 'department', 'bakaID']
        );
    
        $ldap = \ldap_get_entries($this->container->ldap, $sr);
    
        if ($searchTable == 'zaci') {
            $db = $this->container->db->select('zaci', ['JMENO', 'PRIJMENI', 'TRIDA', 'INTERN_KOD']);
        } else if ($searchTable == 'ucitele') {
            $db = $this->container->db->select('ucitele', ['JMENO', 'PRIJMENI', 'INTERN_KOD'], ["FUNKCE[~]" => "učitel%"]);
        } else {
            return false;
        }
    
        $bakaRem = $db;
        $ldapRem = $ldap;
    
        $ldapChange = [];
        $ldapCorrect = [];
        
        foreach($ldap as $ldapKey => $student) {
            if (!is_array($student)) {
                unset($ldapRem[$ldapKey]);
                continue;
            }
            
            if (in_array($student['dn'], $ldapIgnore)) {
                unset($ldapRem[$ldapKey]);
                continue;
            }
            
            if (!array_key_exists('bakaid', $student))
                continue;
            
            $bakaKey = array_search($student['bakaid'][0], array_column($db, "INTERN_KOD"));
            if ($bakaKey !== false) {
                $bakaSt = $db[$bakaKey];
                if (
                    @$student['givenname'][0] !== trim(@$bakaSt['JMENO']) ||
                    @$student['sn'][0] !== trim(@$bakaSt['PRIJMENI']) ||
                    (@$student['department'][0] !== str_replace(".", "", trim(@$bakaSt['TRIDA'])) && $searchTable == 'zaci')
                ) {
                    array_push($ldapChange, [
                        "ldap" => $student,
                        "baka" => $bakaSt
                    ]);
                } else {
                    array_push($ldapCorrect, $student);
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