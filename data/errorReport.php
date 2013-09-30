<?php
require_once(ROOT . "/data/db.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");

class ErrorReport extends vDBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = '
		time int,
		summary text,
		text text,
		playerID int references Player(id),
		gameID int references Game(id)';
	
	public $summary;
	public $text;
	public $playerID;
	public $gameID;
	public $time;
}

?>
