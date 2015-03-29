<?php
namespace rss\exceptions;

class UnknownFeedType extends \Exception {
	public function __construct($rootElement){
		parent::__construct('Cannot parse xml with root element "'.$rootElement.'" !');
	}
}