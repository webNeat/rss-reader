<?php 

namespace rss\exceptions;

class UnsupportedAcceptFormat extends \Exception {
	public function __construct($format){
		parent::__construct('The request format "'.$format.'" is not supported !');
	}
}