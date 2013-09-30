<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/chat.php");
require_once(ROOT . "/data/notification.php");

$env = RemoteEnvironment::get(false);

Game::dropShadowsAndCleanAbandonedGames();

// We allow both OPEN and PREBATTLE because when it's PREBATTLE, the game has
// just started, and its up to this page to send the player to the customize
// page when PREBATTLE is encountered.
if (!$env->player->inGame()
	|| ($env->player->getGame()->status != Game::PREBATTLE
		&& $env->player->getGame()->status != Game::PLAYING))
			throw new httpexception(403, "Access denied.");

$env->player->makeContact()->update(false);

if (empty($env->player->gameID))
	throw new httpexception(410, "The game has been canceled. You will now be sent to the lobby.");

$game = $env->player->getGame();

if ($game->status == Game::PLAYING)
	$env->setStatusAndExit(201);

$result = array();

$lastChatLineID = $env->intFromGET("lastChatLineID");
$result["chat"] = Chat::getUpdateSendable($game->id, $lastChatLineID);

$result['notifications'] = SystemNotification::sendablesForPlayer($env->player->id);

$env->setStatusAndExit(200, json_encode($result));

?>