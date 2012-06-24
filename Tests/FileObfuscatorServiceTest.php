<?php
/**
 * PhpObfuscator_Tests_ProjectObfuscatorTest
 */
require_once(dirname(__FILE__).'/../Classes/ProjectObfuscator.php');
require_once(dirname(__FILE__) . '/../Classes/FileObfuscatorService.php');

/**
 *
 */
class PhpObfuscator_Tests_FileObfuscatorServiceTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var PhpObfuscator_FileObfuscatorService
	 */
	private $realObfuscator;

	public function setUp() {

		$this->encoderServiceMock = $this->getMock('PhpObfuscator_EncoderService');
		$this->variableObfuscatorMock = $this->getMock('PhpObfuscator_VariableObfuscator', array(),array($this->encoderServiceMock));
		$this->usingMockObfuscator = new PhpObfuscator_FileObfuscatorService($this->variableObfuscatorMock);
		$this->realObfuscator = new PhpObfuscator_FileObfuscatorService(new PhpObfuscator_VariableObfuscator(new PhpObfuscator_EncoderService()));
	}

	/**
	 * @test
	 */
	public function canObfuscateWithoutComments() {
		$this->realObfuscator->stripDocComments = true;
		$code = $this->realObfuscator->obfuscateFile(dirname(__FILE__) . '/FixtureProject/TestService.php');
		$this->assertNotContains('@validator TestValidator', $this->getDecodedCode($code));
		$this->assertNotContains('single line comment', $this->getDecodedCode($code));
		$this->assertNotContains('one line doc comment', $this->getDecodedCode($code));

		$this->realObfuscator->stripDocComments = false;
		$code = $this->realObfuscator->obfuscateFile(dirname(__FILE__) . '/FixtureProject/TestService.php');
		$this->assertContains('@validator TestValidator', $this->getDecodedCode($code));
		$this->assertNotContains('single line comment', $this->getDecodedCode($code));
		$this->assertNotContains('one line doc comment', $this->getDecodedCode($code));

		$this->realObfuscator->stripSingleLineDocComments = false;
		$this->realObfuscator->stripNormalComments = false;
		$code = $this->realObfuscator->obfuscateFile(dirname(__FILE__) . '/FixtureProject/TestService.php');
		$this->assertContains('@validator TestValidator', $this->getDecodedCode($code));
		$this->assertContains('single line comment', $this->getDecodedCode($code));
		$this->assertContains('one line doc comment', $this->getDecodedCode($code));
	}

	/**
	 * @test
	 */
	public function canObfuscatePrivateMemberComments_EndToEnd() {
		$this->realObfuscator->getEncoderService()->encodeString = true;
		$this->realObfuscator->stripDocComments = true;

		$code = $this->realObfuscator->obfuscateFile(dirname(__FILE__) . '/FixtureProject/TestService.php');
		// echo $this->getDecodedCode($code);
		$this->assertNotContains('privateMember', $this->getDecodedCode($code));
		$this->assertContains('protectedMember', $this->getDecodedCode($code));
	}


	/**
	 * @test
	 */
	public function canObfuscateVariableScopeTest() {
		$this->realObfuscator->getEncoderService()->encodeString = false;
		$this->realObfuscator->stripDocComments = true;

		$code = $this->realObfuscator->obfuscateFile(dirname(__FILE__) . '/FixtureProject/VariableScopeTest.php');

		echo $this->getDecodedCode($code);
	}

	/**
	 * @param $code
	 * @return string
	 */
	private function getDecodedCode($code) {
		preg_match('/base64_decode\("(.*)"/U', $code,$matches);
		return base64_decode($matches[1]);
	}

}