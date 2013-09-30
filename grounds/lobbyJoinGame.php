<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/chat.php");

$env = RemoteEnvironment::get();
$env->setPlayersOnly();

Game::dropShadowsAndCleanAbandonedGames();

if ($env->player->inGame())
	throw new httpexception(403, "Access denied.");

if ($env->player->gameID)
	throw new httpexception(400, "Player is already in a game.");

$gameID = $env->intFromGET("gameID");

if (Game::table()->recall($gameID) === null)
	throw new httpexception(410, "Game no longer exists.");

// If a game could be full, this is where we'd check for it.

$env->player->set("gameID", $gameID)->update();

Chat::draft($gameID, time(), $env->player->name . " has joined the game.")->insert();

echo "Success";

?>