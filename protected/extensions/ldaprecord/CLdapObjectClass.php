<?php
/*
 * Copyright (C) 2006 - 2014 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *
 * Licensed under the EUPL, Version 1.1 or higher - as soon they
 * will be approved by the European Commission - subsequent
 * versions of the EUPL (the "Licence");
 * You may not use this work except in compliance with the
 * Licence.
 * You may obtain a copy of the Licence at:
 *
 * https://joinup.ec.europa.eu/software/page/eupl
 *
 * Unless required by applicable law or agreed to in
 * writing, software distributed under the Licence is
 * distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied.
 * See the Licence for the specific language governing
 * permissions and limitations under the Licence.
 *
 *
 */

/**
 * CLdapObjectClass class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6
 */

/**
 * CLdapObjectClass
 *
 * CLdapObjectClass holds one LDAP object definition.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.4
 */
class CLdapObjectClass {
	private $_rawDefinition = null;	// original definition string
	private $_oid = '';				// object class identifier
	private $_names = array();		// all associated names
	private $_attributes = array();	// definition of the attributes
	private $_sup = null;			// derived attribute type

	/**
	 * Constructor.
	 * @param string $rawDefinition definition of object class
	 */
	public function __construct($rawDefinition) {
		$this->_rawDefinition = $rawDefinition;
		$this->parseRaw();
	}

	/**
	 * Returns the names of this object class.
	 * @return Array list of names
	 */
	public function getNames() {
		return $this->_names;
	}

	/**
	 * Returns the attribute definitions.
	 * @return Array list of names
	 */
	public function getAttributes() {
		return $this->_attributes;
	}

	/**
	 * Returns the name of the derived attribute.
	 * @return Array list of names
	 */
	public function getSup() {
		return $this->_sup;
	}
	/*
	 * Example
		( 1.3.6.1.4.1.4203.666.11.1.4.3.4.1 NAME ( 'name1' 'name2' ) DESC 'Description with multiple words' SUP parent STRUCTURAL MUST ( must1 $ must2 $ must3 ) MAY ( may1 $ may2 $ may3 ) )
		Array
		(
		    [0] => ( 1.3.6.1.4.1.4203.666.11.1.4.3.4.1 NAME ( 'name1' 'name2' ) DESC 'Description with multiple words' SUP parent STRUCTURAL MUST ( must1 $ must2 $ must3 ) MAY ( may1 $ may2 $ may3 )
		    [1] => 1.3.6.1.4.1.4203.666.11.1.4.3.4.1
		    [2] => ( 'name1' 'name2' )
		    [3] =>  'name2'
		    [4] => DESC 'Description with multiple words'
		    [5] => Description with multiple words
		    [6] => SUP parent
		    [7] => parent
		    [8] => STRUCTURAL
		    [9] => MUST ( must1 $ must2 $ must3 )
		    [10] =>  ( must1 $ must2 $ must3 )
		    [11] =>  must2 $
		    [12] => MAY ( may1 $ may2 $ may3 )
		    [13] =>  ( may1 $ may2 $ may3 )
		    [14] =>  may2 $
		)
	 */
	private function parseRaw() {
		//preg_match("/^\( ([\d\.]+) NAME ('[^']*'|\(( '[^']*')* \)) (DESC '([^']*)' )?(SUP (\w+) )?(ABSTRACT |STRUCTURAL |AUXILIARY )(MUST( \w* | \(( \w+ [$])* \w+ \) ))?(MAY( \w* | \(( \w+ [$])* \w+ \) ))?/",
		//preg_match("/^\( ([\d\.]+) NAME ('[^']*'|\(( '[^']*')* \)) (DESC '([^']*)' )?(SUP (\w+) )?(ABSTRACT |STRUCTURAL |AUXILIARY )(MUST\s?(\(?\s?\w*\s?|(\s?\S+ \$ )* \S+\s?\)?))?/",
		preg_match("/^\( (?<oid>[\d\.]+) NAME (?<name>'[^']*'|\((?: '[^']*')* \)) (?:DESC '(?<desc>[^']*)' )?(?<obs>OBSOLETE )?(?:SUP (?<sup>\w+) )?(?:(?<type>ABSTRACT |STRUCTURAL |AUXILIARY ))?(MUST \(?\s?(?<must>[a-zA-Z0-9_]+(?: [$] [a-zA-Z0-9_]+)*)\s?\)? )?(MAY \(?\s?(?<may>[a-zA-Z0-9_]+(?: [$] [a-zA-Z0-9_]+)*)\s?\)?)?/",
		$this->_rawDefinition, $matches);
		//echo count($matches) . ' ' . $this->_rawDefinition . '<br/>';
		//echo '<pre>' . print_r($matches, true) . '</pre>';
		if (isset($matches['oid']) && '' != $matches['oid']) {
			$this->_oid = $matches['oid'];
		}
		if (isset($matches['name']) && '' != $matches['name']) {
			preg_match("/\(? ?([\w '-]*) ?\)?/", $matches['name'], $names);
			$names = explode(' ', $names[1]);
			foreach($names as $name) {
				if (0 < strlen($name)) {
					$this->_names[] = str_replace('\'', '', $name);
				}
			}
		}
		if (isset($matches['sup']) && '' != $matches['sup']) {
			$this->_sup = $matches['sup'];
		}
		$schema = CLdapServer::getInstance()->getSchema();
		if (isset($matches['must']) && '' != $matches['must']) {
			/* must attrs */
			$attrs = explode(' $ ', $matches['must']);
			foreach ($attrs as $attr) {
				$name = strtolower(trim($attr));
				$this->_attributes[$name] = array('mandatory' => true, 'type' => '', 'value' => null);
				$attrtype = $schema->getAttributeType($name);
				if (!is_null($attrtype)) {
					foreach($attrtype->getNames() as $n) {
						if ($name == $n) continue;
						$this->_attributes[$n] = array('alias' => $name, 'value' => null);
					}
				}
				// ToDo: handle attrtype is null
			}
		}
		if (isset($matches['may']) && '' != $matches['may']) {
			/* may attrs */
			$attrs = explode(' $ ', $matches['may']);
			foreach ($attrs as $attr) {
				$name = strtolower(trim($attr));
				$this->_attributes[$name] = array('mandatory' => false, 'type' => '', 'value' => null);
				$attrtype = $schema->getAttributeType($name);
				if (!is_null($attrtype)) {
					foreach($attrtype->getNames() as $n) {
						if ($name == $n) continue;
						$this->_attributes[$n] = array('alias' => $name, 'value' => null);
					}
				}
				// ToDo: handle attrtype is null
			}
		}
	}

}