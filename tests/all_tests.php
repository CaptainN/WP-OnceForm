<?php
// this is needed for simpletest's addFile method
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));

class AllWPTests extends TestSuite
{
	function AllTests()
	{
		$this->TestSuite( 'All Tests' );
		$this->addFile( 'core_test.php' );
	}
}
