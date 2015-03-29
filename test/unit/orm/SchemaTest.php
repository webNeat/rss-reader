<?php
namespace tests\unit\orm;

use tests\unit\UnitTestCase;
use rss\orm\Connection;
use rss\orm\Schema;
use rss\models\Item;

class SchemaTest extends UnitTestCase {
	protected static $connection; // Created once for all tests
	public static function setUpBeforeClass(){
		self::$connection = new Connection( $GLOBALS['DB_DRIVER'], $GLOBALS['DB_HOST'], $GLOBALS['DB_NAME'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASS']);
	}
	public static function tearDownAfterClass(){
		self::$connection = null;
	}

	public function setUp(){
		$this->tables = ['category', 'channel', 'item'];
		$this->fieldsOfItem = ['author', 'channel_id', 'content', 'created', 'id', 'link', 'pub_date', 'title', 'updated'];
		$this->s = new Schema(self::$connection);
	}

	public function testGetTableNames(){
		$tables = $this->s->getTableNames();
		$this->assertArrayValuesEquals($this->tables, $tables);
	}

	public function testGetTableNameOf(){
		$table = $this->s->getTableNameOfClass('rss\models\Item');
		$this->assertEquals('item', $table);
	}

	public function testGetFieldsOf(){
		$fields = $this->s->getFieldsOf('item');
		$this->assertArrayValuesEquals($this->fieldsOfItem, $fields);
	}

	public function testGetTablesReferencing(){
		$referencingTables = $this->s->getTablesReferencing('channel');
		$this->assertArrayValuesEquals(['item'], $referencingTables);
		$referencingTables = $this->s->getTablesReferencing('category');
		$this->assertArrayValuesEquals(['channel'], $referencingTables);
	}

	public function testGetTablesReferencedBy(){
		$referencedTables = $this->s->getTablesReferencedBy('channel');
		$this->assertArrayValuesEquals(['category'], $referencedTables);
		$referencedTables = $this->s->getTablesReferencedBy('item');
		$this->assertArrayValuesEquals(['channel'], $referencedTables);
	}
}