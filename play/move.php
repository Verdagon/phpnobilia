<?php
require("../vfoundation.php");
require("../data/db.php");
require("../data/game.php");
require("../data/unit.php");

session_start();

try {
	$thisPlayer = Player::table()->fromSession();
	
	$unitID = $_POST["unitID"];
	
	$numSteps = $_POST["numSteps"];
	
	$unit = Unit::table()->recall($unitID);
	
	// check security, $_SESSION["access"] and $_SESSION["currentPlayerID"] and stuff...
	
	$movementWriter = new DataWriter();
	$movementWriter->writeNumber($numSteps);
	$v = null;
	$d = null;
	$currentTile = null;
	$lastTile = null;
	
	for ($i = 0; $i < $numSteps; $i++) {
		$v = $_POST["step" . $i . "v"];
		$d = $_POST["step" . $i . "d"];
		// Step 0 is where the unit already is.
		
		$currentTile = $unit->getGame()->getMap()->getTileByPosition($v, $d);
		
		if ($i > 0) {
			$movementCost = $lastTile->movementCost($currentTile);
			
			$startTurnEnergy = $unit->getHistoricUnit()->getEnergyPoints();
			$afterMovementEnergy = $unit->getEnergyPoints() - $movementCost;
			
			$traveledDistanceBefore = $unit->traveledDistance;
			$traveledDistanceAfter = $traveledDistanceBefore + $movementCost;
			
			Logger::log($startTurnEnergy, $afterMovementEnergy, $traveledDistanceBefore, $traveledDistanceAfter, $unit->getMovementDistance());
			
			if ($afterMovementEnergy < 0 || $traveledDistanceAfter > $unit->getMovementDistance())
				throw new vexception("This unit doesn't have the energy/movement to go this far.");
			
			$unit->traveledDistance += $movementCost;
			$unit->reduceEnergyPoints($movementCost);
		}
		
		$movementWriter->writeNumber($v);
		$movementWriter->writeNumber($d);
		
		$lastTile = $currentTile;
	}
	
	// do movement checking here
	$unit->v = $v;
	$unit->d = $d;
	Unit::table()->update($unit);

	$notification = Notification::updateUnit($unit);
	$unit->getGame()->notifyAllPlayers($notification);

	$notification = Notification::moveUnit($unit, $v, $d, $movementWriter->toString());
	$unit->getGame()->notifyAllPlayers($notification);
	
	$writer = new DataWriter();
	$writer->writeNumber(1);
	$thisPlayer->writeNotificationsStringAndClear($writer);
	echo $writer->flush();
}
catch (Exception $e) {
	DataWriter::writeException($e);
}
?>