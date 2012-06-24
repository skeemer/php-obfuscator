<?php

// Test
/* Test
	 * Block
	 */
/*
	  Test Block 2
	 */
/**
 ** Test Block 3
 **/

$test = "Testing..4";
$msg = function($msg) {
	echo "Message Closure: $msg\n";
};

function test($msg, $msg2) {
	echo "Message: $msg\nMessage 2: {$msg2}\n";
}

class PhpObfuscator_Tests_VariableScopeTest {
	public $test = "Test";

	function __construct() {
		global $test;
		global $msg;
		$this->test = "Testing overriden in constructor";
		$test = "Testing 5";
		$msg($test);
		$this->test("Testing 6..");
	}

	static function test($msg) {
		echo "class method called message: $msg\n";
	}
}


test("Testing1", "Testing2");
PhpObfuscator_Tests_VariableScopeTest::test("Testing3");
echo "{$test}\n";
$test = new PhpObfuscator_Tests_VariableScopeTest();
echo $test->test; echo "\n";
$msg("Testing 7");

echo "\n"; // New line for dump

var_dump($test, $msg);

?>
