<?php

require_once(dirname(__FILE__).'/EncoderService.php');
require_once(dirname(__FILE__) . '/VariableObfuscator.php');

/**
 * Service that Obfuscates PHP files
 */
class PhpObfuscator_FileObfuscatorService {
	public $stripDocComments = false; // Strip comments and whitespace
	public $stripSingleLineDocComments = true; // Strip comments and whitespace
	public $stripNormalComments = true; // Strip comments and whitespace

	public $stripWhitespaces = true; // Strip whitespace


	private $code = null;
	private $tokens = null;

	private $class = false;	//Flag is currently in a class
	private $function = false;	//flag if currently in a function
	private $depth = 0; // Keep track of how deep in curly brackets we are, so we can unset $class and $function when needed.

	private $scopeByLine;	//cache during packing to guess scope of variables and functions

	/**
	 * @var PhpObfuscator_EncoderService
	 */
	private $encoderService;

	/**
	 * @var PhpObfuscator_VariableObfuscator
	 */
	private $variableObfuscator;

	public function __construct(PhpObfuscator_VariableObfuscator $variableObfuscator = null) {
		if (!defined('T_ML_COMMENT')) define('T_ML_COMMENT', T_COMMENT);
	  if($variableObfuscator == null) {
  		$this->variableObfuscator = new PhpObfuscator_VariableObfuscator();
	  } else {
  		$this->variableObfuscator = $variableObfuscator;
	  }
		$this->encoderService = $this->variableObfuscator->getEncoderService();
		return $this;
	}

	private function file($file) {
		if (file_exists($file)) $this->code = file_get_contents($file);
		return $this->tokenize();
	}

	private function code($text = null) {
		if (empty($text)) return $this->code;
		$this->code = $text;
		return $this->tokenize();
	}

	private function save($file) {
		if (!empty($this->code)) if (@file_put_contents($file, $this->code)) return true;
		return false;
	}

	public function obfuscateFile($file) {
		$this->file($file);
		$this->pack();
		return $this->code;
	}
	
	public function obfuscateCode($code) {
	  $this->code($code);
	  $this->pack();
	  return $this->code;
	}

	protected function pack() {
		if (empty($this->tokens)) return false;
		$tokenKeysWithClassMethodCalls = array();
		foreach ($this->tokens as $token_key=> &$token) {
			if (is_array($token)) {
				switch ($token[0]) {
					case T_FUNCTION:
						if ($this->tokens[$token_key - 2][0] == T_VARIABLE) {
							//we enter a new closure function
							$this->function = $this->tokens[$token_key - 2][1];
						}
						elseif ($this->tokens[$token_key + 2][0] == T_STRING) {
							//we enter a new normal function
							$this->function = $this->tokens[$token_key + 2][1];
							if ($this->class) {
								$this->tokens[$token_key + 2][1]= $this->variableObfuscator->renameAndRegisterMethod($this->tokens[$token_key + 2][1],$this->class, $this->scopeByLine[$token[2]]);
							}
						}
						break;
					case T_CLASS:
						$this->class = $this->tokens[$token_key + 2][1];
						break;
					case T_PROTECTED:
					case T_PRIVATE:
					case T_PUBLIC:
						$this->scopeByLine[$token[2]] = $token[0];
						break;
					case T_VARIABLE:
						$token[1] = $this->packVariable($token, $token_key);
						break;
					case T_OBJECT_OPERATOR:
						if ($this->tokens[$token_key - 1][1] == '$this' && $this->tokens[$token_key + 2] == '(') {
							if ($this->class) {
								// Function call like $this->method()
								$tokenKeysWithClassMethodCalls[$this->class][] = $token_key + 1;
							}
						}
						elseif ($this->tokens[$token_key - 1][1] == '$this' && $this->function && $this->class)  {
							//local object variable call like $this->variable:
							$fullVariableName = $this->variableObfuscator->getRenamedClassPropertyIfRegistered("\$". $this->tokens[$token_key + 1][1], $this->class);
							$this->tokens[$token_key + 1][1] = substr($fullVariableName,1);
						}
						break;
					case T_DOUBLE_COLON:
						if ($this->tokens[$token_key + 2] == '(') {
							// Function call like Class:method() => leave it alone.
						}
						else {
							if ($this->tokens[$token_key - 1][1] != '$this') {
								//static variable call like classname::variable
								//leave it for now
								//  $this->tokens[$token_key + 1][1] = $this->variableObfuscator->renameVariableUsageInFunctionScope($this->tokens[$token_key + 1][1],$this->function, $this->tokens[$token_key - 1][1]);
							}
							elseif ($this->function && $this->class) {
								//staic function call for current class thike $this::variable

								$this->tokens[$token_key - 1][1] = '$' . $this->encoderService->encodeString('this');
								$this->tokens[$token_key + 1][1] = $this->variableObfuscator->getRenamedClassProperty($this->tokens[$token_key + 1][1],$this->class);
							}
						}
						break;
					case T_DOC_COMMENT:
						if (strpos($token[1],PHP_EOL)) {
							//multiline doc comments
							if ($this->stripDocComments) $token[1] = '';
						}
						else {
							if ($this->stripSingleLineDocComments) $token[1] = '';
						}
						break;
					case T_COMMENT:
					case T_ML_COMMENT: // Will be equal to T_COMMENT if not in PHP 4.
						if ($this->stripNormalComments) $token[1] = '';
						break;
					case T_START_HEREDOC:
						// Automatically turn whitespace stripping off, because formatting needs to stay the same.
						$this->stripWhitespaces = false;
						break;
					case T_END_HEREDOC:
						$token[1] = "\n{$token[1]}";
						break;
					case T_CURLY_OPEN:
					case T_DOLLAR_OPEN_CURLY_BRACES:
					case T_STRING_VARNAME:
						if ($this->function) $this->depth++;
						break;
				}
			} else {
				switch ($token) {
					case '{':
						if ($this->function) $this->depth++;
						break;
					case '}':
						$this->depth--;
						if ($this->depth < 0) $this->depth = 0;
						if ($this->function && $this->depth == 0) {
							$this->function = false;
						}
						elseif ($this->class && $this->depth == 0) {
							$this->class = false;
						}
						break;
				}
			}
		}
		//Now check all remebered method usages and check if we should replace them because they are registered (as private) method
		foreach ($tokenKeysWithClassMethodCalls as $class => $methodTokens) {
			foreach ($methodTokens as $methodTokenKey) {
				//Now go and check to rename methods:
				$this->tokens[$methodTokenKey][1] = $this->variableObfuscator->getRenamedMethodNameIfRegistered($this->tokens[$methodTokenKey][1], $class);
			}
		}
		$this->detokenize();
		return $this;
	}

