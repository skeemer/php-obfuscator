<?php

/**
 * System functionality to encode strings
 */
class PhpObfuscator_EncoderService {

	private $algos;

	public $encodeString = true; // if normal strings should be unicode encoded
	public $stripWhitespaces = true;
	public $b64 = true; // Base64 passover

	public function __construct() {
		$this->algos = hash_algos();
	}

	public function getRandomString() {
		$number = round((mt_rand(1, mt_rand(1000, 10000)) * mt_rand(1, 10)) / mt_rand(1, 10));
		if (!empty($this->algos)) $algo = $this->algos[mt_rand(0, (count($this->algos) - 1))];
		$hash = hash($algo, $number);
		return $hash;
	}

	public function encode($tmp) {
		if ($this->stripWhitespaces) $tmp = preg_replace('/[\n\t\s]+/', ' ', $tmp);
		$tmp = preg_replace('/^\<\?(php)*/', '', $tmp);
		$tmp = preg_replace('/\?\>$/', '', $tmp);
		$tmp = str_replace(array('\"', '$', '"'), array('\\\"', '\$', '\"'), $tmp);
		$tmp = trim($tmp);
		if ($this->b64) {
			$tmp = base64_encode("$tmp");
			$tmp = "<?php \$code=base64_decode(\"$tmp\"); eval(\"return eval(\\\"\$code\\\");\"); ?>\n";
		} else $tmp = "<?php eval(eval(\"$tmp\")); ?>\n";
		return $tmp;
	}

	public function encodeString($text) {
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
}

