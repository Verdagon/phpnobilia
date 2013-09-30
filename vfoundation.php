<?php

class Description {
	protected $object;
	protected $stringSendable;
	protected $quotesStartLevel;
	
	public $visited = array();
	
	public static function multipleSeparateDescriptions($array) {
		$messages = array();
		foreach ($array as $arg) 
			$messages[] = self::of($arg, false, 1);
		return implode(" ", $messages);
	}
	
	public static function of($object, $stringSendable = 20, $quotesStartLevel = 0) {
		$describer = new Description($object, $stringSendable, $quotesStartLevel);
		return $describer->describe();
	}
	
	protected function __construct($object, $stringSendable, $quotesStartLevel) {
		foreach (array("object", "quotesStartLevel", "stringSendable", /*"threshold"*/) as $i)
			$this->$i = $$i;
	}
	
	public function describe() {
		return $this->describeData($this->object, '$root', 0);
	}
	
	public function describeData($data, $path, $level) {
		if (is_object($data) || is_array($data)) {
		
			if (in_array($data, $this->visited, true))
				if (!is_array($data) || count($data) > 0)
					return $this->fromVisited($data, $path);
			
			$this->visited[$path] = $data;
			
			if (is_object($data)) {
				if ($data instanceof Exception)
					return $this->fromException($data, $path, $level);
				if ($data instanceof vobject)
					return $this->fromVObject($data, $path, $level);
				return $this->fromObject($data, $path, $level);
			}
			
			if (is_array($data)) {
				return $this->fromArray($data, $path, $level);
			}
		}
		
		if (is_resource($data)) {
			return $this->fromResource($data, $path, $level);
		}
		
		if (is_string($data)) {
			return $this->fromString($data, $path, $level);
		}
		
		if ($data === null) {
			return "null";
		}
		
		if (is_integer($data) || is_float($data)) {
			return $data;
		}
		
		if (is_bool($data)) {
			return ($data ? "true" : "false");
		}
		
		throw new Exception("Unknown data type: " . gettype($data));
	}
	
	protected function fromVisited($data, $path) {
		$previousPath = array_search($data, $this->visited, true);
		
		$type = get_class($data);
		if (is_array($data))
			$type = "Array";
			
		return '(' . $type . ': ' . $previousPath . ')';
	}
	
	private static function getPriorityFields($data) {
		$class = new VReflectionClass($data);
		if ($priorityFields = $class->getNewestConstant('descriptionPriority')) {
			$priorityFields = explode(" ", $priorityFields);
			return $priorityFields;
		}
		return array();
	}
	
	private static function prioritizeCollection($collection, $priorityFields) {
		$prioritizedCollection = array();
		for ($i = 0; $i < count($priorityFields); $i++) {
			$field = $priorityFields[$i];
			assert(array_key_exists($field, $collection));
			$prioritizedCollection[$field] = $collection[$field];
		}
		
		foreach ($collection as $key => $value)
			if (!array_key_exists($key, $prioritizedCollection))
				$prioritizedCollection[$key] = $value;
		
		return $prioritizedCollection;
	}
	
	protected function fromVObject($vobject, $path, $level) {
		$collection = $vobject->declassify();
		if ($priorityFields = self::getPriorityFields($vobject))
			$collection = self::prioritizeCollection($collection, $priorityFields);
		
		return $this->fromCollection($collection, $path, get_class($vobject), $level);
	}
	
	protected function fromException($exception, $path, $level) {
		$collection = array(
			"message" => $exception->getMessage(),
			"stacktrace" => $exception->getTrace());
		return $this->fromCollection($collection, $path, get_class($exception), $level);
	}
	
	protected function fromObject($object, $path, $level) {
		$collection = $object;
		if ($priorityFields = self::getPriorityFields($object))
			$collection = self::prioritizeCollection($collection, $priorityFields);
		
		return $this->fromCollection($collection, $path, get_class($object), $level);
	}
	
