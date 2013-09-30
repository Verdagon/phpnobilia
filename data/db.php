<?php
require_once(ROOT . "/vfoundation.php");

// Verdagon's Database Abstraction Layer
// Copyright (C) Evan Ovadia 2009

define("TRACK", true);

class NoResultException extends vexception { }

class vDBQuery extends vobject {
	public $query;
	public function __construct($query) { $this->query = $query; }
}

abstract class vDB {
	const mysqlusername = 'verdagon_nobilia'; // 'root' or 'verdagon_nobilia'
	const mysqlpassword = 'carrot7'; // 'root' or 'carrot7'
	const mysqldatabase = 'verdagon_nobilia';
	
	private static $inconsequential = 0;
	
	public static function escape($thing) {
		return mysql_real_escape_string($thing, self::connection());
	}
	
	private static function connection() {
		static $connection = null;
		if ($connection == null) {
			$connection = mysql_connect("localhost", self::mysqlusername, self::mysqlpassword);
			assert($connection !== false);
			mysql_select_db(self::mysqldatabase);
		}
		return $connection;
	}
	
	public static function customQuery(vDBQuery $query, $canTrack = true) {
		if (TRACK) {
			self::$inconsequential++;
			$activity = CustomQueried::draft($query->query);
			if ($canTrack)
				$activity->insert(false);
			self::$inconsequential--;
		}
		
		$result = vDB::query($query);
		if ($result === false)
			throw new vexception("Query '" . $query->query . "' produced exception: '" . mysql_error() . "'");
		
		if (TRACK) {
			$log = preg_replace("/\s(\s\s\s|\t\t)+\s/", " ", trim($query->query));
			
			if (self::$inconsequential)
				Logger::logUnimportant($log);
			else
				Logger::log($log);
			
			self::$inconsequential++;
			if ($canTrack)
				$activity->update(false);
			self::$inconsequential--;
		}
		
		return $result;
	}
	
	private static function query(vDBQuery $query) {
		return mysql_query($query->query, self::connection());
	}
	
	public static function select(vDBQuery $query, $canTrack) {
		assert(strtolower(strtok($query->query, " \r\n\t")) == "select")
		 or vexception::throwNew("Don't give non-select queries to select: " . $query->query);
		
		if (TRACK) {
			self::$inconsequential++;
			$activity = SelectQueried::draft($query->query);
			if ($canTrack)
				$activity->insert(false);
			self::$inconsequential--;
		}
		
		$result = vDB::query($query);
		if ($result === false)
			throw new vexception("Query '" . $query->query . "' produced exception: '" . mysql_error() . "'");
		
		if (TRACK) {
			$log = preg_replace("/\s(\s\s\s|\t\t)+\s/", " ", trim($query->query));
			
			$activity->rows = mysql_num_rows($result);
			$log .= " [rows: " . $activity->rows;
		
			if ($activity->rows == 1) {
				$firstRow = mysql_fetch_assoc($result);
				mysql_data_seek($result, 0);
				
				if (isset($firstRow["id"])) {
					$activity->resultID = $firstRow["id"];
					$log .= ", id " . $activity->resultID;
				}
			}
			
			$log .= "]";
		
			if (self::$inconsequential)
				Logger::logUnimportant($log);
			else
				Logger::log($log);
			
			self::$inconsequential++;
			if ($canTrack)
				$activity->update(false);
			self::$inconsequential--;
		}
		
		return $result;
	}
	
	public static function insert(vDBQuery $query, $canTrack, &$insertID = null) {
		assert(strtolower(strtok($query->query, " \r\n\t")) == "insert")
		 or vexception::throwNew("Don't give non-insert queries to insert: " . $query->query);
		
		if (TRACK) {
			self::$inconsequential++;
			$activity = InsertQueried::draft($query->query);
			if ($canTrack)
				$activity->insert(false);
			self::$inconsequential--;
		}
		
		$result = vDB::query($query);
		if ($result === false)
			throw new vexception("Query '" . $query->query . "' produced exception: '" . mysql_error() . "'");
		
		if (TRACK) {
			$log = preg_replace("/\s(\s\s\s|\t\t)+\s/", " ", trim($query->query));
		}
		
		$insertID = $activity->insertID = mysql_insert_id();
		
		if (TRACK) {
			$log .= " [id: " . $activity->insertID . "]";
			
			if (self::$inconsequential)
				Logger::logUnimportant($log);
			else
				Logger::log($log);
			
			self::$inconsequential++;
			if ($canTrack)
				$activity->update(false);
			self::$inconsequential--;
		}
		
		return $result;
	}
	
