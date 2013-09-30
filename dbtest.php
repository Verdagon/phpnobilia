<?php

define('ROOT', '.');

require_once(ROOT . "/nobiliaPage.php");
require_once(ROOT . "/data/db.php");
require_once(ROOT . "/data/game.php");
require_once(ROOT . "/data/player.php");
require_once(ROOT . "/data/stats.php");
require_once(ROOT . "/data/notification.php");
require_once(ROOT . "/data/unit.php");
require_once(ROOT . "/data/chat.php");
require_once(ROOT . "/data/item.php");
/*
class Thing extends DBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = '
		firstNotificayshunID int references Notificayshun(id),
		secondNotificayshunID int references Notificayshun(id)';
	public $firstNotificayshunID, $secondNotificayshunID;
}

class Playyur extends DBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = 'name varchar(63)';
	public $name;
}

class Yoonit extends DBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = '
		hp int,
		playerID int references Playyur(id)';
}

class Notificayshun extends DBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const children = 'ChatNotificayshun, SiteFrozenNotificayshun';
	const columns = '
		time int,
		playerID int references Playyur(id)';
	public $time, $playerID;
}

class ChatNotificayshun extends Notificayshun {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const children = 'ColoredChatNotificayshun, EmotedChatNotificayshun';
	const columns = '
		fromPlayerID int references Playyur(id),
		text text';
	public $fromPlayerID, $text;
}

class SiteFrozenNotificayshun extends Notificayshun {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = 'reason text';
	public $reason;
}

class ColoredChatNotificayshun extends ChatNotificayshun {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
	
	const columns = 'color varchar(6)';
	public $color;
}

class EmotedChatNotificayshun extends ChatNotificayshun {
	public static function draft() { return vDBClass::get(__CLASS__)->draft(func_get_args()); }
}

class Tester {
	public static function runTests() {
		assert_options(ASSERT_WARNING, false);
		assert_options(ASSERT_CALLBACK, array('Tester', 'failedAssert'));
		set_error_handler(create_function('$a, $b, $c, $d', 'throw new ErrorException($b, 0, $a, $c, $d);'), E_ALL);
		
		$class = new ReflectionClass(__CLASS__);
		foreach ($class->getMethods() as $method) {
			if (strncmp($method->name, "test", strlen("test")) == 0) {
				Logger::log("=== Test " . $method->name . " ===");
				
				try {
					if ($method->invoke(null) === false)
						echo "<div>Test {$method->name} <b>failed</b>, returned false.</div>";
					else
						echo "<div>Test {$method->name} passed.</div>";
				}
				catch (Exception $e) {
					echo "<p>Test {$method->name} <b>failed</b>: " . nl2br($e->getMessage() . "\n" . formatBacktrace($e->getTrace())) . "</p>";
				}
				
				Logger::log("=== End Test " . $method->name . " ===");
			}
		}
	}
	
	public static function failedAssert() {
		Logger::log("Assert failed:\n" . formatBacktrace(debug_backtrace()));
		throw new Exception('Assert failed');
	}
	
	public static function testResetDatabase() {
		vDB::resetDatabase();
	}
	
	public static function testAccessSimple() {
		Playyur::table();
	}
	
	// Simple
	
	public static function testDraftSimple() {
		Playyur::draft('myname');
	}
	
	public static function testIncorrectDraftSimple() {
		try {
			Playyur::draft(4005);
			return false;
		}
		catch (Exception $e) { }
	}
	
	public static function testInsertSimple() {
		$id = Playyur::draft('coolname')->insert()->id;
		assert(!empty($id));
	}
	
	public static function testCache() {
		$Playyur = Playyur::draft('coolername')->insert();
		$playerID = $Playyur->id;
		
		assert(Playyur::table()->recall($playerID) === $Playyur);
	}
	
	public static function testClearCache() {
		$Playyur = Playyur::draft('coolername')->insert();
		$playerID = $Playyur->id;
		Playyur::table()->clearCache();
		
		assert(Playyur::table()->recall($playerID) !== $Playyur);
	}
	
	public static function testRecallSimple() {
		$id = Playyur::draft('lolname')->insert()->id;
		$Playyur = Playyur::table()->recall($id);
		assert($Playyur->name == 'lolname');
	}
	
	public static function testRecallMultipleSimple() {
		vDB::resetDatabase();
		Playyur::draft('klorkname')->insert();
		Playyur::draft('blorglol')->insert();
		Playyur::draft('beepgob')->insert();
		Playyur::draft('shibblewit')->insert();
		$query = "select * from Playyur where name like '%o%'";
		$players = Playyur::table()->recallMultipleFromQuery($query);
		assert(count($players) == 3);
	}
	
	public static function testUpdateSimple() {
		$id = Playyur::draft('sleeblewobbit')->insert()->id;
		Playyur::table()->recall($id)->set("name", "shorblewoozle")->update();
		assert(Playyur::table()->recall($id)->name == 'shorblewoozle');
	}
	
	public static function testDeleteSimple() {
		$id = Playyur::draft('sleeblewobbit')->insert()->id;
		Playyur::table()->recall($id)->delete();
		try {
			Playyur::table()->recall($id);
			return false;
		}
		catch (Exception $e) { }
	}
	
	public static function testDeleteMultipleSimple() {
		vDB::resetDatabase();
		Playyur::draft('klorkname')->insert();
		Playyur::draft('blorglol')->insert();
		Playyur::draft('beepgob')->insert();
		Playyur::draft('shibblewit')->insert();
		vDB::affect("delete from Playyur where name like '%o%'");
		$players = vDB::select("select * from Playyur");
		assert(count($players) == 1);
	}
	
	// Referencer
	
	public static function testIncorrectDraft() {
		try {
			Yoonit::draft(44, "shouldnt be a string!");
			return false;
		}
		catch (Exception $e) { }
	}
	
	public static function testInsertHangingReferencer() {
		try {
			Yoonit::draft(40, 84949394)->insert();
			return false;
		}
		catch (Exception $e) { }
	}
	
	// Root
	
	public static function testDraftRoot() {
		$playerID = Playyur::draft('shibblename')->insert()->id;
		Notificayshun::draft(time() + 100, $playerID);
	}
	
	public static function testIncorrectDraftRoot() {
		$playerID = Playyur::draft('shibblename')->insert()->id;
		
		try {
			Notificayshun::draft('wowSHLOOP', $playerID)->insert();
			return false;
		}
		catch (Exception $e) { }
	}
	
	public static function testInsertRoot() {
		$playerID = Playyur::draft('shibblename')->insert()->id;
		$id = Notificayshun::draft(time() + 100, $playerID)->insert()->id;
		assert(!empty($id));
	}
	
	public static function testRecallRootFromCache() {
		$playerID = Playyur::draft('shloopynoodle')->insert()->id;
		$time = time() + 100;
		$NotificayshunID = Notificayshun::draft($time, $playerID)->insert()->id;
		$Notificayshun = Notificayshun::table()->recall($NotificayshunID);
		assert($Notificayshun->time == $time);
		assert($Notificayshun->playerID == $playerID);
	}
	
	public static function testRecallRoot() {
		$playerID = Playyur::draft('chablis')->insert()->id;
		$id = Notificayshun::draft(39485766, $playerID)->insert()->id;
		
		Notificayshun::table()->clearCache();
		
		$Notificayshun = Notificayshun::table()->recall($id);
		assert(get_class($Notificayshun) == 'Notificayshun');
		assert($Notificayshun->id == $id);
		assert($Notificayshun->playerID == $playerID);
	}
	
	public static function testRecallMultipleRoot() {
		$playerID = Playyur::draft('shibblename')->insert()->id;
		
		$time = time() - 100;
		Notificayshun::draft($time, $playerID)->insert();
		Notificayshun::draft($time, $playerID)->insert();
		Notificayshun::draft(time(), $playerID)->insert();
		$query = "select * from Notificayshun where time='$time'";
		$Notificayshuns = Notificayshun::table()->recallMultipleFromQuery($query);
		assert(count($Notificayshuns) == 2);
	}
	
	public static function testUpdateRoot() {
		$playerID = Playyur::draft('shibblename')->insert()->id;
		$id = Notificayshun::draft(42378964, $playerID)->insert()->id;
		
		Notificayshun::table()->recall($id)->set("time", 42378264)->update();
		
		assert(Notificayshun::table()->recall($id)->time == 42378264);
	}
	
	public static function testDeleteRoot() {
		$playerID = Playyur::draft('shibblename')->insert()->id;
		$id = Notificayshun::draft(42378964, $playerID)->insert()->id;
		Notificayshun::table()->recall($id)->delete();
		
		try {
			Notificayshun::table()->recall($id);
			return false;
		}
		catch (Exception $e) { }
	}
	
	public static function testDeleteMultipleRoot() {
		$playerID = Playyur::draft('shibblename')->insert()->id;
		
		$time = time() - 100;
		Notificayshun::draft($time, $playerID)->insert();
		Notificayshun::draft($time, $playerID)->insert();
		Notificayshun::draft(time(), $playerID)->insert();
		
		$query = "delete from Notificayshun where time='$time'";
		vDB::affect($query);
		
		$query = "select * from Notificayshun where time='$time'";
		$Notificayshuns = Notificayshun::table()->recallMultipleFromQuery($query);
		assert(count($Notificayshuns) == 0);
	}
	
	// Child
	
	public static function testDraftChild() {
		$playerID = Playyur::draft('lolrus')->insert()->id;
		$toPlayerID = Playyur::draft('numrich')->insert()->id;
		ChatNotificayshun::draft(66554433, $playerID, $toPlayerID, 'LOL HALLO');
	}
	
	public static function testInsertChild() {
		$playerID = Playyur::draft('lolbert')->insert()->id;
		$toPlayerID = Playyur::draft('zurich')->insert()->id;
		$id = ChatNotificayshun::draft(66554433, $playerID, $toPlayerID, 'LOL HALLO')->insert()->id;
		assert(!empty($id));
	}
	
	public static function testRecallChild() {
		$playerID = Playyur::draft('zurblis')->insert()->id;
		$toPlayerID = Playyur::draft('kalthro')->insert()->id;
		$id = ChatNotificayshun::draft(39485766, $playerID, $toPlayerID, 'LOL aw.')->insert()->id;
		
		Notificayshun::table()->clearCache();
		
		$Notificayshun = Notificayshun::table()->recall($id);
		assert($Notificayshun instanceof ChatNotificayshun);
		assert($Notificayshun->time == 39485766);
		assert($Notificayshun->text == "LOL aw.");
	}
	
	public static function testRecallDescendant1() {
		$playerID = Playyur::draft('sheeplah')->insert()->id;
		$toPlayerID = Playyur::draft('zatnikatel')->insert()->id;
		$id = EmotedChatNotificayshun::draft(39485766, $playerID, $toPlayerID, 'LOL aw.')->insert()->id;
		
		Notificayshun::table()->clearCache();
		
		$Notificayshun = Notificayshun::table()->recall($id);
		assert($Notificayshun instanceof EmotedChatNotificayshun);
		assert($Notificayshun->time == 39485766);
		assert($Notificayshun->text == "LOL aw.");
	}
	
	public static function testRecallDescendant2() {
		$playerID = Playyur::draft('blahbloo')->insert()->id;
		$toPlayerID = Playyur::draft('zeeplydoodah')->insert()->id;
		$id = ColoredChatNotificayshun::draft(39485766, $playerID, $toPlayerID, 'LOL aw.', '4040FF')->insert()->id;
		
		Notificayshun::table()->clearCache();
		
		$Notificayshun = Notificayshun::table()->recall($id);
		assert($Notificayshun instanceof ColoredChatNotificayshun);
		assert($Notificayshun->time == 39485766);
		assert($Notificayshun->text == "LOL aw.");
		assert($Notificayshun->color == '4040FF');
	}
	
	public static function testRecallDifferentChildrenFromCache() {
		$playerID = Playyur::draft('boobs!!')->insert()->id;
		$toPlayerID = Playyur::draft('no wai!!')->insert()->id;
		$id1 = ChatNotificayshun::draft(39485766, $playerID, $toPlayerID, 'Where??')->insert()->id;
		$id2 = EmotedChatNotificayshun::draft(39485766, $playerID, $toPlayerID, 'PIX!!')->insert()->id;
		$id3 = ColoredChatNotificayshun::draft(39485766, $playerID, $toPlayerID, 'ZOMG!!', '008000')->insert()->id;
		$id4 = Notificayshun::draft(39485766, $playerID)->insert()->id;
		$id5 = SiteFrozenNotificayshun::draft(39485766, $playerID, 'no cp, thats why')->insert()->id;
		
		$n1 = Notificayshun::table()->recall($id1);
		$n2 = Notificayshun::table()->recall($id2);
		$n3 = Notificayshun::table()->recall($id3);
		$n4 = Notificayshun::table()->recall($id4);
		$n5 = Notificayshun::table()->recall($id5);
		
		assert(get_class($n1) == 'ChatNotificayshun');
		assert($n1->text == 'Where??');
		
		assert($n2 instanceof EmotedChatNotificayshun);
		assert($n2->text == 'PIX!!');
		
		assert($n3 instanceof ColoredChatNotificayshun);
		assert($n3->text == 'ZOMG!!');
		assert($n3->color == '008000');
		
		assert(get_class($n4) == 'Notificayshun');
		
		assert($n5 instanceof SiteFrozenNotificayshun);
		assert($n5->reason == 'no cp, thats why');
	}
	
	public static function testRecallDifferentChildrenNotFromCache() {
		$playerID = Playyur::draft('boobs!!')->insert()->id;
		$toPlayerID = Playyur::draft('no wai!!')->insert()->id;
		$id1 = ChatNotificayshun::draft(39485766, $playerID, $toPlayerID, 'Where??')->insert()->id;
		$id2 = EmotedChatNotificayshun::draft(39485766, $playerID, $toPlayerID, 'PIX!!')->insert()->id;
		$id3 = ColoredChatNotificayshun::draft(39485766, $playerID, $toPlayerID, 'ZOMG!!', '008000')->insert()->id;
		$id4 = Notificayshun::draft(39485766, $playerID)->insert()->id;
		$id5 = SiteFrozenNotificayshun::draft(39485766, $playerID, 'no cp, thats why')->insert()->id;
		
		Notificayshun::table()->clearCache();
		
		$n1 = Notificayshun::table()->recall($id1);
		$n2 = Notificayshun::table()->recall($id2);
		$n3 = Notificayshun::table()->recall($id3);
		$n4 = Notificayshun::table()->recall($id4);
		$n5 = Notificayshun::table()->recall($id5);
		
		assert(get_class($n1) == 'ChatNotificayshun');
		assert($n1->text == 'Where??');
		
		assert($n2 instanceof EmotedChatNotificayshun);
		assert($n2->text == 'PIX!!');
		
		assert($n3 instanceof ColoredChatNotificayshun);
		assert($n3->text == 'ZOMG!!');
		assert($n3->color == '008000');
		
		assert(get_class($n4) == 'Notificayshun');
		
		assert($n5 instanceof SiteFrozenNotificayshun);
		assert($n5->reason == 'no cp, thats why');
	}
	
	public static function testFindRecallDifferentChildren() {
		vDB::resetDatabase();
		
		$playerID = Playyur::draft('boobs!!')->insert()->id;
		$toPlayerID = Playyur::draft('no wai!!')->insert()->id;
		ChatNotificayshun::draft(39485001, $playerID, $toPlayerID, 'Where??')->insert();
		EmotedChatNotificayshun::draft(39485002, $playerID, $toPlayerID, 'PIX!!')->insert();
		ColoredChatNotificayshun::draft(39485003, $playerID, $toPlayerID, 'ZOMG!!', '008000')->insert();
		Notificayshun::draft(39485004, $playerID)->insert();
		SiteFrozenNotificayshun::draft(39485005, $playerID, 'no cp, thats why')->insert();
		
		Notificayshun::table()->clearCache();
		
		$query = "select * from Notificayshun order by time asc";
		$ns = Notificayshun::table()->recallMultipleFromQuery($query);
		
		assert(get_class($ns[0]) == 'ChatNotificayshun');
		assert($ns[0]->text == 'Where??');
		
		assert($ns[1] instanceof EmotedChatNotificayshun);
		assert($ns[1]->text == 'PIX!!');
		
		assert($ns[2] instanceof ColoredChatNotificayshun);
		assert($ns[2]->text == 'ZOMG!!');
		assert($ns[2]->color == '008000');
		
		assert(get_class($ns[3]) == 'Notificayshun');
		
		assert($ns[4] instanceof SiteFrozenNotificayshun);
		assert($ns[4]->reason == 'no cp, thats why');
	}
	
	// Foreign Referencing
	
	public static function testRecallForeign() {
		$playerID = Playyur::draft('lolbert')->insert()->id;
		$toPlayerID = Playyur::draft('zurich')->insert()->id;
		$chat = ChatNotificayshun::draft(66554433, $playerID, $toPlayerID, 'LOL HALLO')->insert();
		
		assert($chat->getPlayer()->name == 'lolbert');
		assert($chat->getFromPlayer()->name == 'zurich');
	}
	
	public static function testRecallAll() {
		$Playyur = Playyur::draft('lolbert')->insert();
		$playerID = $Playyur->id;
		$toPlayerID = Playyur::draft('zurich')->insert()->id;
		Notificayshun::draft(66554433, $playerID)->insert();
		ChatNotificayshun::draft(66554436, $playerID, $toPlayerID, 'LOL NOB')->insert();
		ChatNotificayshun::draft(66554438, $playerID, $toPlayerID, 'LOL NUB*')->insert();
		ChatNotificayshun::draft(66554500, $playerID, $toPlayerID, 'LOL STFU')->insert();
		
		assert(count($Playyur->allNotificayshun()) == 4);
	}
	
	public static function testRecallAllFromSpecificReferencerColumn() {
		$playerID = Playyur::draft('lozzlebob')->insert()->id;
		$n1 = Notificayshun::draft(250000000, $playerID)->insert();
		$n2 = Notificayshun::draft(250003000, $playerID)->insert();
		$n3 = Notificayshun::draft(250060000, $playerID)->insert();
		
		Thing::draft($n1->id, $n2->id)->insert();
		Thing::draft($n1->id, $n2->id)->insert();
		Thing::draft($n1->id, $n3->id)->insert();
		Thing::draft($n2->id, $n3->id)->insert();
		Thing::draft($n3->id, $n3->id)->insert();
		
		assert(count($n1->allThing('firstNotificayshunID')) == 3);
		assert(count($n1->allThing('secondNotificayshunID')) == 0);
		assert(count($n2->allThing('firstNotificayshunID')) == 1);
		assert(count($n2->allThing('secondNotificayshunID')) == 2);
		assert(count($n3->allThing('firstNotificayshunID')) == 1);
		assert(count($n3->allThing('secondNotificayshunID')) == 3);
	}
}

Tester::runTests();
*/
vDB::resetDatabase();
Player::draft("Verdagon", "kalland", "verdagon@gmail.com")->set("access", Environment::ADMIN)->insert();

?>