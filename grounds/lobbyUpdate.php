<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/notification.php");

$env = RemoteEnvironment::get(false);
$env->setPlayersOnly();
if ($env->player->inGame())
	throw new httpexception(403, "Access denied.");

Game::dropShadowsAndCleanAbandonedGames();

$result = array();

$result['games'] = array();
foreach (Game::findOpenGames() as $game)
	$result['games'][] = $game->sendable();

$result['chat'] = Chat::getUpdateSendable(0, $env->intFromGET("lastChatLineID"));

$result['notifications'] = SystemNotification::sendablesForPlayer($env->player->id);

echo json_encode($result);

?>