<?php
namespace rss\orm;

class Connection extends \PDO {

	public function __construct($driver, $host, $name, $user, $pass){
		parent::__construct($driver.':dbname='.$name.';host='.$host, $user, $pass);
	}

	public function executeQuery($query, $args){
		$stmnt = $this->prepare($query);
		if($stmnt->execute($args))
			return $stmnt;
		return false;
	}

	public function getErrors(){
		$errs = $this->errorInfo();
		return '[SQL Code: '.$errs[0].', Driver Code: '.$errs[1]
				.', message: "'.$errs[2].'" ]';
	}

	public function getFieldsOfTable($table){
		$stmnt = $this->query('DESCRIBE ' . $table);
		if( ! $stmnt ){
			throw new Exception('Cannot retreive fields of table "' . $table 
				. '" : ' . $this->getErrors());
		}
		$fields = [];
		foreach ($stmnt as $row) {
			$fields[] = $row['Field'];
		}
		return $fields;
	}

}