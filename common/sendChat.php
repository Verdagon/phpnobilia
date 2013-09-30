<?php
define("ROOT", "..");
require_once(ROOT . "/data/chat.php");
require_once(ROOT . "/nobiliaPage.php");

$env = RemoteEnvironment::get();

$text = $env->articulateStringFromGET("text");

Chat::draft($env->player->gameID, time(), $env->player->name . ": " . $text)->insert();


$lastLineID = $env->intFromGET("lastLineID");
echo json_encode(Chat::getUpdateSendable($env->player->gameID, $lastLineID));

?>