<?php

require_once(dirname(__FILE__) . '/FileObfuscatorService.php');

/**
 * Service that encodes/Obfuscate folders
 */
class PhpObfuscator_ProjectObfuscator {
	private $projectDir;
	private $outputDir;
	/**
	 * @var PhpObfuscator_FileObfuscatorService
	 */
	private $fileObfuscatorService;


	/**
	 * @param PhpObfuscator_FileObfuscatorService $fileObfuscatorService
	 */
	public function __construct(PhpObfuscator_FileObfuscatorService $fileObfuscatorService ) {
		$this->fileObfuscatorService = $fileObfuscatorService;
	}

	public function setOutputDir($outputDir) {
		if (!is_dir($outputDir)) {
			@mkdir($outputDir);
		}
		if (!is_dir($outputDir)) {
			throw new InvalidArgumentException('No valid target dir');
		}
		$this->outputDir = $this->appendDirectorySeperator($outputDir);
	}

	public function getOutputDir() {
		return $this->outputDir;
	}

	public function setProjectDir($projectDir) {
		if (!is_dir($projectDir)) {
			throw new InvalidArgumentException('No valid source dir');
		}
		$this->projectDir = $this->appendDirectorySeperator($projectDir);
	}

	public function getProjectDir() {
		return $this->projectDir;
	}

	private function appendDirectorySeperator($dir) {
		if (substr($dir,-1) != '/') {
			return $dir.'/';
		}
		return $dir;
	}

	public function process() {
		if (!isset($this->projectDir)) {
			throw new Exception('Project Dir not Set!');
		}
		if (isset($this->outputDir)) {
			$output = $this->outputDir;
		}
		else {
			//Encoding in folder directly!
			$output = $this->projectDir;
		}

		$this->processDirectory($this->projectDir,$this->outputDir);
	}

	private function processDirectory($from, $to) {
		//Now go to Directory and load all Files
		$handle = @opendir($from);
		if (!$handle) {
			throw new InvalidArgumentException('Could not open file handle for dir:'. $from);
		}
		while (false !== ($file = readdir($handle))) {
			if (is_dir($from . $file) && $file != "." && $file != "..") {
				$this->processDirectory($from . $file . '/', $to . $file . '/');
			}
			elseif (is_file($from . $file)) {
				echo 'Processing '.$from.$file. ' ==> '.$to.$file.PHP_EOL;
				file_put_contents($to . $file, $this->fileObfuscatorService->obfuscateFile($from . $file));
			}
		}
		closedir($handle);
	}

	/**
	 * @param PhpObfuscator_FileObfuscatorService $fileObfuscatorService
	 */
	public function setFileObfuscatorService($fileObfuscatorService) {
		$this->fileObfuscatorService = $fileObfuscatorService;
	}

	/**
	 * @return PhpObfuscator_FileObfuscatorService
	 */
	public function getFileObfuscatorService() {
		return $this->fileObfuscatorService;
	}
}

