<?php 

namespace rss\exceptions;

class ReadingFields extends \Exception {
	public function __construct($tablename, $dbErrors){
		parent::__construct('Could not retreive fields of table "{$tablename}". DB: {$dbErrors}');
	}
}