<?php
/*
 * Copyright (C) 2006 - 2014 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *  Axel Westhagen <axel.westhagen@limbas.com>
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
 * CPhpLibvirt class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

/**
 * CPhpLibvirt
 *
 * CPhpLibvirt Interface to libvirt.
 *
 * The used design pattern is Singleton. To get the one and
 * only instance of this class call CPhpLibvirt::getInstance().
 *
 * @author Christian Wittkowski <wittkowski@devroom.de>
 * @version $Id: $
 * @since 0.4
 */
class CPhpLibvirt {
	private static $_instance = null;

	public static $VIR_DOMAIN_NOSTATE	  = 0; // no state
	public static $VIR_DOMAIN_RUNNING	  = 1; // the domain is running
	public static $VIR_DOMAIN_BLOCKED	  = 2; // the domain is blocked on resource
	public static $VIR_DOMAIN_PAUSED	  = 3; // the domain is paused by user
	public static $VIR_DOMAIN_SHUTDOWN	  = 4; // the domain is being shut down
	public static $VIR_DOMAIN_SHUTOFF	  = 5; // the domain is shut off
	public static $VIR_DOMAIN_CRASHED	  = 6; // the domain is crashed
	public static $VIR_DOMAIN_PMSUSPENDED	  = 7; // the domain is suspended by guest power management

	public static $VIR_DOMAIN_XML_SECURE     = 1; // dump security sensitive information too
	public static $VIR_DOMAIN_XML_INACTIVE   = 2; // dump inactive domain information
	public static $VIR_DOMAIN_XML_UPDATE_CPU = 4; // update guest CPU requirements according to host CPU
	public static $VIR_DOMAIN_XML_MIGRATABLE = 8; // dump XML suitable for migration

	public static $VIR_MIGRATE_LIVE              =    1; // live migration
	public static $VIR_MIGRATE_PEER2PEER         =    2; // direct source -> dest host control channel Note the less-common spelling that we're stuck with: VIR_MIGRATE_TUNNELLED should be VIR_MIGRATE_TUNNELED
	public static $VIR_MIGRATE_TUNNELLED         =    4; // tunnel migration data over libvirtd connection
	public static $VIR_MIGRATE_PERSIST_DEST      =    8; // persist the VM on the destination
	public static $VIR_MIGRATE_UNDEFINE_SOURCE   =   16; // undefine the VM on the source
	public static $VIR_MIGRATE_PAUSED            =   32; // pause on remote side
	public static $VIR_MIGRATE_NON_SHARED_DISK   =   64; // migration with non-shared storage with full disk copy
	public static $VIR_MIGRATE_NON_SHARED_INC    =  128; // migration with non-shared storage with incremental copy (same base image shared between source and destination)
	public static $VIR_MIGRATE_CHANGE_PROTECTION =  256; //protect for changing domain configuration through the whole migration process; this will be used automatically when supported
	public static $VIR_MIGRATE_UNSAFE            =  512; // force migration even if it is considered unsafe
	public static $VIR_MIGRATE_OFFLINE           = 1024; // offline migrate
	public static $VIR_MIGRATE_COMPRESSED        = 2048; // compress data during migration

	public static $VIR_DOMAIN_START_PAUSED	 =	1; // Launch guest in paused state

	private $connections = array();

	private function __construct() {
	}

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
// 		$con = $this->getConnection($data['libvirt']);
// 		Yii::log('startVm: libvirt_domain_create_xml(' . $data['libvirt'] . ', ' . $this->getXML($data) . ')', 'profile', 'phplibvirt');
// 		return libvirt_domain_create_xml($con, $this->getXML($data));

