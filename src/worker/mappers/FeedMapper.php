<?php
namespace rss\worker\mappers;

use rss\models\Item;
use rss\models\Channel;
use rss\exceptions\MissingXMLField;

abstract class FeedMapper {
	protected $data;

	public function itemTagName(){
		return $this->data['item_tag_name'];
	}
	

	public function fillChannel(\SimpleXMLElement $xml, Channel $channel){
		foreach( $this->data['channel_elements'] as $attr => $tag ){
			$elements = $xml->$tag['name'];
			if(isset($elements[0]))
				$channel->$attr = $elements[0]->__toString();
			else if($tag['required'])
				throw new MissingXMLField($this->xmlType(), $tag['name']);
		}
		$this->fillAdditionalChannelAttributs($xml, $channel);
	}

	public function fillItem(\SimpleXMLElement $xml, Item $item){
		foreach( $this->data['item_elements'] as $attr => $tag ){
			$elements = $xml->$tag['name'];
			if(isset($elements[0]))
				$item->$attr = $elements[0]->__toString();
			else if($tag['required'])
				throw new MissingXMLField($this->xmlType(), $tag['name']);
		}
		$this->fillAdditionalItemAttributs($xml, $item);
	}

	protected function xmlType(){
		return $this->data['xml_type'];
	}

	abstract protected function fillAdditionalChannelAttributs(\SimpleXMLElement $xml, Channel $channel);
	abstract protected function fillAdditionalItemAttributs(\SimpleXMLElement $xml, Item $item);
}