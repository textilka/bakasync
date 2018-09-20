<?php

namespace lib;

function getUserLists($container, string $searchDN, string $searchTable, array $ldapIgnore) {
    $sr = \ldap_search(
        $container->ldap,
        $searchDN,
        "(&(objectclass=user)(objectcategory=person))",
        ['givenName', 'sn', 'department', 'bakaID']
    );

    $ldap = \ldap_get_entries($container->ldap, $sr);

    if ($searchTable == 'zaci') {
        $db = $container->db->select('zaci', ['JMENO', 'PRIJMENI', 'TRIDA', 'INTERN_KOD']);
    } else if ($searchTable == 'ucitele') {
        $db = $container->db->select('ucitele', ['JMENO', 'PRIJMENI', 'INTERN_KOD']);
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
                $student['givenname'][0] !== trim($bakaSt['JMENO']) ||
                $student['sn'][0] !== trim($bakaSt['PRIJMENI']) ||
                $student['department'][0] !== str_replace(".", "", trim($bakaSt['TRIDA']))
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