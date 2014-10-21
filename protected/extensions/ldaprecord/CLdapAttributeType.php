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
 * CLdapAttributeType class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6
 */

/**
 * CLdapAttributeType
 *
 * CLdapAttributeType holds one LDAP attribute definition.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.4
 */
class CLdapAttributeType {
	private $_rawDefinition = null;	// original definition string
	private $_oid = '';				// attribute type identifier
	private $_names = array();		// all associated names
	private $_sup = null;			// derived attribute type

	/**
	 * Constructor.
	 * @param string $rawDefinition definition of attribute type
	 */
	public function __construct($rawDefinition) {
		$this->_rawDefinition = $rawDefinition;
		$this->parseRaw();
	}

	/**
	 * Returns the names of this attribute.
	 * @return Array list of names
	 */
	public function getNames() {
		return $this->_names;
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
		( 2.5.4.4 NAME ( 'sn' 'surname' ) DESC 'RFC2256: last (family) name(s) for which the entity is known by' SUP name )

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
		)
	 */
	private function parseRaw() {
		preg_match("/^\( (?<oid>[\d\.]+) NAME (?<name>'[^']*'|\((?: '[^']*')* \)) (?:DESC '(?<desc>[^']*)' )?(?:SUP (?<sup>\w+) )?/",
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
					$this->_names[] = str_replace('\'', '', strtolower($name));
				}
			}
		}
		//echo $this->_oid . ': ' . implode(', ', $this->_names) . '<br/>';
		if (isset($matches['sup']) && '' != $matches['sup']) {
			$this->_sup = $matches['sup'];
		}
	}

}