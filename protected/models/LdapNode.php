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
 * LdapNode class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

class LdapNode extends CLdapRecord {
	protected $_branchDn = 'ou=nodes,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'sstNode=*');
	protected $_dnAttributes = array('sstNode');
	protected $_objectClasses = array('sstVirtualizationNode', 'sstRelationship', 'labeledURIObject', 'top');

	public function relations()
	{
		return array(
			// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'vms' => array(self::HAS_MANY, 'sstNode', 'LdapVm', 'sstNode'),
			'vmtemplates' => array(self::HAS_MANY, 'sstNode', 'LdapVmFromTemplate', 'sstNode'),
			'types' => array(self::HAS_MANY, 'dn', 'LdapNodeType', '"ou=node-types," . $model->getDn()'),
			'networks' => array(self::HAS_MANY, 'dn', 'LdapNodeNetwork', '"ou=networks," . $model->getDn()'),
			'vmpools' => array(self::HAS_MANY_DEPTH, 'dn', 'LdapNameless', '"ou=virtual machine pools,ou=virtualization,ou=services"', array('ou'=>'"$model->sstNode"')),
		);
	}

	/**
	 * Returns the static model of the specified LDAP class.
	 * @return CLdapRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'node' => Yii::t('node', 'Node'),
			'type' => Yii::t('node', 'Type'),
		);
	}

	public function getName() {
		return $this->sstNode;
	}

	public function getVLanIP($vlan) {
		$network = CLdapRecord::model('LdapNodeNetwork')->findByDn('ou=' . $vlan . ',ou=networks,' . $this->getDn());
		if (!is_null($network)) {
			return $network->sstNetworkIPAddress;
		}
		else {
			return '?unknown?';
		}
	}

	public function getSpiceIp() {
		$ip = (Yii::app()->params['virtualization']['spiceByName'] ? $this->getName() : $this->getVLanIP('pub'));
		if ('0.0.0.0' == $ip) {
			$ip = $_SERVER['SERVER_ADDR'];
		}
		return $ip;
	}

	public function getLibvirtUri() {
		return 'qemu+tcp://' . $this->getVLanIP('int') . '/system';
	}

	public function isType($typeName) {
		$retval = false;
		//echo '<pre>' . print_r($this->types, true) . '</pre>';
		foreach($this->types as $type) {
			if ($type->sstNodeType == $typeName) {
				$retval = true;
				break;
			}
		}
		return $retval;
	}

	public function search()
	{
		$criteria = array(
			'attr' => array(
				'host' => $this->host
			),
		);

		return new CLdapDataProvider('LdapNode', array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => 1,
			),
		));
	}
/*
	public static function compareNode($left, $right) {
		//echo "{$left->host}, {$right->host} " . strcmp($left->host, $right->host) . '<br/>';
		return strcmp($left->sstNode, $right->sstNode);
	}
	public static function compareNodeAsc($left, $right) {
		return LdapNode::compareNode($left, $right);
	}
	public static function compareNodeDesc($left, $right) {
		return -1 * LdapNode::compareNode($left, $right);
	}
*/
	public static function getAllTypeDefinitions() {
		// ToDo: Only used for the Node Wizard; $criteria finds only the implemented Node Types
		$criteria = array('attr'=>array('sstNodeType' => 'VM*'));
		$nodeTypes = CLdapRecord::model('LdapNodeTypeDefinition')->findAll($criteria);
		$retval = array();
		foreach($nodeTypes as $nodeType) {
			$retval[$nodeType->sstNodeType] = $nodeType->sstDisplayName;
		}
		return $retval;
	}

}