<?php 
namespace rss\exceptions;

class TableNotFound extends \Exception {
	public function __construct($tablename){
		parent::__construct('Table "'.$tablename.'" was not found in the database !');
	}
}