<?php
/**
 * PhpObfuscator_Tests_ProjectObfuscatorTest
 */
require_once(dirname(__FILE__).'/../Classes/ProjectObfuscator.php');
require_once(dirname(__FILE__) . '/../Classes/FileObfuscatorService.php');

/**
 *
 */
class PhpObfuscator_Tests_ProjectObfuscatorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var PhpObfuscator_ProjectObfuscator
	 */
	private $obfuscator;

	public function setUp() {
		$this->obfuscator = new PhpObfuscator_ProjectObfuscator( new PhpObfuscator_FileObfuscatorService( new PhpObfuscator_VariableObfuscator( new PhpObfuscator_EncoderService() ) ) );
		@unlink(dirname(__FILE__) . '/Tmp/TestService.php');
		@unlink(dirname(__FILE__) . '/Tmp/VaribleScopeTest.php');
	}
	/**
	 * @test
	 */
	public function canObfuscateFolder() {
		$obfuscatedFile = dirname(__FILE__) . '/Tmp/TestService.php';
		$this->assertFalse(is_file($obfuscatedFile));
		// T_PROTECTED echo token_name(343);
		//T_PRIVATE echo token_name(344);
		//T_STRING echo token_name(307);
		//T_VARIABLE echo token_name(309);
		// T_OBJECT_OPERATOR echo token_name(357);  ->

		//print_r(token_get_all(file_get_contents(dirname(__FILE__) . '/FixtureProject/TestService.php')));
		$this->obfuscator->getFileObfuscatorService()->getEncoderService()->encodeString = true;
		$this->obfuscator->setProjectDir(dirname(__FILE__) . '/FixtureProject');
		$this->obfuscator->setOutputDir(dirname(__FILE__) . '/Tmp');
		$this->obfuscator->getFileObfuscatorService()->stripComments = false;
		$this->obfuscator->process();

		$this->assertTrue(is_file($obfuscatedFile));
		require_once($obfuscatedFile);
		$testService = new PhpObfuscator_Tests_TestService();
		$this->assertEquals($testService->outPutPrivat(), 'private_member protected_member +++');


		//test if reflection still works
		$method = new ReflectionMethod('PhpObfuscator_Tests_TestService', 'outPutPrivat');
		$this->assertContains('@validator TestValidator', $method->getDocComment());
	}

}