	public static function affect(vDBQuery $query, $canTrack, &$affectedRows = null) {
		assert(strtolower(strtok($query->query, " \r\n\t")) != "select")
		 or vexception::throwNew("Don't give select queries to affect: " . $query->query);
		assert(strtolower(strtok($query->query, " \r\n\t")) != "insert")
		 or vexception::throwNew("Don't give insert queries to affect: " . $query->query);
		
		if (TRACK) {
			self::$inconsequential++;
			$activity = AffectQueried::draft($query->query);
			if ($canTrack)
				$activity->insert(false);
			self::$inconsequential--;
		}
		
		$result = vDB::query($query);
		if ($result === false)
			throw new vexception("Query '" . $query->query . "' produced exception: '" . mysql_error() . "'");
		
		if (TRACK) {
			$log = preg_replace("/\s(\s\s\s|\t\t)+\s/", " ", trim($query->query));
		}
		
		$affectedRows = $activity->rows = mysql_affected_rows();
		
		if (TRACK) {
			$log .= " [affected: " . $activity->rows . "]";
			
			if (self::$inconsequential)
				Logger::logUnimportant($log);
			else
				Logger::log($log);
			
			self::$inconsequential++;
			if ($canTrack)
				$activity->update(false);
			self::$inconsequential--;
		}
		
		return $result;
	}
	
	public static function build($query) {
		$args = func_get_args();
		array_shift($args);
		return self::buildInner($query, $args);
	}
	
	public static function buildSequential($steps) {
		$lastStepName = null;
		$lastStep = null;
		
		foreach ($steps as $stepName => $step) {
			if (isset($lastStepName)) {
				if (strpos($step, '{$' . $lastStepName . '}') === false)
					throw new vexception('{$' . $lastStepName . '} not found in: ' . $step);
				$step = str_replace('{$' . $lastStepName . '}', " ({$lastStep}) as {$lastStepName} ", $step);
			}
			
			$lastStepName = $stepName;
			$lastStep = $step;
		}
		
		Logger::log("Result last step:", $lastStep);
		
		$args = func_get_args();
		array_shift($args);
		return self::buildInner($lastStep, $args);
	}
	
	private static function buildInner($query, $replacements) {
		$pattern = '/\'{\$\d+}\'/';
		assert(preg_match($pattern, $query) == 0);
		$values = array();
		for ($i = 0; $i < count($replacements); $i++) {
			$value = $replacements[$i];
			if (!isset($value))
				$value = 'null';
			else
				$value = "'" . vDB::escape($value) . "'";
			$values[] = $value;
		}
		return new vDBQuery(buildFromArray($query, $values));
	}
	
	public static function resetDatabase() {
		$dbDataClass = new ReflectionClass('vDBData');
		foreach (get_declared_classes() as $className) {
			$class = new ReflectionClass($className);
			if ($class->isSubclassOf($dbDataClass))
				vDBClass::get($className);
		}
		
		foreach (vDBTableClass::$tableClasses as $tableClass)
			$tableClass->resetTable();
	}
}

abstract class vDBColumn {
	public $class;
	
	public $ddl;
	public $name;
	
	public $referencedClass = null;
	
	public function __construct($class, $ddl, $name) {
		$this->class = $class;
		$this->ddl = $ddl;
		$this->name = $name;
	}
	
	public function referencesAnything() {
		return isset($this->referencedClass);
	}
	