	protected function fromArray($array, $path, $level) {
		return $this->fromCollection($array, $path, null, $level);
	}
	
	protected function fromCollection($objectCollection, $path, $objectType, $level) {
		$useIndented = false;
		$lineString = '[';
		$indentedString = '[';
		
		$isObject = ($objectType !== null);
		
		if ($isObject) {
			$lineString .= $objectType . ' ';
			$indentedString .= $objectType . ' ';
		}
		
		$keyedValuesForLineString = array();
		$keyedValuesForIndentedString = array();
		
		foreach ($objectCollection as $key => $value) {
			if ($isObject)
				$newPath = $path . '->' . $key;
			else
				$newPath = $path . '[' . $this->describeData($key, $path, $level) . ']';
			
			$useIndented = $useIndented || is_object($value) || (is_array($value) && count($value > 1));
			
			$value = $this->describeData($value, $newPath, $level + 1);
			
			if (!$useIndented)
				$keyedValuesForLineString[] = $key . '=' . $value;
			
			//$keyedValuesForIndentedString[] = "\n" . str_repeat('   ', $level + 1) . $key . '=' . $value;
			$keyedValuesForIndentedString[] =
				str_replace(
					"\n",
					("\n" . str_repeat('   ', $level + 1)),
					"\n" . $key . '=' . $value);
			
			// if any children are indented, then indent this
			$useIndented = $useIndented || strpos($value, "\n") !== false;
		}
		
		$lineString .= implode(' ', $keyedValuesForLineString);
		$indentedString .= implode($keyedValuesForIndentedString);
		
		$useIndented = $useIndented || strlen($lineString) > 100;
		
		return ($useIndented ? $indentedString : $lineString) . ']';
	}
	
	protected function fromString($string, $path, $level) {
		if ($this->stringSendable && strlen($string) > $this->stringSendable)
			$string = substr($string, 0, $this->stringSendable - 2) . "..";
		
		if ($level >= $this->quotesStartLevel)
			$string = addslashes($string);
		
		if ($this->stringSendable !== false) {
			$string = str_replace("\r\n", "\\n", $string);
			$string = str_replace("\n", "\\n", $string);
			$string = str_replace("\r", "\\n", $string);
		}
		
		if ($level >= $this->quotesStartLevel)
			$string = '"' . $string . '"';
		
		return $string;
	}
	
	protected function fromResource($resource, $path, $level) {
		$collection = array();
		
		if (mysql_num_rows($resource)) {
			mysql_data_seek($resource, 0);
			while ($row = mysql_fetch_assoc($resource))
				$collection[] = $row;
			mysql_data_seek($resource, 0);
		}
		
		return $this->fromCollection($collection, $path, null, $level);
	}
	
	// We put this in Description because if it was in vobject, it would give us
	// access to the protected members too. We don't want that.
	public static function sendableOf($data, $extraSendable = null) {
		if (is_array($data) || is_object($data)) {
			$sendable = array();
			
			foreach ($data as $key => $value) {
				if ($value instanceof vobject)
					$sendable[$key] = $value->sendable();
				else
					$sendable[$key] = self::sendableOf($value);
			}
			
			if (isset($extraSendable)) {
				assert(is_array($extraSendable));
				
				// If 0 is set, that means it's a numeric array, but we summarize
				// objects as purely associative arrays.
				assert(!isset($extraSendable[0]));
				
				$sendable = array_merge($sendable, $extraSendable);
			}
			
			$sendable['phpClassName'] = get_class($data);
			
			return $sendable;
		}
		
		return $data;
	}
}

abstract class vobject {
	// This function is only for use by vfoundation (really, only the Description
	// class), it gives us access to any protected members.
	public function declassify() {
		$contents = array();
		foreach ($this as $key => $value)
			$contents[$key] = $value;
		return $contents;
	}
	
	public function sendable() {
		return Description::sendableOf($this);
	}
	
