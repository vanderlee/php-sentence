<?php
	/**
	 * phpSentence
	 * Splits up text into sentences.
	 *
	 * @author Martijn W. van der Lee <martijn-at-vanderlee-dot-com>
	 * @copyright Copyright (c) 2016, Martijn W. van der Lee
	 * @license http://www.opensource.org/licenses/mit-license.php
	 */

	/**
	 * Classloader for the library
	 * @param string $class
	 */
	function Sentence_autoloader($class) {
		if (!class_exists($class) && is_file(dirname(__FILE__). '/' . $class . '.php')) {
			require dirname(__FILE__). '/' . $class . '.php';
		}
	}
	
	spl_autoload_register('Sentence_autoloader');
