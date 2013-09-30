<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/notification.php");

$env = RemoteEnvironment::get(false);
$env->setPlayersOnly();

Game::dropShadowsAndCleanAbandonedGames();

if (!$env->player->inGame()
	|| $env->player->getGame()->status != Game::PLAYING)
		throw new httpexception(403, "Access denied.");

$env->player->makeContact()->update(false);

$result = array();

$lastChatLineID = $env->intFromGET("lastChatLineID");
$result["chat"] = Chat::getUpdateSendable($env->player->gameID, $lastChatLineID);

$result['notifications'] = Notification::sendablesForPlayer($env->player->id);

echo json_encode($result);

?>