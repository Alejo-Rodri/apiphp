<?php
require_once './utils/Env.php';
require_once 'Services.php';

$options = array('uri' => 'urn:ApiService');
$server = new SoapServer(Env::get("SOAP_WSDL"), $options);

$server->setClass("Services");
$server->handle();
?>