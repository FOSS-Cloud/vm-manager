<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *
 * Licensed under the EUPL, Version 1.1 or – as soon they
 * will be approved by the European Commission - subsequent
 * versions of the EUPL (the "Licence");
 * You may not use this work except in compliance with the
 * Licence.
 * You may obtain a copy of the Licence at:
 *
 * http://www.osor.eu/eupl
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
 *
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6
 */

/**
 * CLdapRecord
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
abstract class CLdapRecord extends CModel {
	const BELONGS_TO_DN='CLdapBelongsToDN';
	const HAS_ONE='CLdapHasOne';
	const HAS_ONE_DN='CLdapHasOneDn';
	const HAS_ONE_DEPTH='CLdapHasOneDepth';
	const HAS_ONE_DEPTH_BRANCH='CLdapHasOneDepthBranch';
	const HAS_MANY='CLdapHasMany';
	const HAS_MANY_DEPTH='CLdapHasManyDepth';

	protected $_dn = null;						// DN of this node
	private $_readDn = null;					// set just one time after reading an entry from LDAP
	protected $_branchDn = '';					// DN of the parent node
	protected $_filter = array();				// possible filter; used by reading
	protected $_dnAttributes = array();			// attributes used to create DN; order important!
	protected $_objectClasses = array();		// allowed object classes
	private $_md;								// meta data
	protected $_attributes = null;				// array of actual attributes
	protected $_related = array();				// attribute name => related objects
	protected $_overwrite = false;				// overwrite existing attributes

	/**
	 * Constructor.
	 * @param string scenario name. See {@link CModel::scenario} for more details about this parameter.
	 */
	public function __construct($scenario='insert')	{
		$this->createAttributes();

		if($scenario === null) {
			return;
		}

		$this->setScenario($scenario);

		$this->init();

		$this->attachBehaviors($this->behaviors());
	}

	/**
	 * PHP getter magic method.
	 * This method is overridden so that attributes can be accessed like properties.
	 * @param string property name
	 * @return mixed property value
	 * @see getAttribute
	 */
	public function __get($name)
	{
		$retval = $this->getAttribute($name);
		if ($retval === false) {
			if(isset($this->_related[$name])) {
				return $this->_related[$name];
			}
			else if(isset($this->getMetaData()->relations[$name])) {
				return $this->getRelated($name);
			}
			else {
				return parent::__get($name);
			}
		}
		return $retval;
	}

	/**
	 * PHP setter magic method.
	 * This method is overridden so that attributes can be accessed like properties.
	 * @param string property name
	 * @param mixed property value
	 */
	public function __set($name, $value)
	{
		if(property_exists($this, $name)) {
			$this->$name=$value;
		}
		else if ($this->hasAttribute($name) || 'attributes' == $name) {
			$this->setAttribute($name, $value);
		}
		else if ($this->hasRelation($name)) {
			$this->_related[$name] = $value;
		}
		else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Checks if a property value is null.
	 * This method overrides the parent implementation by checking
	 * if the named attribute is null or not.
	 * @param string the property name or the event name
	 * @return boolean whether the property value is null
	 */
	public function __isset($name)
	{
		if(isset($this->_attributes[strtolower($name)]) && isset($this->_attributes[strtolower($name)]['value'])) {
			return true;
		}
		else {
			return parent::__isset($name);
		}
	}

	public function __call($name , $args) {
		if (false !== strpos($name, 'compare') && 0 == strpos($name, 'compare')) {
			$class = get_class($this);
			Yii::log("__call ($name): $class", 'profile', 'ext.ldaprecord.CLdapRecord');

			$name = substr($name, 7);
			//echo 'Asc: ' . $name . ': ' .strlen($name) . '; ' . strpos($name, 'Asc') . '<br/>';
			//echo 'Desc: ' . $name . ': ' .strlen($name) . '; ' . strpos($name, 'Desc') . '<br/>';
			if (false !== strpos($name, 'Asc') && (strlen($name) - 3) == strpos($name, 'Asc')) {
				$name = strtolower(substr($name, 0, strlen($name) - 3));
				//echo $name . ': ' . count($args) . '<br/>';
				if ($this->hasAttribute($name)) {
					Yii::log("$name: " . $args[0]->$name . ', ' . $args[1]->$name . '=' . strcmp($args[0]->$name, $args[1]->$name), 'profile', 'ext.ldaprecord.CLdapRecord');
					return strcmp($args[0]->$name, $args[1]->$name);
				}
				else {
					throw new CLdapException(Yii::t('LdapComponent.record', 'Property "{class}.{property}" is not defined.',
						array('{class}'=>$class, '{property}'=>$name)));
				}
			}
			else if (false !== strpos($name, 'Desc') && (strlen($name) - 4) == strpos($name, 'Desc')) {
				$name = strtolower(substr($name, 0, strlen($name) - 4));
				//echo $name . ': ' . count($args) . '<br/>' . '<br/>';
				if ($this->hasAttribute($name)) {
					Yii::log("$name: " . $args[1]->$name . ', ' . $args[0]->$name . '=' . strcmp($args[1]->$name, $args[0]->$name), 'profile', 'ext.ldaprecord.CLdapRecord');
					return strcmp($args[1]->$name, $args[0]->$name);
				}
				else {
					throw new CLdapException(Yii::t('LdapComponent.record', 'Property "{class}.{property}" is not defined.',
						array('{class}'=>$class, '{property}'=>$name)));
				}
			}
		}
	}

	/**
	 * Returns the list of all attribute names of the model.
	 * @return array list of attribute names.
	 */
	public function attributeNames() {
		return array_keys($this->_attributes);
	}

	/**
	 * Initializes this model.
	 * This method is invoked when an instance is newly created and has
	 * its {@link scenario} set.
	 * You may override this method to provide code that is needed to initialize the model (e.g. setting
	 * initial property values.)
	 */
	public function init() {
	}

	/**
	 * Returns the named attribute value.
	 * If this record is the result of a query and the attribute is not loaded,
	 * null will be returned.
	 * You may also use $this->AttributeName to obtain the attribute value.
	 * @param string the attribute name
	 * @return mixed the attribute value. Null if the attribute is not set or does not exist.
	 * @see hasAttribute
	 */
	public function getAttribute($name)
	{
		if(property_exists($this, $name)) {
			return $this->$name;
		}
/*
		else if ('attributes' == $name) {
			$retval = array();
			foreach($this->_attributes as $name => $info) {
				if (isset($info['alias'])) continue;
				$retval[$name] = $info['value'];
			}
			return $retval;
		}
*/
		else if(array_key_exists($name, $this->_attributes) ||
			array_key_exists(strtolower($name), $this->_attributes)) {
			$name = strtolower($name);
			if (isset($this->_attributes[$name]['alias'])) {
				return $this->getAttribute($this->_attributes[$name]['alias']);
			}
			return $this->_attributes[$name]['value'];
		}
		return false;
	}

	/**
	 * Sets the named attribute value.
	 * You may also use $this->AttributeName to set the attribute value.
	 * @param string the attribute name
	 * @param mixed the attribute value.
	 * @return boolean whether the attribute exists and the assignment is conducted successfully
	 * @throws CLdapException if an error occurs.
	 * @see hasAttribute
	 */
	public function setAttribute($name, $value)
	{
		//echo "setAttribute($name, $value)<br/>";
		//echo '<pre>' . print_r($this->_attributes, true) . '</pre>';
		if(property_exists($this, $name)) {
			$this->$name=$value;
		}
		else if ('attributes' == $name && is_array($value)) {
			foreach ($value as $key => $val) {
				$this->$key = $val;
			}
		}
		else if(isset($this->_attributes[$name]) || isset($this->_attributes[strtolower($name)]) || '*' == $this->_objectClasses) {
			if(isset($this->_attributes[strtolower($name)])) {
				$name = strtolower($name);
			}
			if (isset($this->_attributes[$name]['alias'])) {
				return $this->setAttribute($this->_attributes[$name]['alias'], $value);
			}
			if (isset($this->_attributes[$name]['type'])) {
				switch($this->_attributes[$name]['type']) {
					case 'assozarray':
						preg_match($this->_attributes[$name]['typedata'], $value, $parts);
						//echo '<pre>' . $value . "\n" . $this->_attributes[$name]['typedata'] . "\n" . print_r($parts, true) . '</pre>';
						if (3 != count($parts)) {
							throw new CLdapException(Yii::t('LdapComponent.record', 'Parse value from attr \'{name}\' failed! (Wrong reg pattern)', array('{name}'=>$name)));
						}
						$this->_attributes[$name]['value'][$parts[1]] = $parts[2];
						break;
					case 'array':
						if ($this->_overwrite) {
							if (is_array($value)) {
								$this->_attributes[$name]['value'] = $value;
							}
							else {
								$this->_attributes[$name]['value'] = array($value);
							}
						}
						else {
							$this->_attributes[$name]['value'][] = $value;
						}
						break;
					default:
						if (isset($this->_attributes[$name]['value']) && !$this->_overwrite) {
							$this->_attributes[$name]['type'] = 'array';
							$firstvalue = $this->_attributes[$name]['value'];
							if (is_array($value)) {
								$this->_attributes[$name]['value'] = array_merge(array($firstvalue), $value);
							}
							else {
								$this->_attributes[$name]['value'] = array($firstvalue, $value);
							}
						}
						else {
							$this->_attributes[$name]['value'] = $value;
						}
						break;
				}
			}
			else {
				if (isset($this->_attributes[$name]['value']) && !$this->_overwrite) {
					$this->_attributes[$name]['type'] = 'array';
					$firstvalue = $this->_attributes[$name]['value'];
					if (is_array($value)) {
						$this->_attributes[$name]['value'] = array_merge(array($firstvalue), $value);
					}
					else {
						$this->_attributes[$name]['value'] = array($firstvalue, $value);
					}
				}
				else {
					$this->_attributes[$name]['value'] = $value;
				}
			}
		}
		else {
			$attrtype = CLdapServer::getInstance()->getSchema()->getAttributeType($name);
			if (null == $attrtype) {
				return false;
			}
			$aliases = $attrtype->getNames();
			$ok = false;
			foreach($aliases as $alias) {
				if ($name == $alias) continue;
				if (isset($this->_attributes[$alias]) || isset($this->_attributes[strtolower($alias)])) {
					$ok = $this->setAttribute($alias, $value);
					break;
				}
			}
			if (!$ok) {
				return parent::__set($name, $value);
			}
		}
		return true;
	}

	/**
	 * Returns the related record(s).
	 * This method will return the related record(s) of the current record.
	 * If the relation is HAS_ONE or BELONGS_TO, it will return a single object
	 * or null if the object does not exist.
	 * If the relation is HAS_MANY, it will return an array of objects
	 * or an empty array.
	 * @param string the relation name (see {@link relations})
	 * @param boolean whether to reload the related objects from database. Defaults to false.
	 * @param array additional parameters that customize the query conditions as specified in the relation declaration.
	 * @return mixed the related object(s).
	 * @throws CLdapException if an error occurs.
	 * @see hasRelated
	 */
	public function getRelated($name, $refresh=false, $params=array())
	{
		if(!$refresh && $params === array() && (isset($this->_related[$name]) || array_key_exists($name,$this->_related))) {
			return $this->_related[$name];
		}

		$md = $this->getMetaData();
		if(!isset($md->relations[$name])) {
			throw new CLdapException(Yii::t('LdapComponent.record','{class} does not have relation "{name}".',
			array('{class}'=>get_class($this), '{name}'=>$name)));
		}
		//Yii::trace('lazy loading '.get_class($this).'.'.$name,'system.db.ar.CActiveRecord');
		$relation = $md->relations[$name];
		//echo '<pre>' . print_r($relation, true) . '</pre>';
		if($this->isNewEntry() && ($relation instanceof CLdapHasOne || $relation instanceof CLdapHasMany)) {
			return $relation instanceof CLdapHasOne ? null : array();
		}
		if($params !== array()) {
			$exists = isset($this->_related[$name]) || array_key_exists($name, $this->_related);
			if($exists) {
				$save = $this->_related[$name];
			}
			unset($this->_related[$name]);
		}
		try {
			$this->_related[$name] = $relation->createRelationalRecord($this, $params);
		}
		catch (CLdapException $e) {
			// Nothing to do; Next if() will do all
		}

		if(!isset($this->_related[$name])) {
			if($relation instanceof CLdapHasMany) {
				$this->_related[$name] = array();
			}
			else {
				$this->_related[$name] = null;
			}
		}

		if($params !== array()) {
			$results = $this->_related[$name];
			if($exists) {
				$this->_related[$name] = $save;
			}
			else {
				unset($this->_related[$name]);
			}
			return $results;
		}
		else {
			return $this->_related[$name];
		}
	}

	/**
	 * Returns a value indicating whether the named attribute is defined.
	 * @param string the relation name
	 * @return booolean a value indicating whether the named attribute is defined.
	 */
	public function hasAttribute($name)
	{
		$name = strtolower($name);
		$retval = isset($this->_attributes[$name]) || '*' == $this->_objectClasses;
		if (!$retval) {
			$attrtype = CLdapServer::getInstance()->getSchema()->getAttributeType($name);
			if (!is_null($attrtype)) {
				$aliases = $attrtype->getNames();
				foreach($aliases as $alias) {
					if ($name == $alias) continue;
					if (isset($this->_attributes[$alias])) {
						$retval = true;
						break;
					}
				}
			}
			// no else because $retval is already false
		}
		return $retval;
	}

	/**
	 * Returns a value indicating whether the named related object(s) is defined.
	 * @param string the relation name
	 * @return booolean a value indicating whether the named related object(s) is defined.
	 */
	public function hasRelation($name)
	{
		return isset($this->getMetaData()->relations[$name]);
	}

	/**
	 * Returns a value indicating whether the named related object(s) has been loaded.
	 * @param string the relation name
	 * @return booolean a value indicating whether the named related object(s) has been loaded.
	 */
	public function hasRelated($name)
	{
		return isset($this->_related[$name]) || array_key_exists($name, $this->_related);
	}

	public function setDn($dn) {
		$this->_dn = $dn;
	}

	/**
	 * Return the Dn
	 *
	 * @return string with Dn.
	 */
	public function getDn() {
		return $this->_dn;
	}

	/**
	 * Sets the branchDn for the model.
	 * @param string $dn the branchDn that this model is in.
	 */
	public function setBranchDn($dn) {
		$this->_branchDn = $dn;
	}

	/**
	 * Return the branchDn of this model.
	 *
	 * @return string branchDn.
	 */
	public function getBranchDn() {
		return $this->_branchDn;
	}

	/**
	 * Sets the overwrite mode for the model.
	 * @param boolean $ow whether to overwrite the attributes.
	 */
	public function setOverwrite($ow) {
		$this->_overwrite = $ow;
	}

	/**
	 * Sets this model as NOT saved.
	 * Only works if you create a new and different DN.
	 */
	public function setAsNew() {
		$this->_readDn = null;
	}

	/**
	 * Return all the attributes.
	 *
	 * @return array with attributes and their type definition.
	 */
	public function getLdapAttributes() {
		return $this->_attributes;
	}

	/**
	 * This method should be overridden to declare related objects.
	 *
	 * There are three types of relations that may exist between two active record objects:
	 * <ul>
	 * <li>BELONGS_TO: e.g. a member belongs to a team;</li>
	 * <li>HAS_ONE: e.g. a member has at most one profile;</li>
	 * <li>HAS_MANY: e.g. a team has many members;</li>
	 * </ul>
	 *
	 * Each kind of related objects is defined in this method as an array with the following elements:
	 * <pre>
	 * 'varName'=>array('relationType', 'own_attribute', 'className', 'foreign_attribute', ...additional options)
	 * </pre>
	 * where 'varName' refers to the name of the variable/property that the related object(s) can
	 * be accessed through; 'relationType' refers to the type of the relation, which can be one of the
	 * following four constants: self::BELONGS_TO, self::HAS_ONE and self::HAS_MANY;
	 * 'own_attribute' is the name of the attribute in the base object, if set to 'dn' 'foreign_attribute' can be set
	 * to a php statement that returns the DN (see example below);
	 * 'className' refers to the name of the ldap record class that the related object(s) is of;
	 * and 'foreign_attribute' is the name of the attribute in the related object(s).
	 *
	 * Additional options may be specified as name-value pairs in the rest array elements:
	 * <ul>
	 * <li>'<attributename>': string, definition of an item for a possible filter</li>
	 * </ul>
	 *
	 * Below is an example declaring related objects for 'Post' active record class:
	 * <pre>
	 * return array(
	 *     'address'=>array(self::BELONGS_TO, 'addressUID', 'LdapAddress', 'uid'),
	 *     'disks' => array(self::HAS_MANY, 'dn', 'LdapDisk', '$model->getDn()', array('sstDisk' => '*')),
	 * );
	 * </pre>
	 *
	 * @return array list of related object declarations. Defaults to empty array.
	 */
	public function relations()
	{
		return array();
	}

	/**
	 * Returns the static model of the specified Ldap class.
	 *
	 * @param string $className active record class name.
	 * @return CLdapRecord ldap record model instance.
	 */
	public static function model($className=__CLASS__)
	{
		$model = new $className(null);
		$model->_md = new CLdapRecordMetaData($model);
		$model->attachBehaviors($model->behaviors());

		return $model;
	}

	/**
	 * Returns the meta-data for this ldap record
	 * @return CLdapRecordMetaData the meta for this ldap record class.
	 */
	public function getMetaData()
	{
		if($this->_md !== null) {
			return $this->_md;
		}
		else {
			return $this->_md = self::model(get_class($this))->_md;
		}
	}

	public function findSubTree($criteria) {
		if (!isset($criteria['branchDn'])) {
			$criteria['branchDn'] = $this->_branchDn;
		}
		$class = get_class($this);
		if ('LdapSubTree' != $class && !is_subclass_of($class, 'LdapSubTree')) {
			throw new CLdapException(Yii::t('LdapComponent.record', 'findSubTree failt: used class is not type or subtype of \'LdapSubTree\'!'),
				 0x100002);
		}
		$server = CLdapServer::getInstance();
		$entries = $server->findSubTree($this, $criteria);
		//echo '<pre>' . print_r($entries, true) . '</pre>';
		$branchDn = '';
		$nodes = array();
		$retval = array();
		for ($i=0; $i<$entries['count']; $i++) {
			$objclasses = array();
			$item = new $class();
			for ($j=0; $j<$entries[$i]['count']; $j++) {
				if ('objectclass' == $entries[$i][$j]) {
					$attr = $entries[$i][$j];
					for ($k=0; $k<$entries[$i][$attr]['count']; $k++) {
						$objclasses[] = $entries[$i][$attr][$k];
					}
					continue;
				}
				$attr = $entries[$i][$j];
				for ($k=0; $k<$entries[$i][$attr]['count']; $k++) {
					$item->$attr = $entries[$i][$attr][$k];
				}
			}
			$item->_objectClasses = $objclasses;
			$item->_dn = $entries[$i]['dn'];
			$item->_readDn = $item->_dn;
			$item->_branchDn = $this->getParentDn($item->_dn);
			if (0 == $i) {
				$branchDn = $item->_dn;
			}
			$nodes[$item->_dn] = $item;
			if ($branchDn != $item->_dn) {
				$parentDn = substr($item->_dn, 1 + strpos($item->_dn, ','));
				if (isset($nodes[$parentDn])) {
					if (!isset($nodes[$parentDn]->children)) {
						$nodes[$parentDn]->children = array();
					}
					$nodes[$parentDn]->addChild($item);
				}
				else {
					throw new CLdapException(Yii::t('LdapComponent.record', 'findSubTree failt: parent \'{dn}\' not read!',
						array('{dn}'=>$parentDn)), 0x100001);
				}
			}
		}
		return $nodes[$branchDn];
	}

	public function findByAttributes($attributes)
	{
		$retval = $this->findAll($attributes);
		return 0 == count($retval) ? null : $retval[0];
	}

	public function findAll($criteria=array('attr'=>null)) {
		if (!isset($criteria['branchDn'])) {
			$criteria['branchDn'] = $this->_branchDn;
		}
		$class = get_class($this);
		Yii::log("findAll: $class", 'profile', 'ext.ldaprecord.CLdapRecord');
		$server = CLdapServer::getInstance();
		$entries = $server->findAll($this, $criteria);
		Yii::log('findAll: entries: ' . $entries['count'], 'profile', 'ext.ldaprecord.CLdapRecord');
		if (isset($criteria['limit'])) {
			$limit = $criteria['limit'];
		}
		else {
			$limit = $entries['count'];
		}
		if (isset($criteria['offset'])) {
			$start = $criteria['offset'];
		}
		else {
			$start = 0;
		}
		$end = $start + $limit;
		if ($end > $entries['count']) {
			$end = $entries['count'];
		}
//		echo '<pre>' . print_r($criteria, true) . '</pre>';
//		echo "$start bis $end<br/>";
		$retval = array();
		for ($i=$start; $i<$end; $i++) {
		//for ($i=0; $i<$entries['count']; $i++) {
			$item = new $class();
			for ($j=0; $j<$entries[$i]['count']; $j++) {
				/* TODO: check if objectclasses are OK */
				if ('objectclass' == $entries[$i][$j]) {
					continue;
				}
				$attr = $entries[$i][$j];
				for ($k=0; $k<$entries[$i][$attr]['count']; $k++) {
					$item->$attr = $entries[$i][$attr][$k];
				}
			}
			$item->_dn = $entries[$i]['dn'];
			$item->_readDn = $item->_dn;
			$item->_branchDn = $this->getParentDn($item->_dn);
			$retval[] = $item;
		}
		if (isset($criteria['sort']) && '' != $criteria['sort']) {
			$sort = explode('.', $criteria['sort']);
			$fname = 'compare' . ucfirst($sort[0]);
			if (isset($sort[1])) {
				$fname .= ucfirst($sort[1]);
			}
			usort($retval, array($this, $fname));
		}
		return $retval;
	}

	/**
	 * Returns all attribute values.
	 * Note, related objects are not returned.
	 * @param mixed $names names of attributes whose value needs to be returned.
	 * If this is true (default), then all attribute values will be returned, including
	 * those that are not loaded from LDAP (null will be returned for those attributes).
	 * If this is null, all attributes except those that are not loaded from LDAP will be returned.
	 * @return array attribute values indexed by attribute names.
	 */
	public function getAttributes($names=true)
	{
		$attributes=array();
		foreach($this->_attributes as $name => $info) {
			if (isset($info['alias'])) continue;
			if (isset($info['readOnly']) && $info['readOnly']) continue;
			$attributes[$name] = $info['value'];
		}
		if(is_array($names))
		{
			$attrs=array();
			foreach($names as $name)
			{
				$name = strtolower($name);
				if(isset($attributes[$name]))
					$attrs[$name]=$attributes[$name];
			}
			return $attrs;
		}
		else
			return $attributes;
	}

	/**
	 * Saves the current record.
	 *
	 * The record is inserted as a node if its {@link isNewEntry}
	 * property is true (usually the case when the record is created using the 'new'
	 * operator). Otherwise, it will be used to update the corresponding node
	 * (usually the case if the record is obtained using one of those 'find' methods.)
	 *
	 * Validation will be performed before saving the record. If the validation fails,
	 * the record will not be saved. You can call {@link getErrors()} to retrieve the
	 * validation errors.
	 *
	 * If the record is saved via insertion, its {@link isNewEntry} property will be
	 * set false, and its {@link scenario} property will be set to be 'update'.
	 * And if its primary key is auto-incremental and is not set before insertion,
	 * the primary key will be populated with the automatically generated key value.
	 *
	 * @param boolean $runValidation whether to perform validation before saving the record.
	 * If the validation fails, the record will not be saved to database.
	 * @param array $attributes list of attributes that need to be saved. Defaults to null,
	 * meaning all attributes that are loaded from DB will be saved.
	 * @return boolean whether the saving succeeds
	 */
	public function save($runValidation=true, $attributes=null)
	{
		if(!$runValidation || $this->validate($attributes)) {
			return $this->isNewEntry() ? $this->insert($attributes) : $this->update($attributes);
		}
		else {
			return false;
		}
	}

	/**
	 * Inserts a node based on this ldap record attributes.
	 * Note, validation is not performed in this method. You may call {@link validate} to perform the validation.
	 * After the record is inserted to Ldap successfully, its {@link isNewEntry} property will be set false,
	 * and its {@link scenario} property will be set to be 'update'.
	 * @param array $attributes list of attributes that need to be saved. Defaults to null,
	 * meaning all attributes that are loaded from DB will be saved.
	 * @return boolean whether the attributes are valid and the record is inserted successfully.
	 * @throws CLdapException if the record is not new
	 */
	public function insert($attributes=null)
	{
		if(!$this->isNewEntry()) {
			throw new CLdapException(Yii::t('LdapComponent.record', 'The entry cannot be inserted to LDAP because it is not new.'));
		}
		Yii::log('insert: ' . get_class($this), 'profile', 'ext.ldaprecord.CLdapRecord');
		$server = CLdapServer::getInstance();
		return $server->add($this->createDn(), $this->createEntry(false));
	}

	/**
	 * Updates the node represented by this ldap record.
	 * All loaded attributes will be saved.
	 * Note, validation is not performed in this method. You may call {@link validate} to perform the validation.
	 * @param array $attributes list of attributes that need to be saved. Defaults to null,
	 * meaning all attributes that are loaded from DB will be saved.
	 * @return boolean whether the update is successful
	 * @throws CLdapException if the record is new
	 */
	public function update($attributes=null)
	{
		if($this->isNewEntry()) {
			throw new CLdapException(Yii::t('LdapComponent.record', 'The entry cannot be updated within LDAP because it is new.'));
		}
		Yii::log('update: ' . get_class($this), 'profile', 'ext.ldaprecord.CLdapRecord');
		Yii::log('update: ' . $this->_readDn . ' == ' . $this->createDn(), 'profile', 'ext.ldaprecord.CLdapRecord');
		$server = CLdapServer::getInstance();
		if ($this->_readDn == $this->createDn()) {
			$retval = $server->modify($this->_readDn, $this->createEntry(true));
		}
		else {
			$retval = $server->rename($this->_readDn, $this->createDnBase());
			if ($retval) {
				$retval = $server->modify($this->_dn, $this->createEntry(true));
			}
		}
		return $retval;
	}

	public function delete($recursive=false, $keepDn=false) {
		Yii::log('delete: ' . get_class($this), 'profile', 'ext.ldaprecord.CLdapRecord');
		$server = CLdapServer::getInstance();
		return $server->delete($this->dn, $recursive, $keepDn);
	}

	public function move($parent) {
		if($this->isNewEntry()) {
			throw new CLdapException(Yii::t('LdapComponent.record', 'The entry cannot be moved within LDAP because it is new.'));
		}
		Yii::log('move: ' . get_class($this), 'profile', 'ext.ldaprecord.CLdapRecord');
		$server = CLdapServer::getInstance();
		return $server->move($this->_readDn, $this->createDnBase(), $parent);
	}

	public function findByDn($dn) {
		$item = null;
		Yii::log('findByDn: ' . get_class($this), 'profile', 'ext.ldaprecord.CLdapRecord');
		$server = CLdapServer::getInstance();
		$entry = $server->findByDn($dn, $this);
		if (1 < $entry['count']) {
			throw new CLdapException(Yii::t('LdapComponent.record', 'Wrong result count ({count}) on findByDn'), array('{count}'=> $entry['count']));
		}
		else if (1 == $entry['count']) {
			$item = $this->model(get_class($this));
			for ($j=0; $j<$entry[0]['count']; $j++) {
				/* TODO: check if objectclasses are OK */
				if ('objectclass' == $entry[0][$j]) {
					continue;
				}
				$attr = $entry[0][$j];
				for ($k=0; $k<$entry[0][$attr]['count']; $k++) {
					$item->$attr = $entry[0][$attr][$k];
				}
			}
			$item->_dn = $entry[0]['dn'];
			$item->_readDn = $item->_dn;
			$item->_branchDn = $this->getParentDn($item->_dn);
		}
		return $item;
	}

	public function isNewEntry() {
		return is_null($this->_readDn);
	}

	public function getFilter($name) {
		return $this->_filter[$name];
	}

	public function hasObjectClass($objClassName) {
		return in_array($objClassName, $this->_objectClasses);
	}

	public function removeAttributesByObjectClass($objClassName) {
		if ($this->hasObjectClass($objClassName)) {
			$schema = CLdapServer::getInstance()->getSchema();
			$objClass = $schema->getObjectClass($objClassName);
			if (null != $objClass) {
				foreach($objClass->getAttributes() as $name => $info) {
					unset($this->_attributes[$name]);
				}
				unset($this->_objectClasses[array_search($objClassName,$this->_objectClasses)]);
			}
		}
		else {
			throw new CLdapException(Yii::t('LdapComponent.record', 'Class "{class}" not found for removeAttributesByObjectClass', array('{class}' => $class)));
		}
	}

	public function removeAttribute($names) {
		if (!is_array($names)) {
			$names = array($names);
		}
		foreach($names as $name) {
			if(isset($this->_attributes[$name])) {
				unset($this->_attributes[$name]);
			}
			else if(isset($this->_attributes[strtolower($name)])) {
				unset($this->_attributes[strtolower($name)]);
			}
		}
	}

	public static function getParentDn($dn) {
		return substr($dn, strpos($dn, ',') + 1);
	}

	private function createDn() {
		$this->_dn = $this->createDnBase() . ',' . $this->_branchDn;
		return $this->_dn;
	}

	private function createDnBase() {
		$dn = '';
		foreach($this->_dnAttributes as $name) {
			if ('' != $dn) {
				$dn .= ',';
			}
			$dn .= $name . '=' . $this->$name;
		}
		return $dn;
	}

	private function createEntry($isModify) {
		$entry = array();
		foreach($this->_attributes as $key => $value) {
			if ('member' == $key) continue;
			if ('dn' !== $key && isset($value['value']) && '' !== $value['value'] && (!isset($value['readOnly']) || !$value['readOnly'])) {
				if (is_array($value['value'])) {
					if ('assozarray' == $value['type']) {
						$retval = array();
						//echo '<pre>' . $name . ' ' . print_r($this->_attributes[$name]['value'], true) . '</pre>';
						foreach($value['value'] as $name => $val) {
							$retval[] = $name . ' ' . $val;
						}
						$entry[$key] = $retval;
					}
					else {
						$entry[$key] = $value['value'];
					}
				}
				else {
					$entry[$key][] = $value['value'];
				}
			}
		}
		if (!$isModify) {
			if (0 != count($this->_objectClasses)) {
				$entry['objectclass'] = array();
				foreach($this->_objectClasses as $class) {
					$entry['objectclass'][] = $class;
				}
			}
			else {
				throw new CLdapException(Yii::t('LdapComponent.record', 'Failt to createEntry for ldap_add. No objectClass(es) defined!'));
			}
		}
		return $entry;
	}

	// TODO: change name to createAttributeDefinitions
	protected function createAttributes() {
		$schema = CLdapServer::getInstance()->getSchema();
		if ('*' == $this->_objectClasses) {
			$this->_attributes = array();
			return;
		}
		foreach($this->_objectClasses as $objClassName) {
			$objClass = $schema->getObjectClass($objClassName);
			if (null != $objClass) {
				if (is_null($this->_attributes)) {
					$this->_attributes = $objClass->getAttributes();
				}
				else {
					$this->_attributes = array_merge($this->_attributes, $objClass->getAttributes());
				}
				// TODO: is labeledURIObject also used in other LDAP servers than OpenLdap
			}
			else {
				throw new CLdapException(Yii::t('LdapComponent.record', 'Unknown objectClass "{class}"!', array('{class}' => $objClassName)));
			}
			if ('labeledURIObject' == $objClassName) {
				$this->_attributes['member'] = array('mandatory' => false, 'type' => 'array');
			}
		}
		$this->_attributes['createtimestamp'] = array('mandatory' => false, 'readOnly' => true, 'type' => '', 'value' => null);
		$this->_attributes['modifytimestamp'] = array('mandatory' => false, 'readOnly' => true, 'type' => '', 'value' => null);

		//echo '<pre>' . print_r($this->_attributes, true) . '</pre>';
	}
	
	public function formatCreateTimestamp($format=null) {
		$retval = null;
		if (isset($this->createTimestamp)) {
			if (is_null($format)) {
				$retval = $this->createTimestamp;
			}
			else {
				$retval = $this->formatTimestamp($this->createTimestamp, $format);
			}
		}
		return $retval;
	}
	
	protected function formatTimestamp($date, $format) {
		/*
		 * Timestamp format: 20111108221310Z
		 */
		$time = mktime (substr($date, 8, 2), substr($date, 10, 2), substr($date, 12, 2), 
				substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4));
		return date($format, $time);
	}
}

