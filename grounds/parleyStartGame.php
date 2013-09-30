<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/unit.php");
require_once(ROOT . "/data/stats.php");

$env = RemoteEnvironment::get();
$env->setPlayersOnly();

$MALE_NAMES = array("Awv", "Alister", "Arter", "Aeneas", "Ailill", "Irial", "Aineislis", "Anamcha", "Aodh", "Aodhan", "Artgal", "Beircheart", "Brion", "Cainneach", "Calbhach", "Cathal", "Cathaoir", "Cearbhall", "Conchobhar", "Conn", "Cormac", "Cu Coigriche", "Donal", "Dualtach", "Dunlang", "Earnan", "Coilin", "Daibhi", "Emon", "Fellick", "Eimhin", "Eoghan", "Eolann", "Eireamhon", "Fearadhach", "Finin", "Glaisne", "Lachtna", "Laisren", "Laoiseach", "Lochlainn", "Luchaidh", "Maelechlainn", "Maodhog", "Niall", "Ruairi", "Seanan", "Suibhne", "Tadgh", "Tarlach", "Tomaltach", "Uaithne", "Proinnsias", "Sheary", "Garod", "Ownry", "Eoin", "Shosef", "Mannus", "Mairtin", "Maha", "Mickel", "Nickol", "Noe", "Auliffe", "Paarig", "Padraig", "Raymun", "Reginald", "Ricard", "Solav", "Liam");
//$FEMALE_NAMES = array"Aine", "Aoife", "Aisling", "Alva", "Blinne", "Doireann", "Eithne", "Elva", "Fainche", "Fionnuala", "Maeve", "Maille", "Muireann", "Marion", "Riona", "Saraid", "Sive", "Sorcha", "Sophia", "Taillte", "Taltena", "Ailis", "Alastriona", "Bairbre", "Caireann", "Caitriona", "Sile", "Isibeal", "Seosaimhin", "Mairsil", "Mairead", "Maurayd", "Maire", "Rois", "Treasa");

Game::dropShadowsAndCleanAbandonedGames();

if (!$env->player->inGame()
 || $env->player->getGame()->status != Game::OPEN
 || $env->player->getGame()->hostPlayerID != $env->player->id)
	throw new httpexception(403, "Access denied.");

$game = $env->player->getGame();

$game->status = Game::PREBATTLE;
$game->update();

foreach ($game->allPlayer() as $player) {
	$player->ready = false;
	$player->money = 1000;
	$player->update();
	
	for ($i = 0; $i < 9; $i++) {
		$name = $MALE_NAMES[mt_rand(0, count($MALE_NAMES) - 1)];
		
		$stats = Stats::draftRandom()->insert();
		
		$potentialStats = $stats->add($stats)->insert();
		
		Unit::draft($game->id, $player->id, $name, $stats->id, $potentialStats->id)->insert();
	}
}

echo "Success";

?>