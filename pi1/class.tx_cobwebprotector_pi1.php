<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Francois Suter (Cobweb) <support@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*
* $Id: class.tx_cobwebprotector_pi1.php 3283 2007-05-14 07:39:56Z fsuter $
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Cobweb Protector' for the 'cobweb_protector' extension.
 *
 * Insert this plugin on a page to have it test a series of submitted form fields
 * If all fields match expected values, the plugin does nothing. If not, however, it redirects
 * to the selected page
 *
 * @author	Francois Suter (Cobweb) <support@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_cobwebprotector
 */
class tx_cobwebprotector_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_cobwebprotector_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_cobwebprotector_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'cobweb_protector';	// The extension key.
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!

// Get the type of condition to be applied to all checks (AND or OR)
// The default behaviour is AND

		if (isset($this->conf['condition'])) {
			$condition = strtolower($this->conf['condition']);
		}
		else {
			$condition = 'and';
		}

// Read the list of fields to check
// Fields are stored as an array with each a name and a value property

		if (isset($this->conf['fields.'])) {
			$fields = $this->conf['fields.'];
			if (is_array($fields)) {
				foreach ($fields as $fieldInfo) { // Loop on all the fields
					if (isset($fieldInfo['value.'])) { // Value is a stdWrap, interpret it
						$testValue = $this->cObj->stdWrap('',$fieldInfo['value.']);
					}
					else { // Value is not stdWrap
						if (isset($fieldInfo['value'])) { // Value is defined, use it
							$testValue = $fieldInfo['value'];
						}
						else { // Value is not defined, set test value to false for later
							$testValue = false;
						}
					}
					if (empty($fieldInfo['source'])) {
						$source = 'post';
					}
					else {
						$source = strtolower($fieldInfo['source']);
					}
					if (empty($fieldInfo['name'])) {
						continue; // Skip test if name is not defined
					}
					else {
						switch ($source) {
							case 'get':
								$variable = t3lib_div::_GET($fieldInfo['name']);
								break;
							case 'env':
								$variable = t3lib_div::getIndpEnv($fieldInfo['name']);
								break;
							default: // Default is post
								$variable = t3lib_div::_POST($fieldInfo['name']);
								break;
						}
					}
					if ($testValue === false) { // If the field has no value, the variable must not be empty
						$localResult = !empty($variable);
					}
					else { // If the value is defined, the variable must match it, according to the test defined
						if (empty($fieldInfo['test'])) {
							$test = 'eq'; // Make sure test is not empty
						}
						else {
							$test = strtolower($fieldInfo['test']);
						}
						switch ($test) {
							case 'ne':
								$localResult = $variable != $testValue;
								break;
							case 'le':
								$localResult = $variable <= $testValue;
								break;
							case 'lt':
								$localResult = $variable < $testValue;
								break;
							case 'ge':
								$localResult = $variable >= $testValue;
								break;
							case 'gt':
								$localResult = $variable > $testValue;
								break;
							case 'in':
							case 'co':
								if ($test == 'in') {
									$stringResult = strpos($testValue,$variable);
								}
								else {
									$stringResult = strpos($variable,$testValue);
								}
								if ($stringResult === false) {
									$localResult = false;
								}
								else {
									$localResult = true;
								}
								break;
							default:
								$localResult = $variable == $testValue;
								break;
						}
					}

// Assemble global result by ANDing or ORing each test result

					if (!isset($globalResult)) {
						$globalResult = $localResult;
					}
					else {
						if ($condition == 'or') {
							$globalResult |= $localResult;
						}
						else {
							$globalResult &= $localResult;
						}
					}
				}
			}
		}
		else { // If no fields are defined, nothing is done and the test is true anyway
			$globalResult = true;
		}

// If result of check is false, redirect to selected page

		if (!$globalResult) {
			$url = t3lib_div::locationHeaderUrl($this->cObj->getTypoLink_URL($this->cObj->data['pages']));
			header('Location: '.$url);
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cobweb_protector/pi1/class.tx_cobwebprotector_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cobweb_protector/pi1/class.tx_cobwebprotector_pi1.php']);
}

?>