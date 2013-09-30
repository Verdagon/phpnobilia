<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");

$env = RemoteEnvironment::get();

$env->loggedOut();

$env->setStatusAndExit(200);

?>
