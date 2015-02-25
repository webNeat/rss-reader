<?php 
namespace rss\orm;

use rss\utils\String;
use \Exception;

class Mapper {
	private $cn;

	public function __construct(Connection $c){
		$this->cn = $c;
	}

	public function persist(Model $m){
		if(null == $m->id){ // should be inserted
			if(! $this->insert($m))
				throw new Exception('Error happened while inserting : ' 
					. $this->cn->getErrors());
		} else { // should be updated
			if( !$this->update($m))
				throw new Exception('Error happened while updating : ' 
					. $this->cn->getErrors());
		}
	}

	public function remove(Model $m){
		if(null != $m->id){
			$table = $this->getTableNameOf($m);
			if( ! $this->cn->executeQuery(
				'DELETE FROM ' . $table . ' WHERE id = :id', 
				['id' => $m->id]
			)){
				throw new Exception('Error happened while removing : ' 
					. $this->cn->getErrors());
			}
		}
	}

	protected function insert(Model $m){
		$table = $this->getTableNameOf($m);
		$fields = $this->cn->getFieldsOfTable($table);
		$query = 'INSERT INTO ' . $table 
			. '(' . implode(',', $fields) 
			. ') VALUES (:' . implode(', :', $fields) . ')';
		$m->created = time();
		$values = $this->getValuesFrom($m, $fields);
		$done = ( false !== $this->cn->executeQuery($query, $values) );
		if( $done )
			$m->id = $this->cn->lastInsertId();
		return $done;
	}

	protected function update(Model $m){
		$table = $this->getTableNameOf($m);
		$values = $this->getValuesFrom($m, $this->cn->getFieldsOfTable($table));
		$updates = [];
		foreach( $values as $field => $value ){
			if($field != 'id')
				$updates[] = $field . '= :' . $field;
		}
		$query = 'UPDATE ' . $table . ' SET ' . implode(', ', $updates) 
			. ' WHERE id = :id';
		return ( false !== $this->cn->executeQuery($query, $values) );
	}

	protected function getTableNameOf(Model $m){
		$className = explode('\\', get_class($m));
		$className = array_pop($className);
		$className = String::CamlCaseToUnderscores($className);
		return $className;
	}

	protected function getValuesFrom(Model $m, $fields){
		// We assume that field name == attribute name for simplicity
		// We also assume that attributs to be written to DB are public
		$publicAttributs = $this->getPublicAttributsOf($m);
		$values = [];
		foreach( $fields as $field ){
			$values[$field] = null;
		}
		$foundFields = 0;
		foreach ( $publicAttributs as $attr ){
			if(array_key_exists($attr, $values)){
				$values[$attr] = $m->$attr;
				$foundFields ++;
			}
		}
		if($foundFields < count($fields)){
			throw new Exception('A field on the table "' . $this->getTableNameOf($m) 
				. '" is missing in the corresponding class. Make sure all fields are declared public');
		}
		return $values;
	}
	protected function getPublicAttributsOf(Model $m){
		$reflect = new \ReflectionObject($m);
		return array_map(function($attr){
			return $attr->name;
		}, $reflect->getProperties(\ReflectionProperty::IS_PUBLIC));
	}
}