<?php
/*
 * Copyright (C) 2014 FOSS-Group
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

class UpdatePeopleRoles extends Patch
{
	private $countPeople = 0;
	private $processedPeople = 0;
	private $idx = 0;
	private $adminRole = null;
	private $userRole = null;
	
	public function init($xmlPatch) {
		parent::init($xmlPatch);
		$users = LdapUser::model()->findAll(array('attr' => array()));
		$this->countPeople = count($users);
		Yii::log('User: ' . $this->countPeople, 'profile', 'patch.UpdatePeopleRoles');
		$this->adminRole = LdapUserRole::model()->findByAttributes(array('attr' => array('sstDisplayName' => 'Admin')));
		$this->userRole = LdapUserRole::model()->findByAttributes(array('attr' => array('sstDisplayName' => 'VM-User')));
	}

	public function processPeople($init, $params) {
		Yii::log('processPeople init: ' . var_export($init, true), 'profile', 'patch.UpdatePeopleRoles');
		$processed = $this->processedPeople;
		if ($init) {
			$actionCount = 1;
		}
		else {
			$this->log = array();
			$users = LdapUser::model()->findAll(array('attr' => array()));
			if (0 < count($users)) {
				//sleep(4);
				$processed++;
				$user = $users[$this->idx];
				Yii::log('processPeople: User=' . $user->getName(), 'profile', 'patch.UpdatePeopleRoles');
				
				$newRoleUid = $this->userRole->uid; 
				$newRoleTxt = ' (VM-User)';
				//echo '<i>' . $user->getName() . '</i>';
				$criteria = array('branchDn' => $user->getDn(), 'attr' => array());
				$roles = LdapUserRoleOld::model()->findAll($criteria);
				if (!is_null($roles) && 0 < count($roles)) {
					$userok = false;
					//echo ' has old User Roles' . (!$go ? '<br/>' : '');
					foreach($roles as $role) {
						if ('Admin' === substr($role->sstRole, 0, 5)) {
							$newRoleUid = $this->adminRole->uid; 
							$newRoleTxt = ' (Admin)';
							$userok = true;
							//echo ' &nbsp; Role found: Admin<br/>';
							break;
						}
						else if ('User' === substr($role->sstRole, 0, 4)) {
							$userok = true;
							//echo ' &nbsp; Role found: User<br/>';
						}
					}

					if ($userok) {
						//echo ' =&gt; ' . $newRoleUid . $newRoleTxt;
						$user->setOverwrite(true);
						$user->sstRoleUid = $newRoleUid;
						$user->save();
						foreach($roles as $role) {
							$role->delete();
						}
						//echo '<br/>';
						$this->log('User <b>' . $user->getName() . '</b> (' . $user->uid . ')' . ' successfully updated to ' . $newRoleUid . $newRoleTxt . '!');
					}
					else {
						throw new PatchException('ERROR: User <b>' . $user->getName() . '</b> (' . $user->uid . ')' . ' has neither the role "Admin" nor the role "User"!');
					}
				}
				else {
					$this->log('User <b>' . $user->getName() . '</b> (' . $user->uid . ')' . ' nothing to do!');
				}
				
				$this->actionProgress += 100 / $this->countPeople;
				$this->processedPeople++;
				
				$this->idx++;
			}
			if ($this->processedPeople === $this->countPeople) {
				$this->actionProgress = 100;
			}
			$actionCount = $this->actionCount;
		}
		return array('parttext' => $this->actions[0]->description . '<br/><b style="margin-left: 20px;">' . $processed . ' of ' . $this->countPeople . ' User processed</b>', 
				'partvalue' => $this->actionProgress); 
	}
}