/**
 * CLdapRecordMetaData represents the meta-data for aa Ldap Record class.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.4
 */
class CLdapRecordMetaData
{
	/**
	 * @var array list of relations
	 */
	public $relations = array();

	private $_model;

	/**
	 * Constructor.
	 * @param CLdapRecord $model the model instance
	 */
	public function __construct($model)
	{
		$this->_model = $model;

		foreach($model->relations() as $name => $config) {
			$this->addRelation($name, $config);
		}
	}

	/**
	 * Adds a relation.
	 *
	 * $config is an array with three elements:
	 * relation type, own attribute, the related ldap record class and the foreign attribute.
	 *
	 * @throws CLdapException
	 * @param string $name Name of the relation.
	 * @param array $config Relation parameters.
	 * @return void
	 */
	public function addRelation($name, $config)
	{
		if(isset($config[0], $config[1], $config[2], $config[3])) {
			if (isset($config[4])) {
				$this->relations[$name] = new $config[0]($name, $config[1], $config[2], $config[3], $config[4]);
			}
			else {
				$this->relations[$name] = new $config[0]($name, $config[1], $config[2], $config[3]);
			}
		}
		else {
			throw new CLdapException(Yii::t('LdapComponent.record','Ldap record "{class}" has an invalid configuration for relation "{relation}". It must specify the relation type, the related active record class and the foreign key.', array('{class}'=>get_class($this->_model),'{relation}'=>$name)));
		}
	}
}

