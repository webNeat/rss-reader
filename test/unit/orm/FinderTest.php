<?php
namespace tests\unit\orm;

use tests\unit\UnitTestCase;
use rss\orm\Connection;
use rss\orm\Schema;
use rss\orm\Mapper;
use rss\orm\Finder;
use rss\models\Item;
use rss\models\Channel;
use rss\models\Category;

// TODO
//   Add more tests !
class FinderTest extends UnitTestCase {
	protected static $connection; // Created once for all tests
	protected static $emptyCategoryId;
	public static function setUpBeforeClass(){
		self::$connection = new Connection( $GLOBALS['DB_DRIVER'], $GLOBALS['DB_HOST'], $GLOBALS['DB_NAME'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASS']);
		self::$connection->query('DELETE FROM item');
		self::$connection->query('DELETE FROM channel');
		self::$connection->query('DELETE FROM category');
		// Filling DB with testing data
		$schema = new Schema(self::$connection);
		$mapper = new Mapper(self::$connection, $schema);

		$category = new Category;
		$category->name = 'Empty Category';
		$mapper->persist($category);
		self::$emptyCategoryId = $category->id;

		$category = new Category;
		$category->name = 'Filled Category';
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
		$item->title = 'First Item';
		$item->link = 'http://otherfeed.site/item';
		$item->content = 'Lorem ipsum dolor sit amet, consectetur adipisicing tempor incididunt ...';
		$channel->items[] = $item;
		$item = new Item;
		$item->title = 'Second Item';
		$item->link = 'http://otherfeed.site/item';
		$item->content = 'Lorem ipsum dolor sit amet, consectetur adipisicing tempor incididunt ...';
		$channel->items[] = $item;

		$category->channels[] = $channel;
		$mapper->persist($category);
	}
	public static function tearDownAfterClass(){
		// Removing all data from DB
		self::$connection->query('DELETE FROM item');
		self::$connection->query('DELETE FROM channel');
		self::$connection->query('DELETE FROM category');
		self::$connection = null;
	}

	public function setUp(){
		$schema = new Schema(self::$connection);
		$this->categoriesFinder = new Finder(self::$connection, $schema, 'rss\models\Category');
		$this->channelsFinder = new Finder(self::$connection, $schema, 'rss\models\Channel');
		$this->itemsFinder = new Finder(self::$connection, $schema, 'rss\models\Item');
	}

	public function testGettingModelById(){
		$category = $this->categoriesFinder->getById(self::$emptyCategoryId);
		$this->assertNotNull($category);
		$this->assertEquals('Empty Category', $category->name);
	}
	
	public function testReturnsNullWhenNotFoundById(){
		// We added only 2 categories, so there will be no category with id = self::$emptyCategoryId + 3 
		$category = $this->categoriesFinder->getById(self::$emptyCategoryId + 3);
		$this->assertNull($category);
	}

	public function testGetModelsWithRelationships(){
		$categories = $this->categoriesFinder->setRecursions(2)->get();
		$this->assertNotNull($categories);
		$this->assertTrue(is_array($categories));
		$this->assertEquals(2, count($categories));
		
		$category = $categories[0];
		$this->assertEquals('Empty Category', $category->name);
		$this->assertEquals(0, count($category->channels));
		
		$category = $categories[1];
		$this->assertEquals('Filled Category', $category->name);
		$this->assertEquals(2, count($category->channels));
		$channel = $category->channels[0];
		$this->assertEquals('Foo', $channel->title);
		$this->assertEquals(0, count($channel->items));
		$channel = $category->channels[1];
		$this->assertEquals('Bar', $channel->title);
		$this->assertEquals(2, count($channel->items));
		$this->assertEquals('First Item', $channel->items[0]->title);
		$this->assertEquals('Second Item', $channel->items[1]->title);
	}

}