	public function description($quotes = false) {
		return Description::of($this);
	}
}

class VReflectionClass extends ReflectionClass {
	public function getNewestConstant($constantName) {
		// constants are inherited, so if class B inherits from base class A,
		// and constant A::myconst = 3, while B has no constant myconst,
		// B::myconst will return 3. How do we determine if B has a constant of
		// its own? By comparing B::myconst to A::myconst. If theyre the same,
		// then B hasn't redefined myconst, and we know it really comes from A.
		$constantValue = $this->getConstant($constantName);
		if (!isset($this->parent))
			return $constantValue;
		if (!$this->parent->hasConstant($constantName))
			return $constantValue;
		if ($this->parent->getConstant($constantName) != $constantValue)
			return $constantValue;
		return null;
	}
}

class Logger {
	protected static $local = array();
	
	private static $callDepth = false;
	
	public static function getLoggerPosition() {
		return count(self::$local);
	}
	
	public static function getLogsSince($logPosition, $asString = false) {
		$result = array();
		for ($i = $logPosition; $i < count(self::$local); $i++)
			$result[] = self::$local[$i];
		return ($asString ? implode("\n", $result) : $result);
	}
	
	private static $timestampPrinted = false;
	
	public static function log() {
		if (self::$callDepth > 10)
			throw new Exception("Fatal error: recursive log call!");
		
		self::$callDepth++;
		$log = Description::multipleSeparateDescriptions(func_get_args());
		self::$local[] = $log;
		
		if (self::$timestampPrinted == false) {
			error_log(date("[d-M-Y h:m:s]\n"), 3, ROOT . "/complete_logs.log");
			self::$timestampPrinted = true;
		}
		
		error_log($log . "\n", 3, ROOT . "/complete_logs.log");
		
		$prefixLength = strlen(date("[d-M-Y h:m:s] "));
		$log = str_replace("\n", "\n" . str_repeat(" ", $prefixLength), $log);
		error_log($log);
		
		self::$callDepth--;
		return $log;
	}
	
	public static function logUnimportant() {
		if (self::$callDepth > 10)
			throw new Exception("Fatal error: recursive log call!");
		
		self::$callDepth++;
		$log = Description::multipleSeparateDescriptions(func_get_args());
		self::$local[] = $log;
		
		if (self::$timestampPrinted == false) {
			error_log(date("[d-M-Y h:m:s]\n"), 3, ROOT . "/complete_logs.log");
			self::$timestampPrinted = true;
		}
		
		error_log($log . "\n", 3, ROOT . "/complete_logs.log");
		
		self::$callDepth--;
		return $log;
	}
	
	public static function display() {
		if (self::$callDepth > 10)
			throw new vexception("Fatal error: recursive log call!");
		self::$callDepth++;
		$log = Description::multipleSeparateDescriptions(func_get_args());
		$log = nl2br($log);
		echo '<div style="border: 1px solid #C0C0C0; background-color: #FFFFFF; color: #000000; margin: .5em; padding: .25em;">' . $log . "</div>";
		self::$callDepth--;
		return $log;
	}
	
	// Called logBlast because we're blasting into every possible outlet
	public static function logBlast() {
		$log = Description::multipleSeparateDescriptions(func_get_args());
		self::log($log);
		self::display($log);
		return $log;
	}
}

function formatBacktrace($backtrace, $withArgs = false, $oneLine = false) {
	$lines = array();
	for ($i = 0; $i < count($backtrace); $i++) {
		$line = "";
		$frame = $backtrace[$i];
		
		$frameFile = (isset($frame["file"]) ? basename($frame["file"]) : "?");
		$frameLine = (isset($frame["line"]) ? $frame["line"] : "?");
		$line .= "   Frame " . $i . ": " . $frameFile . ":" . $frameLine . ": " . $frame["function"] . "(";
		
		if ($withArgs) {
			for ($j = 0; $j < count($frame["args"]); $j++) {
				if ($j > 0)
					$line .= ", ";
				$line .= vobject::makeString($frame["args"][$j], true, 50);
			}
		}
		
		$line .= ")";
		$lines[] = $line;
	}
	
	if ($oneLine)
		return implode("   /", $lines);
	else
		return "\n" . implode("\n", $lines);
}



