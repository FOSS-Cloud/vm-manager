<?php

/*
 * Copyright (C) 2006 - 2017 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Sören Busse <soeren.2011@live.de>
 *
 * Parts adapted from:
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
 * Class ApiBackend
 * Handles all API requests
 */
class ApiBackend
{
	// Does the FOSS-Cloud contains the FC4School module?
	private $hasSchoolModule = false;

	/**
	 * ApiBackend constructor.
	 */
	public function __construct()
	{
		// Does the Schoolmodule exists?
		$this->hasSchoolModule = Yii::app()->hasModule('school');

		// If the School module exsists
		if ($this->hasSchoolModule) {
			// Import the Schoolmodule Wrapper
			Yii::import('application.modules.school.components.MappingWrapper');
		}
	}

	/**
	 * Checks the authentication of the User
	 * @param $realmParam string The realm from the GET-Request
	 * @return LdapUserIdentity If successful it returns the user identity otherwise it will die
	 */
	public function checkAuthentification($realmParam)
	{
		// Are any HTTP Basic information set?
		if (!isset($_SERVER["PHP_AUTH_USER"]) || !isset($_SERVER["PHP_AUTH_PW"])) {
			(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400002))->setDevDescription("HTTP-Auth missing")->display();
		}

		// Check if user or password is empty
		if (strlen(trim($_SERVER["PHP_AUTH_USER"])) == 0 || strlen(trim($_SERVER["PHP_AUTH_PW"])) == 0) {
			(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400005))->setDevDescription("Password or username empty")->display();
		}

		// Set the default realm
		$realm = Yii::app()->getModule('api')->defaultRealm;

		// Is a custom realm using the GET-Request is set?
		if (!empty($realmParam)) {
			$realm = $realmParam;
		}

		// Check credentials
		$userIdentity = new LdapUserIdentity($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"], $realm);
		$userIdentity->authenticate();

		// Otherwise show the error
		switch ($userIdentity->errorCode) {
			case LdapUserIdentity::ERROR_USERNAME_INVALID:
				(new JsonFormatter(HttpStatusCode::UNAUTHORIZED, 401003))->setDevDescription("Incorrect credentials")->display();
				break;
			case LdapUserIdentity::ERROR_PASSWORD_INVALID:
				(new JsonFormatter(HttpStatusCode::UNAUTHORIZED, 401003))->setDevDescription("Incorrect credentials")->display();
				break;
			case LdapUserIdentity::ERROR_REALM_INVALID:
				(new JsonFormatter(HttpStatusCode::UNAUTHORIZED, 401004))->setDevDescription("Incorrect realm")->display();
				break;
			case LdapUserIdentity::ERROR_NONE:
				Yii::app()->user->login($userIdentity, 0);
				return $userIdentity;
		}
	}

	/**
	 * Determine all realms registred on the server
	 * @return array Realminformations
	 */
	public function getRealms()
	{
		$realms = array();
		$server = CLdapServer::getInstance();

		// LDAP-Request
		$result = $server->search("ou=authentication,ou=virtualization,ou=services", "(&(objectClass=sstLDAPAuthenticationProvider))", array('ou', 'sstDisplayName'));

		// Iterate through all results and put it into the realms array
		foreach ($result as $realmEntry) {
			if (empty($realmEntry["ou"][0]))
				continue;

			$realms[] = array(
				"uid" => $realmEntry["ou"][0],
				"displayName" => $realmEntry["sstdisplayname"][0],
				"default" => ($realmEntry["ou"][0] == Yii::app()->getModule('api')->defaultRealm)
			);
		}

		// Return the realms
		return $realms;
	}

	/**
	 * Returns the dynamic vms
	 * @param $showAll boolean If the user has the right list all dynamic vms
	 * @return array|null
	 */
	public function getDynamicVms($showAll)
	{
		// The Group UIDs the user is assigned to
		$usergroups = Yii::app()->user->groupuids;

		// Array with all the vms
		$allVms = array();

		// Should all VMs been returned
		if ($showAll) {
			// Does the user have the right to do?
			if (!Yii::app()->user->hasRight('dynamicVM', COsbdUser::$RIGHT_ACTION_VIEW, COsbdUser::$RIGHT_VALUE_ALL)) {
				(new JsonFormatter(HttpStatusCode::FORBIDDEN, 403033))->setDevDescription("The user doesn't have permission to list all vms")->display();
			}

			// Search criteria
			$criteria = array('attr' => array('sstVirtualMachineType' => 'dynamic'));

			// Find all entries
			$vms = LdapVm::model()->findAll($criteria);

			// Iterate through all foundings
			foreach ($vms as $vm) {
				$allVms[] = $this->convertInfosToArray(
					$vm->sstVirtualMachine,
					$vm->sstDisplayName,
					($vm->sstVirtualMachineSubType == "Golden-Image") ? null : $vm->node->getSpiceIp(),
					($vm->sstVirtualMachineSubType == "Golden-Image") ? null : $vm->sstNode,
					($vm->sstVirtualMachineSubType == "Golden-Image") ? null : $vm->sstSpicePort,
					($vm->sstVirtualMachineSubType == "Golden-Image") ? null : $vm->sstSpicePassword,
					($vm->sstVirtualMachineSubType == "Golden-Image") ? null : $this->getVmStatus($vm),
					$vm->sstVirtualMachineType,
					$vm->sstVirtualMachineSubType,
					$vm->defaults->getProfileGroup(),
					$vm->sstVirtualMachinePool);
			}

			return $allVms;
		}

		// If the user has no rights to list the VMs
		if (!Yii::app()->user->hasRight('dynamicVM', COsbdUser::$RIGHT_ACTION_VIEW, COsbdUser::$RIGHT_VALUE_OWNER) && !Yii::app()->user->hasRight('dynamicVM', COsbdUser::$RIGHT_ACTION_VIEW, COsbdUser::$RIGHT_VALUE_ALL))
			return array();

		// Check all Dynamic VM-Pools
		$vmpools = LdapVmPool::model()->findAll(array('attr' => array('sstVirtualMachinePoolType' => 'dynamic')));

		// Iterate through each VM-Pool
		foreach ($vmpools as $vmpool) {
			// Does the user belongs to this pool?
			$poolAssigned = false;

			// Iterate through all assigned groups of this pool
			foreach ($vmpool->groups as $poolgroup) {
				if (array_search($poolgroup->ou, $usergroups) !== false) {
					// The user is assigned to this pool
					$poolAssigned = true;
					break;
				}
			}

			// If the user doesn't belong to a group try to check if the user itself is assigned to this pool
			if (!$poolAssigned) {
				foreach ($vmpool->people as $vmuser) {
					if (Yii::app()->user->uid == $vmuser->ou) {
						// The user is assigned to this pool
						$poolAssigned = true;
						break;
					}
				}
			}

			// If the user still doesn't belong to this pool; continue with the next one
			if (!$poolAssigned)
				continue;

			// Is there a DynVM free?
			$vmFree = false;

			$isAssigned = false;

			// Iterate through all running DynVMs
			foreach ($vmpool->runningDynVms as $vm) {
				// Is the VM free?
				if (count($vm->people) == 0)
					$vmFree = true;

				// Is the VM already assigned to the user?
				if ($this->isVmAssigned($vm)) {
					$allVms[] = $this->convertInfosToArray(
						$vm->sstVirtualMachine,
						$vmpool->sstDisplayName,
						$vm->node->getSpiceIp(),
						$vm->sstNode,
						$vm->sstSpicePort,
						$vm->sstSpicePassword,
						$this->getVmStatus($vm),
						$vm->sstVirtualMachineType,
						$vm->sstVirtualMachineSubType,
						$vm->defaults->getProfileGroup(),
						$vm->sstVirtualMachinePool);

					$isAssigned = true;
				}
			}

			// If there is no assigned VM, but still a free available return it
			if ($vmFree && !$isAssigned) {
				$allVms[] = $this->convertInfosToArray(
					null,
					$vmpool->sstDisplayName,
					null,
					null,
					null,
					null,
					"assignable",
					"dynamic",
					null,
					null,
					$vmpool->sstVirtualMachinePool
				);
			}
		}

		return $allVms;
	}

	/**
	 * Returns the persistent vms
	 * @return array
	 */
	public function getPersistentVms()
	{
		// All VMs array
		$allVms = array();

		// Get the assigned VMs
		$vms = LdapVm::getAssignedVms('persistent', array('attr' => array('sstVirtualMachineType' => 'persistent')));

		// Iterate through all VMs and build the array
		foreach ($vms as $vm) {
			$allVms[] = $this->convertInfosToArray(
				$vm->sstVirtualMachine,
				$vm->sstDisplayName,
				$vm->node->getSpiceIp(),
				$vm->sstNode,
				$vm->sstSpicePort,
				$vm->sstSpicePassword,
				$this->getVmStatus($vm),
				$vm->sstVirtualMachineType,
				$vm->sstVirtualMachineSubType,
				$vm->defaults->getProfileGroup(),
				$vm->sstVirtualMachinePool);
		}

		// Return all vms
		return $allVms;
	}

	/**
	 * Get the Mapped VMs
	 * @param $macAddress
	 * @return array
	 */
	public function getMappingVm($macAddress)
	{
		// Is the school module available
		if (!$this->hasSchoolModule) {
			(new JsonFormatter(HttpStatusCode::INTERNAL_SERVER_ERROR, 500052))->setDevDescription("The school module isn't available")->display();
		}

		// Is this a valid Mac-Address?
		if (!Utils::isValidMacAddress($macAddress)) {
			(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400055))->setDevDescription("Invalid macaddress or don't connected locally")->display();
		}

		// Instantiate the Mapping Handler
		$mappingWrapper = new MappingWrapper();

		// Retrieve the mapped VMs by
		$vm = $mappingWrapper->getMappedDynVM($macAddress);

		switch ($vm) {
			case MappingWrapper::ERROR_INVALID_USER:
				(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400053))->setDevDescription("You've to use the dynvmuser")->display();
				break;
			case MappingWrapper::ERROR_NO_VM_FOUND:
				(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400054))->setDevDescription("There's no vm for this mac available")->display();
				break;
		}

		// Return the assigned VM
		if ($this->isVmAssigned($vm)) {
			return $this->convertInfosToArray(
				$vm->sstVirtualMachine,
				$vm->vmpool->sstDisplayName,
				$vm->node->getSpiceIp(),
				$vm->sstNode,
				$vm->sstSpicePort,
				$vm->sstSpicePassword,
				$this->getVmStatus($vm),
				$vm->sstVirtualMachineType,
				$vm->sstVirtualMachineSubType,
				$vm->defaults->getProfileGroup(),
				$vm->sstVirtualMachinePool);
		}

		// Return the not assigned vm
		return $this->convertInfosToArray(
			null,
			$vm->vmpool->sstDisplayName,
			null,
			null,
			null,
			null,
			"assignable",
			"dynamic",
			null,
			null,
			$vm->vmpool->sstVirtualMachinePool);
	}

	/**
	 * Assigns a user to a vm pool
	 * @param $pool string VmPool
	 * @return array A Vm-Array
	 */
	public function assignVm($pool)
	{
		$vmpool = CLdapRecord::model('LdapVmPool')->findByDn("sstVirtualMachinePool=" . $pool . ",ou=virtual machine pools,ou=virtualization,ou=services,dc=foss-cloud,dc=org");

		// Does the VM Pool exist?
		if (is_null($vmpool)) {
			(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400042))->setDevDescription("VM pool not found")->display();
		}

		// Get a free VM
		$vm = $vmpool->getFreeVm();

		// Do we get a vm?
		if (is_null($vm)) {
			(new JsonFormatter(HttpStatusCode::INTERNAL_SERVER_ERROR, 500043))->setDevDescription("No free vm available")->display();
		}

		// Assign the user to this vm
		$vm->assignUser();

		// Return the assigned vm
		return $this->convertInfosToArray(
			$vm->sstVirtualMachine,
			$vmpool->sstDisplayName,
			$vm->node->getSpiceIp(),
			$vm->sstNode,
			$vm->sstSpicePort,
			$vm->sstSpicePassword,
			"assigned",
			$vm->sstVirtualMachineType,
			$vm->sstVirtualMachineSubType,
			$vm->defaults->getProfileGroup(),
			$vm->sstVirtualMachinePool
		);
	}

	/**
	 * Assigns the MAC address to this vm
	 * @param $pool
	 * @param $macAddress
	 * @return array
	 */
	public function assignMappingVm($pool, $macAddress)
	{
		// Is the school module available
		if (!$this->hasSchoolModule) {
			(new JsonFormatter(HttpStatusCode::INTERNAL_SERVER_ERROR, 500062))->setDevDescription("The school module isn't available")->display();
		}

		// Is this a valid Mac-Address?
		if (!Utils::isValidMacAddress($macAddress)) {
			(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400065))->setDevDescription("Invalid macaddress or don't connected locally")->display();
		}

		// Instantiate the Mapping Handler
		$mappingWrapper = new MappingWrapper();

		// Retrieve the mapped VMs by
		$vm = $mappingWrapper->assignVm($pool, $macAddress);

		// Are there any errors?
		switch ($vm) {
			case MappingWrapper::ERROR_INVALID_USER:
				(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400063))->setDevDescription("You've to use the dynvmuser")->display();
				break;
			case MappingWrapper::ERROR_NO_VM_FOUND:
				(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400064))->setDevDescription("There's no vm for this mac available")->display();
				break;
			case MappingWrapper::ERROR_VMPOOL_NOT_FOUND:
				(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400066))->setDevDescription("This pool isn't valid")->display();
				break;
			case MappingWrapper::ERROR_USER_ALREADY_ASSIGNED:
				(new JsonFormatter(HttpStatusCode::BAD_REQUEST, 400067))->setDevDescription("The user is already assigned to this mapped vm")->display();
				break;
		}

		// Update the VM status

		// Return the assigned vm
		return $this->convertInfosToArray(
			$vm->sstVirtualMachine,
			$vm->vmpool->sstDisplayName,
			$vm->node->getSpiceIp(),
			$vm->sstNode,
			$vm->sstSpicePort,
			$vm->sstSpicePassword,
			"assigned",
			$vm->sstVirtualMachineType,
			$vm->sstVirtualMachineSubType,
			$vm->defaults->getProfileGroup(),
			$vm->sstVirtualMachinePool
		);
	}

	/**
	 * Converts VM informations to an array
	 * @param $uuid
	 * @param $name
	 * @param $ip
	 * @param $node
	 * @param $port
	 * @param $password
	 * @param $status
	 * @param $type
	 * @param $subtype
	 * @param $os
	 * @param $pool
	 * @return array An array with vm information
	 */
	private function convertInfosToArray($uuid, $name, $ip, $node, $port, $password, $status, $type, $subtype, $os, $pool)
	{
		return array(
			"uuid" => $uuid,
			"name" => $name,
			"ip" => $ip,
			"node" => $node,
			"port" => $port,
			"password" => $password,
			"status" => $status,
			"type" => $type,
			"subtype" => $subtype,
			"os" => $os,
			"pool" => $pool
		);
	}

	/**
	 * Checks the VM running status
	 * @param $vm LdapVm
	 * @return string The status of the vm
	 */
	private function getVmStatus($vm)
	{
		// Load VM status from virtd
		$libvirt = CPhpLibvirt::getInstance();
		$status = $libvirt->getVmStatus(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine));

		if ($vm->sstVirtualMachineType == "dynamic") {
			if (count($vm->people) == 0 && !$this->isVmAssigned($vm)) {
				return "assignable";
			} else {
				return "assigned";
			}
		} else {
			// Is VM active
			if ($status["active"]) {
				return "online";
			} else {
				return "offline";
			}
		}
	}

	/**
	 * Checks if the specified VM is assigned to the user
	 * @param $vm
	 * @return bool Whether the VM is assigned
	 */
	private function isVmAssigned($vm)
	{
		if ($vm->sstVirtualMachineType) {
			foreach ($vm->people as $vmuser) {
				if (Yii::app()->user->uid == $vmuser->ou) {
					return true;
				}
			}
		}

		return false;
	}
}