<?php
require_once __DIR__ . '/../utils/Env.php';

class DeleteOp
{
    public function deleteEntry($uid, $ldap_connection)
    {
        $dn = "uid=" . $uid . "," . Env::get("LDAP_OU") . "," . Env::get("LDAP_ROOT_DN");

        if (ldap_delete($ldap_connection, $dn)) $toReturn = "200";
        else $toReturn="404";

        return $toReturn;
    }
}
?>