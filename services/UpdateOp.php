<?php
require_once __DIR__ . '/../utils/Env.php';

class UpdateOp
{
    public function updatePassword($uid, $psswd, $ldap_connection)
    {
        $hashedPassword = "{SHA}" . base64_encode(pack("H*", sha1($psswd)));

        $dn = "uid=" . $uid . "," . Env::get("LDAP_OU") . "," . Env::get("LDAP_ROOT_DN");
        $attributes = array(
            "userPassword" => $hashedPassword
        );

        if (ldap_modify($ldap_connection, $dn, $attributes)) $toReturn = "200";
        else $toReturn="404";

        return $toReturn;
    }
}
?>