<?php
namespace rss\orm;

use rss\exceptions\TableNotFound;
use rss\exceptions\ReadingFields;
use rss\utils\String;

class Schema {
	protected $cn;
	protected $tables;
	/*
	$tables = [
		'name' => [
			fields => ['id', 'name', ...],
			referencing => ['table1', 'table2', ...],
			referenced_by => ['table1', 'table2', ...]
		], 
		...
	]
	*/

	public function __construct(Connection $c){
		$this->cn = $c;
		$this->tables = [];
		$this->fillTables();
	}

	public function getTableNames(){
		return array_keys($this->tables);
	}

	public function getTableNameOfModel(Model $m){
		return $this->getTableNameOfClass(get_class($m));
	}
	
	public function getTableNameOfClass($className){
		$className = explode('\\', $className);
		$className = array_pop($className);
		$className = String::camlCaseToUnderscores($className);
		return $className;
	}

	public function getClassOfTable($table){
		$className = String::underscoresToCamlCase($table);
		$className = 'rss\models\\' . $className; // this should be generic !
		return $className;
	}

	public function getFieldsOf($table){
		if(! array_key_exists($table, $this->tables))
			throw new TableNotFound($table);
		return $this->tables[$table]['fields'];
	}
	
	public function getTablesReferencing($table){
		if(! array_key_exists($table, $this->tables))
			throw new TableNotFound($table);
		return $this->tables[$table]['referenced_by'];
	}

	public function getTablesReferencedBy($table){
		if(! array_key_exists($table, $this->tables))
			throw new TableNotFound($table);
		return $this->tables[$table]['referencing'];
	}

	protected function fillTables(){
		// Filling table names
		$stmnt = $this->cn->query('SHOW TABLES'); // Using MySQL
		foreach($stmnt as $table){
			$this->tables[$table[0]] = [];
			$this->tables[$table[0]]['fields'] = [];
			$this->tables[$table[0]]['referencing'] = [];
			$this->tables[$table[0]]['referenced_by'] = [];
		}
		// Filling fields and references
		// Table foo references table bar if the field bar_id exists in foo
		foreach ( $this->tables as $name => $table ){
			$stmnt = $this->cn->query('DESCRIBE ' . $name);
			if( ! $stmnt )
				throw new ReadingFields($name, $this->cn->getErrors());
			foreach ($stmnt as $row) {
				$field = $row['Field'];
				$this->tables[$name]['fields'][] = $field;
				if('_id' == substr($field, -3)){
					$referenced = substr($field, 0, count($field) - 4);
					if(array_key_exists($referenced, $this->tables)){
						$this->tables[$name]['referencing'][] = $referenced;
						$this->tables[$referenced]['referenced_by'][] = $name;
					}
				}
			}
		}
	}
}