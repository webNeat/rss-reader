<?php
namespace tests\unit\orm;

use tests\unit\UnitTestCase;
use rss\orm\Connection;
use rss\orm\Schema;
use rss\orm\Mapper;
use rss\models\Item;
use rss\models\Channel;
use rss\models\Category;
// TODO:
//   Testing update 
class MapperTest extends UnitTestCase {
	protected static $connection; // Created once for all tests
	public static function setUpBeforeClass(){
		self::$connection = new Connection( $GLOBALS['DB_DRIVER'], $GLOBALS['DB_HOST'], $GLOBALS['DB_NAME'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASS']);
	}
	public static function tearDownAfterClass(){
		self::$connection = null;
	}

	public function setUp(){
		$schema = new Schema(self::$connection);
		$this->mapper = new Mapper(self::$connection, $schema);
	}

	public function testInsertAndRemoveOneModelWithoutRelationships(){
		$category = new Category;
		$category->name = 'Awesome';

		$rows = $this->countRowsOf('category');
		$this->mapper->persist($category);

		$this->assertEquals($rows + 1, $this->countRowsOf('category'));
		$this->checkInsertedCategory($category);
		$id = $category->id;

		$this->mapper->remove($category);

		$this->assertEquals($rows, $this->countRowsOf('category'));
		$this->checkRemovedCategory($category, $id);
	}

	public function testInsertAndRemoveArrayOfModelsWithoutRelationships(){
		$categories = [ new Category, new Category];
		$categories[0]->name = 'Foo';
		$categories[1]->name = 'Bar';

		$rows = $this->countRowsOf('category');
		$this->mapper->persist($categories);

		$this->assertEquals($rows + 2, $this->countRowsOf('category'));
		$ids = [];
		foreach ($categories as $key => $category){
			$this->checkInsertedCategory($category);
			$ids[$key] = $category->id;
		}

		$this->mapper->remove($categories);

		$this->assertEquals($rows, $this->countRowsOf('category'));
		foreach ($categories as $key => $category){
			$this->checkRemovedCategory($category, $ids[$key]);
		}
	}

	public function testInsertAndRemoveOneModelWithRelationships(){
		$category = new Category;
		$category->name = 'Awesome';

		$channel = new Channel;
		$channel->feedLink = 'http://somefeed.site/rss';
		$channel->title = 'Foo';
		$channel->link = 'http://somefeed.site';
		$channel->description = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit,...';
		$category->channels[] = $channel;

		$channel = new Channel;
		$channel->feedLink = 'http://otherfeed.site/rss';
		$channel->title = 'Bar';
		$channel->link = 'http://otherfeed.site';
		$channel->description = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit,...';
		$item = new Item;
		$item->title = 'My First Item';
		$item->link = 'http://otherfeed.site/item';
		$item->content = 'Lorem ipsum dolor sit amet, consectetur adipisicing tempor incididunt ...';
		$channel->items[] = $item;
		$category->channels[] = $channel;

		$categoryRows = $this->countRowsOf('category');
		$channelRows = $this->countRowsOf('channel');
		$itemRows = $this->countRowsOf('item');
		$this->mapper->persist($category);

		$this->assertEquals($categoryRows + 1, $this->countRowsOf('category'));
		$this->assertEquals($channelRows + 2, $this->countRowsOf('channel'));
		$this->assertEquals($itemRows + 1, $this->countRowsOf('item'));
		$this->checkInsertedCategory($category);
		$id = $category->id;
		$ids = [];
		foreach ($category->channels as $k => $channel) {
			$ids[$k] = $channel->id;
		}

		$this->mapper->remove($category);

		$this->assertEquals($categoryRows, $this->countRowsOf('category'));
		$this->assertEquals($channelRows, $this->countRowsOf('channel'));
		$this->assertEquals($itemRows, $this->countRowsOf('item'));
		$this->checkRemovedCategory($category, $id);
		foreach ($category->channels as $k => $channel) {
			$this->checkRemovedChannel($channel, $ids[$k]);
		}
	}

	private function countRowsOf($table){
		$stmnt = self::$connection->executeQuery('SELECT COUNT(*) as count FROM ' . $table);
		$stmnt = $stmnt->fetch(\PDO::FETCH_ASSOC);
		return $stmnt['count'];
	}

	private function checkInsertedCategory($category){
		$this->assertNotNull($category->id);
		$row = self::$connection->executeQuery(
			'SELECT name FROM category WHERE id = :id', 
			['id' => $category->id]
		)->fetch(\PDO::FETCH_ASSOC);
		$this->assertEquals($category->name, $row['name']);
		foreach ($category->channels as $channel) {
			$this->checkInsertedChannel($channel);
		}
	}

	private function checkInsertedChannel($channel){
		$this->assertNotNull($channel->id);
		$row = self::$connection->executeQuery(
			'SELECT title, feed_link, link, description FROM channel WHERE id = :id', 
			['id' => $channel->id]
		)->fetch(\PDO::FETCH_ASSOC);
		$this->assertEquals($channel->title, $row['title']);
		$this->assertEquals($channel->feedLink, $row['feed_link']);
		$this->assertEquals($channel->link, $row['link']);
		$this->assertEquals($channel->description, $row['description']);
	}

	private function checkRemovedCategory($category, $id){
		$this->assertNull($category->id);
		$row = self::$connection->executeQuery(
			'SELECT name FROM category WHERE id = :id', 
			['id' => $id]
		)->fetch(\PDO::FETCH_ASSOC);
		$this->assertFalse($row);
	}
	
	private function checkRemovedChannel($channel, $id){
		$this->assertNull($channel->id);
		$row = self::$connection->executeQuery(
			'SELECT title FROM channel WHERE id = :id', 
			['id' => $id]
		)->fetch(\PDO::FETCH_ASSOC);
		$this->assertFalse($row);
	}
}