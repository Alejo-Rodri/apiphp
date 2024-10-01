<?php
require_once __DIR__ . '/../utils/Env.php';
require_once __DIR__ . '/ReadOp.php';

class CreateOp
{
    public function createEntry($uid, $cn, $sn, $mail, $psswd, $ldap_connection, $uidRequester, $maxStorage)
    {
        LoggerSingleton::getInstance()->info("uidRequester: " . $uidRequester);
        $auth2 = new Auth2Op();

        if (!$auth2->isAdmin(uid: $uidRequester, ldap_connection: $ldap_connection)) return "401";
        $hashedPassword = "{SHA}" . base64_encode(pack("H*", sha1($psswd)));

        $read = new ReadOp();
        $newUidNumber = $read->getLastGUidNumber(ldap_connection: $ldap_connection, character: "u", maxGUidNumber: Env::get("MAX_UID_NUMBER")) + 1;
        $newGidNumber = $read->getLastGUidNumber(ldap_connection: $ldap_connection, character: "g", maxGUidNumber: Env::get("MAX_GID_NUMBER")) + 1;

        $dn = "uid=" . $uid . "," . Env::get("LDAP_OU") . "," . Env::get("LDAP_ROOT_DN");
        $attributes = array(
            "cn" => $cn,
            "sn" => $sn,
            "mail" => $mail,
            "roomNumber" => $maxStorage,
            "userPassword" => $hashedPassword,
            "uidNumber" => $newUidNumber,
            "gidNumber" => $newGidNumber,
            "homeDirectory" => "/home/$uid",
            "loginShell" => "/bin/bash",
            "objectClass" => array("top", "shadowAccount", "inetOrgPerson", "posixAccount")
        );

        if (ldap_add($ldap_connection, $dn, $attributes)) {
            if ($this->createFileGroup(uid: $uid, groupName: $uid, ldap_connection: $ldap_connection) == 200) $toReturn = "200";
            else $toReturn = "404";
        }
        else $toReturn = "404";

        return $toReturn;
    }

    public function createAdminEntry($uid, $cn, $sn, $mail, $psswd, $ldap_connection, $uidRequester, $maxStorage)
    {
        $auth2 = new Auth2Op();
        if (!$auth2->isAdmin(uid: $uidRequester, ldap_connection: $ldap_connection)) return "401";
        $hashedPassword = "{SHA}" . base64_encode(pack("H*", sha1($psswd)));

        $read = new ReadOp();
        $newUidNumber = $read->getLastGUidNumber(ldap_connection: $ldap_connection, character: "u", maxGUidNumber: Env::get("MAX_UID_NUMBER")) + 1;
        $newGidNumber = $read->getLastGUidNumber(ldap_connection: $ldap_connection, character: "g", maxGUidNumber: Env::get("MAX_GID_NUMBER")) + 1;

        $dn = "uid=" . $uid . "," . Env::get("LDAP_OU_ADMINS") . "," . Env::get("LDAP_ROOT_DN");
        $attributes = array(
            "cn" => $cn,
            "sn" => $sn,
            "mail" => $mail,
            "roomNumber" => $maxStorage,
            "userPassword" => $hashedPassword,
            "uidNumber" => $newUidNumber,
            "gidNumber" => $newGidNumber,
            "homeDirectory" => "/home/$uid",
            "loginShell" => "/bin/bash",
            "objectClass" => array("top", "shadowAccount", "inetOrgPerson", "posixAccount")
        );

        if (ldap_add($ldap_connection, $dn, $attributes)) {
            if ($this->createFileGroup(uid: $uid, groupName: $uid, ldap_connection: $ldap_connection) == 200) $toReturn = "200";
            else $toReturn = "404";
        } else $toReturn = "404";

        return $toReturn;
    }

    public function createFileGroup($uid, $groupName, $ldap_connection)
    {
        $dn = "cn=" . $groupName . "," . Env::get("LDAP_OU_FILE") . "," . Env::get("LDAP_ROOT_DN");
        $attributes = array(
            "cn" => $groupName,
            "owner" => "uid=" . $uid . "," . Env::get("LDAP_OU") . "," . Env::get("LDAP_ROOT_DN"),
            "uniqueMember" => "uid=" . $uid . "," . Env::get("LDAP_OU") . "," . Env::get("LDAP_ROOT_DN"),
            "ou" => Env::get("LDAP_OU_FILE"),
            "objectClass" => array("top", "groupOfUniqueNames")
        );

        if (ldap_add($ldap_connection, $dn, $attributes)) {
            LoggerSingleton::getInstance()->info("se creo correctamente el grupo $groupName");
            $toReturn = "200";
        } else {
            LoggerSingleton::getInstance()->info("no se pudo crear el grupo $groupName :c");
            $toReturn = "404";
        }

        return $toReturn;
    }

    public function addUserToFileGroup($uidToAdd, $groupName, $ldap_connection)
    {
        $dn = "cn=" . $groupName . "," . Env::get("LDAP_OU_FILE") . "," . Env::get("LDAP_ROOT_DN");
        $entry = array(
            "uniqueMember" => "uid=" . $uidToAdd . "," . Env::get("LDAP_OU") . "," . Env::get("LDAP_ROOT_DN"),
        );

        if (ldap_mod_add($ldap_connection, $dn, $entry)) {
            $toReturn = "200";
            LoggerSingleton::getInstance()->info("El atributo uniqueMember ha sido agregado correctamente para $uidToAdd");
        } else {
            $toReturn = "404";
            LoggerSingleton::getInstance()->info("Error al agregar el atributo uniqueMember para el usuario $uidToAdd");
        }

        return $toReturn;
    }
}
?>