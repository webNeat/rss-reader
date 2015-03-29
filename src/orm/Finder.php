<?php
namespace rss\orm;

use rss\utils\String;
use \Exception;

// We assume that field name == attribute name for simplicity
// We also assume that attributs to be read from DB are public
class Finder {
	private $cn; // The connection
	private $schema; // The schema helper
	private $table; // The related table name
	private $fields; // Fields of this table
	private $className; // The model class name
	private $relationships; // Relations of the model with other models

	private $recursions; // Maximum recursions to retreive related models
	private $conditions; // array of SQL conditions
	private $params; // parameters of the query
	private $orderBy; // [field => ... , way => 'ASC' | 'DESC' ]

	public function __construct(Connection $c, Schema $s, $className){
		$this->cn = $c;
		$this->schema = $s;
		$this->className = $className;
		$this->table = $s->getTableNameOfClass($className);
		$this->fields = $s->getFieldsOf($this->table);
		$this->recursions = 1;
		$this->relationships = null;
	}

	public function getById($id){
		$this->where('id', '=', $id);
		return $this->get(1);
	}

	// Add the where condition to the query 
	public function where($field, $operator, $value){
		$operator = strtoupper($operator);
		if(! in_array($field, $this->fields))
			throw new Exception('Adding condition for inexistant field "'
				. $field . '" on table "' . $this->table . '"');
		if(! in_array($operator, ['=', '<', '<=', '>', '>=', '<>', '!=']) && 'LIKE' !== $operator)
			throw new Exception('Adding condition with unvalid comparaison operator "' . $operator );
		if($operator == '!=')
			$operator = '<>';
		$param = $this->addQueryParam($value);
		$this->conditions[] = $field . ' ' . $operator . ' ' . $param;
		return $this;
	}

	// Sort by the field ( $way = 'asc' or 'desc')
	public function sortBy($field, $way = 'asc'){
		if(in_array($field, $this->fields)){
			$this->orderBy = $field . (( 'asc' == strtolower($way) ) ? ' ASC' : ' DESC');
		} else
			throw new Exception('Trying to order by inexistant field "'
				. $field . '" on table "' . $this->table . '"');
		return $this;
	}

	public function setRecursions($l){
		$this->recursions = $l;
		return $this;
	}

	// Apply the query and return the result as array of Models
	// or one Model if $count == 1
	public function get($count = false){
		// Construct the query
		$query = 'SELECT * FROM ' . $this->table;
		if( ! empty($this->conditions)) 
			$query .= ' WHERE ' . implode(' AND ', $this->conditions);
		if( ! is_null($this->orderBy)){
			$query .= ' ORDER BY ' . $this->orderBy;
		}
		if(false !== $count){
			$query .= ' LIMIT ' . ((int) $count);
		}
		// Execute the query
		// echo 'Query: ' . $query . PHP_EOL;
		// print_r($this->params);
		$result = $this->cn->executeQuery($query, $this->params);
		// If some error happened
		if(false === $result){
			throw new Exception ('Could not execute the query "{$query}" : ' 
				. $this->cn->getErrors());
		}
		// Making array of models
		$models = [];
		$row = $result->fetch(\PDO::FETCH_ASSOC);
		if( false === $row)
			$models = null;
		else {
			while(false !== $row){
				$m = new $this->className;
				foreach ( $row as $field => $value ){
					if('_id' != substr($field, -3)){
						$field = String::underscoresToCamlCase($field, true);
						$m->$field = $value;
					}
				}
				if($this->recursions > 0){
					$this->fillRelationships();
					foreach ( $this->relationships as $relation ){
						switch($relation['type']){
							case 'has_many':
								$childs = $relation['finder']->where( $relation['field'], '=', $m->id )->get();
								$m->$relation['attribute'] = (is_null($childs)) ? [] : $childs;
							break;
							case 'belongs_to':
								$m->$relation['attribute'] = $relation['finder']->getById($row[$relation['field']]);
							break;
						}
					}
				}
				$models[] = $m;
				$row = $result->fetch(\PDO::FETCH_ASSOC);
			}
			if(1 === $count)
				$models = $models[0];
		}
		// Cleaning up for futur calls
		$this->clear();
		return $models;
	}

	protected function fillRelationships(){
		if(is_null($this->relationships)){
			$this->relationships = [];
			$childTables = $this->schema->getTablesReferencing($this->table);
			foreach ( $childTables as $childTable ){
				$relation = [];
				$relation['type'] = 'has_many';
				$childClass = $this->schema->getClassOfTable($childTable);
				$relation['finder'] = new Finder($this->cn, $this->schema, $childClass);
				$relation['finder']->setRecursions($this->recursions - 1);
				$relation['attribute'] = $childTable . 's';
				$relation['field'] = $this->table . '_id';
				$this->relationships[] = $relation;
			}
			$parentTables = $this->schema->getTablesReferencedBy($this->table);
			foreach ( $parentTables as $parentTable ){
				$relation = [];
				$relation['type'] = 'belongs_to';
				$rClass = $this->schema->getClassOfTable($parentTable);
				$relation['finder'] = new Finder($this->cn, $this->schema, $rClass);
				$relation['finder']->setRecursions($this->recursions - 1);
				$relation['attribute'] = $parentTable;
				$relation['field'] = $parentTable . '_id';
				$this->relationships[] = $relation;
			}
		}
	}

	// Add value to the query params and returns the key
	protected function addQueryParam($value){
		$name = ':param_' . count($this->params);
		$this->params[$name] = $value;
		return $name;
	}

	protected function clear(){
		$this->conditions = [];
		$this->params = [];
		$this->orderBy = null;
	}

}