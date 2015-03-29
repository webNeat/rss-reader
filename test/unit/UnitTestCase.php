<?php
namespace tests\unit;

class UnitTestCase extends \PHPUnit_Framework_TestCase {
	protected function assertArrayValuesEquals($expected, $current){
		sort($expected);
		sort($current);
		$this->assertEquals($expected, $current);
	}
}