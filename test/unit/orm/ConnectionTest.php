<?php
namespace tests\unit\orm;

use tests\unit\UnitTestCase;
use rss\orm\Connection;

class ConnectionTest extends UnitTestCase {

	public function testExceptionWhenDBInfosIncorrect(){
		$this->setExpectedException('\PDOException');
		$c = new Connection('mysql','localhost','somethingIncorrect','root','00');
	}

	public function testInstanciation(){
		$c = new Connection( $GLOBALS['DB_DRIVER'], $GLOBALS['DB_HOST'], $GLOBALS['DB_NAME'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASS']);
	}

}