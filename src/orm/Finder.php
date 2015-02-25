<?php
namespace rss\orm;

use rss\utils\String;
use \Exception;

class Finder {
	private $cn;
	private $table;
	private $fields;
	private $className;

	public function __construct(Connection $c, $className){
		$this->cn = $c;
		$this->className = $className;
		$className = explode('\\', $className);
		$className = array_pop($className);
		$this->table = String::CamlCaseToUnderscores($className);
		$this->fields = $this->cn->getFieldsOfTable($this->table);
	}

	public function get($id){
		// We assume that field name == attribute name for simplicity
		// We also assume that attributs to be read from DB are public
		$result = $this->cn->executeQuery(
			'SELECT * FROM ' . $this->table . ' WHERE id = :id LIMIT 1',
			['id' => $id]
		);
		if(false === $result){
			throw new Exception ('Could not select row with id "' . $id 
				. '" from the table "' . $this->table . '" : ' 
				. $this->cn->getErrors());
		}
		$result = $result->fetch(\PDO::FETCH_ASSOC);
		if( false === $result)
			$entity = null;
		else{
			$entity = new $this->className;
			foreach ( $result as $field => $value ){
				$entity->$field = $value;
			}
		}
		return $entity;
	}
}