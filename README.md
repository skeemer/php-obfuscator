PHP Obfuscator 1.0
=============================================================================
*This script is in it's very beginning stages. Report any bugs you may find.*


Usage
-----------------------------------------------------------------------------
Refer to the testcases and the Binary


Interna:
-----------------------------------------------------------------------------
Originaly cloned from https://github.com/Southern/php-obfuscator and refactored / extended to the follwing class structure:

* ProjectObfuscator
	* Service to encode directorys recursive, it uses FileObfuscatorService

* FileObfuscatorService
	* Service that encodes a file and returns the enoced code ( ->obfuscateFile() )
	* It used an injected instance of VariableObfuscator
	* It also uses a EncoderService ( getted from VariableObfuscator to be sure the same settings are used)
	* Public flags that control how comments should be stripped

* VariableObfuscator
	* Core for renaming variables of all the diffrent types and scopes
	* for now also responsible for renaming private methods (to be refactored)
	* Public flags that controls which variables should be renamed


To-Do
-----------------------------------------------------------------------------
* More Unit Tests to proove stability and robustness
* Analyse Task: The ProjectObfuscator should first run trough all classes - so that later renaming of public methods are possible
* Configuration for ProjectObfuscator, so that certan files could be encoded stronger or lesser




