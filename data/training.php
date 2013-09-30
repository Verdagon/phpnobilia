<?php
require_once(ROOT . "/data/db.php");
require_once(ROOT . "/data/unit.php");

abstract class Training extends vDBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	
	public static function specificForUnit($type, $unitID) {
		assert(is_int($unitID));
		$query = vDB::build('select * from Training where className={$1} and unitID={$2}', $type, $unitID);
		return self::table()->recallPossibleFromQuery($query);
	}
	
	const children = '
		HunterTraining,
		StrengthTraining,
		StaminaTraining,
		HistoricalTraining,
		ArcheryTraining,
		SwordplayTraining,
		DefensiveTraining,
		StrategeryTraining,
		ManaTraining';
	
	const columns = '
		unitID int references Unit(id),
		skill int default 0,
		potential int default 5';
	public $unitID, $skill, $potential;
	
	public function trainAndUpdate() {
		$cost = $this->getDBClass()->getNewestConstant('cost');
		$player = $this->getUnit()->getPlayer();
		if ($player->money < $cost)
			throw new httpexception(400, "You don't have enough money to get that training.");
		$player->money -= $cost;
		$player->update();
		
		$changes = $this->implement();
		$changes["playerMoney"] = $player->money;
		return $changes;
	}
	
	protected abstract function implement();
}

class HunterTraining extends Training {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const cost = 100;
	
	public function implement() {
		$unit = $this->getUnit();
		$stats = $unit->getStats();
		$potential = $unit->getPotentialStats();
		
		$stats->exercise("sta", 1, $potential);
		$potential->sta += 3;
		$stats->exercise("sta", 2, $potential);
		
		$stats->exercise("intel", 1, $potential);
		
		$stats->update();
		$potential->update();
		
		$this->skill += mt_rand(10, 13);
		$this->potential = min($this->potential, $this->skill) + 3;
		$this->update();
		
		return array("stats" => $stats->sendable(), "HunterTraining" => $this->sendable());
	}
}

class StrengthTraining extends Training {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const cost = 100;
	
	public function implement() {
		$unit = $this->getUnit();
		$stats = $unit->getStats();
		$potential = $unit->getPotentialStats();
		
		$stats->exercise("str", 1, $potential);
		$potential->str += 3;
		$stats->exercise("str", 2, $potential);
		
		$stats->exercise("sta", 1, $potential);
		
		$stats->update();
		$potential->update();
		
		$this->skill += mt_rand(10, 13);
		$this->potential = min($this->potential, $this->skill) + 3;
		$this->update();
		
		return array("stats" => $stats->sendable(), "StrengthTraining" => $this->sendable());
	}
}

class StaminaTraining extends Training {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const cost = 100;
	
	public function implement() {
		$unit = $this->getUnit();
		$stats = $unit->getStats();
		$potential = $unit->getPotentialStats();
		
		$stats->exercise("sta", 1, $potential);
		$potential->sta += 3;
		$stats->exercise("sta", 2, $potential);
		
		$stats->exercise("str", 1, $potential);
		
		$stats->update();
		$potential->update();
		
		$this->skill += mt_rand(10, 13);
		$this->potential = min($this->potential, $this->skill) + 3;
		$this->update();
		
		return array("stats" => $stats->sendable(), "StaminaTraining" => $this->sendable());
	}
}

class HistoricalTraining extends Training {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const cost = 100;
	
	public function implement() {
		$unit = $this->getUnit();
		$stats = $unit->getStats();
		$potential = $unit->getPotentialStats();
		
		$stats->exercise("intel", 1, $potential);
		$potential->intel += 1;
		$stats->exercise("intel", 1, $potential);
		
		$stats->update();
		$potential->update();
		
		$this->skill += mt_rand(10, 13);
		$this->potential = min($this->potential, $this->skill) + 3;
		$this->update();
		
		return array("stats" => $stats->sendable(), "HistoricalTraining" => $this->sendable());
	}
}

class ArcheryTraining extends Training {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const cost = 100;
	
	public function implement() {
		$unit = $this->getUnit();
		$stats = $unit->getStats();
		$potential = $unit->getPotentialStats();
		
		$stats->exercise("str", 1, $potential);
		$potential->str += 1;
		$stats->exercise("str", 1, $potential);
		
		$stats->exercise("intel", 1, $potential);
		$potential->intel += 1;
		$stats->exercise("intel", 1, $potential);
		
		$stats->update();
		$potential->update();
		
		$this->skill += mt_rand(10, 13);
		$this->potential = min($this->potential, $this->skill) + 3;
		$this->update();
		
		return array("stats" => $stats->sendable(), "ArcheryTraining" => $this->sendable());
	}
}

class SwordplayTraining extends Training {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const cost = 100;
	
	public function implement() {
		$unit = $this->getUnit();
		$stats = $unit->getStats();
		$potential = $unit->getPotentialStats();
		
		$stats->exercise("str", 1, $potential);
		$potential->str += 1;
		$stats->exercise("str", 1, $potential);
		
		$stats->exercise("sta", 1, $potential);
		$potential->sta += 1;
		$stats->exercise("sta", 1, $potential);
		
		$stats->exercise("agl", 1, $potential);
		$potential->agl += 2;
		$stats->exercise("agl", 1, $potential);
		
		$stats->update();
		$potential->update();
		
		$this->skill += mt_rand(10, 13);
		$this->potential = min($this->potential, $this->skill) + 3;
		$this->update();
		
		return array("stats" => $stats->sendable(), "SwordplayTraining" => $this->sendable());
	}
}

class DefensiveTraining extends Training {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const cost = 100;
	
	public function implement() {
		$unit = $this->getUnit();
		$stats = $unit->getStats();
		$potential = $unit->getPotentialStats();
		
		$stats->exercise("agl", 1, $potential);
		$potential->agl += 3;
		$stats->exercise("agl", 2, $potential);
		
		$stats->update();
		$potential->update();
		
		$this->skill += mt_rand(10, 13);
		$this->potential = min($this->potential, $this->skill) + 3;
		$this->update();
		
		return array("stats" => $stats->sendable(), "DefensiveTraining" => $this->sendable());
	}
}

class StrategeryTraining extends Training {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const cost = 100;
	
	public function implement() {
		$unit = $this->getUnit();
		$stats = $unit->getStats();
		$potential = $unit->getPotentialStats();
		
		$stats->exercise("intel", 1, $potential);
		$potential->intel += 2;
		$stats->exercise("intel", 2, $potential);
		
		$stats->update();
		$potential->update();
		
		$this->skill += mt_rand(10, 13);
		$this->potential = min($this->potential, $this->skill) + 3;
		$this->update();
		
		return array("stats" => $stats->sendable(), "StrategeryTraining" => $this->sendable());
	}
}

class ManaTraining extends Training {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const cost = 1;
	
	public function implement() {
		$unit = $this->getUnit();
		$stats = $unit->getStats();
		$potential = $unit->getPotentialStats();
		
		$stats->exercise("spir", 1, $potential);
		$potential->spir += 2;
		$stats->exercise("spir", 2, $potential);
		
		$stats->update();
		$potential->update();
		
		$this->skill += mt_rand(10, 13);
		$this->potential = min($this->potential, $this->skill) + 3;
		$this->update();
		
		return array("stats" => $stats->sendable(), "ManaTraining" => $this->sendable());
	}
}

?>