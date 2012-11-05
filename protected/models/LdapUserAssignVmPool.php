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

class LdapUserAssignVmPool extends CLdapRecord {
	protected $_branchDn = '';
	protected $_filter = array('all' => '');
	protected $_dnAttributes = array('sstVirtualMachinePool');
	protected $_objectClasses = array('sstVirtualMachines', 'top');

	public function removeVmAssignment($uuid) {
		$vms = $this->sstVirtualMachine;
		if (!is_null($vms)) {
			$idx = array_search($uuid, $vms);
			if (false !== $idx) {
				unset($vms[$idx]);
				$this->setOverwrite(true);
				$this->sstVirtualMachine = $vms;
				$this->save();
			}
		}
	}

	protected function createAttributes() {
		parent::createAttributes();

		if (isset($this->_attributes['sstvirtualmachine'])) {
			$this->_attributes['sstvirtualmachine']['type'] = 'array';
		}
	}
}