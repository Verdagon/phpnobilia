<?php
require_once(ROOT . "/data/db.php");

abstract class Notification extends vDBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	
	public static function forPlayer($playerID) {
		assert(is_int($playerID));
		$query = vDB::build('select * from Notification where receivingPlayerID={$1} order by id asc', $playerID);
		return self::table()->recallMultipleFromQuery($query);
	}
	
	public static function sendablesForPlayer($playerID) {
		$notificationsSendables = array();
		foreach (self::forPlayer($playerID) as $notification) {
			$notificationsSendables[] = $notification->sendable();
			$notification->delete();
		}
		return $notificationsSendables;
	}
	
	const children = '
		SystemNotification,
		UnitChangeIntNotification,
		UnitAttackedNotification,
		UnitMovedNotification,
		SwitchTurnNotification,
		PlayerWonNotification';
	
	const columns = 'receivingPlayerID int references Player(id)';
	public $receivingPlayerID;
}

class SystemNotification extends Notification {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	public static function forPlayer($playerID) {
		assert(is_int($playerID));
		$query = vDB::build('select * from Notification where className={$1} and receivingPlayerID={$2} order by id asc', get_class($this), $playerID);
		return self::table()->recallMultipleFromQuery($query);
	}
	
	const columns = 'text text';
	public $text;
}

abstract class UnitChangeIntNotification extends Notification {
	const children = 'UnitChangeHPNotification, UnitChangeEnergyNotification';
	
	const columns = 'number int';
	public $ivalue;
}

class UnitChangeHPNotification extends UnitChangeIntNotification {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
}

class UnitChangeEnergyNotification extends UnitChangeIntNotification {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
}

class UnitAttackedNotification extends Notification {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const children = 'UnitDestroyedNotification';
	
	const columns = '
		attackerID int references Unit(id),
		defenderID int references Unit(id)';
	public $attackerID, $defenderID;
}

class UnitDestroyedNotification extends UnitAttackedNotification {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
}

class UnitMovedNotification extends Notification {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = 'v int, d int';
	public $v, $d;
}

class SwitchTurnNotification extends Notification {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = 'playerID int references Player(id)';
	public $playerID;
}

class PlayerWonNotification extends Notification {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = 'playerID int references Player(id)';
}
?>