	public function setReference($referencedClassName, $referencedKey) {
		$this->referencedClass = vDBClass::get($referencedClassName);
		
		// what else would we do with it? The database already operates under the
		// assumption that the ID is the primary key in any table.
		assert($referencedKey == "id");
	}
	
	public function followReference($value, $expectResult) {
		assert($this->referencesAnything());
		return $this->referencedClass->recall($value, $expectResult);
	}
	
	// Override
	public function setTypeDetails($details)
	 { throw new vexception('Unexpected type details: ' . $details); }
	
	public function cast($value)
	 { return $value; }
	
	abstract public function compatible($value);
	
}

class vDBNumber extends vDBColumn {
	public function compatible($value) {
		return is_null($value) || is_numeric($value) || is_bool($value);
	}
	
	public function cast($value) {
		if ((int)$value == $value)
			return (int)$value;
		assert(is_numeric($value) || is_null($value) || is_bool($value));
		return (float)$value;
	}
}

class vDBText extends vDBColumn {
	// what should we do with this? should we enforce?
	private $maxLength = null;
	
	public function setTypeDetails($maxLength)
	 { $this->maxLength = $maxLength; }
	
	public function compatible($value) {
		return is_null($value) || $value == (string)$value;
	}
}

class vDBClass extends VReflectionClass {
	private static $classCache = array();
	
	public static function get($className) {
		if (!isset(self::$classCache[$className])) {
			assert($className != 'vDBData');
			
			$class = new ReflectionClass($className);
			
			assert($class->isSubclassOf(new ReflectionClass('vDBData')));
			
			while ($class->getParentClass()->name != 'vDBData') {
				// the entire family is constructed at the same time. We are
				// creating the family right now; if one of our parent classes was
				// already created, then we've found a bug.
				assert(!isset(self::$classCache[$class->name]));
				
				$class = $class->getParentClass();
				
				assert($class != null);
			}
			
			new vDBTableClass($class->name);
		}
		
		return self::$classCache[$className];
	}
	
	public $table;
	public $parent;
	
	public $creator = null;
	public $columnsByIndex = array();
	
	public $gettersByName = array();
	public $settersByName = array();
	
	public $children = array();
	
	public function getAllDescendants() {
		$descendants = array($this);
		foreach ($this->children as $child)
			$descendants = array_merge($descendants, $child->getAllDescendants());
		return $descendants;
	}
	
	public function columnForName($name) {
		foreach ($this->columnsByIndex as $column)
			if ($column->name == $name)
				return $column;
		return null;
	}
	
	public function __construct($name, vDBClass $parent = null) {
		parent::__construct($name);
		
		$this->parent = $parent;
		
		// This needs to be before the columns. If theres a column that references
		// its own class (like historicUnitID or nextPlayerID), then it asks for
		// it, and since the cache hasn't been told of this, then we create an
		// endless loop.
		self::$classCache[$name] = $this;
		
		
		if (isset($parent)) {
			$this->columnsByIndex = $this->parent->columnsByIndex;
			$this->table = $this->parent->table;
		}
		else {
			$this->table = $this;
		}
		
		if ($creatorName = $this->getNewestConstant('creator')) {
			assert($this->hasMethod($creatorName));
			$this->creator = $this->getMethod($creatorName);
			assert($this->creator->isStatic());
			assert($this->creator->isPublic());
		}
		
		if ($newColumnsDDL = $this->getNewestConstant('columns')) {
			foreach ($this->createColumnsFromDDL($newColumnsDDL) as $column) {
				$this->columnsByIndex[] = $column;
				$this->table->familyColumnsByName[$column->name] = $column;
				
				if ($this->hasMethod($getterName = 'get' . ucfirst($column->name)))
					$this->gettersByName[$column->name] = $this->getMethod($getterName);
					
				if ($this->hasMethod($setterName = 'set' . ucfirst($column->name)))
					$this->settersByName[$column->name] = $this->getMethod($setterName);
			}
		}
		
		if ($children = $this->getNewestConstant('children')) {
			preg_match_all('/[\w\d]+/i', $children, $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$childClassName = $match[0];
				$this->children[] = new vDBClass($childClassName, $this);
			}
		}
	}
	