/**
 * CLdapBaseRelation is the base class for all ldap relations.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.4
 */
abstract class CLdapBaseRelation extends CComponent {
	public $name;				// name of the relation
	public $attribute;			// own attribute
	public $className;			// foreign ldap record class
	public $foreignAttribute;	// foreign attribute
	public $options = array();

	/**
	 * Constructor.
	 * @param string name of the relation
	 * @param string name of the own attribute
	 * @param string name of the related ldap record class
	 * @param string name of the foreign attriubte for this relation
	 * @param array additional options (name=>value). The keys must be the property names of this class.
	 */
	public function __construct($name, $attribute, $className, $foreignAttribute, $options=array()) {
		$this->name = $name;
		$this->attribute = $attribute;
		$this->className = $className;
		$this->foreignAttribute = $foreignAttribute;
		$this->options = $options;
	}

	abstract public function createRelationalRecord($model);
}

/**
 * CLdapHasOne represents the parameters specifying a HAS_ONE relation.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.4
 */
class CLdapHasOne extends CLdapBaseRelation {
	public function createRelationalRecord($model) {
		if ('dn' == $this->attribute) {
			$template = $this->foreignAttribute;
			eval("\$branchDn = $template;");
			//echo "branchDn: $branchDn" . '<br/>';
			$criteria = array();
			$criteria['branchDn'] = $branchDn;
			$criteria['attr'] = array();
		}
		else {
			$attr = $this->attribute;
			$criteria = array('attr' => array($this->foreignAttribute => $model->$attr));
		}
		foreach($this->options as $key => $value) {
			$criteria['attr'][$key] = $value;
		}
		//echo 'Criteria: <pre>' . print_r($criteria, true) . '</pre>';
		$results = CLdapRecord::model($this->className)->findAll($criteria);
		//echo 'Result: <pre>' . print_r($results, true) . '</pre>';
		if (0 == count($results)) {
			return null;
		}
		else if (1 < count($results)) {
			throw new CLdapException('Relation ' . __CLASS__ . ' between ' . get_class($model) . ' and ' . $this->className . ' (' . count($results) . ')');
		}
		return $results[0];
	}
}

