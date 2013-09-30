<?php
require_once(ROOT . "/data/db.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/unit.php");
require_once(ROOT . "/data/chat.php");

class Player extends vDBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	
	public static function draft($name, $password, $email) {
		$player = new Player();
		$player->name = $name;
		$player->password = sha1($password);
		$player->email = $email;
		return $player;
	}
	
	public static function findByGameID($gameID) {
		$query = vDB::build('
			select * from Player
			where gameID={$1}
		', $gameID);
		return self::table()->recallMultipleFromQuery($query);
	}
	
	public static function findPossibleByNameAndPassword($name, $password) {
		$query = vDB::build('
			select * from Player
			where name={$1}
			and password=sha1({$2})
		', $name, $password);
		return self::table()->recallPossibleFromQuery($query);
	}
	
	public static function findPossibleByName($name) {
		$query = vDB::build('
			select * from Player
			where name={$1}
		', $name);
		return self::table()->recallPossibleFromQuery($query);
	}
	
	const columns = '
		name varchar(63),
		password char(40),
		email varchar(63),
		access int default 1,
		lastContact int,
		gameID int references Game(id),
		money int,
		ready int,
		nextPlayerID int references Player(id),
		color varchar(15)';
	public $name, $email, $lastContact, $access;
	protected $password;
	public $gameID, $money, $ready, $nextPlayerID, $color;
	
	public function sendable() {
		$units = array();
		foreach ($this->allUnit() as $unit)
			$units[] = $unit->sendable();
		
		return Description::sendableOf($this, array("units" => $units));
	}
	
	public function inGame() {
		return !empty($this->gameID);
	}
	
	public function isHost() {
		assert($this->inGame());
		
		return $this->getGame()->hostPlayerID == $this->id;
	}
	
	public function leaveGameAndUpdate() {
		assert($this->inGame());
		
		Chat::draft($this->gameID, time(), $this->name . " has left the game.")->insert();
		
		return $this->set("gameID", 0)->update();
	}
	
	public function getEnemyUnits() {
		return Unit::table()->enemiesForPlayer($this->gameID, $this->id);
	}
	
	public function writeNotificationsStringAndClear($writer) {
		$notifications = $this->getNotifications();
		$writer->writeNumber(count($notifications));
		foreach ($notifications as $notification) {
			$writer->writeString($notification->notification);
			Notification::table()->delete($notification);
		}
		return $writer;
	}
	
	private $nextPlayer;
	public function getNextPlayer() {
		if ($this->nextPlayer === null)
			$this->nextPlayer = Player::table()->recall($this->nextPlayerID);
		return $this->nextPlayer;
	}
	
	
	
	
	
	public function makeContact() {
		$this->lastContact = time();
		return $this;
	}
	
	public function beginTurn() {
		Logger::log("Starting turn,", $this);
		
		foreach ($this->getUnits() as $unit)
			$unit->beginTurn();
	}
	
	public function endTurn() {
		foreach ($this->getUnits() as $unit)
			$unit->endTurn();
	}
}
?>