<?php
require("../vfoundation.php");
require("../data/db.php");
require("../data/player.php");
require("../data/unit.php");

session_start();

define("struck", 0);
define("missed", 1);
define("killed", 2);

function unitCanHit($attacker, $defender) {
	$toHit = ($attacker->getStats()->intel + $attacker->getStats()->agl) * rand(80, 120) / 100.0;
	Logger::log("toHit:", $toHit, "on unit", $attacker);
	$defenderDV = $defender->getDV() * rand(60, 140) / 100;
	Logger::log("dv:", $defenderDV, "on unit", $defender);
	
	return $toHit >= $defenderDV;
}

function getMeleeDamage($attacker, $defender) {
	$damage = $attacker->getStats()->str + $attacker->getStats()->agl / 3 + $attacker->getStats()->intel / 3;
	
	$damage -= $defender->getPV();
	
	return max(1, 10 * $damage * rand(50, 125) / 100.0);
}

function strike($attacker, $defender, $attackRatio) {
	$energyCost = $attackRatio * $attackRatio * 40;
	
	if ($energyCost > $attacker->getEnergyPoints())
		return missed;
	
	$result = missed;
	
	if (unitCanHit($attacker, $defender)) {
		$damageToDefender = getMeleeDamage($attacker, $defender) * $attackRatio;
		$attacker->reduceEnergyPoints($energyCost);
		
		$defender->reduceHealthPoints($damageToDefender);
		
		$result = struck;
	}
	
	$game = $attacker->getGame();
	
	$game->notifyAllPlayers(Notification::attackUnit($attacker, $defender));
	
	if ($defender->health > 0) {
		Unit::table()->update($defender);
	}
	else {
		$game->notifyAllPlayers(Notification::destroyUnit($defender));
		Unit::table()->delete($defender);
		
		if (count($attacker->getPlayer()->getEnemyUnits()) == 0) {
			$game->victoryPlayerID = $attacker->getPlayer()->id;
			Game::table()->update($game);
			$game->notifyAllPlayers(Notification::gameOver($game->victoryPlayerID));
		}
		
		$result = killed;
	}
	
	return $result;
}

function attack($attacker, $defender) {
	if (strike($attacker, $defender, 1) == killed)
		return;
	
	if (strike($defender, $attacker, 0.7) != missed)
		return;
	
	if (strike($attacker, $defender, 0.7) != missed)
		return;
	
	if (strike($defender, $attacker, 0.45) != missed)
		return;
	
	if (strike($attacker, $defender, 0.45) != missed)
		return;
	
	if (strike($defender, $attacker, 0.25) != missed)
		return;
	
	if (strike($attacker, $defender, 0.25) != missed)
		return;
		
	if (strike($defender, $attacker, 0.1) != missed)
		return;
}

try {
	$thisPlayer = Player::table()->fromSession();
	
	$unitID = $_POST["unitID"];
	$targetUnitID = $_POST["targetUnitID"];
	
	$unit = Unit::table()->recall($unitID);
	if ($unit->getEnergyPoints() < 40)
		throw new vexception("This unit doesn't have enough energy to attack.");
	
	$targetUnit = Unit::table()->recall($targetUnitID);
	
	attack($unit, $targetUnit);
	
	$writer = new DataWriter();
	$writer->writeNumber(1);
	$thisPlayer->writeNotificationsStringAndClear($writer);
	echo $writer->flush();
}
catch (Exception $e) {
	DataWriter::writeException($e);
}
?>