/**
 * CLdapHasOneDn represents the parameters specifying a HAS_ONE_DN relation.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.6
 */
class CLdapHasOneDn extends CLdapHasOne {
	public function createRelationalRecord($model) {
		if ('dn' == $this->attribute) {
			$template = $this->foreignAttribute;
			eval("\$dn = $template;");
			$pos = strpos($dn, 'ldap:///');
			if (false !== $pos && $pos == 0) {
				$dn = substr($dn, 8);
			}
			//echo "dn: $dn" . '<br/>';
			$result = CLdapRecord::model($this->className)->findByDn($dn);
		}
		else {
			$attr = $this->attribute;
			$criteria = array('attr' => array($this->foreignAttribute => $model->$attr));
		}
		return $result;
	}
}

/**
 * CLdapHasOneDepth represents the parameters specifying a HAS_ONE_DEPTH relation.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.6
 */
class CLdapHasOneDepth extends CLdapHasOne {
	public function createRelationalRecord($model) {
		$attr = $this->attribute;
		if ('*' == $attr) {
			$value = '*';
		}
		else {
			$value = $model->$attr;
		}
		$criteria = array('depth' => true, 'attr' => array($this->foreignAttribute => $value));
		foreach($this->options as $key => $value) {
			$criteria['attr'][$key] = $value;
		}
		//echo 'Criteria: <pre>' . print_r($criteria, true) . '</pre>';
		$results = CLdapRecord::model($this->className)->findAll($criteria);
		//echo 'Result: <pre>' . print_r($results, true) . '</pre>';
		if (0 == count($results)) {
			return null;
		}
		else if (1 < count($results)) {
			throw new CLdapException('Relation ' . __CLASS__ . ' between ');
		}
		return $results[0];
	}
}

