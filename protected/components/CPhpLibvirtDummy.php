<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *  Axel Westhagen <axel.westhagen@limbas.com>
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
 * CPhpLibvirtDummy class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

/**
 * CPhpLibvirtDummy
 *
 * CPhpLibvirtDummy Interface to libvirt.
 *
 * The used design pattern is Singleton. To get the one and
 * only instance of this class call CPhpLibvirt::getInstance().
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @since 0.4
 */
class CPhpLibvirtDummy extends CPhpLibvirt {
	/**
	 * Starts a Vm.
	 *
	 * $data is an array with key value pairs.
	 *
	 * @throws CPhpLibvirtException
	 * @param array $data necessary paramters to start a Vm
     * @return boolean
	 */
	public function startVm($data) {
		$_SESSION['phplibvirt']['vms'][$data['sstName']] = true;
		sleep(2);
		return 2701;
	}

	public function rebootVm($data) {
		return true;
	}

	public function shutdownVm($data) {
		$_SESSION['phplibvirt']['vms'][$data['name']] = false;
		return true;
	}

	public function destroyVm($data) {
		$_SESSION['phplibvirt']['vms'][$data['name']] = false;
		sleep(2);
		return true;
	}

	public function migrateVm($data) {
		$flags = self::$VIR_MIGRATE_LIVE | self::$VIR_MIGRATE_UNDEFINE_SOURCE | self::$VIR_MIGRATE_PEER2PEER | self::$VIR_MIGRATE_TUNNELLED;
		Yii::log('dummy_migrateVm: ' . $data['name'] . ', ' . $data['newhost'] . ', ' . $flags . ', ' . 0, 'profile', 'phplibvirt');
		sleep(5);
		return true;
	}

	public function getVmStatus($data) {
		$retval = array();
		if (!isset($_SESSION['phplibvirt']['vms'][$data['name']])) {
			$_SESSION['phplibvirt']['vms'][$data['name']] = 5 > rand(1, 10);
		}
		$retval['active'] = $_SESSION['phplibvirt']['vms'][$data['name']];
		$retval['memory'] = rand(128000, 16777216);
		$retval['maxMem'] = 16777216;
		$retval['cpuTime'] = rand(30, 85);
		$retval['nrVirtCpu'] = 1;
		$retval['cpuTimeOrig'] = 32.5;
		Yii::log('dummy_getVmStatus: ' . print_r($retval, true), 'profile', 'phplibvirt');
		sleep(2);
		return $retval;
	}

	public function createVolumeFile($host, $capacity) {
		$templatesdir = LdapStoragePoolDefinition::getPathByType('vm-templates');
		$volumename = $this->generateUUID();

		$path = $templatesdir . Yii::app()->params['virtualization']['vmtemplatestoragepool'];
		if (!file_exists($path)) {
			mkdir($path, 0770);
		}

		$sourcefile = $path . '/' . $volumename . '.qcow2';
		$fh = fopen($sourcefile, 'w');
		fwrite($fh, 'Capacity: ' . $capacity);
		fclose($fh);
		sleep(2);
		Yii::app()->getSession()->add('libvirtdummy.count', CPhpLibvirtDummy::$testcount);
		return array('VolumeName' => $volumename, 'SourceFile' => $sourcefile);
	}

	public function copyVolumeFile($persistentdir, $disk) {
		$volumename = $this->generateUUID();

		$name = Yii::app()->params['virtualization']['vmstoragepool'];
		$path = $persistentdir . '/' . $name;
		if (!file_exists($path)) {
			mkdir($path, 0770);
		}
		$sourcefile = $path . '/' . $volumename . '.qcow2';
		copy($disk->sstSourceFile, $sourcefile);
		sleep(2);
		Yii::app()->getSession()->add('libvirtdummy.count', CPhpLibvirtDummy::$testcount);

		return array('VolumeName' => $volumename, 'SourceFile' => $sourcefile, 'pid' => 54321);
	}

	public function copyIsoFile($source, $dest) {
		copy($source, $dest);
		sleep(2);
		Yii::app()->getSession()->add('libvirtdummy.count', CPhpLibvirtDummy::$testcount);

		return array('pid' => 12345);
	}

	private static $testcount = 10;
	public function checkPid($pid){
		$count = Yii::app()->getSession()->get('libvirtdummy.count', CPhpLibvirtDummy::$testcount);
		Yii::app()->getSession()->add('libvirtdummy.count', $count - 1);
		if (0 < $count) {
			return true;
		}
	    return false;
	}

	public function createStoragePool($host, $basepath) {
		$path = $basepath . '/' . $this->generateUUID();

		if (!file_exists($path)) {
			mkdir($path, 0770);
		}
	}

	public function deleteStoragePool($host, $basepath, $uuid) {
		$path = $basepath . '/' . $uuid;

		if (!file_exists($path)) {
			rmdir($path);
		}
	}
}
