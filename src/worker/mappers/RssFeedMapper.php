<?php
namespace rss\worker\mappers;

use rss\models\Item;
use rss\models\Channel;

class RssFeedMapper extends FeedMapper {
	public function __construct(){
		$this->data = [
			'xml_type' => 'RSS',
			'item_tag_name' => 'item',
			'channel_elements' => [
				'title' => [
					'name' => 'title',
					'required' => true
				],
				'description' => [
					'name' => 'description',
					'required' => true
				],
				'link' => [
					'name' => 'link',
					'required' => true
				]
			],
			'item_elements' => [
				'title' => [
					'name' => 'title',
					'required' => true
				],
				'link' => [
					'name' => 'link',
					'required' => true
				]
			]
		];
	}

	protected function fillAdditionalChannelAttributs(\SimpleXMLElement $xml, Channel $channel){
		if(isset($xml->pubDate[0])){
			$channel->pubDate = date('Y-m-d H:i:s', strtotime($xml->pubDate[0]));
		}
		if(isset($xml->image) && isset($xml->image[0]->url[0])){
			$channel->imageUrl = $xml->image[0]->url[0]->__toString();
		}
	}

	protected function fillAdditionalItemAttributs(\SimpleXMLElement $xml, Item $item){
		$content = $xml->description[0];
		if($content->count() == 0)
			$item->content = $content->__toString();
		else {
			$item->content = '';
			foreach( $content->children() as $child ){
				$item->content .= $child->asXML(); 
			}
		}
		$item->pubDate = date('Y-m-d H:i:s', strtotime($xml->pubDate[0]->__toString()));
	}
}