/**
 * CLdapHasOneDepthBranch represents the parameters specifying a HAS_ONE_DEPTH_BRANCH relation.
 *
 * ATTENTION: Just for Bugfixing MUST be removed!!
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.8.2
 */
class CLdapHasOneDepthBranch extends CLdapHasOne {
	public function createRelationalRecord($model) {
		$attr = $this->attribute;
		if ('*' == $attr) {
			$value = '*';
		}
		else {
			$value = $model->$attr;
		}
		$criteria = array('depth' => true, 'branchDn' => $model->getDn(), 'attr' => array($this->foreignAttribute => $value));
		foreach($this->options as $key => $value) {
			$criteria['attr'][$key] = $value;
		}
		//echo 'Criteria: <pre>' . print_r($criteria, true) . '</pre>';
		$results = CLdapRecord::model($this->className)->findAll($criteria);
		//echo 'Result: <pre>' . print_r($results, true) . '</pre>';
		if (0 == count($results)) {
			return null;
		}
		else if (1 < count($results)) {
			throw new CLdapException('Relation ' . __CLASS__ . ' between ');
		}
		return $results[0];
	}
}

/**
 * CLdapBelongsTo represents the parameters specifying a BELONGS_TO_DN relation.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.4
 */
class CLdapBelongsToDn extends CLdapBaseRelation {
	public function createRelationalRecord($model) {
		$template = $this->attribute;
		if ('~' == $template[0]) {
			$steps = substr($template, 1);
			$parts = explode(',', $model->dn);
			for($i=0; $i<$steps; $i++) {
				unset($parts[$i]);
			}
			$branchDn = implode(',', $parts);
		}
		else {
			eval("\$branchDn = $template;");
		}
		//echo "branchDn: $branchDn" . '<br/>';
		return CLdapRecord::model($this->className)->findByDn($branchDn);
	}
}