class vexception extends Exception {
	public $httpcode;
	
	public static function from(Exception $parent, $defaultHTTPCode, $message = null) {
		$httpcode = $defaultHTTPCode;
		if ($parent instanceof vexception)
			$httpcode = $defaultHTTPCode;
		
		if (get_class($parent) == "Exception")
			self::logException($parent);
		
		if (isset($message))
			$message .= "\n(Because:)\n" . $parent->getMessage();
		else
			$message = $parent->getMessage();
		
		return new vexception($httpcode, $message);
	}
	
	private static function logException(Exception $e) {
		$backtrace = debug_backtrace();
		array_shift($backtrace);
		array_shift($backtrace);
		Logger::log("Exception:", $e->getMessage(), formatBacktrace($backtrace));
	}
	
	public function __construct() {
		$httpcode = 500;
		$message = null;
		
		if (func_num_args() == 1) {
			$message = func_get_arg(0);
		}
		else if (func_num_args() == 2) {
			$httpcode = func_get_arg(0);
			$message = func_get_arg(1);
		}
		else
			throw new vexception("vexception takes at most 2 arguments.");
		
		assert(is_int($httpcode) && $httpcode);
		$this->httpcode = $httpcode;
		
		parent::__construct($message);
		
		self::logException($this);
	}
	
	public static function throwNew($message) {
		throw new vexception($message);
	}
}



function build($string) {
	for ($i = 1; $i < func_num_args(); $i++) {
		$arg = func_get_arg($i);
		$string = str_replace('{$' . $i . '}', $arg, $string);
	}
	
	return $string;
}

function buildFromArray($string, $replacements) {
	for ($i = 0; $i < count($replacements); $i++)
		$string = str_replace('{$' . ($i + 1) . '}', $replacements[$i], $string);
	return $string;
}

if (!function_exists('lcfirst')) {
	function lcfirst($str) {
		return (string)(strtolower(substr($str,0,1)).substr($str,1));
	}
}



abstract class Insertable extends vobject {
	public abstract function insertBefore(InsertionPoint $insertionPoint);
	public abstract function insertAfter(InsertionPoint $insertionPoint);
}

abstract class Template extends Insertable {
	protected $mPrevious = null;
	public function previous() { return $this->mPrevious; }
	
	protected $mNext = null;
	public function next() { return $this->mNext; }
	
	const descriptionPriority = 'mNext mPrevious';
	
	public final function insertAfter(InsertionPoint $insertionPoint) {
		$insertionPoint->takeAfter($this);
		return $this;
	}
	
	public final function insertBefore(InsertionPoint $insertionPoint) {
		$insertionPoint->takeBefore($this);
		return $this;
	}
}

class StringTemplate extends Template {
	protected $string;
	
	const descriptionPriority = 'string mNext mPrevious';
	
	public static function create($string) {
		$replacements = func_get_args();
		array_shift($replacements);
		return new StringTemplate($string, $replacements);
	}
	
	private function __construct($string, $replacements) {
		$this->string = buildFromArray($string, $replacements);
	}
	
	public function output() {
		echo $this->string;
	}
}

class InsertionPoint extends Template {
	protected $canTakeBefore, $canTakeAfter;
	
	public static function create($canTakeBefore, $canTakeAfter)
	 { return new InsertionPoint($canTakeBefore, $canTakeAfter); }
	public function __construct($canTakeBefore, $canTakeAfter) {
		$this->canTakeBefore = $canTakeBefore;
		$this->canTakeAfter = $canTakeAfter;
	}
	