		$con = $this->getConnection($data['libvirt']);
		Yii::log('startVm: libvirt_domain_lookup_by_name(' . $data['libvirt'] . ', ' . $data['sstName'] . ')', 'profile', 'phplibvirt');
		$domain = libvirt_domain_lookup_by_name($con, $data['sstName']);
		Yii::log('startVm: libvirt_domain_create(' . $data['sstName'] . ')', 'profile', 'phplibvirt');
		return libvirt_domain_create($domain);
	}

	public function startDynVm($data) {
		$con = $this->getConnection($data['libvirt']);
		Yii::log('startVm: libvirt_domain_create_xml(' . $data['libvirt'] . ', ' . $this->getXML($data) . ')', 'profile', 'phplibvirt');
		return libvirt_domain_create_xml($con, $this->getXML($data));
	}

	public function defineVm($data) {
		$con = $this->getConnection($data['libvirt']);
		Yii::log('defineVm: libvirt_domain_define_xml(' . $data['libvirt'] . ', ' . $this->getXML($data) . ')', 'profile', 'phplibvirt');
		return libvirt_domain_define_xml($con, $this->getXML($data));
	}

	public function undefineVm($data) {
		$con = $this->getConnection($data['libvirt']);
		Yii::log('undefineVm: libvirt_domain_lookup_by_name(' . $data['libvirt'] . ', ' . $data['name'] . ')', 'profile', 'phplibvirt');
		$domain = @libvirt_domain_lookup_by_name($con, $data['name']);
		if (false !== $domain) {
			Yii::log('undefineVm: libvirt_domain_undefine(' . $data['name'] . ')', 'profile', 'phplibvirt');
			return libvirt_domain_undefine($domain);
		}
		return true;
	}

	public function redefineVm($data) {
		$this->undefineVm($data);
		unset($data['name']);
		return $this->defineVm($data);
	}

	public function rebootVm($data) {
		$con = $this->getConnection($data['libvirt']);
		Yii::log('rebootVm: libvirt_domain_lookup_by_name(' . $data['libvirt'] . ', ' . $data['name'] . ')', 'profile', 'phplibvirt');
		$domain = libvirt_domain_lookup_by_name($con, $data['name']);
		Yii::log('rebootVm: libvirt_domain_reboot(' . $data['name'] . ')', 'profile', 'phplibvirt');
		libvirt_domain_reboot($domain);
		return true;
	}

	public function shutdownVm($data) {
		$con = $this->getConnection($data['libvirt']);
		Yii::log('shutdownVm: libvirt_domain_lookup_by_name(' . $data['libvirt'] . ', ' . $data['name'] . ')', 'profile', 'phplibvirt');
		$domain = libvirt_domain_lookup_by_name($con, $data['name']);
		Yii::log('shutdownVm: libvirt_domain_shutdown(' . $data['name'] . ')', 'profile', 'phplibvirt');
		return libvirt_domain_shutdown($domain);
	}

	public function destroyVm($data) {
		$con = $this->getConnection($data['libvirt']);
		Yii::log('destroyVm: libvirt_domain_lookup_by_name(' . $data['libvirt'] . ', ' . $data['name'] . ')', 'profile', 'phplibvirt');
		$domain = libvirt_domain_lookup_by_name($con, $data['name']);
		Yii::log('destroyVm: libvirt_domain_destroy(' . $data['name'] . ')', 'profile', 'phplibvirt');
		return libvirt_domain_destroy($domain);
	}

	public function migrateVm($data) {
//		echo print_r($data, true) . "\n";
		$con = $this->getConnection($data['libvirt']);
		Yii::log('migrateVm: libvirt_domain_lookup_by_name(' . $data['libvirt'] . ', ' . $data['name'] . ')', 'profile', 'phplibvirt');
		$domain = libvirt_domain_lookup_by_name($con, $data['name']);
		Yii::log('migrateVm: libvirt_domain_get_xml_desc(' . $data['libvirt'] . ',NULL)', 'profile', 'phplibvirt');
		$xmllibvirt = libvirt_domain_get_xml_desc($domain, NULL);
		Yii::log('migrateVm: orig XML: ' . $xmllibvirt, 'profile', 'phplibvirt');
		$xml = $this->replaceXML($xmllibvirt, $data);
		Yii::log('migrateVm:  new XML: ' . $xml, 'profile', 'phplibvirt');

		$flags = self::$VIR_MIGRATE_LIVE | self::$VIR_MIGRATE_UNDEFINE_SOURCE | self::$VIR_MIGRATE_PEER2PEER | self::$VIR_MIGRATE_TUNNELLED | self::$VIR_MIGRATE_PERSIST_DEST | self::$VIR_MIGRATE_UNSAFE;
// 		Yii::log('migrateVm: libvirt_domain_migrate_to_uri(' . $data['libvirt'] . ', ' . $data['newlibvirt'] . ', ' . $flags . ', ' . $data['name'] . ',0)', 'profile', 'phplibvirt');
// 		return libvirt_domain_migrate_to_uri($domain, $data['newlibvirt'], $flags, $data['name'], 0);
		Yii::log('migrateVm: libvirt_domain_migrate_to_uri2(' . $data['libvirt'] . ', ' . $data['newlibvirt'] . ', null, <XML>, ' . $flags . ', ' . $data['name'] . ',0)', 'profile', 'phplibvirt');
		return libvirt_domain_migrate_to_uri2($domain, $data['newlibvirt'], null, $xml, $flags, $data['name'], 0);
	}

	public function changeVmBootDevice($data) {
		$con = $this->getConnection($data['libvirt']);
		Yii::log('changeVmBootDevice: libvirt_domain_lookup_by_name(' . $data['libvirt'] . ', ' . $data['name'] . ')', 'profile', 'phplibvirt');
		$domain = libvirt_domain_lookup_by_name($con, $data['name']);
		Yii::log('changeVmBootDevice: libvirt_domain_change_boot_devices(' . $data['libvirt'] . ', ' . $data['device1'] . ', ' . $data['device2'] . ')', 'profile', 'phplibvirt');
		return libvirt_domain_change_boot_devices($domain, $data['device1'], $data['device2']);
	}

	public function getVmStatus($data) {
		$retval = array('active' => false);
		try {
			$con = $this->getConnection($data['libvirt']);
			if (false !== $con) {
				Yii::log('getVmStatus: libvirt_domain_lookup_by_name(' . $data['libvirt'] . ', ' . $data['name'] . ')', 'profile', 'phplibvirt');
				$domain = @libvirt_domain_lookup_by_name($con, $data['name']);
				if (false !== $domain) {
					//Yii::log('getVmStatus: libvirt_node_get_info (' . $data['libvirt']  . ')', 'profile', 'phplibvirt');
					//$nodeinfo = libvirt_node_get_info($con);
					Yii::log('getVmStatus: libvirt_domain_is_active (' . $data['name']  . ')', 'profile', 'phplibvirt');
					$retval['active'] = 1 === libvirt_domain_is_active($domain);
					Yii::log('getVmStatus: libvirt_domain_get_info (' . $data['name'] . ')', 'profile', 'phplibvirt');
					$domaininfo = libvirt_domain_get_info($domain);
					$retval = array_merge($retval, $domaininfo);
					$cpuPercentage = 0;
					$actTime = $this->getUTime();
					if (isset($_SESSION['libvirt'][$data['name']]['lasttime'])) {
						$cpudiff = $retval['cpuUsed'] - $_SESSION['libvirt'][$data['name']]['lastcpu'];
						$timediff = $actTime - $_SESSION['libvirt'][$data['name']]['lasttime'];
						$cpuPercentage = number_format(abs(100 * $cpudiff / ($timediff * $retval['nrVirtCpu'] * 1000000000.0)), 2);

						//error_log($retval['cpuTime'] . ', ' . $cpudiff . '; ' . $timediff . ', ' . $cpuPercentage);
					}
					$_SESSION['libvirt'][$data['name']]['lasttime'] = $actTime;
					$_SESSION['libvirt'][$data['name']]['lastcpu'] = $retval['cpuUsed'];
					$retval['actTime'] = $actTime;
					$retval['cpuTimeOrig'] = $retval['cpuUsed'];
					$retval['cpuTime'] = round($cpuPercentage);
				}
				else {
					// nothing to do, active is already false
				}
			}
			else {
				$retval['status'] = 'nocon';
			}
		}
		catch(Exception $e) {
			Yii::log('getVmStatus: Exception: ' . $e->getTraceAsString(), 'profile', 'phplibvirt');
			//echo '<pre>Exception: ' . print_r($e, true) . '</pre>';
			if (VIR_ERR_NO_DOMAIN != $e->getCode()) {
				throw $e;
			}
			// nothing to do, active is already false
		}
		Yii::log('getVmStatus: return: ' . print_r($retval, true), 'profile', 'phplibvirt');
		return $retval;
	}

	public function checkNode($libvirt) {
		return $this->getConnection($libvirt);
	}

	public function getLastError() {
		$retval = libvirt_get_last_error();
		return $retval;
	}

	private static $xmlTemplate = '
