<?php

/**
 * This class saves informations about variables, scopes and renamed variables
 * You can control it with certain flags
 */
class PhpObfuscator_VariableObfuscator {

	public $renameProtectedClassProperties = false;
	public $renamePublicClassProperties = false;
	public $renamePrivateClassProperties = true;

	public $renamePublicVariables = false;
	public $renamePrivateClassMethods = true;


	private $renamedClassProperties;
	private $renamedVariablesByScope;
	private $renamedGlobalVariables;
	private $renamedClassMethods;

	private $skip_variables = array('$_GET', '$_POST', '$_REQUIRE', '$_SERVER', '$_ENV', '$_SESSION', '$_FILES', '$_COOKIE');

	private $phpMagicMethods = array('__sleep','__construct','__wakeup');
	/**
	 * @var PhpObfuscator_EncoderService
	 */
	private $encoderService;


	public function __construct(PhpObfuscator_EncoderService $encoderService) {
		$this->encoderService = $encoderService;
	}

	public function renameAndRegisterMethod($name, $class, $accessScope) {
		if (in_array($name, $this->phpMagicMethods)) {
			return $name;
		}

		if ($this->renamePrivateClassMethods && $accessScope == T_PRIVATE) {
			$this->renamedClassMethods[$class][$name] = $name.$this->encoderService->getRandomString();
		}
		else {
			//$this->renamedClassMethods[$class][$name] = $this->encoderService->encodeString($name);
		}

		return $this->getRenamedMethodNameIfRegistered($name, $class);
	}

	public function getRenamedMethodNameIfRegistered($name, $class) {
		if (isset($this->renamedClassMethods[$class][$name])) {
			return $this->renamedClassMethods[$class][$name];
		}
		return $name;
	}

	public function renameAndRegisterClassProperty($propertyName,$class,$accessScope) {
		if ($this->renamePrivateClassProperties && $accessScope == T_PRIVATE) {
			$this->renamedClassProperties[$class][$propertyName] = $this->generateNewVariableName();
		}
		else {
			$this->renamedClassProperties[$class][$propertyName] = "\${$this->encoderService->encodeString(substr($propertyName,1))}";
		}
		return $this->getRenamedClassPropertyIfRegistered($propertyName, $class);
	}

	public function getRenamedClassPropertyIfRegistered($propertyName, $class) {
		if (isset($this->renamedClassProperties[$class][$propertyName])) {
			return $this->renamedClassProperties[$class][$propertyName];
		}
		return $propertyName;
	}

	public function renameGlobalVariable($variableName) {
		if (in_array($variableName, $this->skip_variables)) {
			// Skip renaming anything that should be ignored, but encode it so that it's not in plaintext.
			return "\${$this->encoderService->encodeString(substr($variableName,1))}";
		}
		if (!isset($this->renamedGlobalVariables[$variableName])) {
			if ($this->renameGlobalVariables) {
				$this->renamedGlobalVariables[$variableName] = $this->generateNewVariableName();
			}
			else {
				$this->renamedGlobalVariables[$variableName] = "\${$this->encoderService->encodeString(substr($variableName,1))}";
			}
		}
		return $this->renamedGlobalVariables[$variableName];
	}

	public function registerGlobalVariableInFunctionScope($variableName, $functionName, $class = 'NOCLASS_GLOBAL') {
		$this->globalVariablesRegistry[$class][$functionName][$variableName] = true;
	}

	public function renameVariableUsageInFunctionScope($variableName,$functionName, $class = 'NOCLASS_GLOBAL') {
		if (in_array($variableName, $this->skip_variables)) {
			// Skip renaming anything that should be ignored, but encode it so that it's not in plaintext.
			return "\${$this->encoderService->encodeString(substr($variableName,1))}";
		}
		if (isset($this->globalVariablesRegistry[$class][$functionName][$variableName])) {
			return $this->renameGlobalVariable($variableName);
		}
		if (!isset($this->renamedVariables[$class][$functionName][$variableName])) {
			$this->renamedVariables[$class][$functionName][$variableName] = $this->generateNewVariableName();
		}
		return $this->renamedVariables[$class][$functionName][$variableName];
	}



	private function encode($tmp) {
		if ($this->stripWhitespaces) $tmp = preg_replace('/[\n\t\s]+/', ' ', $tmp);
		$tmp = preg_replace('/^\<\?(php)*/', '', $tmp);
		$tmp = preg_replace('/\?\>$/', '', $tmp);
		$tmp = str_replace(array('\"', '$', '"'), array('\\\"', '\$', '\"'), $tmp);
		$tmp = trim($tmp);
		if ($this->b64) {
			$tmp = base64_encode("$tmp");
			$tmp = "<?php \$code=base64_decode(\"$tmp\"); eval(\"return eval(\\\"\$code\\\");\") ?>\n";
		} else $tmp = "<?php eval(eval(\"$tmp\")); ?>\n";
		$this->code = $tmp;
	}

	private function encode_string($text) {
		if (!$this->encodeString) {
			return $text;
		}
		for ($i = 0; $i <= strlen($text) - 1; $i++) {
			$chr = ord(substr($text, $i, 1));
			if ($chr == 32 || $chr == 34 || $chr == 39) $tmp[] = chr($chr); // Space, leave it alone.
			elseif ($chr == 92 && preg_match('/\\\(n|t|r|s)/', substr($text, $i, 2))) {
				// New line, leave it alone, and add the next char with it.
				$tmp[] = substr($text, $i, 2);
				$i++; // Skip the next character.
			}
			else $tmp[] = '\x' . strtoupper(base_convert($chr, 10, 16));
		}
		if (!empty($tmp)) $text = implode('', $tmp);
		return $text;
	}

	private function generateNewVariableName() {
		return "\$_{$this->encoderService->getRandomString()}";
	}

	/**
	 * @return \PhpObfuscator_EncoderService
	 */
	public function getEncoderService() {
		return $this->encoderService;
	}

}

