<?php
namespace tests\unit;

use rss\models\Item;
use rss\models\Channel;
use rss\worker\fetchers\Fetcher;

class FetcherTest extends UnitTestCase {
	public function testFetchSimpleRssFeed(){
		$channel = new Channel;
		$channel->items = [];
		$xml = new \SimpleXMLElement(file_get_contents(__DIR__.'/../files/rss/rss.xml'));
		$fetcher = new Fetcher($xml, $channel);
		$fetcher->fetch();
		$this->assertEquals('RSS Simple Feed', $channel->title);
		$this->assertEquals('RSS Feed Sample description', $channel->description);
		$this->assertEquals('http://rss.sample.com', $channel->link);
		$this->assertEquals(1, count($channel->items));
		$this->assertEquals('Item 1', $channel->items[0]->title);
		$this->assertEquals('http://rss.sample.com/item1', $channel->items[0]->link);
		$this->assertEquals('Item 1 content', $channel->items[0]->content);
	}

	public function testFetchUpdatedRssFeed(){
		$channel = new Channel;
		$channel->title = 'RSS Simple Feed';
		$item = new Item;
		$item->title = 'Item 1';
		$item->link = 'http://rss.sample.com/item1';
		$channel->items = [$item];
		$xml = new \SimpleXMLElement(file_get_contents(__DIR__.'/../files/rss/updated-rss.xml'));
		$fetcher = new Fetcher($xml, $channel);
		$fetcher->fetch();
		$this->assertEquals('RSS Simple Feed Updated', $channel->title);
		$this->assertEquals(3, count($channel->items));
		$titles = array_map(function($item){
			return $item->title;
		}, $channel->items);
		$this->assertArrayValuesEquals(['Item 1', 'Item 2', 'Item 3'], $titles);
	}

	public function testFetchSimpleAtomFeed(){
		$channel = new Channel;
		$channel->items = [];
		$xml = new \SimpleXMLElement(file_get_contents(__DIR__.'/../files/atom/atom.xml'));
		$fetcher = new Fetcher($xml, $channel);
		$fetcher->fetch();
		$this->assertEquals('Atom Simple Feed', $channel->title);
		$this->assertEquals('Atom Feed Sample description', $channel->description);
		$this->assertEquals('http://atom.sample.com', $channel->link);
		$this->assertEquals(1, count($channel->items));
		$this->assertEquals('Item 1', $channel->items[0]->title);
		$this->assertEquals('http://atom.sample.com/item1', $channel->items[0]->link);
		$content = str_replace('  ','', $channel->items[0]->content);
		$content = str_replace("\n",'', $content);
		$content = str_replace("\t",'', $content);
		$this->assertEquals('<div xmlns="http://www.w3.org/1999/xhtml"><p>This is the entry content.</p></div>', $content);
	}

	public function testFetchUpdatedAtomFeed(){
		$channel = new Channel;
		$channel->title = 'Atom Simple Feed';
		$item = new Item;
		$item->title = 'Item 1';
		$item->link = 'http://atom.sample.com/item1';
		$channel->items = [$item];
		$xml = new \SimpleXMLElement(file_get_contents(__DIR__.'/../files/atom/updated-atom.xml'));
		$fetcher = new Fetcher($xml, $channel);
		$fetcher->fetch();
		$this->assertEquals('Atom Simple Feed Updated', $channel->title);
		$this->assertEquals(2, count($channel->items));
		$titles = array_map(function($item){
			return $item->title;
		}, $channel->items);
		$this->assertArrayValuesEquals(['Item 1', 'Item 2'], $titles);
	}

	public function testThrowsExceptionWhenUnknownFeedType(){
		$this->setExpectedException('rss\exceptions\UnknownFeedType');
		$channel = new Channel;
		$xml = new \SimpleXMLElement(file_get_contents(__DIR__.'/../files/other.xml'));
		$fetcher = new Fetcher($xml, $channel);
	}

	public function testThrowsExceptionWhenMissingField(){
		$this->setExpectedException('rss\exceptions\MissingXMLField');
		$channel = new Channel;
		$xml = new \SimpleXMLElement(file_get_contents(__DIR__.'/../files/rss-with-missing-field.xml'));
		$fetcher = new Fetcher($xml, $channel);
		$fetcher->fetch();
	}
}