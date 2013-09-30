<?php
require_once(ROOT . "/data/db.php");
require_once(ROOT . "/data/game.php");

class Chat extends vDBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	public static function getUpdateSendable($gameID, $lastLineID) {
		$lastChatTime = 0;
		
		$result = array();
		
		if (empty($lastLineID)) {
			$query = vDB::build('
				select * from (
					select * from Chat
					where gameID={$1} and id>{$2}
					order by id desc limit 20) as innerQuery
				order by id asc', $gameID, $lastLineID);
		}
		else {
			$query = vDB::build('
				select * from Chat
				where gameID={$1} and id>{$2}
				order by id asc', $gameID, $lastLineID);
		}
		
		foreach (self::table()->recallMultipleFromQuery($query) as $chat) {
			$lastLineID = $chat->id;
			$lastChatTime = $chat->time;
			$result[] = $chat->sendable();
		}
		
		if ($lastChatTime)
			$lastChatTime = date("g:i", $lastChatTime);
		
		return array(
			"chats" => $result,
			"newLastLineID" => $lastLineID,
			"lastChatTime" => $lastChatTime
		);
	}
	
	const columns = '
		gameID int references Game(id),
		time int,
		text text';
	public $gameID;
	public $time;
	public $text;
}
?>