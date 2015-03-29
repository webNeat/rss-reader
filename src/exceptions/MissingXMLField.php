<?php
namespace rss\exceptions;

class MissingXMLField extends \Exception {
	public function __construct($type, $field){
		parent::__construct('While parsing the '.$type.' xml, The element "'.$field.'" is missing !');
	}
}