	private function createColumnsFromDDL($ddl) {
		$ddl = preg_replace('/\s+/', ' ', $ddl);
		
		$columns = array();
		$columnsDDLs = self::splitColumnsDDL($ddl);
		foreach ($columnsDDLs as $columnDDL)
			$columns[] = $this->createColumn($columnDDL);
			
		return $columns;
	}
	
	private static function splitColumnsDDL($ddl) {
		$columns = array();
		while (($position = self::findTopLevelComma($ddl)) !== false) {
			$column = substr($ddl, 0, $position);
			$ddl = substr($ddl, $position + 1);
			$columns[] = $column;
		}
		$columns[] = $ddl;
		return $columns;
	}
	
	private static function findTopLevelComma($ddl) {
		$position = 0;
		$ends = array();
		
		for ( ; $position < strlen($ddl); $position++) {
			$character = $ddl[$position];
			
			if ($character == '\\') {
				$position++;
				continue;
			}
			
			if (count($ends) == 0 && $character == ',')
				return $position;
			
			if (count($ends) && $character == end($ends)) {
				array_pop($ends);
				continue;
			}
			
			switch ($character) {
			case '(': array_push($ends, ')'); break;
			case '[': array_push($ends, '}'); break;
			case '{': array_push($ends, '}'); break;
			case '<': array_push($ends, '>'); break;
			
			case '`':
			case "'":
			case '"':
				array_push($ends, $character);
				break;
			}
		}
		
		return false;
	}
	
	private function createColumn($ddl) {
		$ddl = trim($ddl);
		$originalDDL = $ddl;
		
		if (preg_match('/^\s*`([^`]+)`/i', $ddl, $matches)) {
			$ddl = substr($ddl, strlen($matches[0]));
			$name = $matches[1];
		}
		else if (preg_match('/^\s*(\S+)/i', $ddl, $matches)) {
			$ddl = substr($ddl, strlen($matches[0]));
			$name = $matches[1];
		}
		else
			throw new vexception("Can't find name in column ddl: '{$originalDDL}'");
		
		if (!preg_match('/^\s*(\w+)(\s*\(\s*(\d+)\s*\))?/i', $ddl, $matches))
			throw new vexception("Can't find type in column ddl: '{$originalDDL}'");
		$ddl = substr($ddl, strlen($matches[0]));
		$type = $matches[1];
		
		switch ($type) {
		case 'int':
		case 'float':
			$column = new vDBNumber($this, $originalDDL, $name);
			break;
			
		case 'char':
		case 'varchar':
		case 'text':
		case 'enum':
			$column = new vDBText($this, $originalDDL, $name);
			break;
			
		default:
			throw new vexception("Unknown type '{$type}' for column '{$name}' in ddl '{$originalDDL}'");
		}
		
		if (!empty($matches[2]))
			$column->setTypeDetails($matches[3]);
		
		if (preg_match('/references ([^\s\(]+)\((\s*[^\s\)]+\s*)\)/i', $ddl, $matches))
			$column->setReference($matches[1], $matches[2]);
		
		return $column;
	}
	
	public function newInstance() {
		if (isset($this->creator))
			return $this->creator->invoke(null);
		return parent::newInstance();
	}
	
	public function draft($args) {
		$instance = $this->newInstance();
		
		for ($argIndex = 0; $argIndex < count($args); $argIndex++) {
			$columnName = $this->columnsByIndex[$argIndex]->name;
			$instance->setValueForColumn($columnName, $args[$argIndex]);
		}
		
		return $instance;
	}
	
	protected function populate(vDBData $data, $row) {
		$this->table->storedValues[$data->id] = array();
		$data->id = (int)$row['id'];
		foreach ($this->columnsByIndex as $column) {
			$value = $row[$column->name];
			$this->table->storedValues[$data->id][$column->name] = $column->cast($value);
			$data->setValueForColumn($column->name, $value);
		}
	}
}

class vDBTableClass extends vDBClass {
	public static $tableClasses = array();
	public $familyColumnsByName = array();
	
