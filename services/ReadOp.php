<?php
require_once __DIR__ . '/../utils/Env.php';
require_once __DIR__ . '/../utils/Logger.php';

class ReadOp
{
    public function readEntry($uid, $ldap_connection)
    {
        $dn = "uid=" . $uid . "," . Env::get("LDAP_OU") . "," . Env::get("LDAP_ROOT_DN");

        $filter = "(objectclass=*)";
        $attributes = array("cn", "sn", "mail");

        $result = ldap_read($ldap_connection, $dn, $filter, $attributes);
        if (!is_bool($result))
        {
            $entries = ldap_get_entries($ldap_connection, $result);
            $toReturn = "200";
        } else $toReturn = "404";

        return [
            'status' => $toReturn,
            'userAttributes' => $entries
        ];
    }

    public function getUsersFromGroup($groupName, $ldap_connection)
    {
        $dn = "cn=" . $groupName . "," . Env::get("LDAP_OU_FILE") . "," . Env::get("LDAP_ROOT_DN");
        $filter = "(objectclass=*)";
        $attributes = array("uniqueMember");

        $result = ldap_read($ldap_connection, $dn, $filter, $attributes);
        if (!is_bool($result))
        {
            $entries = ldap_get_entries($ldap_connection, $result);
            LoggerSingleton::getInstance()->info("devolviendo correctamente los usuarios de $groupName");
            $toReturn = "200";
        } else {
            LoggerSingleton::getInstance()->info("no se pudo devolver los usuarios de $groupName");
            $toReturn = "404";
        }

        return [
            'status' => $toReturn,
            'users' => $entries
        ];
    }

    public function isUserInGroup($uid, $groupName, $ldap_connection) {
        $response = $this->getUsersFromGroup(groupName: $groupName, ldap_connection: $ldap_connection);

        if ($response["status"] != "200") return false;
        return in_array($uid, $response["users"]);
    }

    public function getGroupsFromUser($uid, $ldap_connection)
    {
        $userDN = "uid=" . $uid . "," . Env::get("LDAP_OU") . "," . Env::get("LDAP_ROOT_DN");
        $dn = Env::get("LDAP_OU_FILE") . "," . Env::get("LDAP_ROOT_DN");
        $filter = "(uniqueMember=" . $userDN . ")";
        $attributes = array("cn");

        $result = ldap_search($ldap_connection, $dn, $filter, $attributes);
        if (!is_bool($result))
        {
            $entries = ldap_get_entries($ldap_connection, $result);
            $groups = [];

            foreach ($entries as $entry) {
                if (isset($entry["cn"][0])) {
                    $groups[] = $entry["cn"][0];
                }
            }
            LoggerSingleton::getInstance()->info("Grupos devueltos correctamente para el usuario $uid");
            $toReturn = "200";
        } else {
            LoggerSingleton::getInstance()->info("No se encontraron grupos para el usuario $uid");
            $toReturn = "404";
            $groups = [];
        }

        return [
            'status' => $toReturn,
            'uid' => $uid,
            'groups' => $groups
        ];
    }

    // esto lo que hace es primero obtener toooodos los valores de uidNumber y
    // regresar el mas alto
    public function getLastGUidNumber($ldap_connection, $character, $maxGUidNumber)
    {
        $baseDn = Env::get("LDAP_ROOT_DN");
        $filter = "(" . $character . "idNumber=*)";
        $attributes = array($character . "idNumber");

        $search = ldap_search($ldap_connection, $baseDn, $filter, $attributes);

        if ($search === false) {
            LoggerSingleton::getInstance()->error("Error searching the last " . $character . "idNumber");
            return $maxGUidNumber;
        }

        $entries = ldap_get_entries($ldap_connection, $search);

        if ($entries["count"] > 0) {
            foreach ($entries as $entry) {
                if (isset($entry[$character . "idnumber"][0])) {
                    $currentValue = (int)$entry[$character . "idnumber"][0];

                    if ($currentValue > $maxGUidNumber) {
                        $maxGUidNumber = $currentValue;
                    }
                }
            }
        }

        return $maxGUidNumber;
    }

    public function getMaxStorage($uid, $ldap_connection) {
        $base_dn_users = Env::get("LDAP_OU") . "," . Env::get("LDAP_ROOT_DN");
        $filter = "(uid=$uid)";
        $attributes = array("roomNumber");

        $search_users = ldap_search($ldap_connection, $base_dn_users, $filter, $attributes);

        if ($search_users) {
            $entries_users = ldap_get_entries($ldap_connection, $search_users);

            if ($entries_users["count"] > 0 && isset($entries_users[0]["roomNumber"])) {
                return [
                    'status' => 200,
                    'maxStorage' => $entries_users[0]["roomNumber"][0]
                ];
            }
        }

        $base_dn_admins = Env::get("LDAP_OU_ADMINS") . "," . Env::get("LDAP_ROOT_DN");
        $search_admins = ldap_search($ldap_connection, $base_dn_admins, $filter, $attributes);

        if ($search_admins) {
            $entries_admins = ldap_get_entries($ldap_connection, $search_admins);

            if ($entries_admins["count"] > 0 && isset($entries_admins[0]["roomNumber"])) {
                return [
                    'status' => 200,
                    'maxStorage' => $entries_admins[0]["roomNumber"][0]
                ];
            }
        }

        return [
            'status' => 404,
            'maxStorage' => "no se encontro una monda"
        ];
    }
}
?>