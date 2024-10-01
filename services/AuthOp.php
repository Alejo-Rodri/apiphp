<?php
require_once __DIR__ . '/../utils/Env.php';
require_once __DIR__ . '/Auth2Op.php';

use Firebase\JWT\JWT;

class AuthOp
{
    public function authenticate($uid, $psswd, $ldap_connection)
    {
        $normalDn = "uid=" . $uid . "," . Env::get("LDAP_OU") . "," . Env::get("LDAP_ROOT_DN");

        if (ldap_bind($ldap_connection, $normalDn, $psswd)) {
            return [
                'status' => 200,
                "token" => $this->generateToken(uid: $uid, ldap_connection: $ldap_connection)
            ];
        }

        $adminDn = "uid=" . $uid . "," . Env::get("LDAP_OU_ADMINS") . "," . Env::get("LDAP_ROOT_DN");
        if (ldap_bind($ldap_connection, $adminDn, $psswd)) {
            return [
                'status' => 200,
                "token" => $this->generateToken(uid: $uid, ldap_connection: $ldap_connection)
            ];
        }

        return [
            "status" => 401,
            "token" => ""
        ];
    }

    public function generateToken($uid, $ldap_connection)
    {
        $auth2 = new Auth2Op();
        if ($auth2->isAdmin(uid: $uid, ldap_connection: $ldap_connection)) $role = "admin";
        else $role = "normal";

        $now = strtotime("now");
        $key = Env::get("API_KEY");
        $payload = [
            'exp' => $now + 3600000,
            'uid' => $uid,
            'role' => $role
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        LoggerSingleton::getInstance()->info("jwt: " . $jwt);
        return $jwt;
    }
}
?>