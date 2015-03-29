<?php 
namespace rss\orm;

use \Exception;
use rss\utils\String;

class Mapper {
	private $cn;
	private $schema;

	public function __construct(Connection $c, Schema $s){
		$this->cn = $c;
		$this->schema = $s;
	}

	public function persist($models){
		if(is_array($models))
			foreach($models as $m)
				$this->persistOne($m);
		else if($models instanceof Model)
			$this->persistOne($models);
		else
			throw new Exception('Unable to persist object of type "' . get_class($models) 
				. '". Please make sure all your model classes extends the rss\\orm\\Model class !');
	}

	public function remove($models){
		if(is_array($models))
			foreach($models as $m)
				$this->removeOne($m);
		else if($models instanceof Model)
			$this->removeOne($models);
		else
			throw new Exception('Unable to remove object of type "' . get_class($models) 
				. '". Please make sure all your model classes extends the rss\\orm\\Model class !');
	}

	protected function persistOne(Model $m){
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

	protected function removeOne(Model $m){
		if(null != $m->id){
			$table = $this->schema->getTableNameOfModel($m);
			$childTables = $this->schema->getTablesReferencing($table);
			foreach ($childTables as $childs) {
				$childs .= 's';
				if(isset($m->$childs) && is_array($m->$childs)){
					$this->remove($m->$childs);
				}
			}
			if( ! $this->cn->executeQuery(
				'DELETE FROM ' . $table . ' WHERE id = :id', 
				['id' => $m->id]
			)){
				throw new Exception('Error happened while removing : ' 
					. $this->cn->getErrors());
			}
			$m->id = null;
		}
	}

	protected function insert(Model $m){
		$table = $this->schema->getTableNameOfModel($m);
		$fields = $this->schema->getFieldsOf($table);
		$query = 'INSERT INTO ' . $table 
			. '(' . implode(',', $fields) 
			. ') VALUES (:' . implode(', :', $fields) . ')';
		$m->created = date('Y-m-d H:i:s');
		$values = $this->getValuesFrom($m, $fields);
		$done = ( false !== $this->cn->executeQuery($query, $values) );
		if( $done ){
			$m->id = $this->cn->lastInsertId();
			$this->persistChilds($m, $table);
		}
		return $done;
	}

	protected function update(Model $m){
		$table = $this->schema->getTableNameOfModel($m);
		$fields = $this->schema->getFieldsOf($table);
		$values = $this->getValuesFrom($m, $fields);
		$values['updated'] = date('Y-m-d H:i:s');
		$updates = [];
		foreach( $values as $field => $value ){
			if($field != 'id')
				$updates[] = $field . '= :' . $field;
		}
		$query = 'UPDATE ' . $table . ' SET ' . implode(', ', $updates) 
			. ' WHERE id = :id';
		$done = ( false !== $this->cn->executeQuery($query, $values) );
		if( $done ){
			$this->persistChilds($m, $table);
		}
		return $done;
	}

	protected function persistChilds(Model $m, $table){
		$childTables = $this->schema->getTablesReferencing($table);
		foreach ($childTables as $childs) {
			$childs .= 's';
			if(isset($m->$childs) && is_array($m->$childs)){
				foreach ($m->$childs as $child) {
					$child->$table = $m;
					$this->persist($child);
				}
			}
		}
	}

	protected function getValuesFrom(Model $m, $fields){
		// We assume that field name == attribute name for simplicity
		// We also assume that attributs to be written to DB are public
		$publicAttributs = $this->getPublicAttributsOf($m);
		$values = [];
		$foundFields = 0;
		foreach( $fields as $field ){
			$values[$field] = null;
			if('_id' == substr($field, -3)){
				$parent = substr($field, 0, count($field) - 4);
				if(!in_array($parent, $publicAttributs))
					throw new Exception('Ooops 1 !!');
				if(is_null($m->$parent))
					throw new Exception('Ooops 2 !!');
				if(!isset($m->$parent->id))
					throw new Exception('Ooops 3 !!');
				$values[$field] = $m->$parent->id;
				$foundFields ++;
			}
		}
		foreach ( $publicAttributs as $attr ){
			$field = String::CamlCaseToUnderscores($attr);
			if(array_key_exists($field, $values)){
				$values[$field] = $m->$attr;
				$foundFields ++;
			}
		}
		if($foundFields < count($fields)){
			throw new Exception('A field on the table "' . $this->schema->getTableNameOfModel($m) 
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