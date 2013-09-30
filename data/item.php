<?php

abstract class Item extends vDBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const children = 'WaterItem, PlagueItem';
	
	const columns = 'unitID int references Unit(id)';
	public $unitID;
	
	public function useItem() { }
	public function beginTurn() { }
	public function endTurn() { }
}



class WaterItem extends Item {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = 'startedUse int';
	public $startedUse;
	
	public function useItem() {
		$this->startedUse = $this->getUnit()->getGame()->turn;
	}
	
	public function startTurn() {
		$currentTurn = $this->getUnit()->getGame()->turn;
		
		if ($currentTurn - $this->startedUse > 3)
			Item::table()->delete($this);
		
		$this->getUnit()->energy += .1;
	}
}



class PlagueItem extends Item {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	public function useItem() {
		foreach ($this->getUnit()->getGame()->getUnits() as $unit) {
			if ($unit->playerID != $this->getUnit()->playerID) {
				$unit->health /= 2;
			}
		}
		
		$this->delete();
	}
}

?>