<?php

namespace controller;

class action extends controller {

    const TEST = true;
    /**
     * API schema:
     * put:/action/[userID]
     * 
     * mode = [mod,add,rem]
     * type = [student,teacher]
     * mod|add:
     *  gn = given name
     *  sn = surname
     *  dp = department
     * rem:
     *  none
     */
    use \traits\remote;

    function __construct ($container) {
        parent::__construct($container);
    }

    function __invoke ($request, $response, $args) {
        if ($request->isPost()) {
            $postVars = $request->getParsedBody();

            if (is_string(@$postVars['mode']) || strlen($postVars['mode']) != 3)
                return $response->redirectWithMessage($response, "dashboard", "error", "Chyba komunikace");
            
            if ($args['id'] == "all") {
                //assuming batch mode
                return $response->withJson(["status" => "error", "message" => "not implemented"]);
            }

            if (!is_string(@$postVars['type']) || ($postVars['type'] != "student" && $postVars['type'] != "teacher")) {
                return $response->redirectWithMessage($response, "dashboard", "error", "Chyba komunikace");
            }
            $type = $postVars['type'] == "student" ? STUDENT : TEACHER;

            $userList = $this->getUserLists($type);

            switch ($postVars['mode']) {

                case "mod":
                    if (!is_string(@$postVars['gn']) || !is_string(@$postVars['sn']) || !is_string(@$postVars['dp']))
                        return $response->redirectWithMessage($response, "dashboard", "error", "Chyba komunikace");

                    if (($id = $this->listSearch($userList['different']['ldap'], $args['id'])) !== false) {
                        $dn = $userList['different']['ldap']['dn'];
                        $mail = $this->getSAM($postVars['gn'], $postVars['sn'], $type) . "@" . $this->container->conf->data['/ldap/domain'];
                        if ($this->ldap_log("mod", $dn, [
                            "givenname" => $postVars['gn'],
                            "sn" => $postVars['sn'],
                            "department" => $postVars['dp'],
                            "mail" => $mail,
                            "displayname" => $postVars['gn'] . " " . $postVars['sn'],
                            "cn" => $postVars['gn'] . " " . $postVars['sn'],
                            "samaccountname" => $this->getSAM($postVars['gn'], $postVars['sn'], $type),
                            "userprincipalname" => $mail
                        ])) {
                            return $response->redirectWithMessage($response, "dashboard", "success", "Uživatel aktualizován");
                        } else {
                            return $response->redirectWithMessage($response, "dashboard", "error", "Změna uživatele selhala");
                        }
                    } else {
                        return $response->redirectWithMessage($response, "dashboard", "error", "Uživatel nenalezen");
                    }
                    break;

                case "add":
                    if (!is_string(@$postVars['gn']) || !is_string(@$postVars['sn']) || !is_string(@$postVars['dp']))
                        return $response->redirectWithMessage($response, "dashboard", "error", "Chyba komunikace");

                    if (($id = $this->listSearch($userList['onlyBaka'], $args['id'])) !== false) {
                        $dn = "CN=" . $this->getSAM($postVars['gn'], $postVars['sn'], $type) . "," . $type == STUDENT ? $this->container->conf->data['/ldap/search/students'] : $this->container->conf->data['/ldap/search/teachers'];
                        
                        $mail = $this->getSAM($postVars['gn'], $postVars['sn'], $type) . "@" . $this->container->conf->data['/ldap/domain'];
                        if ($this->ldap_log("add", $dn, [
                            "givenname" => $postVars['gn'],
                            "sn" => $postVars['sn'],
                            "department" => $postVars['dp'],
                            "mail" => $mail,
                            "displayname" => $postVars['gn'] . " " . $postVars['sn'],
                            "cn" => $postVars['gn'] . " " . $postVars['sn'],
                            "samaccountname" => $this->getSAM($postVars['gn'], $postVars['sn'], $type),
                            "userprincipalname" => $mail,
                            "bakaid" => $userList['onlyBaka'][$id]['id']
                        ])) {
                            return $response->redirectWithMessage($response, "dashboard", "success", "Uživatel přidán");
                        } else {
                            return $response->redirectWithMessage($response, "dashboard", "error", "Přidání uživatele selhalo");
                        }
                    } else {
                        return $response->redirectWithMessage($response, "dashboard", "error", "Uživatel nenalezen");
                    }
                    break;

                case "rem":
                    if (($id = $this->listSearch($userList['onlyLDAP'], $args['id'])) !== false) {
                        $dn = $userList['onlyLDAP']['dn'];
                        if ($this->ldap_log("rem", $dn)) {
                            return $response->redirectWithMessage($response, "dashboard", "success", "Uživatel zakázán");
                        } else {
                            return $response->redirectWithMessage($response, "dashboard", "error", "Zakázání uživatele selhalo");
                        }
                    } else {
                        return $response->redirectWithMessage($response, "dashboard", "error", "Uživatel nenalezen");
                    }
                    break;

                default:
                    return $request;
            }
        }
        return $request;
    }

    private function getSAM($gn, $sn, $type) {
        $remove = "/[\s,\.-_]/";
        return preg_replace($remove, "", strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $gn))) . 
               ($type == STUDENT ? "_" : ".") . 
               preg_replace($remove, "", strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $sn)));
    }

    private function listSearch(array $list, string $id) {
        return array_search($id, array_column($list, "id"));
    }

    private function ldap_log(string $action, string $dn, array $args = null) {
        $message = $action . " " . $dn . (is_null($args) ? "" : " " . print_r($args, true));
        $this->container->conf->insert("ldap_log", ["source" => __FILE__, "msg" => $message]);

        if ($this::TEST)
            return true;
        
        switch ($action) {
            case "add":
                return ldap_add($this->container->ldap, $dn, $args);
            case "mod":
                return ldap_mod_replace($this->container->ldap, $dn, $args);
            case "rem":
                // https://support.microsoft.com/en-us/help/305144/
                return ldap_mod_replace($this->container->ldap, $dn, ["userAccountControl" => 514]);
            default:
                return false;
        }
    }
}
