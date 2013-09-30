<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/player.php");

$env = RemoteEnvironment::get();

$env->setGuestsOnly();

$name = $env->simpleStringFromGET("name");
$password = $env->articulateStringFromGET("password");

$player = Player::findPossibleByNameAndPassword($name, $password);

if ($player === null)
	throw new vexception(401, "The supplied username and password were invalid. Remember, passwords are case sensitive.");

$env->loginPlayer($player);

echo "Success";

?>