	private function tokenize() {
		if (empty($this->code)) return false;
		$this->tokens = token_get_all($this->code);
		return $this;
	}

	private function detokenize() {
		if (empty($this->tokens)) return; // No tokens to parse. Exit.
		foreach ($this->tokens as &$token) {
			if (is_array($token)) {
				switch ($token[0]) {
					case T_INCLUDE:
					case T_INCLUDE_ONCE:
					case T_REQUIRE:
					case T_REQUIRE_ONCE:
					case T_BREAK:
					case T_CONTINUE:
					case T_ENDSWITCH:
					case T_CONST:
					case T_DECLARE:
					case T_ENDDECLARE:
					case T_FOR:
					case T_ENDFOR:
					case T_FOREACH:
					case T_ENDFOREACH:
					case T_IF:
					case T_ENDIF:
					case T_RETURN:
					case T_UNSET:
					case T_EXIT:

					case T_STATIC:
					case T_PUBLIC:
					case T_PRIVATE:
					case T_PROTECTED:
					case T_FUNCTION:
					case T_CLASS:
					case T_EXTENDS:
					case T_GLOBAL:
					case T_NEW:
					case T_ECHO:
					case T_DO:
					case T_WHILE:
					case T_SWITCH:
					case T_CASE:

					case T_VAR:
					//case T_STRING:  (makes problems)
					case T_ENCAPSED_AND_WHITESPACE:
					case T_CONSTANT_ENCAPSED_STRING:
						$token[1] = $this->encoderService->encodeString($token[1]);
						break;
				}
				$tmp[] = $token[1];
			}
			else $tmp[] = $token;
		}
		$tmp = implode('', $tmp);
		$this->code = $this->encoderService->encode($tmp);
	}

	/**
	 * @param array $token
	 * @param string $token_key
	 * @return modified Variable token value ($token[1] )
	 */
	private function packVariable(array $token, $token_key) {
		if ($token[1] == '$this') {
			return $token[1];
		}
		if (!empty($this->tokens[$token_key - 1][1]) && $this->tokens[$token_key - 1][0] == T_DOUBLE_COLON) {
			// Static class variable. Don't touch it.
			return $token[1];
		}

		if (!empty($this->tokens[$token_key - 2][1]) && $this->tokens[$token_key - 2][0] == T_GLOBAL) {
			//global variable
			if ($this->function && !$this->class) {
				$this->variableObfuscator->registerGlobalVariableInFunctionScope($token[1], $this->function);
			}
			elseif ($this->function && $this->class) {
				$this->variableObfuscator->registerGlobalVariableInFunctionScope($token[1], $this->function, $this->class);
			}
			return $this->variableObfuscator->renameGlobalVariable($token[1]);
		}
		elseif ($this->class && !$this->function) {
			//in class scope only
			return $this->variableObfuscator->renameAndRegisterClassProperty($token[1], $this->class, $this->scopeByLine[$token[2]]);
		}
		elseif ($this->class && $this->function) {
			return $this->variableObfuscator->renameVariableUsageInFunctionScope($token[1], $this->function, $this->class);
		}
		elseif (!$this->class && $this->function) {
			return $this->variableObfuscator->renameVariableUsageInFunctionScope($token[1], $this->function);
		}
		elseif (!$this->class && !$this->function) {
			return $this->variableObfuscator->renameGlobalVariable($token[1]);
		}
		throw new Exception('Something is wrong - this point should never be reached since all variable types are handeled above');
	}

	/**
	 * @param \PhpObfuscator_EncoderService $encoderService
	 */
	public function setEncoderService($encoderService) {
		$this->encoderService = $encoderService;
	}

	/**
	 * @return \PhpObfuscator_EncoderService
	 */
	public function getEncoderService() {
		return $this->encoderService;
	}
}