<domain type=\"{$data[\'sstType\']}\">
	<name>{$data[\'sstName\']}</name>
	<uuid>{$data[\'sstUuid\']}</uuid>
	<memory>{$data[\'sstMemory\']}</memory>
	<vcpu>{$data[\'sstVCPU\']}</vcpu>
	<os>
		<type arch=\"{$data[\'sstOSArchitecture\']}\" machine=\"{$data[\'sstOSMachine\']}\">{$data[\'sstOSType\']}</type>
		<boot dev=\"{$data[\'sstOSBootDevice\']}\"/>
		<smbios mode=\"sysinfo\"/>
	</os>
	<sysinfo type=\"smbios\">
		<bios>
			<entry name=\"vendor\">FOSS-Group</entry>
		</bios>
		<system>
			<entry name=\"manufacturer\">FOSS-Group</entry>
			<entry name=\"vendor\">FOSS-Group</entry>
			<entry name=\"serial\">{$data[\'sstUuid\']}</entry>
		</system>
	</sysinfo>
	<features>
		{$features}
	</features>
	<clock offset=\"{$data[\'sstClockOffset\']}\"/>
	<on_poweroff>{$data[\'sstOnPowerOff\']}</on_poweroff>
	<on_reboot>{$data[\'sstOnReboot\']}</on_reboot>
	<on_crash>{$data[\'sstOnCrash\']}</on_crash>
	<devices>
		<emulator>{$data[\'devices\'][\'sstEmulator\']}</emulator>
		<graphics type=\"spice\" port=\"{$data[\'devices\'][\'graphics\'][\'spiceport\']}\" tlsPort=\"0\" autoport=\"no\" listen=\"{$data[\'devices\'][\'graphics\'][\'spicelistenaddress\']}\" passwd=\"{$data[\'devices\'][\'graphics\'][\'spicepassword\']}\">
			<listen type=\"address\" address=\"{$data[\'devices\'][\'graphics\'][\'spicelistenaddress\']}\" />
{$spiceparams}		</graphics>
		<channel type=\"spicevmc\">
			<target type=\"virtio\" name=\"com.redhat.spice.0\"/>
		</channel>
		<video>
			<model type=\"qxl\" vram=\"65536\" heads=\"{$data[\'sstNumberOfScreens\']}\"/>
		</video>
		<input type=\"tablet\" bus=\"usb\"/>
		<controller type=\"usb\" index=\"0\" model=\"ich9-ehci1\">
			<address type=\"pci\" slot=\"0x08\" function=\"0x7\"/>
		</controller>
		<controller type=\"usb\" index=\"0\" model=\"ich9-uhci1\">
			<address type=\"pci\" slot=\"0x08\" function=\"0x0\" multifunction=\"on\"/>
		</controller>
		<controller type=\"usb\" index=\"0\" model=\"ich9-uhci2\">
			<address type=\"pci\" slot=\"0x08\" function=\"0x1\"/>
		</controller>
		<controller type=\"usb\" index=\"0\" model=\"ich9-uhci3\">
			<address type=\"pci\" slot=\"0x08\" function=\"0x2\"/>
		</controller>	
		<redirdev bus=\"usb\" type=\"spicevmc\"></redirdev>
		<redirdev bus=\"usb\" type=\"spicevmc\"></redirdev>
		<redirdev bus=\"usb\" type=\"spicevmc\"></redirdev>
   		<redirfilter>
			<usbdev allow=\"{$data[\'devices\'][\'usb\']}\"/>
		</redirfilter>
		{$devices}
	</devices>
</domain>
';

	public function getXML($data) {
		$data['sstMemory'] = floor($data['sstMemory'] / 1024);
		$features = '';
		foreach($data['sstFeature'] as $feature) {
			$features .= "<$feature/>";
		}
		$devices = '';
		if ($data['devices']['sound']) {
			$devices .= '		<sound model="ac97"/>' . "\n";
		}

		foreach($data['devices']['disks'] as $disk) {
			$devices .= '		<disk type="' . $disk['sstType'] . '" device="' . $disk['sstDevice'] . '">' . "\n";
			if (isset($disk['sstDriverName']) && isset($disk['sstDriverType'])) {
				$devices .= '			<driver name="' . $disk['sstDriverName'] . '" type="' . $disk['sstDriverType'] .
					(isset($disk['sstDriverCache']) && '' != $disk['sstDriverCache'] ? '" cache="' . $disk['sstDriverCache'] : '') . '"/>' . "\n";
			}
			$devices .= '			<source file="' . $disk['sstSourceFile'] . '"/>' . "\n";
			$devices .= '			<target dev="' . $disk['sstDisk'] . '" bus="' . $disk['sstTargetBus'] . '"/>' . "\n";
			if (isset($disk['sstReadonly']) && 'TRUE' == $disk['sstReadonly']) {
				$devices .= '			<readOnly/>' . "\n";
			}
			$devices .= '		</disk>' . "\n";
		}
		foreach($data['devices']['interfaces'] as $interface) {
			$devices .= '		<interface type="' . $interface['sstType'] . '">' . "\n";
			$devices .= '			<source bridge="' . $interface['sstSourceBridge'] . '"/>' . "\n";
			$devices .= '			<mac address="' . $interface['sstMacAddress'] . '"/>' . "\n";
			$devices .= '			<model type="' . $interface['sstModelType'] . '"/>' . "\n";
			$devices .= '		</interface>' . "\n";
		}
		$spiceparams = '';
		if ($data['devices']['graphics']['spiceacceleration']) {
			$spiceparams = '			<image compression="off"/><jpeg compression="never"/><zlib compression="never"/><streaming mode="off"/>' . "\n";
		}

		$template = CPhpLibvirt::$xmlTemplate;
		if (false === eval("\$retval = \"$template\";")) {
			echo "EVAL ERROR!";
		}
		return $retval;
	}

	public function replaceXML($originalxml, $data) {
		$spicePort = $data['spiceport'];
		$listen = $data['newlisten'];
		$xml = $originalxml;
		$pos1 = strpos($xml, '<graphics');
		if (false !== $pos1) {
			$pos2 = strpos($xml, "</graphics>", $pos1 + 1);
			if (false !== $pos2)  {
				$pos3 = strpos($xml, "port='", $pos1 + 1);
				if (false !== $pos3 && $pos3 < $pos2) {
					$start = $pos3 + 6;
					$end = strpos($xml, "'", $start);
					if (false !== $end) {
						$xml = substr_replace($xml, $spicePort, $start, $end - $start);
					}
				}
				$pos3 = strpos($xml, "listen='", $pos1 + 1);
				if (false !== $pos3 && $pos3 < $pos2) {
					$start = $pos3 + 8;
					$end = strpos($xml, "'", $start);
					if (false !== $end) {
						$xml = substr_replace($xml, $listen, $start, $end - $start);
					}
				}
				$pos3 = strpos($xml, "address='", $pos1 + 1);
				if (false !== $pos3 && $pos3 < $pos2) {
					$start = $pos3 + 9;
					$end = strpos($xml, "'", $start);
					if (false !== $end) {
						$xml = substr_replace($xml, $listen, $start, $end - $start);
					}
				}
			}
		}
		return $xml;
	}

	public function generateUUID() {
		if(extension_loaded('uuid')) {
			return uuid_create();
		}
		else {
			return sprintf('%08x-%04x-4%03x-%04x-%04x%04x%04x',
				0xFFFFFFFF & time(),
				mt_rand(0, 0xFFFF),
				mt_rand(0, 0x0FFF),
				mt_rand(0, 0xFFFF) & 0xBFFF,
				mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF)
			);
		}
	}

	public function generateMacAddress() {
		return sprintf('%02x:%02x:%02x:%02x:%02x:%02x', 0x52, 0x54, 0x00, rand(0, 0xFF), rand(0, 0xFF), rand(0, 0xFF));
	}

	public function generateSpicePassword() {
		$dummy	= array_merge(range('0', '9'), range('a', 'z'), range('A', 'Z')/*, array('#','&','@','$','_','%','?','+')*/);

		mt_srand((double)microtime() * 1000000);

		for ($i = 1; $i <= (count($dummy)*2); $i++)
		{
			$swap = mt_rand(0, count($dummy) - 1);
			$tmp = $dummy[$swap];
			$dummy[$swap] = $dummy[0];
			$dummy[0] = $tmp;
		}

		return htmlentities(substr(implode('',$dummy), 0, 12));
	}

	public function nextSpicePort($node) {
		$port = 0;
		$portMin = 5900; //Config::getInstance()->getSpicePortMin();
		$portMax = 5999; // Config::getInstance()->getSpicePortMax();
		$size = $portMax - $portMin + 1;
		$portsUsed = array();
		for ($i = 0; $i < $size; $i++) {
			$portsUsed[$i] = false;
		}

		$server = CLdapServer::getInstance();
		/*
		 * Use this filter for node-wide unique spice ports
		 * '(&(objectClass=sstSpice)(sstNode=' . $node . '))'
		 */
		$result = $server->search('ou=virtualization,ou=services', '(&(objectClass=sstSpice)(sstNode=' . $node . '))', array('sstSpicePort'));
		for($i=0; $i<$result['count']; $i++) {
			$port = $result[$i]['sstspiceport'][0];
			$portsUsed[$port - $portMin] = true;
		}
		$result = $server->search('ou=virtualization,ou=services', '(&(objectClass=sstVirtualizationVirtualMachine)(sstMigrationNode=' . $node . '))', array('sstMigrationSpicePort'));
		for($i=0; $i<$result['count']; $i++) {
			$port = $result[$i]['sstmigrationspiceport'][0];
			$portsUsed[$port - $portMin] = true;
		}

		$port = 0;
		for ($i = 0; $i < $size; $i++) {
			if (!$portsUsed[$i]) {
				$port = $portMin + $i;
				break;
			}
		}

		return $port;
	}

	private static $xmlPoolTemplate = '
<pool type=\"dir\">
	<name>{$data[\'name\']}</name>
	<uuid>{$data[\'uuid\']}</uuid>
	<target>
		<path>{$data[\'path\']}</path>
		<permissions>
			<mode>0770</mode>
			<owner>0</owner>
			<group>3000</group>
		</permissions>
	</target>
</pool>
';

	public function getStoragePoolXML($data) {
		$template = CPhpLibvirt::$xmlPoolTemplate;
		if (false === eval("\$retval = \"$template\";")) {
			echo "EVAL ERROR!";
		}
		return $retval;
	}

	public function createStoragePool($host, $uuid, $path) {
		$data = array();
		$data['uuid'] = $uuid;
		$data['name'] = $data['uuid'];
		$data['path'] = $path;
		$xml = $this->getStoragePoolXML($data);

		$retval = false;
		$con = $this->getConnection($host);
		if (!is_null($con)) {
			Yii::log('createStoragePool: connection ok', 'profile', 'phplibvirt');
			Yii::log('createStoragePool: libvirt_storagepool_define_xml(' . $host . ', ' . $xml . ')', 'profile', 'phplibvirt');
			$pool = libvirt_storagepool_define_xml($con, $xml, 0);
			if (!is_null($pool)) {
				Yii::log('createStoragePool: pool defined', 'profile', 'phplibvirt');
				if (libvirt_storagepool_build($pool)) {
					Yii::log('createStoragePool: pool build', 'profile', 'phplibvirt');
					if (libvirt_storagepool_undefine($pool)) {
						Yii::log('deleteStoragePool: pool undefined', 'profile', 'phplibvirt');
						$retval = true;
						Yii::log('createStoragePool: pool created', 'profile', 'phplibvirt');
					}
				}
			}
		}
		if (!$retval) {
			Yii::log('createStoragePool: error: ' . libvirt_get_last_error(), 'profile', 'phplibvirt');
		}
		return $retval;
	}

	public function deleteStoragePool($host, $uuid, $path) {
		$data = array();
		$data['uuid'] = $uuid;
		$data['name'] = $data['uuid'];
		$data['path'] = $path;
		$xml = $this->getStoragePoolXML($data);

		$retval = false;
		$con = $this->getConnection($host);
		if (!is_null($con)) {
			Yii::log('deleteStoragePool: connection ok', 'profile', 'phplibvirt');
			Yii::log('deleteStoragePool: libvirt_storagepool_define_xml(' . $host . ', ' . $xml . ')', 'profile', 'phplibvirt');
			$pool = libvirt_storagepool_define_xml($con, $xml, 0);
			if (!is_null($pool)) {
				Yii::log('deleteStoragePool: pool defined', 'profile', 'phplibvirt');
				if (libvirt_storagepool_delete($pool)) {
					Yii::log('deleteStoragePool: pool deleted', 'profile', 'phplibvirt');
					if (libvirt_storagepool_undefine($pool)) {
						Yii::log('deleteStoragePool: pool undefined', 'profile', 'phplibvirt');
						$retval = true;
						Yii::log('deleteStoragePool: pool deleted', 'profile', 'phplibvirt');
					}
				}
			}
		}
		if (!$retval) {
			Yii::log('deleteStoragePool: error: ' . libvirt_get_last_error(), 'profile', 'phplibvirt');
		}
		return $retval;
	}

	public function assignStoragePoolToNode($host, $uuid, $path) {
		$data = array();
		$data['uuid'] = $uuid;
		$data['name'] = $data['uuid'];
		$data['path'] = $path;
		$xml = $this->getStoragePoolXML($data);

		$retval = false;
		$con = $this->getConnection($host);
		if (!is_null($con)) {
			Yii::log('assignStoragePoolToNode: connection ok', 'profile', 'phplibvirt');
			try {
				Yii::log('assignStoragePoolToNode: libvirt_storagepool_lookup_by_name(' . $host . ', ' . $uuid . ')', 'profile', 'phplibvirt');
				$pool = libvirt_storagepool_lookup_by_uuid_string($con, $uuid);
				if (is_null($pool) || 0 == $pool) {
					Yii::log('assignStoragePoolToNode: libvirt_storagepool_define_xml(' . $host . ', ' . $xml . ')', 'profile', 'phplibvirt');
					$pool = libvirt_storagepool_define_xml($con, $xml, 0);
					if (!is_null($pool)) {
						Yii::log('assignStoragePoolToNode: pool defined', 'profile', 'phplibvirt');
						if (libvirt_storagepool_create($pool)) {
							Yii::log('assignStoragePoolToNode: pool created', 'profile', 'phplibvirt');
							if (libvirt_storagepool_set_autostart($pool, true)) {
								Yii::log('assignStoragePoolToNode: pool set autostart', 'profile', 'phplibvirt');
								$retval = true;
								Yii::log('assignStoragePoolToNode: pool assigned', 'profile', 'phplibvirt');
							}
						}
					}
				}
				else {
					$retval = true;
				}
			} catch (Exception $e) {
				Yii::log('assignStoragePoolToNode: error: ' . $e->getMessage(), 'profile', 'phplibvirt');
			}
		}
		if (!$retval) {
			Yii::log('assignStoragePoolToNode: error: ' . libvirt_get_last_error(), 'profile', 'phplibvirt');
		}
		return $retval;
	}

	public function removeStoragePoolToNodeAssignment($host, $uuid) {
		$retval = false;
		$con = $this->getConnection($host);
		if (!is_null($con)) {
			Yii::log('removeStoragePoolToNodeAssignment: connection ok', 'profile', 'phplibvirt');
			Yii::log('removeStoragePoolToNodeAssignment: libvirt_storagepool_lookup_by_uuid_string(' . $host . ', ' . $uuid . ')', 'profile', 'phplibvirt');
			$pool = libvirt_storagepool_lookup_by_uuid_string($con, $uuid);
			if (!is_null($pool)) {
				Yii::log('removeStoragePoolToNodeAssignment: pool found', 'profile', 'phplibvirt');
				if (libvirt_storagepool_set_autostart($pool, 0)) { // make sure the pool is not autostarted again, just in case we get interrupted
					Yii::log('removeStoragePoolToNodeAssignment: pool autostart removed', 'profile', 'phplibvirt');
					if (libvirt_storagepool_destroy($pool)) { // stops the pool (but it is still defined)
						Yii::log('removeStoragePoolToNodeAssignment: pool destroyed', 'profile', 'phplibvirt');
						if (libvirt_storagepool_undefine($pool)) {
							Yii::log('removeStoragePoolToNodeAssignment: pool undefined', 'profile', 'phplibvirt');
							$retval = true;
							Yii::log('removeStoragePoolToNodeAssignment: pool assignment removed', 'profile', 'phplibvirt');
						}
					}
				}
			}
		}
		if (!$retval) {
			Yii::log('removeStoragePoolToNodeAssignment: error: ' . libvirt_get_last_error(), 'profile', 'phplibvirt');
		}
		return $retval;
	}
	

	private static $xmlVolumeTemplate = '
<volume>
	<name>{$data[\'name\']}</name>
	<allocation>0</allocation>
	<capacity>{$data[\'capacity\']}</capacity>
	<target>
		<format type=\"qcow2\"/>
		<permissions>
			<owner>0</owner>
			<group>3000</group>
			<mode>0660</mode>
		</permissions>
	</target>
</volume>
';

	public function getVolumeXML($data) {
		$template = CPhpLibvirt::$xmlVolumeTemplate;
		if (false === eval("\$retval = \"$template\";")) {
			echo "EVAL ERROR!";
		}
		return $retval;

	}

	public function createVolumeFile($templatesdir, $pooluuid, $host, $capacity) {
		$volumename = $this->generateUUID();

		$path = $templatesdir;
		if (!file_exists($path)) {
			mkdir($path, 0770);
		}
		$con = $this->getConnection($host);
		Yii::log('createVolumeFile: ' . $host . ', ' . $path . ', ' . $pooluuid, 'profile', 'phplibvirt');
		$pool = libvirt_storagepool_lookup_by_uuid_string($con, $pooluuid);
		$retval = false;
		if (!is_null($pool)) {
			Yii::log('createVolumeFile: pool found', 'profile', 'phplibvirt');
			$data['name'] = $volumename . '.qcow2';
			$data['capacity'] = $capacity;
			Yii::log('createVolumeFile: ' . $this->getVolumeXML($data), 'profile', 'phplibvirt');
			$volume = libvirt_storagevolume_create_xml($pool, $this->getVolumeXML($data));
			if (!is_null($volume)) {
				Yii::log('createVolumeFile: volume created', 'profile', 'phplibvirt');
				$sourcefile = $path . '/' . $volumename . '.qcow2';

				$retval = array('VolumeName' => $volumename, 'SourceFile' => $sourcefile);
			}
		}
		if (false === $retval) {
			Yii::log('createVolumeFile: error: ' . libvirt_get_last_error(), 'profile', 'phplibvirt');
		}
		return $retval;
	}

	public function copyVolumeFile($persistentdir, $disk) {
		$volumename = $this->generateUUID();

		$path = $persistentdir;
		if (!file_exists($path)) {
			mkdir($path, 0770);
		}
		$sourcefile = $path . '/' . $volumename . '.qcow2';
		$pidfile = $path . '/' . $volumename . '.pid';
		Yii::log('copyVolumeFile: ' . $disk->sstSourceFile . ' => ' . $sourcefile, 'profile', 'phplibvirt');

		//exec(sprintf("cp %s %s > /dev/null 2>&1 & echo $! >> %s", $disk->sstSourceFile, $sourcefile, $pidfile));
		//$cmd = sprintf("cp %s %s > /dev/null 2>&1 & echo $! >> %s", $disk->sstSourceFile, $sourcefile, $sourcefile, $pidfile);
		//$cmd = sprintf('{ echo $$ > "%s" ; cp "%s" "%s" > /dev/null 2>&1 && chmod 660 "%s" ; echo $? > "%s" ; } &', $pidfile, $disk->sstSourceFile, $sourcefile, $sourcefile, $returnvaluefile);
		$cmd = sprintf('{ echo $$ > "%s" ; cp "%s" "%s" > /dev/null 2>&1 ; chmod 660 "%s" ; chgrp 3000 "%s" ; } &', $pidfile, $disk->sstSourceFile, $sourcefile, $sourcefile, $sourcefile);
		Yii::log('copyVolumeFile: ' . $cmd, 'profile', 'phplibvirt');
		//$cmd = escapeshellcmd($cmd);
		error_log($cmd);
		exec($cmd);
		//copy($disk->sstSourceFile, $sourcefile);
		sleep(2);
		$pid = file($pidfile);
		unlink($pidfile);

		return array('VolumeName' => $volumename, 'SourceFile' => $sourcefile, 'pid' => (int) rtrim($pid[0]));
	}

	public function deleteVolumeFile($file) {
		if (is_file($file)) {
			if(!is_writeable($file)) {
				chmod($file,0666);
			}
			return unlink($file);
		}
		return true;
	}

	private static $xmlBackingStoreVolumeTemplate = '
<volume>
	<name>{$data[\'name\']}</name>
	<allocation>0</allocation>
	<capacity>{$data[\'capacity\']}</capacity>
	<backingStore>
		<path>{$data[\'goldenimagepath\']}</path>
		<format type=\"qcow2\"/>
	</backingStore>
	<target>
		<format type=\"qcow2\"/>
		<permissions>
			<owner>0</owner>
			<group>3000</group>
			<mode>0660</mode>
		</permissions>
	</target>
</volume>';

	public function getBackingStoreVolumeXML($data) {
		$template = CPhpLibvirt::$xmlBackingStoreVolumeTemplate;
		if (false === eval("\$retval = \"$template\";")) {
			echo "EVAL ERROR!";
		}
		return $retval;
	}

	public function createBackingStoreVolumeFile($templatesdir, $pooluuid, $goldenimagepath, $host, $capacity) {
		$volumename = $this->generateUUID();
		$path = $templatesdir;

		Yii::log('createBackingStoreVolumeFile: ' . $host . ', ' . $path . ',' . $pooluuid . ',' . $goldenimagepath, 'profile', 'phplibvirt');

		$retval = false;
		$con = $this->getConnection($host);
		if (!is_null($con)) {
			Yii::log('createBackingStoreVolumeFile: connection ok', 'profile', 'phplibvirt');
			$pool = libvirt_storagepool_lookup_by_uuid_string($con, $pooluuid);
			if (!is_null($pool)) {
				Yii::log('createBackingStoreVolumeFile: pool found', 'profile', 'phplibvirt');
//				$goldenimagevolume = libvirt_storagevolume_lookup_by_name($pooluuid, $goldenuuid);
//				if (!is_null($goldenimagevolume)) {
//					$goldenimagepath = libvirt_storagevolume_get_path($goldenimagevolume);
//					if (!is_null($goldenimagepath)) {
						$data['name'] = $volumename . '.qcow2';
						$data['capacity'] = $capacity;
						$data['goldenimagepath'] = $goldenimagepath; //$goldenimagepath;
						Yii::log('createBackingStoreVolumeFile: ' . $this->getBackingStoreVolumeXML($data), 'profile', 'phplibvirt');
						$volume = libvirt_storagevolume_create_xml($pool, $this->getBackingStoreVolumeXML($data));
						if (!is_null($volume)) {
							Yii::log('createBackingStoreVolumeFile: volume created', 'profile', 'phplibvirt');
							$sourcefile = $path . '/' . $volumename . '.qcow2';

							$retval = array('VolumeName' => $volumename, 'SourceFile' => $sourcefile);
						}
//					}
//				}
			}
		}
		if (false === $retval) {
			Yii::log('createBackingStoreVolumeFile: error: ' . libvirt_get_last_error(), 'profile', 'phplibvirt');
		}
		return $retval;
	}

	public function copyIsoFile($source, $dest) {
		$pidfile = $dest . '.pid';
		Yii::log('copyIsoFile: ' . $source . ' => ' . $dest, 'profile', 'phplibvirt');
		exec(sprintf("cp \"%s\" \"%s\" > /dev/null 2>&1 & echo $! >> %s", $source, $dest, $pidfile));
		//copy($disk->sstSourceFile, $sourcefile);
		sleep(2);
		$pid = file($pidfile);
		unlink($pidfile);

		return array('pid' => (int) rtrim($pid[0]));
	}

	public function deleteIsoFile($file) {
		if (is_file($file)) {
			if(!is_writeable($file)) {
				chmod($file,0666);
			}
			return unlink($file);
		}
		return true;
	}

	public function checkPid($pid){
		try{
			$result = shell_exec(sprintf("ps %d", $pid));
			Yii::log('checkPid: ' . $pid . ': ' . print_r($result, true), 'profile', 'phplibvirt');
			if(count(preg_split("/\n/", $result)) > 2) {
				return true;
			}
		}catch(Exception $e){}

		return false;
	}

	protected function rmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != '.' && $object != '..') {
					if (filetype($dir . '/' . $object) == 'dir') {
						//$this->rmdir($dir . '/' . $object);
					}
					else {
						unlink($dir . '/' . $object);
					}
				}
			}
			//reset($objects);
			rmdir($dir);
		}
	}

	protected function getUTime() {
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}

	protected function getConnection($connection) {
		if (!isset($this->connections[$connection])) {
			$this->connections[$connection] = libvirt_connect($connection, false);
			Yii::log('getConnection: libvirt_connect (' . $connection . '): ' . $this->connections[$connection], 'profile', 'phplibvirt');
			if (false === $this->connections[$connection]) {
				Yii::log('getConnection: libvirt_connect failed!', 'profile', 'phplibvirt');
			}
		}
		return $this->connections[$connection];
	}

	/*
	 * get singleton instance of CPhpLibvirt
	 */
	public static function getInstance() {
		if (is_null(self::$_instance)) {
			if (isset(Yii::app()->params['useLibvirtDummy']) && Yii::app()->params['useLibvirtDummy']) {
				self::$_instance = new CPhpLibvirtDummy();
			}
			else {
				self::$_instance = new CPhpLibvirt();
			}
		}
		return self::$_instance;
	}

	/*
	 * Don't allow clone from outside
	 */
	private function __clone() {}
}

class CPhpLibvirtException extends CException {
}
