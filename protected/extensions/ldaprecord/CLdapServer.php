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
 * CLdapServer
 *
 * CLdapServer holds all the defined object classes.
 *
 * The used design pattern is Singleton. To get the one and
 * only instance of this class call CLdapServer::getInstance().
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.4
 */
class CLdapServer {
	/**
	 * @var CLdapServer static .
	 */
	protected static $_instance = null;

	/**
	 * @var array with configuration params.
	 */
	protected $_config = null;
	/**
	 * @var Ldap link identifier.
	 */
	protected $_connection = null;
	/**
	 * @var
	 */
	protected $_anonymous = false;

	/**
	 * @var CLdapSchema associated Schema
	 */
	protected $_schema = null;


	/**
	 * Constructor private
	 *
	 * establish connection to Ldap server
	 */
	protected function __construct() {
		$this->_config = array();
		$comp = Yii::app()->getComponent('ldap');
		$this->_config['server'] = $comp->server;
		$this->_config['port'] = $comp->port;
		$this->_config['base_dn'] = $comp->base_dn;
		$this->_config['passwordtype'] = $comp->passwordtype;
		if (isset($comp->bind_rdn)) {
			$this->_config['bind_rdn'] = $comp->bind_rdn;
		}
		if (isset($comp->bind_pwd)) {
			$this->_config['bind_pwd'] = $comp->bind_pwd;
		}
		if (null != $this->_config) {
			$this->_connection = @ldap_connect($this->_config['server'], $this->_config['port']) or die('LDAP connect failed!');
			if ($this->_connection === false) {
				throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_connect to {server} failed', array('{server}'=>$this->_config['server'])));
			}

			ldap_set_option($this->_connection, LDAP_OPT_PROTOCOL_VERSION, 3);

			if (isset($comp->bind_rdn) && isset($comp->bind_pwd)) {
				$ldapbind = @ldap_bind($this->_connection, $this->_config['bind_rdn'], $this->_config['bind_pwd']);
			}
			else {
				$this->_anonymous = true;
				$ldapbind = @ldap_bind($this->_connection);
			}
			if ($ldapbind === false) {
				throw new CLdapException(
				Yii::t('LdapComponent.server', 'ldap_bind failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
			}
		}
	}

	/**
	 * Return the schema for this server.
	 *
	 * @return CLdapSchema the schema.
	 */
	public function getSchema() {
		if (is_null($this->_schema)) {
			$this->_schema = new CLdapSchema();
			$this->getDefinitions();
		}
		return $this->_schema;
	}

	/**
	 * Get all defined objectclasses and attributetypes from LDAP server.
	 *
	 * @return array [objectclasses]=>CLdapObjectClass, [attributetypes]=>CLdapAttributeType
	 * @throws CLdapException if the LDAP server generates an error.
	 */
	public function getDefinitions() {
		$result = @ldap_read($this->_connection,$this->_config['base_dn'],'objectClass=*',array('subschemaSubentry'),false,0,10,LDAP_DEREF_NEVER);
		if ($result === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_read failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		$entries = @ldap_get_entries($this->_connection, $result);
		if ($entries === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_get_entries failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		$entry = $entries[0];
		$subschemasubentry = $entry[$entry[0]][0];

		$result = @ldap_read($this->_connection, $subschemasubentry,'objectClass=*',array('objectclasses','attributetypes'),false,0,10,LDAP_DEREF_NEVER);
		if ($result === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_read failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		$entries = @ldap_get_entries($this->_connection, $result);
		//echo '<pre>' . print_r($entries, true) . '</pre>';
		if ($entries === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_get_entries failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		$objectclasses = array();
		$attributetypes = array();
		for($i=0; $i<$entries[0]['count']; $i++) {
			if ('attributetypes' == $entries[0][$i]) {
				$attrtypes = $entries[0][$entries[0][$i]];
				for ($j=0; $j<$attrtypes['count']; $j++) {
					$attrtype = new CLdapAttributeType($attrtypes[$j]);
					foreach($attrtype->getNames() as $name) {
						$this->_schema->addAttributeType($name, $attrtype);
					}
				}
			}
		}
		// We need all attributes before parsing the objectclasses
		for($i=0; $i<$entries[0]['count']; $i++) {
			if ('objectclasses' == $entries[0][$i]) {
				$objclasses = $entries[0][$entries[0][$i]];
				for ($j=0; $j<$objclasses['count']; $j++) {
					$objclass = new CLdapObjectClass($objclasses[$j]);
					foreach($objclass->getNames() as $name) {
						$this->_schema->addObjectClass($name, $objclass);
					}
				}
			}
		}
	}

	/**
	 * Return the base Dn from configuration.
	 *
	 * @return string with base Dn.
	 */
	public function getBaseDn() {
		return $this->_config['base_dn'];
	}

	/**
	 * Return encryption type for password encoding
	 *
	 * @return string type.
	 */
	public function getEncryptionType() {
		return $this->_config['passwordtype'];
	}

	/**
	 * Return if connection is anonymous.
	 *
	 * @return boolean is anonymous.
	 */
	public function isAnonymous() {
		return $this->_anonymous;
	}

	/**
	 * Finds all ldap records satisfying the specified condition (one level only.).
	 *
	 * @param CLdapRecord $model
	 * @param array $criteria
	 * @return array a complete result information in a multi-dimensional array on success and false on error.
	 * @throws CLdapException if the LDAP server generates an error.
	 */
	public function findAll($model, $criteria=array('attr'=>null)) {
		if (isset($criteria['attr'])) {
			if (!is_null($criteria['attr'])) {
				$filter = '(&';
				foreach ($criteria['attr'] as $key => $value) {
					if ('' != $value) {
						if (is_array($value)) {
							$filter .= '(|';
							foreach($value as $part) {
								$filter .= "($key=$part)";
							}
							$filter .= ')';
						} 
						else if ('*' == $value) {
							$filter .= "($key=*)";
						}
						else {
							$filter .= "($key=$value)";
						}
					}
				}
				$filter .= ')';
				if ('(&)' == $filter) {
					$filter = $model->getFilter('all');
				}
			}
		}
		else if (isset($criteria['filter'])) {
			$filter = $criteria['filter'];
		}
		else if (isset($criteria['filterName'])) {
			$filter = $model->getFilter($criteria['filterName']);
		}
		else {
			throw new CLdapException(Yii::t('LdapComponent.server', 'findAll: neither attr nor filter set in criteria!'));
		}
		if (strpos($criteria['branchDn'], $this->_config['base_dn']) === false) {
			$branchDn = $criteria['branchDn'] . ',' . $this->_config['base_dn'];
		}
		else {
			$branchDn = $criteria['branchDn'];
		}
		Yii::log("findAll: branchDn: $branchDn", 'profile', 'ext.ldaprecord.CLdapServer');
		Yii::log("findAll: filter: $filter", 'profile', 'ext.ldaprecord.CLdapServer');
		if (!is_null($model) && !$model instanceof LdapNameless) {
			Yii::log("findAll: attrs: " . print_r($model->attributeNames(), true), 'info', 'ext.ldaprecord.CLdapServer');
		}
		if (isset($criteria['depth']) && $criteria['depth']) {
			if (!is_null($model) && !$model instanceof LdapNameless) {
				$result = @ldap_search($this->_connection, $branchDn, $filter, $model->attributeNames());
			}
			else {
				$result = @ldap_search($this->_connection, $branchDn, $filter);
			}
			if ($result === false) {
				throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_search failed ({errno}): {message}',
					array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
			}
		}
		else {
			if (!is_null($model) && !$model instanceof LdapNameless) {
				$result = @ldap_list($this->_connection, $branchDn, $filter, $model->attributeNames());
			}
			else {
				$result = @ldap_list($this->_connection, $branchDn, $filter);
			}
			if ($result === false) {
				throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_list failed ({errno}): {message}',
					array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
			}
		}
		$entries = @ldap_get_entries($this->_connection, $result);
		Yii::log('findAll: entries: ' . $entries['count'], 'profile', 'ext.ldaprecord.CLdapServer');
		if ($entries === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_get_entries failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		return $entries;
	}

	/**
	 * Finds all ldap records satisfying the specified condition (subtree.).
	 *
	 * @param CLdapRecord $model
	 * @param array $criteria
	 * @return array a complete result information in a multi-dimensional array on success and false on error.
	 * @throws CLdapException if the LDAP server generates an error.
	 */
	public function findSubTree($model, $criteria) {
		$filter = '(&';
		if (isset($criteria['attr'])) {
			foreach ($criteria['attr'] as $key => $value) {
				if ('' != $value) {
					if ('*' == $value) {
						$filter .= "($key=*)";
					}
					else {
						$filter .= "($key=$value)";
					}
				}
			}
		}
		$filter .= ')';
		if ('(&)' == $filter) {
			$filter = $model->getFilter('all');
		}
		if (strpos($criteria['branchDn'], $this->_config['base_dn']) === false) {
			$branchDn = $criteria['branchDn'] . ',' . $this->_config['base_dn'];
		}
		else {
			$branchDn = $criteria['branchDn'];
		}
		Yii::log("findSubTree: branchDn: $branchDn", 'profile', 'ext.ldaprecord.CLdapServer');
		Yii::log("findSubTree: filter: $filter", 'profile', 'ext.ldaprecord.CLdapServer');
		$result = @ldap_search($this->_connection, $branchDn, $filter);
		if ($result === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_search failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		$entries = @ldap_get_entries($this->_connection, $result);
		Yii::log('findSubTree: entries: ' . count($entries), 'profile', 'ext.ldaprecord.CLdapServer');
		if ($entries === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_get_entries failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		return $entries;
	}

	/**
	 * Search LDAP (Wrapper to LDAP function ldap_search).
	 *
	 * @param array $base_dn
	 * @param array $filter
	 * @param array $attributes
	 * @return array a complete result information in a multi-dimensional array on success and false on error.
	 * @throws CLdapException if the LDAP server generates an error.
	 * @since 0.6
	 */
	public function search($base_dn, $filter, $attributes=array()) {
		if (strpos($base_dn, $this->_config['base_dn']) === false) {
			$base_dn = $base_dn . ',' . $this->_config['base_dn'];
		}
		Yii::log("search: baseDn: $base_dn", 'profile', 'ext.ldaprecord.CLdapServer');
		Yii::log("search: filter: $filter", 'profile', 'ext.ldaprecord.CLdapServer');
		$result = @ldap_search($this->_connection, $base_dn, $filter, $attributes);
		if ($result === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_search failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		$entries = @ldap_get_entries($this->_connection, $result);
		Yii::log('search: entries: ' . $entries['count'], 'profile', 'ext.ldaprecord.CLdapServer');
		if ($entries === false) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_get_entries failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		return $entries;
	}

	/**
	 * Find one ldap record satisfying the Dn.
	 *
	 * @param string $dn
	 * @param CLdapRecord $model
	 * @return array a complete result information in a multi-dimensional array on success and false on error.
	 * @throws CLdapException if the LDAP server generates an error.
	 */
	public function findByDn($dn, $model=null) {
		if (strpos($dn, $this->_config['base_dn']) === false) {
			$dn = $dn . ',' . $this->_config['base_dn'];
		}
		Yii::log("findByDn: $dn", 'profile', 'ext.ldaprecord.CLdapServer');
		if (is_null($model)) {
			$result = @ldap_read($this->_connection, $dn, '(objectclass=*)');
		}
		else {
			$result = @ldap_read($this->_connection, $dn, '(objectclass=*)', $model->attributeNames());
		}
		if ($result === false) {
			return null;
//			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_read failed ({errno}): {message}',
//				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		$entries = @ldap_get_entries($this->_connection, $result);
		Yii::log('findByDn: entries: ' . $entries['count'], 'profile', 'ext.ldaprecord.CLdapServer');
		if ($entries === false) {
			Yii::log('findByDn failed (' . ldap_errno($this->_connection) . '): ' . ldap_error($this->_connection), 'profile', 'ext.ldaprecord.CLdapServer');
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_get_entries failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		return $entries;
	}

	/**
	 * Modify an existing leaf with defined Dn.
	 *
	 * @param string $dn
	 * @param array all attributes as key->value
	 * @return boolean success.
	 * @throws CLdapException if the LDAP server generates an error.
	 */
	public function modify($dn, $entry) {
		if (strpos($dn, $this->_config['base_dn']) === false) {
			$dn = $dn . ',' . $this->_config['base_dn'];
		}
		Yii::log("modify: $dn\n" . print_r($entry, true), 'profile', 'ext.ldaprecord.CLdapServer');
		$retval = @ldap_modify($this->_connection, $dn, $entry);
		if (!$retval) {
			Yii::log('modify $dn failed (' . ldap_errno($this->_connection) . '): ' . ldap_error($this->_connection), 'profile', 'ext.ldaprecord.CLdapServer');
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_modify failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}

		return true;
	}

	public function modify_del($dn, $entry) {
		if (strpos($dn, $this->_config['base_dn']) === false) {
			$dn = $dn . ',' . $this->_config['base_dn'];
		}
		Yii::log("modify_del: $dn\n" . print_r($entry, true), 'profile', 'ext.ldaprecord.CLdapServer');
		$retval = @ldap_mod_del($this->_connection, $dn, $entry);
		if (!$retval) {
			Yii::log('mod_del $dn failed (' . ldap_errno($this->_connection) . '): ' . ldap_error($this->_connection), 'profile', 'ext.ldaprecord.CLdapServer');
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_mod_del failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}

		return true;
	}

	public function move($dn, $newDn, $parent)
	{
		//echo "dn: $dn<br/>$newDn<br/>$parent<br/>";
		Yii::log("move: $dn new: $newDn; parent: $parent", 'profile', 'ext.ldaprecord.CLdapServer');
		$retval = @ldap_rename($this->getConnection(), $dn, $newDn, $parent, true);

		return true;
	}

	/**
	 * Add a new leaf with defined Dn.
	 *
	 * @param string $dn
	 * @param array all attributes as key->value
	 * @return boolean success.
	 * @throws CLdapException if the LDAP server generates an error.
	 */
	public function add($dn, $entry) {
		if (strpos($dn, $this->_config['base_dn']) === false) {
			$dn = $dn . ',' . $this->_config['base_dn'];
		}
		Yii::log("add: $dn\n" . print_r($entry, true), 'profile', 'ext.ldaprecord.CLdapServer');
		$retval = @ldap_add($this->_connection, $dn, $entry);
		if (!$retval) {
			Yii::log('add $dn failed (' . ldap_errno($this->_connection) . '): ' . ldap_error($this->_connection), 'profile', 'ext.ldaprecord.CLdapServer');
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_add failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}

		return true;
	}

	/**
	 * Delete a leaf with defined Dn.
	 *
	 * @param string $dn
	 * @param boolean $recursive true: delete all sub leafs and dn; default: false
	 * @param boolean $keepDn true: don't delete dn on recursive delete; default: false; since 1.0.0
	 * @return boolean success.
	 * @throws CLdapException if the LDAP server generates an error.
	 * @since 0.6.1
	 */
	public function delete($dn, $recursive=false, $keepDn=false) {
		if (strpos($dn, $this->_config['base_dn']) === false) {
			$dn = $dn . ',' . $this->_config['base_dn'];
		}
		Yii::log("delete: $dn" . ($recursive ? ' recursive' : ''), 'profile', 'ext.ldaprecord.CLdapServer');

		if($recursive == false) {
        	$retval = @ldap_delete($this->_connection, $dn);
		}
		else {
			//searching for sub entries
			$result = @ldap_list($this->_connection, $dn, 'ObjectClass=*', array(''));
			if ($result === false) {
				throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_list failed ({errno}): {message}',
					array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
			}
			$entries = @ldap_get_entries($this->_connection, $result);
			if ($entries === false) {
				throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_get_entries failed ({errno}): {message}',
					array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
			}
			$retval = true;
			for($i=0; $i<$entries['count']; $i++){
				//deleting recursively sub entries
				$result = $this->delete($entries[$i]['dn'], $recursive /* don't add $keepDn*/);
				if(!$result){
					//return result code, if delete fails
					return($result);
				}
			}
			if (!$keepDn) {
				$retval = @ldap_delete($this->_connection, $dn);
			}
		}
		if (!$retval) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_delete failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}
		return $retval;
	}

	/**
	 * Change Dn.
	 * @param string $dn the old Dn.
	 * @param string $newDn the new Dn.
	 * @return boolean success.
	 * @throws CLdapException if the LDAP server generates an error.
	 */
	public function rename($dn, $newDn) {
		//$newDn .= ',' . $this->_config['base_dn'];
		//echo "dn: $dn $newDn</pre>";
		Yii::log("rename: $dn new: $newDn", 'profile', 'ext.ldaprecord.CLdapServer');
		$retval = @ldap_rename($this->_connection, $dn, $newDn, null, false);
		if (!$retval) {
			throw new CLdapException(Yii::t('LdapComponent.server', 'ldap_rename failed ({errno}): {message}',
				array('{errno}'=>ldap_errno($this->_connection), '{message}'=>ldap_error($this->_connection))), ldap_errno($this->_connection));
		}

		return true;
	}

	/**
	 * Close the connection to LDAP server.
	 * This should be done after the lasst action.
	 * Good idea is to call this in the base class of your Yii Controller
	 *
	 * <code>
	 * protected function afterAction($action)
	 * {
	 *    if (CLdapServer::hasInstance()) {
	 *       CLdapServer::getInstance()->close();
	 *    }
	 * }
	 * </code>
	 *
	 * @return void
	 */
	public function close() {
		if (!is_null($this->_connection))
		ldap_unbind($this->_connection);
	}

	/**
	 * Static method which returns the singleton instance of this class.
	 *
	 * @return CLdapServer
	 */
	public static function getInstance() {
		if (is_null(self::$_instance)) {
			$comp = Yii::app()->getComponent('ldap');
			$serverclass = 'CLdapServer';
			if (!is_null($comp->serverclass) && class_exists($comp->serverclass)) {
				$serverclass = $comp->serverclass;
			}
			self::$_instance = new $serverclass();
		}
		return self::$_instance;
	}

	/**
	 * Is there an instance of this class?
	 *
	 * @return boolean whether the instance was created or not
	 */
	public static function hasInstance() {
		return !is_null(self::$_instance);
	}

	/**
	 * Don't allow cloning of this class from outside
	 */
	private function __clone() {}
}
