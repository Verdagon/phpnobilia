<?php
require_once(ROOT . "/data/db.php");

function advance(&$arr) {
	$val = current($arr);
	next($arr);
	return $val;
}

abstract class Activity extends vDBData {
	public static function table() { return vDBClass::get(__CLASS__); }
	
	public static function find($max, $reverse, $types, $begin, $end, $ip, $name) {
		$order = ($reverse ? "desc" : "asc");
		
		$typesClause = "className in ('" . implode("', '", $types) . "')";
		
		$ipClause = (empty($ip) ? "" : "and ip='{$ip}'");
		
		$nameClause = (empty($name) ? "" : "and playerName='{$name}'");
		
		$query = "
			select * from Activity
			where {$typesClause} {$ipClause} {$nameClause}
			and time >= {$begin}
			and time <= {$end}
			order by time {$order}, id {$order}";
			
		if ($max)
			$query .= " limit " . $max;
		
		return self::table()->recallMultipleFromQuery(vDB::build($query));
	}
	
	public static function allByTime() {
		return self::table()->recallMultipleFromQuery(vDB::build('
			select * from
				(select * from Activity order by time desc limit 10) as q
			order by time asc
		'));
	}
	
	// we need this new draft to handle all these defaulted values.
	public static function draft($args, $className) {
		$activity = new $className();
		$activity->fill($args);
		return $activity;
	}
	
	protected function fill(&$args) {
		$this->time = time();
		$this->ip = $_SERVER["REMOTE_ADDR"];
		$this->userAgent = $_SERVER["HTTP_USER_AGENT"];
		
		$this->playerName = null;
		if (Environment::get()->player !== null)
			$this->playerName = Environment::get()->player->name;
		
		$this->backtrace = formatBacktrace(debug_backtrace());
	}
	
	const children = '
		LoggedIn,
		LoggedOut,
		RequestedPage,
		Error,
		Warning,
		DatabaseQueried';
	
	const columns = '
		time int,
		ip varchar(63),
		userAgent text,
		playerName text,
		backtrace text';
	
	public $time, $ip, $userAgent, $playerName, $backtrace;
	
	public function sendable() {
		$extra = array();
		$extra["date"] = date("G:i:s M j", $this->time);
		if (!empty($this->playerID))
			$extra["player"] = Player::table()->recall($this->playerID)->sendable();
		return Description::sendableOf($this, $extra);
	}
	
	public function insert($canTrack = true) {
		return parent::insert(false); // we never want to track activity inserts, theyre too damn annoying
	}
}

class LoggedIn extends Activity {
	public static function draft() { return parent::draft(func_get_args(), __CLASS__); }
}

class LoggedOut extends Activity {
	public static function draft() { return parent::draft(func_get_args(), __CLASS__); }
}

class RequestedPage extends Activity {
	public static function draft() { return parent::draft(func_get_args(), __CLASS__); }
	
	const columns = '
		pageName text,
		requestGET text,
		requestPOST text,
		userSession text';
	
	public $pageName, $requestGET, $requestPOST, $userSession;
	
	public function fill(&$args) {
		parent::fill($args);
		
		$this->pageName = $_SERVER["PHP_SELF"];
		
		$this->requestGET = Description::of($_GET);
		$this->requestPOST = Description::of($_POST);
		$this->userSession = Description::of($_SESSION);
	}
}

abstract class DatabaseQueried extends Activity {
	const children = '
		CustomQueried,
		SelectQueried,
		InsertQueried,
		AffectQueried';
	
	const columns = 'query text';
	public $query;
	
	public function fill(&$args) {
		parent::fill($args);
		$this->query = trim(advance($args));
	}
}

class CustomQueried extends DatabaseQueried {
	public static function draft() { return parent::draft(func_get_args(), __CLASS__); }
}

class SelectQueried extends DatabaseQueried {
	public static function draft() { return parent::draft(func_get_args(), __CLASS__); }
	
	// in this case its the number of result rows
	const columns = '
		rows int default -1,
		resultID int default -1';
	public $rows, $resultID;
	
	// There's no fill because we always manually set and update rows and resultID.
}

class InsertQueried extends DatabaseQueried {
	public static function draft() { return parent::draft(func_get_args(), __CLASS__); }
	
	const columns = 'insertID int';
	public $insertID;
	
	public function fill(&$args) {
		parent::fill($args);
		$this->insertID = advance($args);
	}
}

class AffectQueried extends DatabaseQueried {
	public static function draft() { return parent::draft(func_get_args(), __CLASS__); }
	
	const columns = 'rows int'; // in this case its the number of affected rows
	public $rows;
	
	public function fill(&$args) {
		parent::fill($args);
		$this->rows = advance($args);
	}
}

class Warning extends Activity { // such as when we strip out unsafe characters
	public static function draft() { return parent::draft(func_get_args(), __CLASS__); }
	
	const columns = 'text text';
	public $text;
	
	public function fill(&$args) {
		parent::fill($args);
		$this->text = advance($args);
	}
}

abstract class Error extends Activity {
	public static function findNumUnread() {
		return self::table()->count(vDB::build("
			where className in ('ReportableError', 'SubmissionInconsistency')
			and beenRead=0"
		));
	}
	
	const children = '
		ReportableError';
	
	const columns = '
		message text,
		beenRead int,
		errorBacktrace text';
	public $message, $beenRead, $errorBacktrace;
	
	public function fill(&$args) {
		parent::fill($args);
		$this->message = advance($args);
		$this->beenRead = 0;
		$this->errorBacktrace = advance($args);
	}
}

class ReportableError extends Error {
	public static function draft() { return parent::draft(func_get_args(), __CLASS__); }
	
	const columns = "report text default ''";
	public $report;
}

?>