	public function __construct($name) {
		$this->table = $this;
		self::$tableClasses[$name] = $this;
		parent::__construct($name);
	}
	
	protected $storedValues = array();
	private $cache = array();
	
	public function resetTable() {
		vDB::affect(vDB::build('drop table if exists ' . $this->name), false);
		
		$columnsDDLs = array("\n   id int not null primary key auto_increment");
		
		if (!empty($this->children))
			$columnsDDLs[] = "\n   className varchar(63)";
		
		foreach ($this->familyColumnsByName as $col)
			$columnsDDLs[] = "\n   " . $col->ddl;
		
		$tableDDL = implode(',', $columnsDDLs);
		
		$query = vDB::build('create table ' . $this->name . ' ( ' . $tableDDL . ' );');
		vDB::affect($query, false);
		
		//echo nl2br('<pre>' . $query->query . '</pre>');
	}
	
	public function count(vDBQuery $whereClause) {
		if (strpos(trim(strtolower($whereClause->query)), "where") !== 0)
			vexception::throwNew("count() must be given a where clause starting with 'where'.");
		
		$query = vDB::build('select count(id) rowct from ' . $this->name . ' ' . $whereClause->query);
		$result = vDB::select($query, true);
		return (int)mysql_result($result, 0, "rowct");
	}
	
	public function exists($id) {
		return $this->count(vDB::build('where id={$1}', $id)) > 0;
	}
	
	public function clearCache() {
		$this->cache = array();
	}
	
	public function removeFromCache(vDBData $data) {
		unset($this->cache[$data->id]);
	}
	
	private function recallFromRowThenAddToCache($row) {
		assert(!empty($this->children) == !empty($row['className']));
		
		$rowClass = $this;
		
		if (!empty($row['className'])) {
			assert(!empty($this->children));
			$rowClass = vDBClass::get($row['className']);
		}
		
		$data = $rowClass->newInstance();
		
		$rowClass->populate($data, $row);
		
		return ($this->cache[$data->id] = $data);
	}
	
	
	// Recall from ID
	
	private function query($id, $expectResult, $canTrack) {
		// Recall from ID
		$query = vDB::build('select * from ' . $this->name . ' where id={$1} order by id asc', $id);
		$resource = vDB::select($query, $canTrack);
		
		if (mysql_num_rows($resource) == 0) {
			if ($expectResult)
				throw new NoResultException($this->name . " id " . $id . " doesn't exist!");
			else
				return null;
		}
		
		return $resource;
	}
	
	public function recall($id, $expectResult = true, $canTrack = true)
	 { return $this->recallFromCacheOrID($id, $expectResult, $canTrack); }
	
	private function recallFromCacheOrID($id, $expectResult = true, $canTrack) {
		if (isset($this->cache[$id]))
			return $this->cache[$id];
		
		$result = $this->query($id, $expectResult, $canTrack);
		
		if ($result === null) {
			// if result is null, it has to be because expectResult was false.
			assert($expectResult == false);
			return null;
		}
		
		$row = mysql_fetch_assoc($result);
		return $this->recallFromRowThenAddToCache($row);
	}
	
