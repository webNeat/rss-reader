<?php
namespace rss\orm;

class Connection extends \PDO {

	public function __construct($driver, $host, $name, $user, $pass){
		parent::__construct($driver.':dbname='.$name.';host='.$host, $user, $pass);
	}

	public function executeQuery($query, $args = null){
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
	
}