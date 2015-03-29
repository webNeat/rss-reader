<?php
namespace rss\worker\mappers;

use rss\models\Item;
use rss\models\Channel;

class AtomFeedMapper extends FeedMapper {
	public function __construct(){
		$this->data = [
			'xml_type' => 'ATOM',
			'item_tag_name' => 'entry',
			'channel_elements' => [
				'title' => [
					'name' => 'title',
					'required' => true
				],
				'description' => [
					'name' => 'subtitle',
					'required' => false
				]
			],
			'item_elements' => [
				'title' => [
					'name' => 'title',
					'required' => true
				]	
			]
		];
	}

	protected function fillAdditionalChannelAttributs(\SimpleXMLElement $xml, Channel $channel){
		foreach( $xml->link as $link ){
			$attrs = $link->attributes();
			if(!isset($attrs['rel'])){
				$channel->link = $attrs['href']->__toString();
				break;
			}
		}
		if(isset($xml->updated[0])){
			$channel->pubDate = date('Y-m-d H:i:s', strtotime($xml->updated[0]));
		}
	}

	protected function fillAdditionalItemAttributs(\SimpleXMLElement $xml, Item $item){
		$item->link = null;
		foreach( $xml->link as $link ){
			$attrs = $link->attributes();
			if(!isset($attrs['rel'])){
				$item->link = $attrs['href']->__toString();
				break;
			}
		}
		if(is_null($item->link))
			throw new MissingXMLField($this->xmlType(), 'link');
		$content = $xml->content[0];
		if($content->count() == 0)
			$item->content = $content->__toString();
		else {
			$item->content = '';
			foreach( $content->children() as $child ){
				$item->content .= $child->asXML(); 
			}
		}
		$item->pubDate = date('Y-m-d H:i:s', strtotime($xml->updated[0]->__toString()));
		if(isset($xml->author[0]) && isset($xml->author[0]->name[0]))
			$item->author = $xml->author[0]->name[0]->__toString();
	}
}