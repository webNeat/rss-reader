<?php
namespace rss\models;

use rss\orm\Model;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class Category extends Model {
	// Relationships
	public $channels = []; // has many channels
	// Own attributes
	public $name;
	public $description;
	// Validation rules
	static public function loadValidatorMetadata(ClassMetadata $metadata){
        $metadata->addPropertyConstraint('name', new Assert\Length(['min' => 4]));
    }

    public function newItemsNumber(){
    	$n = 0;
    	foreach ($this->channels as $c){
    		$n += count($c->newItems());
    	}
    	return $n;
    }
}