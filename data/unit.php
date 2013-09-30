<?php

require_once(ROOT . "/data/stats.php");
require_once(ROOT . "/data/training.php");

class Unit extends vDBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const RECRUITED = 'RECRUITED';
	const UNRECRUITED = 'UNRECRUITED';
	const MOVED = 'MOVED';
	const UNMOVED = 'UNMOVED';
	
	public static function findByGameID($gameID) {
		$query = vDB::build('select * from Unit where gameID={$1} and isHistoric={$2}', $gameID, 0);
		return self::table()->recallMultipleFromQuery($query);
	}
	
	public static function forPlayer($playerID) {
		$query = "select * from Unit where playerID={$playerID} and isHistoric=0";
		return self::table()->recallMultipleFromQuery($query);
	}
	
	public static function enemiesForPlayer($gameID, $playerID) {
		$query = "select * from Unit where gameID={$gameID} and playerID!={$playerID} and isHistoric=0";
		return self::table()->recallMultipleFromQuery($query);
	}
	
	// DATA
	
	const children = 'Warrior, Archer, Mage';
	
	const columns = "
		gameID int not null references Game(id),
		playerID int not null references Player(id),
		name varchar(63) not null,
		statsID int not null references Stats(id),
		potentialStatsID int not null references Stats(id),
		alignmentX float default 0,
		alignmentY float default 0,
		isHistoric int default 0,
		historicUnitID int references Unit(id),
		status enum('RECRUITED', 'UNRECRUITED') default 'UNRECRUITED',
		health int default 0,
		energy int default 0,
		magic int default 0,
		traveledDistance int default 0,
		v int default 0,
		d int default 0";
	
	public $gameID;
	public $playerID;
	public $name;
	protected $statsID;
	protected $potentialStatsID;
	public $alignmentX;
	public $alignmentY;
	public $isHistoric;
	public $historicUnitID;
	public $status;
	protected $health;
	protected $energy;
	protected $magic;
	public $traveledDistance;
	public $v;
	public $d;
	
	public function sendable() {
		$superclasses = array();
		foreach (Unit::table()->getAllDescendants() as $descendant)
			if ($descendant->getMethod("eligible")->invoke(null, $this))
				$superclasses[] = $descendant->name;
		
		return Description::sendableOf($this, array(
			"className" => get_class($this),
			"stats" => $this->getStats()->sendable(),
			"potentialStats" => $this->getPotentialStats()->sendable(),
			"superclasses" => $superclasses,
			"training" => $this->allTraining()
		));
	}
	
	public function getHealth() { return $this->health; }
	public function setHealth($health) {
		$this->health = $health;
		$this->healthPoints = null;
		$this->maxHealthPoints = null;
	}
	
	public function getEnergy() { return $this->energy; }
	public function setEnergy($energy) {
		$this->energy = $energy;
		$this->energyPoints = null;
		$this->maxEnergyPoints = null;
	}
	
	public function getMagic() { return $this->magic; }
	public function setMagic($magic) {
		$this->magic = $magic;
		$this->magicPoints = null;
		$this->maxMagicPoints = null;
	}
	
	public function getStatsID() { return $this->statsID; }
	public function setStatsID($statsID) {
		$this->statsID = $statsID;
		$this->resetAllDependentAttributes();
	}
	
	public function getPotentialStatsID() { return $this->potentialStatsID; }
	public function setPotentialStatsID($potentialStatsID) {
		$this->potentialStatsID = $potentialStatsID;
		$this->resetAllDependentAttributes();
	}
	
	protected function didChangeType() {
		$this->resetAllDependentAttributes();
	}
	
	protected function resetAllDependentAttributes() {
		$this->maxHealthPoints = null;
		$this->healthPoints = null;
		$this->maxEnergyPoints = null;
		$this->energyPoints = null;
		$this->maxMagicPoints = null;
		$this->magicPoints = null;
		$this->dv = null;
		$this->pv = null;
		$this->worth = null;
	}
	
	protected $healthPoints;
	protected $maxHealthPoints;
	protected $energyPoints;
	protected $maxEnergyPoints;
	protected $magicPoints;
	protected $maxMagicPoints;
	protected $pv;
	protected $dv;
	protected $movementDistance;
	protected $worth;
	
	public function getWorth() {
		if (!isset($this->worth))
			$this->worth = round($this->getStats()->getTacticalWorth() * 25);
		return $this->worth;
	}
	
	public function getPV() {
		if (!isset($this->pv) || !isset($this->stats))
			$this->pv = $this->getStats()->str / 2;
		return $this->pv;
	}
	
	public function getDV() {
		if (!isset($this->dv) || !isset($this->stats))
			$this->dv = $this->getStats()->agl + $this->getStats()->intel / 3;
		return $this->dv;
	}
	
	public function getMaxHealthPoints() {
		if (!isset($this->maxHealthPoints))
			$this->maxHealthPoints = 48 + 3 * $this->getStats()->str + 1 * $this->getStats()->sta;
		
		return $this->maxHealthPoints;
	}
	
	public function getHealthPoints() {
		if (!isset($this->healthPoints))
			$this->healthPoints = $this->health * $this->getMaxHealthPoints();
		
		return $this->healthPoints;
	}
	
	public function getMaxEnergyPoints() {
		if (!isset($this->maxEnergyPoints))
			$this->maxEnergyPoints = 200 + 2 * $this->getStats()->str + 10 * $this->getStats()->sta;
		
		return $this->maxEnergyPoints;
	}
	
	public function getEnergyPoints() {
		if (!isset($this->energyPoints))
			$this->energyPoints = $this->energy * $this->getMaxEnergyPoints();
		
		return $this->energyPoints;
	}
	
	public function getMaxMagicPoints() {
		if (!isset($this->maxMagicPoints))
			$this->maxMagicPoints = 2 + 3 * $this->getStats()->mana;
		
		return $this->maxMagicPoints;
	}
	
	public function getMagicPoints() {
		if (!isset($this->magicPoints))
			$this->magicPoints = $this->magic * $this->getMaxMagicPoints();
		
		return $this->magicPoints;
	}
	
	public function getMovementDistance() {
		if (!isset($this->movementDistance))
			$this->movementDistance = 30 + ($this->getStats()->str + $this->getStats()->sta + $this->getStats()->agl) * 3;
		
		return $this->movementDistance;
	}
	
	public function reduceHealthPoints($amount) {
		$this->health = $this->health - $amount / $this->getMaxHealthPoints();
	}
	
	public function reduceEnergyPoints($amount) {
		$this->energy = $this->energy - $amount / $this->getMaxEnergyPoints();
	}
	
	public function increaseEnergy($amount) {
		$this->energy = min(1, $this->energy + $amount);
	}
	
	public function reduceMagicPoints($amount) {
		$this->magic = $this->magic - $amount / $this->getMaxMagicPoints();
	}
	
	public function getTraining($trainingType, $ensureExists = false) {
		$instance = Training::specificForUnit($trainingType, $this->id);
		
		if (isset($instance))
			return $instance;
		
		if ($ensureExists) {
			$instance = vDBClass::get($trainingType)->newInstance();
			$instance->unitID = $this->id;
			return $instance->insert();
		}
		
		return null;
	}
	
	public function getSkill($trainingType, $ensureExists = false) {
		$instance = $this->getTraining($trainingType, $ensureExists);
		
		if (isset($instance))
			return $instance->skill;
		
		return 0;
	}
	
	public function skillsByType() {
		$skills = array();
		foreach ($this->allSkill() as $skill)
			$skills[$skill->className] = $skill;
		return $skills;
	}
	
	
	
	
	// CONSTRUCTION AND WRITING
	
	public function willDelete() {
		$this->getStats()->delete();
		
		$this->getPotentialStats()->delete();
		
		foreach ($this->allTraining() as $training)
			$training->delete();
	}
	
	
	
	
	// OTHER
	
	public function beginTurn() {
		Logger::log("Unit: beginTurn");
		
		$this->increaseEnergy(1.0 / 12);
		
		foreach ($this->getItems() as $item)
			$item->startTurn();
		
		$this->traveledDistance = 0;
		
		$this->writeHistory();
		
		$this->update();
		
		$this->getGame()->notifyAllPlayers(Notification::updateUnit($this));
	}
	
	public function endTurn() {
		foreach ($this->getItems() as $item)
			$item->endTurn();
	}
	
	public function getCloneAsNewClass($newClassName) {
		if (!$class->getMethod("eligible")->invoke(null, $unit))
			throw new httpexception(400, "Can't promote unit {$unit->name} to {$className}");
		$newUnit = $class->getMethod("promote")->invoke(null, $unit);
		return $newUnit;
	}
	
	public static function promote(Unit $unit) {
		return false;
	}
	
	public static function eligible($unit) {
		return false;
	}
}



class Archer extends Unit {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	public static function eligible($unit) {
		return $unit instanceof self &&
		 $unit->getStats()->meets(Stats::draft(7, 0, 0, 0, 7, 0)) &&
		 $unit->getSkill('ArcherySkill') >= 10;
	}
	
	public static function promote(Unit $unit) {
		return $unit->cloneInPlaceAsNewClass('Archer');
	}
}

class Warrior extends Unit {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	public static function eligible($unit) {
		return $unit instanceof self &&
		 $unit->getStats()->meets(Stats::draft(7, 6, 0, 6, 0, 0));
	}
	
	public static function promote(Unit $unit) {
		return $unit->cloneInPlaceAsNewClass('Warrior');
	}
}

class Mage extends Unit {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	public static function eligible($unit) {
		return $unit instanceof self &&
		 $unit->getStats()->meets(Stats::draft(0, 0, 0, 0, 7, 8)) &&
		 $unit->getSkill('ManaSkill') >= 20;
	}
	
	public static function promote(Unit $unit) {
		return $unit->cloneInPlaceAsNewClass('Mage');
	}
}

?>