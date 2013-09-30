<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");

$env = RemoteEnvironment::get();
$env->setPlayersOnly();

if (!$env->player->inGame() || $env->player->getGame()->status != Game::PREBATTLE)
	throw new httpexception(403, "Access denied.");

if ($env->player->ready == 1)
	throw new httpexception(400, "You are already marked as ready. The game will start when the other players say they're ready too.");

$env->player->set("ready", 1)->update();
$game = $env->player->getGame();

$allReady = true;
$players = $game->allPlayer();
foreach ($players as $player)
	if (!$player->ready)
		$allReady = false;

if ($allReady) {
	$colors = array("Blue", "Red", "Green", "Yellow", "Teal", "Orange", "Magenta", "Black", "Maroon");
	
	$numPlayers = count($players);
	for ($i = 0; $i < $numPlayers; $i++) {
		$thisPlayer = $players[$i];
		$nextPlayer = $players[($i + 1) % $numPlayers];
		$thisPlayer->nextPlayerID = $nextPlayer->id;
		$thisPlayer->color = $colors[$i];
		$thisPlayer->update();
	}
	
	foreach ($game->allUnit() as $unit)
		if ($unit->status == Unit::RECRUITED)
			$unit->set("status", Unit::UNMOVED)->update();
		else
			$unit->delete();
	
	$radius = 3;
	$numTiles = Map::getNumTiles($radius);
	$rawTerrain = array();
	for ($i = 0; $i < $numTiles; $i++) {
		switch (mt_rand(0, 2)) {
		case 0: $tile = "GL:"; break;
		case 1: $tile = "GH:"; break;
		case 2: $tile = "DL:"; break;
		}
		$rawTerrain[] = $tile . mt_rand(0, 2);
	}
	$rawTerrain = implode(" ", $rawTerrain);
	
	$game->status = "PLAYING";
	$game->radius = $radius;
	$game->terrain = $rawTerrain;
	$game->currentTurnPlayerID = $game->getHostPlayer()->nextPlayerID;
	$game->update();
	
	// Placing Units
	
	if (count($players) == 2) {
		$units = $players[0]->allUnit();
		for ($i = 0; $i < count($units); $i++)
			$units[$i]->set("v", $i)->set("d", $radius - $i)->update();
		
		$units = $players[1]->allUnit();
		for ($i = 0; $i < count($units); $i++)
			$units[$i]->set("v", -$i)->set("d", -$radius + $i)->update();
	}
	else
		throw new httpexception(400, "Invalid number of players");
	
	$game->beginGame();
}

?>