	public function recallReferencersOf($localIDName, $foreignID) {
		$query = vDB::build('
			select * from ' . $this->name . '
			where ' . $localIDName . '={$1} order by id asc
		', $foreignID);
		return $this->recallMultipleFromQuery($query);
	}
	
	
	
	
	
	// Recall from Query
	
	public function recallFromQuery($query) {
		if ($row = mysql_fetch_assoc(vDB::select($query, true)))
			return $this->recallFromCacheOrRow($row);
		throw new vexception($this->name . " not found.");
	}
	
	public function recallMultipleFromQuery(vDBQuery $query) {
		$resource = vDB::select($query, true);
		$instances = array();
		while ($row = mysql_fetch_assoc($resource))
			$instances[] = $this->recallFromCacheOrRow($row);
		return $instances;
	}
	
	public function recallPossibleFromQuery($query) {
		$array = $this->recallMultipleFromQuery($query);
		return count($array) ? $array[0] : null;
	}
	
	private function recallFromCacheOrRow($row) {
		$id = $row["id"];
		if (isset($this->cache[$id]))
			return $this->cache[$id];
		return $this->recallFromRowThenAddToCache($row);
	}
	
	public function all() {
		return $this->recallMultipleFromQuery(vDB::build('
			select * from ' . $this->name . ' order by id asc
		'));
	}
	
	
	
	// Insert
	
	public function calculateInsertQuery(vDBData $data) {
		$vDBClass = $data->getDBClass();
		
		$fields = array();
		$values = array();
		
		if (!empty($vDBClass->table->children)) {
			$fields[] = 'className';
			$values[] = "'" . $vDBClass->name . "'";
		}
		
		foreach ($vDBClass->columnsByIndex as $column) {
			$value = $data->getValueForColumn($column->name);
			
			if (isset($value)) {
				$fields[] = $column->name;
				$values[] = '\'' . vDB::escape($value) . '\'';
			}
		}
		
		$query = "insert into {$this->name} (";
		$query .= implode(", ", $fields);
		$query .= ") values (";
		$query .= implode(", ", $values);
		$query .= ");";
		
		return $query;
	}
	
	public function insert(vDBData $data, $canTrack) {
		assert($data->id == 0);
		
		$data->willInsert();
		
		vDB::insert(vDB::build($this->calculateInsertQuery($data)), $canTrack, $insertID);
		
		//return $this->recallFromRowThenAddToCache(mysql_fetch_assoc($this->query(mysql_insert_id(), true, $canTrack)));
		
		$rowInDB = mysql_fetch_assoc($this->query($insertID, true, $canTrack));
		$data->getDBClass()->populate($data, $rowInDB);
		
		return ($this->cache[$data->id] = $data);
	}
	
	public function update(vDBData $data, $canTrack) {
		assert($data->id > 0) or
			vexception::throwNew("Given data with no id yet.");
		
		$vDBClass = $data->getDBClass();
		
		$data->willUpdate();
		
		$updates = array();
		foreach ($vDBClass->columnsByIndex as $column) {
			if ($column->name == 'id')
				continue;
			$value = $data->getValueForColumn($column->name);
			
			//Logger::log($this->storedValues);
			// If the value hasn't changed...
			if ($value == $this->storedValues[$data->id][$column->name])
				continue;
			
			$this->storedValues[$data->id][$column->name] = $value;
			
			$value = vDB::escape($value);
			$updates[] =  "{$column->name}='{$value}'";
		}
		
		if (count($updates)) {
			$query = 'update ' . $this->name . ' set ';
			$query .= implode(", ", $updates);
			$query .= ' where id={$1}';
			$query = vDB::build($query, $data->id);
			vDB::affect($query, $canTrack);
		}
		
		return $data;
	}
	
	public function delete(vDBData $data, $canTrack) {
		assert($data->id);
		
		$data->willDelete();
		$query = vDB::build('delete from ' . $this->name . ' where id={$1}', $data->id);
		vDB::affect($query, $canTrack, $affectedRows);
		assert($affectedRows);
		$this->removeFromCache($data);
		$data->id = 0;
		return $data;
	}
	
	// We return a NEW piece of data, of the new class, with the same exact
	// attributes and connections as before. The old piece of data is stripped
	// of its ID and is made transient; it shouldn't even really be used ever
	// again. "In Place" means that it even has the same ID, and even using
	// the same row in the database as before.
	public function cloneInPlaceAsNewClass(vDBData $data, $newClassName) {
		assert($data->id);
		
		$this->removeFromCache($data);
		$query = vDB::build('update ' . $this->name . ' set className={$1} where id={$2}', $newClassName, $data->id);
		vDB::affect($query, true, $affectedRows);
		assert($affectedRows);
		$newData = $this->recall($data->id);
		$data->id = 0;
		return $newData;
	}
	
	
	// Foreign keys are columns in this table that point to another table
	public $foreignKeys = array();
	public function addReference($localIDName, vDBTable $foreignTable) {
		$this->foreignKeys[Foreign::table()->name] =
			new ForeignKey($localIDName, $foreignTable);
	}
	
	// Incoming keys are columns in other tables that point to this one.
	public $incomingKeys = array();
	public function addList(vDBTable $foreignTable, $foreignClassPlural, $foreignIDName) {
		$this->incomingKeys[$foreignClassPlural] =
			new IncomingKey($foreignIDName, $foreignTable);
	}
}

abstract class vDBData extends vobject {
	public function getDBClass()
	 { return vDBClass::get(get_class($this)); }
	public function getTable()
	 { return vDBClass::get(get_class($this))->table; }
	
	public $id;
	
	// Overrideable.
	public function willDelete() { }
	public function willInsert() { }
	public function willUpdate() { }
	
	public function insert($canTrack = true) { return $this->getTable()->insert($this, $canTrack); }
	public final function calculateInsertQuery() { return $this->getTable()->calculateInsertQuery($this); }
	public function update($canTrack = true) { return $this->getTable()->update($this, $canTrack); }
	public final function delete($canTrack = true) { return $this->getTable()->delete($this, $canTrack); }
	public final function cloneInPlaceAsNewClass($newClassName) { return $this->getTable()->cloneInPlaceAsNewClass($this, $newClassName); }
	
	public function set($key, $value)
	 { return $this->setValueForColumn($key, $value); }
	
	public function getValueForColumn($columnName) {
		$gettersByName = $this->getDBClass()->gettersByName;
		if (isset($gettersByName[$columnName]))
			return $gettersByName[$columnName]->invoke($this);
		$column = $this->getDBClass()->columnForName($columnName);
		if (!property_exists(get_class($this), $columnName))
			throw new vexception("Column {$columnName} not in " . $this->description());
		return $column->cast($this->$columnName);
	}
	
	public function setValueForColumn($columnName, $newValue) {
		$class = $this->getDBClass();
		
		$column = $class->columnForName($columnName);
		if (!$column->compatible($newValue))
			throw new vexception("Invalid value ('{$newValue}') for column ('{$columnName}')");
		$newValue = $column->cast($newValue);
		
		if (isset($class->settersByName[$columnName]))
			$class->settersByName[$columnName]->invoke($this, $newValue);
		else
			$this->$columnName = $newValue;
			
		return $this;
	}
	
	protected function __call($functionName, $arguments) {
		$errorString = "Tried to call nonexistant {$functionName} on " . get_class($this) . ".";
		
		if (strncmp("get", $functionName, 3) == 0) {
			$localIDFieldName = substr($functionName, 3);
			assert($localIDFieldName != "Table" && $localIDFieldName != "vDBClass");
			$localIDField = lcfirst($localIDFieldName) . "ID";
			
			if ($column = $this->getDBClass()->columnForName($localIDField)) {
				$expectResult = true;
				if (isset($arguments[0]))
					$expectResult = $arguments[0];
				
				return $column->followReference($this->getValueForColumn($localIDField), $expectResult);
			}
		}
		else if (strncmp("all", $functionName, 3) == 0) {
			$foreignClassName = substr($functionName, 3);
			$foreignClass = vDBClass::get($foreignClassName);
			$foreignTable = $foreignClass->table;
			
			$foreignColumn = null;
			foreach ($foreignTable->columnsByIndex as $column) {
				if ($column->referencesAnything()) {
					if ($column->referencedClass->table === $this->getTable()) {
						if (isset($foreignColumn))
							throw new vexception("Foreign class '{$foreignClass->name}' references this class '{$this->getDBClass()->name}' with multiple columns, so specify which one as an argument to the allX function.");
						$foreignColumn = $column;
					}
				}
			}
			
			if (!isset($foreignColumn))
				throw new vexception("Foreign class '{$foreignClass->name}' does not reference class '{$this->getDBClass()->name}'.");
			
			return $foreignTable->recallReferencersOf($foreignColumn->name, $this->id);
		}
		
		throw new vexception($errorString);
	}
}

if (TRACK) {
	require_once(ROOT . "/data/activity.php");
}

?>