	public final function takeAfter(Template $template) {
		if ($this->mNext)
			$this->mNext->mPrevious = $template;
		
		$template->mNext = $this->mNext;
		$this->mNext = $template;
		$template->mPrevious = $this;
	}
	
	public final function takeBefore(Template $template) {
		if ($this->mPrevious)
			$this->mPrevious->mNext = $template;
		
		$template->mPrevious = $this->mPrevious;
		$this->mPrevious = $template;
		$template->mNext = $this;
	}
}

class TemplateList extends Insertable {
	protected $begin, $end;
	
	protected $mInsertion;
	public function insertion() { return $this->mInsertion; }
	
	public static function create() { return new TemplateList(); }
	public function __construct() {
		$this->begin = new InsertionPoint(false, true);
		
		$this->end = new InsertionPoint(true, false);
		$this->end->insertAfter($this->begin);
		
		$this->mInsertion = new InsertionPoint(true, true);
		$this->mInsertion->insertAfter($this->begin);
	}
	
	public function insertBefore(InsertionPoint $insertionPoint) {
		while ($this->begin != null) {
			$next = $this->begin->next();
			$this->begin->insertBefore($insertionPoint);
			$this->begin = $next;
		}
		return $this;
	}
	
	public function insertAfter(InsertionPoint $insertionPoint) {
		while ($this->end != null) {
			$previous = $this->end->previous();
			$this->end->insertAfter($insertionPoint);
			$this->end = $previous;
		}
		return $this;
	}
	
	public function insertAround(InsertionPoint $insertionPoint) {
		while ($this->begin != $this->mInsertion) {
			$next = $this->begin->next();
			$this->begin->insertBefore($insertionPoint);
			$this->begin = $next;
		}
		
		while ($this->end != $this->mInsertion) {
			$previous = $this->end->previous();
			$this->end->insertAfter($insertionPoint);
			$this->end = $previous;
		}
		
		$this->mInsertion = $insertionPoint;
		return $this;
	}
	
	public function printToInsertion() {
		return $this->printTo($this->insertion());
	}
	
	public function start()
	 { return $this->printToInsertion(); }
	
	public function printTo(InsertionPoint $stop) {
		while ($this->begin != $stop) {
			if ($this->begin instanceof StringTemplate)
				$this->begin->output();
			$this->begin = $this->begin->next();
		}
		return $this;
	}
	
	public function printToEnd() {
		$this->printTo($this->end);
		return $this;
	}
	
	public function finish()
	 { return $this->printToEnd(); }
}

class TagTemplate extends TemplateList {
	protected $attributes;
	protected $classes, $addedClasses;
	protected $styles, $addedStyles;
	
	public static function create($tag, $id = null, $class = null, $style = null)
	 { return new TagTemplate($tag, $id, $class, $style); }
	public function __construct($tag, $id = null, $class = null, $style = null) {
		parent::__construct();
		
		StringTemplate::create('<{$1}', $tag)->insertBefore($this->insertion());
		$this->attributes = InsertionPoint::create(true, false)->insertBefore($this->insertion());
		$this->classes = InsertionPoint::create(true, true)->insertBefore($this->insertion());
		$this->styles = InsertionPoint::create(true, true)->insertBefore($this->insertion());
		StringTemplate::create('>')->insertBefore($this->insertion());
		
		StringTemplate::create('</{$1}>', $tag)->insertAfter($this->insertion());
		
		if ($id !== null)
			$this->addAttribute("id", $id);
		
		if ($class !== null)
			$this->addClass($class);
		
		if ($style !== null)
			$this->addStyle($style);
	}
	
	public function addAttribute($attribute, $value) {
		StringTemplate::create(' {$1}="{$2}"', $attribute, $value)->insertBefore($this->attributes);
	}
	
	public function addClass($class) {
		if (!$this->addedClasses) {
			StringTemplate::create(' class="')->insertBefore($this->classes);
			StringTemplate::create('"')->insertAfter($this->classes);
		}
		
		StringTemplate::create(' {$1}', $class)->insertBefore($this->classes);
		
		$this->addedClasses = true;
	}
	
