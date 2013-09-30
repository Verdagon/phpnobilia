<?php

class Stats extends vDBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = '
		str int,
		sta int,
		dex int,
		agl int,
		intel int,
		spir int';
	
	public $str, $sta, $agl, $intel, $spir;
	
	public static function draftRandom() {
		return self::draft(rand(3, 7), rand(4, 6), rand(3, 7), rand(3, 7), rand(4, 6), rand(3, 7));
	}
	
	public static function draftNeutral() {
		return self::draft(0, 0, 0, 0, 0, 0);
	}
	
	public function add($stats) {
		return self::draft(
		 $this->str + $stats->str,
		 $this->sta + $stats->sta,
		 $this->dex + $stats->dex,
		 $this->agl + $stats->agl,
		 $this->intel + $stats->intel,
		 $this->spir + $stats->spir);
	}
	
	public function meets($stats) {
		return $this->str >= $stats->str &&
		 $this->sta >= $stats->sta &&
		 $this->dex >= $stats->dex &&
		 $this->agl >= $stats->agl &&
		 $this->intel >= $stats->intel &&
		 $this->spir >= $stats->spir;
	}
	
	public function getMinimum() {
		return min($this->str, $this->sta, $this->dex, $this->agl, $this->intel, $this->spir);
	}
	
	public function getMaximum() {
		return max($this->str, $this->sta, $this->dex, $this->agl, $this->intel, $this->spir);
	}
	
	public function getAverage() {
		return ($this->str + $this->sta + $this->dex + $this->agl + $this->intel + $this->spir) / 5.0;
	}
	
	public function getTacticalWorth() {
		return $this->getAverage() + ($this->getMaximum() - $this->getMinimum()) / 4.0;
	}
	
	public function exercise($stat, $increase, Stats $potential) {
		$this->$stat = min($this->$stat + $increase, $potential->$stat);
		return $this;
	}
}

?>