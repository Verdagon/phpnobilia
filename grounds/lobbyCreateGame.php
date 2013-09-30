<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/chat.php");

$env = RemoteEnvironment::get();
$env->setPlayersOnly();

if ($env->player->inGame())
	throw new vexception("Access denied");

$gameName = $env->simpleStringFromGET("gameName");

if (Game::nameExists($gameName))
	throw new httpexception(400, "A game by that name already exists.");

$newGame = Game::draft($env->player->id, $gameName)->insert();

$env->player->set("gameID", $newGame->id)->makeContact()->update();

Chat::draft($newGame->id, time(), "Game created.")->insert();

$env->setStatusAndExit(200, $newGame->id);

?>