/**
 * CLdapHasMany represents the parameters specifying a HAS_MANY relation.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.4
 */
class CLdapHasMany extends CLdapBaseRelation {
	public function createRelationalRecord($model) {
		if ('dn' == $this->attribute) {
			$template = $this->foreignAttribute;
			eval("\$branchDn = $template;");
			//echo "branchDn: $branchDn" . '<br/>';
			$criteria = array();
			$criteria['branchDn'] = $branchDn;
			$criteria['attr'] = array();
		}
		else {
			$attr = $this->attribute;
			$criteria = array('attr' => array($this->foreignAttribute => $model->$attr));
		}
		foreach($this->options as $key => $value) {
			$criteria['attr'][$key] = $value;
		}
		//echo '<pre>' . print_r($criteria, true) . '</pre>';
		$results = CLdapRecord::model($this->className)->findAll($criteria);
		//echo '<pre>' . print_r($results, true) . '</pre>';
		return $results;
	}
}

/**
 * CLdapHasManyDepth represents the parameters specifying a HAS_MANY_DEPTH relation.
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @package ext.ldaprecord
 * @since 0.6
 */
class CLdapHasManyDepth extends CLdapHasMany {
	public function createRelationalRecord($model) {
		if ('dn' == $this->attribute) {
			$template = $this->foreignAttribute;
			eval("\$branchDn = $template;");
			//echo "branchDn: $branchDn" . '<br/>';
			$criteria = array();
			$criteria['branchDn'] = $branchDn;
			$criteria['attr'] = array();
		}
		else {
			$attr = $this->attribute;
			$criteria = array('attr' => array($this->foreignAttribute => $model->$attr));
		}

		$criteria['depth'] = true;

		foreach($this->options as $key => $value) {
			if (false !== strpos($value, '"') && '"' == substr($value, 0, 1) && '"' == substr($value, strlen($value)-1)) {
				eval("\$evalue = $value;");
				$criteria['attr'][$key] = $evalue;
			}
			else {
				$criteria['attr'][$key] = $value;
			}
		}
		//echo '<pre>' . print_r($criteria, true) . '</pre>';
		$results = CLdapRecord::model($this->className)->findAll($criteria);
		//echo 'Result: <pre>' . print_r($results, true) . '</pre>';
		if (0 == count($results)) {
			return null;
		}
		return $results;
	}
}
