<?php

class PhpObfuscator_Tests_TestService {

	/**
	 * @var MyType1
	 */
	private $privateMember = 'private_member';

	/**
	 * @var MyType2
	 */
	protected $protectedMember = 'protected_member';


	/**
	 * @return string
	 * @validator TestValidator
	 */
	public function outPutPrivat() {
		//single line comment
		/** one line doc comment */
		return $this->privateMember. ' '. $this->protectedMember.' '.$this->privateMethod();
	}

	private function privateMethod() {
		return '+++';
	}
}