	public function addStyle($style) {
		if (!$this->addedStyles) {
			StringTemplate::create(' style="')->insertBefore($this->styles);
			StringTemplate::create('"')->insertAfter($this->styles);
		}
		
		$style = trim($style);
		
		assert(trim($style, ";") != $style); // makes sure it ends in a semicolon
		
		StringTemplate::create(' ' . $style)->insertBefore($this->styles);
		
		$this->addedStyles = true;
	}
}

class FormTemplate extends TagTemplate {
	public static function create($action, $id = null, $class = null)
	 { return new self($action, $id); }
	public function __construct($action, $id = null) {
		parent::__construct("form", $id);
		$this->addAttribute("method", "post");
		$this->addAttribute("action", $action);
	}
}

abstract class BasePage extends TemplateList {
	protected $mHeadInsertion;
	public function headInsertion() { return $this->mHeadInsertion; }
	
	public function __construct() {
		parent::__construct();
		
		StringTemplate::create('
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
       ')->insertBefore($this->insertion());
		
		TagTemplate::create("html")->insertAround($this->insertion());
		
		$this->mHeadInsertion = TagTemplate::create("head")->insertBefore($this->insertion())->insertion();
		
		TagTemplate::create("body")->insertAround($this->insertion());
	}
	
	public function includeCSS($path) {
		StringTemplate::create('
			<link rel="stylesheet" href="{$1}">
		', $path)->insertBefore($this->headInsertion());
	}
	
	public function includeJS($path) {
		StringTemplate::create('
			<script type="text/javascript" src="{$1}"></script>
		', $path)->insertBefore($this->headInsertion());
	}
	
	public function defineROOTInJS() {
		StringTemplate::create('
			<script type="text/javascript">var ROOT = "{$1}";</script>
		', ROOT)->insertBefore($this->headInsertion());
	}
}

abstract class BaseEnvironment {
	private static $instance = null;
	public static function get() { return self::$instance; }
	
	protected function __construct($trace = true) {
		assert_options(ASSERT_CALLBACK, array('BaseEnvironment', 'assertFailed'));
		
		set_exception_handler(array("BaseEnvironment", "uncaughtException"));
		
		assert(defined("ROOT"));
		
		if (isset(self::$instance))
			trigger_error("Environment already created!", E_WARNING);
		else
			self::$instance = $this;
		
		session_start();
		
		date_default_timezone_set('America/Los_Angeles');
		
		header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
		header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Expires: Mon, 14 Oct 2002 05:00:00 GMT');
		header('Pragma: no-cache');
		
		if ($trace)
			$this->trace();
	}
	
	public static function uncaughtException(Exception $e) {
		if (!($e instanceof vexception))
			$e = new vexception($e);
		
		if (isset(self::$instance)) {
			self::$instance->handleError($e);
		}
		else {
			$header = $_SERVER["SERVER_PROTOCOL"] . " " . 500;
			
			try { ResponseStatusSet::draft($status)->insert(); }
			catch (Exception $e) { }
			
			header($header);
			
			die($e->getMessage());
		}
	}
	
	public static function assertFailed()
	 { throw new vexception("Assert failed."); }
	
	// Public because if we tell the constructor not to trace then change our mind,
	// we can trace after all. (See takeQuizWorker.php, when action is getRemainingTime
	// we dont want to track but when its markAnswer we do)
	public function trace() {
		$lastFrame = array_pop(debug_backtrace());
		Logger::log("Traced " . $_SERVER["REMOTE_ADDR"] . " at " . basename($lastFrame["file"]), "GET:", $_GET, "POST:", $_POST, "SESSION:", $_SESSION, "Browser:", $_SERVER["HTTP_USER_AGENT"]);
	}
	
	protected abstract function filteredUnsafeString($key, $value);
	
	public function __call($name, $arguments) {
		if (preg_match('/^(boolean|email|float|int|ip|url|unsafestring|simplestring|articulatestring)from(get|post|session)$/', strtolower($name), $matches) == 1) {
			$fieldType = $matches[1];
			
			switch ($matches[2]) {
			case "get": $superglobal = &$_GET; break;
			case "post": $superglobal = &$_POST; break;
			case "session": $superglobal = &$_SESSION; break;
			}
			
			$fieldName = $arguments[0];
			
			$infectionTracker = null;
			
			$hasDefaultValue = false;
			$defaultValue = null;
			
			for ($i = 1; $i < count($arguments); $i++) {
				$arg = $arguments[$i];
				if ($arg instanceof InfectionTracker) {
					$infectionTracker = $arg;
				}
				else {
					$hasDefaultValue = true;
					$defaultValue = $arg;
				}
			}
			
			return $this->filterFromRequest($fieldName, $superglobal, $fieldType,
				$hasDefaultValue, $defaultValue, $infectionTracker);
		}
		
		throw new vexception('Call to undefined function: ' . get_class($this) . '::' . $name . '().');
	}
	
	private function filterFromRequest($key, &$source, $type, $hasDefaultValue, $defaultValue, $infectionTracker) {
		$returnValues = array();
		
		if (!isset($source[$key])) {
			if (!$hasDefaultValue)
				throw new vexception("Missing argument '{$key}'");
			return $defaultValue;
		}
		
		if (isset($infectionTracker) && ($type == "simplestring" || $type == "articulateString"))
			throw new vexception("returnNumInvalids and returnFirstInfection are only supported for simpleString and unsafeString");
		
		$value = stripslashes($source[$key]);
		
		switch ($type) {
		case "email":
		case "ip":
		case "url":
			$throwInvalid = true;
		case "float":
		case "int":
			$filterName = strtoupper($type);
			if ($type == "float" || $type == "int")
				$filterName = "NUMBER_" . $filterName;
			$filterName = "FILTER_SANITIZE_" . $filterName;
			
			if (!defined($filterName))
				throw new vexception("No filter for " . $filterName);
			
			$filter = constant($filterName);
			if (filter_var($value, $filter) === false) {
				$this->filteredUnsafeString($key, $value);
				$value = filter_var($value, $filter);
			}
			
			switch ($type) {
			case "boolean": $value = (boolean)$value;
			case "float": $value = (float)$value;
			case "int": $value = (int)$value;
			}
			
			return $value;
			
		case "boolean":
			switch (trim($value)) {
			case "on":
			case "yes":
			case "checked":
			case "selected":
			case "true":
				return true;
			}
			
			if (is_numeric($value) && $value)
				return true;
			
			return false;
			
		case "unsafestring":
			return $value;
		
		case "simplestring":
		case "articulatestring":
			static $articulate = '\' \\r\\n\\t\\!"\\#\\$\\%\\&\\(\\)\\*\\+\\,\\>\\-\\=\\.\\/\\;\\:\\?@\\[\\]_~0-9A-Za-z';
			static $simple = '0-9A-Za-z ';
			
			$allowed = ($type == "simpleString" ? $simple : $articulate);
			
			$numMatches = preg_match("/[^{$allowed}]/", $value, $matches, PREG_OFFSET_CAPTURE);
			
			if ($numMatches) {
				$this->filteredUnsafeString($key, $value);
				if (isset($infectionTracker)) {
					$infectionTracker->numInvalidChars = count($matches);
					$infectionTracker->firstInvalidPosition = $matches[0][1];
					$infectionTracker->firstInvalidChar = $matches[0][0];
					Logger::log($infectionTracker);
				}
				
				$value = preg_replace("/[^{$allowed}]/", "", $value);
			}
			
			return $value;
		}
	}
}

class InfectionTracker {
	public $numInvalidChars, $firstInvalidPosition, $firstInvalidChar;
	public static function create() { return new self; }
	private function __construct() { }
}

?>