<?php
namespace rss\worker\fetchers;

use rss\models\Item;
use rss\models\Channel;
use rss\exceptions\UnknownFeedType;
use rss\worker\mappers\AtomFeedMapper;
use rss\worker\mappers\RssFeedMapper;

class Fetcher {
	protected $xml;
	protected $channel;
	protected $feedMapper;

	public function __construct(\SimpleXMLElement $xml, Channel $channel){
		$this->feedMapper = null;
    	switch($xml->getName()){
			case 'rss':
				$this->feedMapper = new RssFeedMapper();
				$this->xml = $xml->channel[0];
			break;
			case 'feed':
				$this->feedMapper = new AtomFeedMapper();
				$this->xml = $xml;
			break;
			default:
				throw new UnknownFeedType($xml->getName());
		}
		$this->channel = $channel;
	}

	public function fetchChannel(){
		$this->feedMapper->fillChannel($this->xml, $this->channel);
	}

	public function fetch(){
		$newItems = [];
		$this->feedMapper->fillChannel($this->xml, $this->channel);
		$itemTagName = $this->feedMapper->itemTagName();
		foreach( $this->xml->$itemTagName as $itemXML ){
			$item = new Item;
			$this->feedMapper->fillItem($itemXML, $item);
			$newItems[] = $item;
		}
		// Remove duplicated items based on link attribut
		// because pubDate and guid are optional fields on RSS 2.0
		$newItems = array_udiff($newItems, $this->channel->items, function($itemA, $itemB){
			return strcmp($itemA->link, $itemB->link);
		});
		// Merging Items
		$this->channel->items = array_merge($this->channel->items, $newItems);
	}
}