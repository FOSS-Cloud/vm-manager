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
 * SubnetController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.8
 */

class SubnetController extends Controller
{
	private $netmasks = array(); //array('28'=>28, '26'=>26, '24'=>24, '20'=>20, '16'=>16, '12'=>12);
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'network';
			for($i=1; $i<33; $i++) {
				$this->netmasks[$i] = $i;
			}
		}
		return $retval;
	}

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('index', 'delete', 'update', 'create', 'updateRange', 'createRange', 'getSubnets',),
            			'users'=>array('@'),
				'expression'=>'Yii::app()->user->isAdmin'
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	public function actionIndex() {
		$this->render('index');
	}

	public function actionUpdate() {
		$model = new SubnetForm('update');

		if(isset($_GET['dn'])) {
			$model->dn = $_GET['dn'];
		}
		else {
			throw new CHttpException(404,'The requested page does not exist.');
		}

		$this->performAjaxValidation($model);

		if(isset($_POST['SubnetForm'])) {
			$model->attributes = $_POST['SubnetForm'];

			$result = CLdapRecord::model('LdapDhcpSubnet')->findByDn($_POST['SubnetForm']['dn']);
			$result->setOverwrite(true);
			$result->cn = $model->ip;
			$result->dhcpNetMask = $model->netmask;
			$result->sstDisplayName = $model->name;
			if ('' != $model->domainname) {
				$result->dhcpOption = 'domain-name ' . $model->domainname;
			}
			if ('' != $model->domainservers) {
				$result->dhcpOption = 'domain-name-servers ' . $model->domainservers;
			}
			if ('' != $model->defaultgateway) {
				$result->dhcpOption = 'routers ' . $model->defaultgateway;
			}
			if ('' != $model->broadcastaddress) {
				$result->dhcpOption = 'broadcast-address ' . $model->broadcastaddress;
			}
			if ('' != $model->ntpservers) {
				$result->dhcpOption = 'ntp-servers ' . $model->ntpservers;
			}
			$result->save();

			$this->redirect(array('index'));
		}
		else {
			$subnet = CLdapRecord::model('LdapDhcpSubnet')->findbyDn($_GET['dn']);

			$model->dn = $subnet->dn;
			$model->ip = $subnet->cn;
			$model->netmask = $subnet->dhcpNetMask;
			$model->name = $subnet->sstDisplayName;
			$model->domainname = $subnet->dhcpOption['domain-name'];
			$model->domainservers = $subnet->dhcpOption['domain-name-servers'];
			$model->defaultgateway = $subnet->dhcpOption['routers'];
			$model->broadcastaddress = $subnet->dhcpOption['broadcast-address'];
			$model->ntpservers = $subnet->dhcpOption['ntp-servers'];

			$this->render('update',array(
				'model' => $model,
				'netmasks' => $this->netmasks
			));
		}
	}

	public function actionCreate() {
		$model = new SubnetForm('create');
		$subnets = LdapDhcpSubnet::model()->findAll(array('attr'=>array()));
		if (0 < count($subnets)) {
			$subnet = $subnets[0];
			$model->domainname = $subnet->dhcpOption['domain-name'];
			$model->domainservers = $subnet->dhcpOption['domain-name-servers'];
			$model->defaultgateway = $subnet->dhcpOption['routers'];
			$model->broadcastaddress = $subnet->dhcpOption['broadcast-address'];
			$model->ntpservers = $subnet->dhcpOption['ntp-servers'];
		}

		$this->performAjaxValidation($model);

		if(isset($_POST['SubnetForm'])) {

			$model->attributes = $_POST['SubnetForm'];

			$subnet = new LdapDhcpSubnet();
			$subnet->cn = $model->ip;
			$subnet->dhcpNetMask = $model->netmask;
			$subnet->sstDisplayName = $model->name;
			$subnet->description = 'This is the first network for which the DHCP server is responsible. All default DHCP options and statements are defined within this entry.';
			$subnet->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
			$subnet->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
			if ('' != $model->domainname) {
				$subnet->dhcpOption = 'domain-name ' . $model->domainname;
			}
			if ('' != $model->domainservers) {
				$subnet->dhcpOption = 'domain-name-servers ' . $model->domainservers;
			}
			if ('' != $model->defaultgateway) {
				$subnet->dhcpOption = 'routers ' . $model->defaultgateway;
			}
			if ('' != $model->broadcastaddress) {
				$subnet->dhcpOption = 'broadcast-address ' . $model->broadcastaddress;
			}
			if ('' != $model->ntpservers) {
				$subnet->dhcpOption = 'ntp-servers ' . $model->ntpservers;
			}
			$subnet->dhcpStatements = 'authoritative';
			$subnet->dhcpStatements = 'default-lease-time 3600';
			$subnet->dhcpStatements = 'min-lease-time 600';
			$subnet->dhcpStatements = 'max-lease-time 43200';
			$subnet->dhcpStatements = 'ddns-update-style none';
			$subnet->dhcpStatements = 'ddns-updates off';
			$subnet->dhcpStatements = 'ping-check false';
			$subnet->save();

			$server = CLdapServer::getInstance();
			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit');
			$data['ou'] = 'ranges';
			$data['description'] = array('This subtree holds all defined sub-network ranges (dynamic and persistent).');
			$dn = 'ou=ranges,' . $subnet->dn;
			$server->add($dn, $data);

			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit');
			$data['ou'] = 'virtual machines';
			$data['description'] = array('This subtree holds all static MAC address to IP mappings.');
			$dn = 'ou=virtual machines,' . $subnet->dn;
			$server->add($dn, $data);

			$this->redirect(array('index'));
		}
		else {
			$this->render('create',array(
				'model' => $model,
				'netmasks' => $this->netmasks
			));
		}

	}

	public function actionUpdateRange() {
		$model = new RangeForm('update');

		if(isset($_GET['dn'])) {
			$model->dn = $_GET['dn'];
		}
		else {
			throw new CHttpException(404,'The requested page does not exist.');
		}

		$this->performAjaxValidationRange($model);

		if(isset($_POST['RangeForm'])) {
			$model->attributes = $_POST['RangeForm'];

			$result = CLdapRecord::model('LdapDhcpRange')->findByDn($_POST['RangeForm']['dn']);
			$result->setOverwrite(true);
			$result->cn = $model->ip . '/' . $model->netmask;
			$result->sstDisplayName = $model->name;
			$result->sstNetworkType = $model->type;
			$result->save();

			$this->redirect(array('index'));
		}
		else {
			//$subnet = CLdapRecord::model('LdapDhcpSubnet')->findByDn($model->subnetDn);

			$range = CLdapRecord::model('LdapDhcpRange')->findbyDn($_GET['dn']);
			$subnet = $range->subnet;

			$model->dn = $range->dn;
			list($ip, $netmask) = explode('/', $range->cn, 2);
			$model->ip = $ip;
			$model->subnet = $subnet->cn . '/' . $subnet->dhcpNetMask;
			$model->subnetDn = $subnet->dn;
			$model->netmask = $netmask;
			$model->name = $range->sstDisplayName;
			$model->type = $range->sstNetworkType;

			$this->render('updateRange',array(
				'model' => $model,
				'netmasks' => $this->netmasks,
				'types' => array('dynamic'=>'dynamic', 'persistent'=>'persistent', 'template'=>'template'),
				'subnet' => $subnet->cn . '/' . $subnet->dhcpNetMask
			));
		}
	}

	public function actionCreateRange() {
		$model = new RangeForm('create');

		if(isset($_GET['dn'])) {
			$model->subnetDn = $_GET['dn'];
		}
		else {
			throw new CHttpException(404,'The requested page does not exist.');
		}

		$this->performAjaxValidationRange($model);

		if(isset($_POST['RangeForm']))
		{
			$model->attributes = $_POST['RangeForm'];

			$range = new LdapDhcpRange();

			$range->setBranchDn('ou=ranges,' . $model->subnetDn);
			$range->cn = $model->ip . '/' . $model->netmask;
			$range->sstDisplayName = $model->name;
			$range->sstNetworkType = $model->type;
			//$subnet->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
			//$subnet->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
			$range->save();

			$this->redirect(array('index'));
		}
		else {
			$subnet = CLdapRecord::model('LdapDhcpSubnet')->findByDn($model->subnetDn);
			$model->subnet = $subnet->cn . '/' . $subnet->dhcpNetMask;

			$this->render('createRange',array(
				'model' => $model,
				'netmasks' => $this->netmasks,
				'types' => array(''=>'', 'dynamic'=>'dynamic', 'persistent'=>'persistent', 'template'=>'template'),
				'subnet' => $subnet->cn . '/' . $subnet->dhcpNetMask
			));
		}

	}

	public function actionDelete() {
		if ('del' == $_POST['oper']) {
			$level = $_POST['level'];
			$dn = urldecode(Yii::app()->getRequest()->getPost('dn'));
			if (0 == $level) {
				// It is a Subnet
				$subnet = CLdapRecord::model('LdapDhcpSubnet')->findByDn($dn);
				if (!is_null($subnet)) {
					$subnet->delete(true);
				}
			}
			else if (1 == $level) {
				// It is a Range
				$range = CLdapRecord::model('LdapDhcpRange')->findByDn($dn);
				if (!is_null($range)) {
					$range->delete();
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='subnet-form')
		{
			$this->disableWebLogRoutes();
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidationRange($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='range-form')
		{
			$this->disableWebLogRoutes();
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	/**
	 * Ajax functions for JqGrid
	 */
	public function actionGetSubnets() {
		$this->disableWebLogRoutes();
		$page = $_GET['page'];

		// get how many rows we want to have into the grid - rowNum parameter in the grid
		$limit = $_GET['rows'];

		// get index row - i.e. user click to sort. At first time sortname parameter -
		// after that the index from colModel
		$sidx = $_GET['sidx'];

		// sorting order - at first time sortorder
		$sord = $_GET['sord'];

		if (!isset($_GET['n_level'])) {
			$n_level = 0;
		}
		else {
			$n_level = $_GET['n_level'];
			$nodeid = $_GET['nodeid'];
		}
		$criteria = array('attr'=>array());
		if ($sidx != '')
		{
			$criteria['sort'] = $sidx . '.' . $sord;
		}
		$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll($criteria);
		$count = count($subnets);

		// calculate the total pages for the query
		if( $count > 0 && $limit > 0)
		{
			$total_pages = ceil($count/$limit);
		}
		else
		{
			$total_pages = 0;
		}

		// if for some reasons the requested page is greater than the total
		// set the requested page to total page
		if ($page > $total_pages)
		{
			$page = $total_pages;
		}

		// calculate the starting position of the rows
		$start = $limit * $page - $limit;

		// if for some reasons start position is negative set it to 0
		// typical case is that the user type 0 for the requested page
		if($start < 0)
		{
			$start = 0;
		}

		$criteria['limit'] = $limit;
		$criteria['offset'] = $start;

		$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll($criteria);

		// we should set the appropriate header information. Do not forget this.
		//header("Content-type: text/xml;charset=utf-8");

		$s = "<?xml version='1.0' encoding='utf-8'?>";
		$s .= '<rows>';
		$s .= '<page>' . $page . '</page>';
		$s .= '<total>' . $total_pages . '</total>';
		$s .= '<records>' . $count . '</records>';
		$s1 = '';
		$s2 = '';
		if (0 == $n_level) {
			$i = 1;
			foreach ($subnets as $subnet) {
				//	'colNames'=>array('No.', 'DN', 'Range', 'Action'),
				$dhcpVms = $subnet->vms;

				$s .= '<row id="' . $i . '">';
				$s .= '<cell>'. $i . '</cell>';
				$s .= '<cell>' . ($subnet->isUsed() ? 'true' : 'false') . "</cell>\n";
				$s .= '<cell>'. $subnet->dn . '</cell>';
				$s .= '<cell>'. $subnet->cn . '/' . $subnet->dhcpNetMask . '</cell>';
				$s .= '<cell>'. $subnet->sstDisplayName . '</cell>';
				$s .= '<cell></cell>';
				$rangeInfo = Utils::getIpRange($subnet->cn . '/' . $subnet->dhcpNetMask);
				$s .= '<cell>' . $rangeInfo['hostmin'] . '</cell>';
				$s .= '<cell>' . $rangeInfo['hostmax'] . '</cell>';
				$s .= '<cell></cell>';

				$s .= '<cell>0</cell>';
				$s .= '<cell><![CDATA[NULL]]></cell>';
				$s .= '<cell>false</cell>';
				$s .= '<cell>false</cell>';
				$s .= '</row>';
				$j = $i * 100 + 1;
				foreach($subnet->ranges as $range) {
					$rangeInfo = $range->getRange();
					$s1 .= '<row id="' . $j . '">';
					$s1 .= '<cell>'. $j . '</cell>';
					$s1 .= '<cell>' . ($range->isUsed() ? 'true' : 'false') . "</cell>\n";
					$s1 .= '<cell>'. $range->dn . '</cell>';
					$s1 .= '<cell>'. $range->cn . '</cell>';
					$s1 .= '<cell>'. $range->sstNetworkType . ' / ' . $range->sstDisplayName . '</cell>';
					$s1 .= '<cell></cell>';
					$s1 .= '<cell>' . $rangeInfo['hostmin'] . '</cell>';
					$s1 .= '<cell>' . $rangeInfo['hostmax'] . '</cell>';
					$s1 .= '<cell></cell>';

					$s1 .= '<cell>1</cell>';
					$s1 .= '<cell><![CDATA[' . $i . ']]></cell>';
					$s1 .= '<cell>false</cell>';
					$s1 .= '<cell>false</cell>';
					$s1 .= '</row>';
					$s .= $s1;
					$s1 = '';
/*
					$k = $j * 100 + 1;
					foreach($dhcpVms as $dhcpVm) {
						if ($range->inRange($dhcpVm->dhcpStatements['fixed-address'])) {
							$s2 .= '<row id="' . $k . '">';
							$s2 .= '<cell>'. $k . '</cell>';
							$s2 .= '<cell>'. $dhcpVm->dn . '</cell>';
							$s2 .= '<cell>'. $dhcpVm->dhcpStatements['fixed-address'] . '</cell>';
							if (null != $dhcpVm->vm) {
								$s2 .= '<cell><![CDATA[<span title="Virtual Machine">VM</span> / ' . $dhcpVm->vm->sstDisplayName . ']]></cell>';
								$s2 .= '<cell>Virtual Machine</cell>';
							}
							else if (null != $dhcpVm->vmtemplate) {
								$s2 .= '<cell><![CDATA[<span title="Virtual Machine Template">VMT</span> / ' . $dhcpVm->vmtemplate->sstDisplayName . ']]></cell>';
								$s2 .= '<cell>Virtual Machine Template</cell>';
							}
							else {
								$s2 .= '<cell>???</cell>';
								$s2 .= '<cell></cell>';
							}
							$s2 .= '<cell></cell>';
							$s2 .= '<cell></cell>';
							$s2 .= '<cell></cell>';

							$s2 .= '<cell>2</cell>';
							$s2 .= '<cell><![CDATA[' . $j . ']]></cell>';
							$s2 .= '<cell>true</cell>';
							$s2 .= '<cell>false</cell>';
							$s2 .= '</row>';
							$s .= $s2;
							$s2 = '';
							$k++;
						}
					}
*/
					$j++;
				}
				$i++;
			}
			//$s .= $s1 . $s2;
		}
		else if (1 == $n_level) {
			$done = false;
			$i = 1;
			foreach ($subnets as $subnet) {
				$dhcpVms = $subnet->vms;
				$j = $i * 100 + 1;
				if ($j == $nodeid) {
					$s = "<?xml version='1.0' encoding='utf-8'?>";
					$s .= '<rows>';
					$s .= '<page>' . $page . '</page>';
					$s .= '<total>' . $total_pages . '</total>';
					$s .= '<records>' . count($subnet->ranges) . '</records>';
					foreach($subnet->ranges as $range) {
						$k = $j * 100 + 1;
						foreach($dhcpVms as $dhcpVm) {
							if ($range->inRange($dhcpVm->dhcpStatements['fixed-address'])) {
								$s .= '<row id="' . $k . '">';
								$s .= '<cell>'. $k . '</cell>';
								$s .= '<cell>false</cell>';
								$s .= '<cell>'. $dhcpVm->dn . '</cell>';
								$s .= '<cell>'. $dhcpVm->dhcpStatements['fixed-address'] . '</cell>';
								if (null != $dhcpVm->vm) {
									$s .= '<cell><![CDATA[<span title="Virtual Machine">VM</span> / ' . $dhcpVm->vm->sstDisplayName . ']]></cell>';
									$s .= '<cell>Virtual Machine</cell>';
								}
								else if (null != $dhcpVm->vmtemplate) {
									$s .= '<cell><![CDATA[<span title="Virtual Machine Template">VMT</span> / ' . $dhcpVm->vmtemplate->sstDisplayName . ']]></cell>';
									$s .= '<cell>Virtual Machine Template</cell>';
								}
								else {
									$s .= '<cell>???</cell>';
									$s .= '<cell></cell>';
								}
								$s .= '<cell></cell>';
								$s .= '<cell></cell>';
								$s .= '<cell></cell>';

								$s .= '<cell>2</cell>';
								$s .= '<cell><![CDATA[' . $j . ']]></cell>';
								$s .= '<cell>true</cell>';
								$s .= '<cell>false</cell>';
								$s .= '</row>';
								$k++;
							}
						}
					}
					$done = true;
				}
				if ($done) break;
				$j++;
			}
			$i++;
		}
		$s .= '</rows>';

		header('Content-Type: application/xml');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

}
