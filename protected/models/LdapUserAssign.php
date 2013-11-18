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

class LdapUserAssign extends CLdapRecord {
	protected $_branchDn = '';
	protected $_filter = array('all' => 'sstVirtualMachinePool=*');
	protected $_objectClasses = array('organizationalUnit', 'top');

	public function relations()
	{
		return array(
			'pools' => array(self::HAS_MANY, 'dn', 'LdapUserAssignVmPool', '$model->getDn()', array('sstVirtualMachinePool' => '*')),
		);
	}

	public function removeVmAssignment($uuid) {
		foreach($this->pools as $pool) {
			$pool->removeVmAssignment($uuid);
		}
	}

	public function addVmAssignment($uuid) {
		$vm = CLdapRecord::model('LdapVm')->findByAttributes(array('attr'=>array('sstVirtualMachine' => $uuid)));
		foreach($this->pools as $pool) {
			if ($vm->sstVirtualMachinePool == $pool->sstVirtualMachinePool) {
				$vms = $pool->sstVirtualMachine;
				if (!is_array($vms) || !in_array($uuid, $vms)) {
					$pool->sstVirtualMachine = $uuid;
					$pool->save();
				}
			}
		}
	}

	public function removeVmPoolAssignment($uuid) {
		foreach($this->pools as $pool) {
			if ($uuid == $pool->sstVirtualMachinePool) {
				$pool->delete();
				break;
			}
		}
	}
}