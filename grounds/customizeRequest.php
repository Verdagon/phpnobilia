<?php
define("ROOT", "..");
require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/unit.php");
require_once(ROOT . "/data/stats.php");
require_once(ROOT . "/data/training.php");

$env = RemoteEnvironment::get();

$unitID = $env->intFromGET("unitID");
$request = $env->simpleStringFromGET("request");

$unit = Unit::table()->recall($unitID);
$player = $unit->getPlayer();

$changes = array();

switch ($request) {
case "recruit":
	if ($player->money < ($worth = $unit->getWorth()))
		throw new httpexception(400, "You don't have enough money to recruit this unit.");
	
	$changes['status'] = $unit->status = Unit::RECRUITED;
	$unit->update();
	
	$changes['playerMoney'] = ($player->money -= $worth);
	$player->update();
	
	break;
	
case "unrecruit":
	$changes['status'] = $unit->status = Unit::UNRECRUITED;
	$unit->update();
	
	$changes['playerMoney'] = $player->money += $unit->getWorth() / 2;
	$player->update();
	
	break;
	
case "train":
	$type = $env->simpleStringFromGET("type");
	$changes = $unit->getTraining($type, true)->trainAndUpdate();
	break;
	
case "promote":
	$className = $env->intFromGET("className");
	
	$class = vDBClass::get($className);
	
	$unit = $unit->getCloneAsNewClass($className);
	$changes = array('newUnit' => $unit->sendable());
	
	break;
}

echo json_encode($changes);

?>