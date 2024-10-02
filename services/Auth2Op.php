<?php
require_once __DIR__ . '/../utils/Env.php';
require_once __DIR__ . '/ReadOp.php';
require_once __DIR__ . '/../utils/MongoController.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth2Op
{
    public function validateToken()
    {
        try {
            $soapRequest = file_get_contents('php://input');

            $xml = new SimpleXMLElement($soapRequest);

            $namespaces = $xml->getNamespaces(true);
            $header = $xml->children($namespaces['soapenv'])->Header->children($namespaces['urn']);
            $authToken = (string)$header->AuthToken;

            LoggerSingleton::getInstance()->info("authToken: $authToken");

            $decodedToken = JWT::decode($authToken, new Key(Env::get("API_KEY"), 'HS256'));
            $decoded_array = (array) $decodedToken;
            LoggerSingleton::getInstance()->info("uid: " . $decoded_array["uid"]);
            return [
                'status' => 200,
                'uid' => $decoded_array["uid"],
                'role' => $decoded_array["role"]
            ];
        } catch (Exception $exception) {
            LoggerSingleton::getInstance()->info($exception->getMessage());
            return null;
        }
    }

    public function isAdmin($uid, $ldap_connection)
    {
        LoggerSingleton::getInstance()->info("uid: " . $uid);
        $dn = Env::get("LDAP_OU_ADMINS") . "," . Env::get("LDAP_ROOT_DN");
        $filter = "(uid=" . ldap_escape($uid, "", LDAP_ESCAPE_FILTER) . ")";
        $attributes = array("uid");

        $result = ldap_search($ldap_connection, $dn, $filter, $attributes);
        if (!is_bool($result))
        {
            $entries = ldap_get_entries($ldap_connection, $result);
            if ($entries['count'] > 0) {
                LoggerSingleton::getInstance()->info("El uid $uid fue encontrado, si es admin");
                return true;
            }
        }

        LoggerSingleton::getInstance()->info("El uid $uid no fue encontrado, pailangas tangas");
        return false;
    }

    public function validateRead($uid, $fingerprint, $ldap_connection, $role)
    {
        $mongoController = new MongoController();

        $response = $mongoController->getPermissions(fingerprint: $fingerprint);
        if ($response == null)
            return [
                'status' => "418",
                'uid' => $uid,
                'role' => "mishuevos"
            ];

        LoggerSingleton::getInstance()->info("Permisos: " . $response["permissions"] . " Owner: " . $response["owner"]);

        if ($this->isAdmin(uid: $uid, ldap_connection: $ldap_connection))
            return [
                'status' => "200",
                'uid' => $uid,
                'role' => $role
            ];

        if ($response["owner"] == $uid) 
            return [
                'status' => "200",
                'uid' => $uid,
                'role' => $role
            ];

        $others = $response["permissions"][3]; 
        if (in_array($others, ["4", "5", "7"])) 
            return [
                'status' => "200",
                'uid' => $uid,
                'role' => $role
            ];

        $group = $response["permissions"][2];
        if (in_array($group, ["4", "5", "7"])) {
            $read = new ReadOp();
            $isUserInGroup = $read->isUserInGroup(uid: $uid, groupName: $response["owner"], ldap_connection: $ldap_connection);
            if ($isUserInGroup) 
                return [
                    'status' => "200",
                    'uid' => $uid,
                    'role' => $role
                ];
        }

        return [
            'status' => "403",
            'uid' => $uid,
            'role' => 'cartoncito'
        ];
    }

    public function validateWrite($uid, $fingerprint, $ldap_connection, $role) 
    {
        $mongoController = new MongoController();

        $response = $mongoController->getPermissions(fingerprint: $fingerprint);
        if ($response == null) 
            return [
                'status' => "418",
                'uid' => $uid,
                'role' => "mishuevos"
            ];

        LoggerSingleton::getInstance()->info("Permisos: " . $response["permissions"] . " Owner: " . $response["owner"]);

        if ($this->isAdmin(uid: $uid, ldap_connection: $ldap_connection))
            return [
                'status' => "200",
                'uid' => $uid,
                'role' => $role
            ];

        if ($response["owner"] == $uid)
            return [
                'status' => "200",
                'uid' => $uid,
                'role' => $role
            ];

        $others = $response["permissions"][3]; 
        if (in_array($others, ["2", "3", "6", "7"]))
            return [
                'status' => "200",
                'uid' => $uid,
                'role' => $role
            ];

        $group = $response["permissions"][2];
        if (in_array($group, ["2", "3", "6", "7"]))
            $read = new ReadOp();
            $isUserInGroup = $read->isUserInGroup(uid: $uid, groupName: $response["owner"], ldap_connection: $ldap_connection);
            if ($isUserInGroup)
                return [
                    'status' => "200",
                    'uid' => $uid,
                    'role' => $role
                ];

        return [
            'status' => "403",
            'uid' => $uid,
            'role' => 'cartoncito'
        ];
    }
}
?>