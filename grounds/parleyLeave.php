<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/notification.php");

$env = RemoteEnvironment::get();
$env->setPlayersOnly();

if (!$env->player->inGame()
 || $env->player->getGame()->status != Game::OPEN)
	throw new httpexception(403, "Access denied.");

$game = $env->player->getGame();

if ($env->player->isHost()) {
	foreach ($game->allPlayer() as $player) {
		if ($player == $game->getHostPlayer())
			SystemNotification::draft($player->id, "You canceled the game " . $game->name)->insert();
		else
			SystemNotification::draft($player->id, "The host canceled the game " . $game->name)->insert();
			
		$player->leaveGameAndUpdate();
	}
	
	$game->delete();
}
else {
	SystemNotification::draft($env->player->id, "You left the game " . $game->name);
	
	$env->player->leaveGameAndUpdate();
}

$env->setStatusAndExit(200);

?>