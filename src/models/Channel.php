<?php
namespace rss\models;

use rss\orm\Model;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class Channel extends Model {
	// Relationships
	public $category; // belongs to a category
	public $items = []; // has many items
	// Own attributes
	public $feedLink;
	public $lastHash;
	public $title;
	public $link;
	public $description;
	public $pubDate;
	public $imageUrl;
	// Validation rules
	static public function loadValidatorMetadata(ClassMetadata $metadata){
        $metadata->addPropertyConstraint('feedLink', new Assert\Url);
    }

	public function newItems(){
		return array_filter($this->items, function($item){
			return ! $item->viewed;
		});
	}
}