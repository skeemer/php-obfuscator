<?php

require_once(dirname(__FILE__).'/../Classes/ProjectObfuscator.php');


$pO = new PhpObfuscator_ProjectObfuscator( new PhpObfuscator_FileObfuscatorService( new PhpObfuscator_VariableObfuscator( new PhpObfuscator_EncoderService() ) ) );


