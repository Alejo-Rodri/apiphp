<?php
require_once './utils/Logger.php';
require_once './utils/Env.php';

require_once './services/AuthOp.php';
require_once './services/Auth2Op.php';
require_once './services/CreateOp.php';
require_once './services/ReadOp.php';
require_once './services/DeleteOp.php';
require_once './utils/DataFormatter.php';

// ou fileGroup almacena quien pertenece a que grupo para ver si puede consumir el archivo
// ou roleGroup almacena quien pertenece a que rol para ver si puede consumir el endpoint

class Services
{
    public $ldap_connection;
    private $auth2;
    private $create;
    private $read;
    private $formatter;

    public function __construct()
    {
        $this->ldap_connection = ldap_connect(Env::get("LDAP_URL"));

        ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);

        $ldap_bind = ldap_bind($this->ldap_connection, Env::get("LDAP_ADMIN_DN"), Env::get("LDAP_ADMIN_PASSWORD"));

        if ($ldap_bind) LoggerSingleton::getInstance()->info("Connected and bound to the LDAP server.");
        else LoggerSingleton::getInstance()->error("Unable to connect and bound to the LDAP server.");

        $this->auth2 = new Auth2Op();
        $this->create = new CreateOp();
        $this->read = new ReadOp();
        $this->formatter = new DataFormatter();
    }

    // AUTH

    public function authenticate($uid, $psswd)
    {
        $client = new AuthOp();
        return $client->authenticate(uid: $uid, psswd: $psswd, ldap_connection: $this->ldap_connection);
    }

    public function validateRead($fingerprint)
    {
        $tokenData = $this->auth2->validateToken();
        if ($tokenData == null) return "401";

        $uid = $tokenData['uid'];
        $role = $tokenData['role'];
        return $this->auth2->validateRead(uid: $uid, fingerprint: $fingerprint, ldap_connection: $this->ldap_connection, role: $role);
    }

    public function validateWrite($fingerprint)
    {
        $tokenData = $this->auth2->validateToken();
        if ($tokenData == null) return "401";

        $uid = $tokenData['uid'];
        $role = $tokenData['role'];
        return $this->auth2->validateWrite(uid: $uid, fingerprint: $fingerprint, ldap_connection: $this->ldap_connection, role: $role);
    }

    public function validateToken()
    {
        $result = $this->auth2->validateToken();
        if ($result == null) 
            return [
                "status" => "401",
                "uid" => "unknown",
                'role' => "cartoncito"
            ];

        return $result;
    }

    // CRUD

    public function createEntry($uid, $cn, $sn, $mail, $psswd, $maxStorage)
    {
        $tokenData = $this->auth2->validateToken();
        if ($tokenData == null) return "401";

        $uidRequester = $tokenData['uid'];
        return $this->create->createEntry(uid: $uid, cn: $cn, sn: $sn, mail: $mail, psswd: $psswd, ldap_connection: $this->ldap_connection, uidRequester: $uidRequester, maxStorage: $maxStorage);
    }

    public function createAdminEntry($uid, $cn, $sn, $mail, $psswd, $maxStorage)
    {
        $tokenData = $this->auth2->validateToken();
        if ($tokenData == null) return "401";

        $uidRequester = $tokenData['uid'];
        return $this->create->createAdminEntry(uid: $uid, cn: $cn, sn: $sn, mail: $mail, psswd: $psswd, ldap_connection: $this->ldap_connection, uidRequester: $uidRequester, maxStorage: $maxStorage);
    }

    public function createFileGroup($uid, $groupName)
    {
        $token = $this->auth2->validateToken();
        if ($token == null) return "401";

        return $this->create->createFileGroup(uid: $uid, groupName: $groupName, ldap_connection: $this->ldap_connection);
    }

    public function addUserToFileGroup($uid, $groupName)
    {
        $token = $this->auth2->validateToken();
        if ($token == null) return "401";

        return $this->create->addUserToFileGroup(uidToAdd: $uid, groupName: $groupName, ldap_connection: $this->ldap_connection);
    }

    public function readEntry($uid) 
    {
        $token = $this->auth2->validateToken();
        if ($token == null) return "401";

        $toReturn =  $this->formatter->extractUserAttributes($this->read->readEntry(uid: $uid, ldap_connection: $this->ldap_connection));

        return $toReturn;
    }

    public function getUsersFromGroup($groupName)
    {
        $token = $this->auth2->validateToken();
        if ($token == null) return "401";

        $toReturn = $this->formatter->extractUsers($this->read->getUsersFromGroup(groupName: $groupName, ldap_connection: $this->ldap_connection));

        return $toReturn;
    }

    public function getGroupsFromUser() {
        $tokenData = $this->auth2->validateToken();
        if ($tokenData == null) return "401";

        $uidRequester = $tokenData['uid'];
        return $this->read->getGroupsFromUser(uid: $uidRequester, ldap_connection: $this->ldap_connection);
    }

    public function getMaxStorage() {
        $tokenData = $this->auth2->validateToken();
        if ($tokenData == null) return "401";

        $uidRequester = $tokenData['uid'];
        return $this->read->getMaxStorage(uid: $uidRequester, ldap_connection: $this->ldap_connection);
    }

    public function updatePassword($uid, $psswd)
    {
        $client = new UpdateOp();
        return $client->updatePassword(uid: $uid, psswd: $psswd, ldap_connection: $this->ldap_connection);
    }

    public function deleteEntry($uid)
    {
        $client = new DeleteOp();
        return $client->deleteEntry(uid: $uid, ldap_connection: $this->ldap_connection);
    }

    public function closeConnection()
    {
        ldap_unbind($this->ldap_connection);
    }
}
?>