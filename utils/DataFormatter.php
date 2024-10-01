<?php
class DataFormatter
{
    public function extractUserAttributes($array)
    {
        $userAttributes = [
            'status' => isset($array['status']) ? $array['status'] : null,
            'cn' => isset($array['userAttributes'][0]['cn'][0]) ? $array['userAttributes'][0]['cn'][0] : null,
            'sn' => isset($array['userAttributes'][0]['sn'][0]) ? $array['userAttributes'][0]['sn'][0] : null,
            'mail' => isset($array['userAttributes'][0]['mail'][0]) ? $array['userAttributes'][0]['mail'][0] : null,
        ];

        return $userAttributes;
    }

    public function extractValidateResponse($array)
    {
        $response = [
            'status' => isset($array['status']) ? $array['status'] : null,
            'uid' => isset($array['uid']) ? $array['uid'] : null,
        ];

        return $response;
    }

    public function extractUsers($array)
    {
        $statusValue = $array['status'];

        $users = [];

        if (isset($array['users'][0]['uniquemember']) && is_array($array['users'][0]['uniquemember'])) {
            for ($i = 0; $i < $array['users'][0]['uniquemember']['count']; $i++) {
                $uid = explode('=', $array['users'][0]['uniquemember'][$i])[1];
                $uid = explode(',', $uid)[0];
                $users[] = $uid;
            }
        }

        return [
            'status' => $statusValue,
            'users' => $users
        ];

        return $users;
    }
}
?>