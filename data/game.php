<?php
require_once("db.php");

class Game extends vDBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const OPEN = 'OPEN';
	const PREBATTLE = 'PREBATTLE';
	const PLAYING = 'PLAYING';
	
	public static function nameExists($name) {
		$query = vDB::build('select * from Game where name={$1}', $name);
		return self::table()->recallPossibleFromQuery($query) != null;
	}
	
	public static function findOpenGames() {
		$query = vDB::build('select * from Game where status={$1}', self::OPEN);
		return self::table()->recallMultipleFromQuery($query);
	}
	
	// Shadows are people who haven't made contact in at least 15 seconds.
	public static function dropShadowsAndCleanAbandonedGames() {
		/*$query = vDB::build('select * from Player where UNIX_TIMESTAMP() - lastContact > 15 and gameID>0');
		$players = Player::table()->recallMultipleFromQuery($query);
		foreach ($players as $player)
			$player->leaveGameAndUpdate();
		
		$query = vDB::build('select * from Game where 0 = (select count(id) from Player where Player.gameID = Game.id)');
		foreach (self::table()->recallMultipleFromQuery($query) as $doomedGame)
			$doomedGame->delete();*/
	}
	
	public static function deleteUnrecruitedUnits() {
		$query = vDB::build('select * from Unit where gameID={$1} and status={$2}', $game->id, "UNRECRUITED");
		foreach (self::table()->recallMultipleFromQuery($query) as $unit)
			$unit->delete();
	}
	
	const columns = "
		hostPlayerID int references Player(id),
		name varchar(63),
		status enum('OPEN', 'PREBATTLE', 'PLAYING') default 'OPEN',
		radius int,
		terrain text,
		currentTurnPlayerID int references Player(id),
		turn int,
		victoryPlayerID int references Player(id)";
	public $status;
	public $hostPlayerID;
	public $name;
	public $radius, $terrain;
	public $currentTurnPlayerID;
	public $turn, $victoryPlayerID;
	
	public function sendable() {
		$players = array();
		foreach ($this->allPlayer() as $player)
			$players[] = $player->sendable();
		return Description::sendableOf($this, array("players" => $players));
	}
	
	public function willDelete() {
		foreach ($this->allUnit() as $unit)
			$unit->delete();
		
		foreach ($this->allChat() as $chat)
			$chat->delete();
	}
	
	
	
	public function notifyAllPlayers(Notification $modelNotification) {
		foreach ($this->allPlayer() as $player)
			$modelNotification->forPlayer($player)->insert();
	}
	
	protected $map;
	public function getMap() {
		if ($this->map == null)
			$this->map = new Map($this);
		return $this->map;
	}

	
	
	
	
	
	
	
	
	public function beginGame() {
		$this->getCurrentTurnPlayer()->beginTurn();
	}
	
	public function nextTurn() {
		$this->getCurrentTurnPlayer()->endTurn();
		
		Logger::log("Old turn player id: " . $this->currentTurnPlayerID);
		
		$this->currentTurnPlayerID = $this->getCurrentTurnPlayer()->nextPlayerID;
		
		$startPlayer = $this->getHostPlayer()->nextPlayerID;
		
		if ($this->currentTurnPlayerID == $startPlayer)
			$this->turn++;
			
		Logger::log("New turn player id: " . $this->currentTurnPlayerID);
		
		$this->getCurrentTurnPlayer()->beginTurn();
	}
}



class Map {
	private $game;
	public $radius;
	public $tiles;
	private $rectTiles;
	
	public function __construct(Game $game) {
		$this->game = $game;
		$this->radius = $game->radius;
		$this->tiles = array();
		$this->rectTiles = array();
		
		$rawT = explode(" ", $game->terrain);
		
		$currentID = 0;
		for ($v = -$this->radius; $v <= $this->radius; $v++) {
			for ($d = -$this->radius; $d <= $this->radius; $d++) {
				if ($this->coordinateValid($v, $d)) {
					$tileStr = $rawT[$currentID];
					$colonPos = strpos($tileStr, ":");
					$terrain = substr($tileStr, 0, $colonPos);
					$attributes = explode(",", substr($tileStr, $colonPos + 1));
					$elevation = (int)$attributes[0] + 1;
					$tile = new Tile($currentID, $v, $d, $terrain, $elevation);
					$this->tiles[$currentID] = $tile;
					$this->rectTiles[$v * (2 * $this->radius + 1) + $d] = $tile;
					$currentID++;
				}
			}
		}
	}
	
	public function coordinateValid($v, $d) {
		return (abs($v) <= $this->radius &&
			abs($d) <= $this->radius &&
			abs($v + $d) <= $this->radius);
	}
	
	public function getTileByPosition($v, $d) {
		return $this->rectTiles[$v * (2 * $this->radius + 1) + $d];
	}
	
	
	
	// MAP RADIUS/NUMTILES RELATION
	
	public static $radiusTilesTable = array(1, 7, 19, 37, 61, 91, 127, 169, 217, 271, 331, 397, 469);
	
	public static function getNumTiles($radius) {
		return self::$radiusTilesTable[$radius];
	}
	
	public static function getRadiusFromTiles($numTiles) {
		for ($i = 0; $i < count(self::$radiusTilesTable); $i++) {
			if (self::$radiusTilesTable[$i] == $numTiles) {
				return $i;
			}
		}
		
		die("numTiles: " + $numTiles);
	}
}



class Tile {
	public $id;
	public $v;
	public $d;
	public $terrain;
	public $elevation;
	
	public function __construct($id, $v, $d, $terrain, $elevation) {
		$this->id = $id;
		$this->v = $v;
		$this->d = $d;
		$this->terrain = (string)$terrain;
		$this->elevation = (int)$elevation;
	}
	
	public function distance(Tile $that) {
		$diff = abs($that->v - $this->v) + abs($that->d - $this->d);
		if (($that->v - $this->v)*($that->d - $this->d) < 0)
			$diff -= min(abs($that->v - $this->v), abs($that->d - $this->d));
		return $diff;
	}
	
	// Effort table (for reference): (-2 means going down two, etc.)
	// {-5: 30, -4: 20, -3: 10, -2: 0, -1: -10, 0: 0, 1: 10, 2: 20, 3: 30, 4: 50, 5: 70};
	// ALSO DOCUMENTED IN GAME.JS (keep them synched)
	// When the two tiles are adjacent, this is accurate. This gets less accurate the further the tiles are.
	public function movementCost(Tile $that) {
		$elevationDifference = $that->elevation - $this->elevation;
		if ($elevationDifference < 4)
			return $this->distance($that) * 20 + abs($elevationDifference + 1) * 10 - 10;
		else
			return $this->distance($that) * 20 + $elevationDifference * 20 - 30;
	}
}
?>