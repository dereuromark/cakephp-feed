<?php
/**
 * Feed Plugin - All plugin tests
 */
class AllFeedTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Feed tests');

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'View');

		return $Suite;
	}

}
