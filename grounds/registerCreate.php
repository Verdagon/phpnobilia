<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/player.php");

$env = RemoteEnvironment::get();
$env->setGuestsOnly();

$name = $env->simpleStringFromGET("name");
$password = $env->articulateStringFromGET("password");
$email = $env->emailFromGET("email");

if (Player::findPossibleByName($name))
	throw new vexception(400, "The supplied username is already taken. Please try another one.");

$player = Player::draft($name, $password, $email)->insert();

$env->loginPlayer($player);

echo "Success";

?>
