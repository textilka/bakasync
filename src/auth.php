<?php

class auth {

    protected $container;
    protected $message;
    protected $uname;
    private $logged = false;

    function __construct(\Slim\Container $container) {
        $this->container = $container;
        if (@$_SESSION['auth'] === true) {
            $this->logged = true;
            if (@$_SESSION['auth_ip'] !== $_SERVER['REMOTE_ADDR'] || !is_string(@$_SESSION['auth_name'])) {
                $this->message = "Byli jste odhlášeni";
                $this->logout();
            } else {
                $this->uname = $_SESSION['auth_name'];
            }
        }
    }

    public function getMessage() {
        return $this->message;
    }

    public function getUname() {
        return $this->uname;
    }

    public function logged() {
        return $this->logged;
    }

    public function logout() {
        unset($_SESSION['auth']);
        unset($_SESSION['auth_ip']);
        unset($_SESSION['auth_name']);
        unset($this->uname);
        $this->logged = false;
    }

    public function login(string $uname, string $passw) {

        if (!is_string(@$uname) || !is_string(@$passw) || strlen($uname) == 0 || strlen($passw) == 0) {
            $this->message = "Vyplňte všechny údaje";
            return false;
        }

        $settings = $this->container->conf->data;

        if (!$this->serviceping($settings['/ldap/remote'], $settings['/ldap/port'])) {
            $this->message = "Server je nedostupný, zkuste to později";
            return false;
        }

        $ldap = ldap_connect($settings['/ldap/remote'], $settings['/ldap/port']);
        
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

        if (@ldap_bind($ldap, $uname . "@" . $settings['/ldap/domain'], $passw)) {
            $userDN = $this->getDN($ldap, $uname, $settings['/ldap/search/admins']);
            if (!$userDN) {
                $this->message = "Nemáte oprávnění pro tuto akci";
                return false;
            }
            
            if (!$this->checkGroup($ldap, $userDN, "CN=Domain Admins,CN=Users,DC=" . str_replace(".", ",DC=", $settings['domain']))) {
                $this->message = "Nemáte oprávnění pro tuto akci";
                return false;
            }
            $this->loginUser(explode(", ", ldap_dn2ufn($userDN))[0]);
            return true;
        } else {
            $this->message = "Chybné uživatelské jméno, nebo heslo";
            return false;
        }
    }

    private function loginUser($name) {
        $this->logged = true;
        $this->uname = $name;
        $_SESSION['auth'] = true;
        $_SESSION['auth_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['auth_name'] = $name;
    }

    private function getDN($ad, $samaccountname, $basedn) {
        $attributes = array('dn');
        $result = ldap_search($ad, $basedn,
            "(samaccountname=$samaccountname)", $attributes);
        if ($result === false) { return false; }
        $entries = ldap_get_entries($ad, $result);
        if ($entries['count']>0) { return $entries[0]['dn']; }
        else { return false; };
    }

    private function checkGroup($ad, $userdn, $groupdn) {
        $attributes = array('members');
        $result = ldap_read($ad, $userdn, "(memberof=$groupdn)", $attributes);
        if ($result === false) { return false; };
        $entries = ldap_get_entries($ad, $result);
        return ($entries['count'] > 0);
    }

    private function serviceping($host, $port=389, $timeout=1) {
        $op = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$op) return false;
        else {
            fclose($op);
            return true;
        }
    }
}
