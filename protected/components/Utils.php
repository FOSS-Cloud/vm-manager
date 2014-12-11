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
 * Utils class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.8
 */

class Utils {

	public static function executeScriptASync($scriptPath, $params) {
		$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);

		$wizardDir = Yii::getPathOfAlias('application.runtime.wizard');
		$pidfile = $wizardDir . '/' . $uuid . '.pid';
		$outputfile = $wizardDir . '/' . $uuid . '.out';
		$retvalfile = $wizardDir . '/' . $uuid . '.ret';
		//$cmd = sprintf("(%s %s > %s 2>&1; echo $? > %s )& echo $! > %s", $scriptPath, $params, $outputfile, $retvalfile, $pidfile);
		//error_log($cmd);
		//exec($cmd);
 		//sleep(2);
		//$pid = file($pidfile);
		//unlink($pidfile);
		$cmd = $scriptPath;
		Yii::log('executeScriptASync: ' . $cmd, 'profile', 'utils');

		$output = shell_exec($cmd);
		$fp = fopen($outputfile, "w");
		fputs ($fp, $output);
		fclose ($fp);
		$fp = fopen($retvalfile, "w");
		$output = CJSON::decode($output);
		fputs ($fp, $output['Return Code']);
		fclose ($fp);
		$pid[] = "270165";

		return array('outputfile' => $outputfile, 'retvalfile' => $retvalfile, 'pid' => (int) rtrim($pid[0]));
	}

	private static $testcount = 0;
	public static function readScriptReturn($retvalfile, $pid) {
		$retval = false;
		$count = Yii::app()->getSession()->get('utilsdummy.count', Utils::$testcount);
		Yii::app()->getSession()->add('utilsdummy.count', $count - 1);
		if (!Utils::checkPid($pid) && 0 >= $count) {
			Yii::app()->getSession()->remove('utilsdummy.count');
			$retval = file($retvalfile);
			Yii::log('readScriptReturn: ' . print_r($retval, true), 'profile', 'utils');
			unlink($retvalfile);
		}
		return $retval;
	}

	public static function readScriptOutput($outputfile, $pid) {
		$retval = false;
		$count = Yii::app()->getSession()->get('utilsdummy.count', Utils::$testcount);
		Yii::app()->getSession()->add('utilsdummy.count', $count - 1);
		if (!Utils::checkPid($pid) && 0 >= $count) {
			Yii::app()->getSession()->remove('utilsdummy.count');
			$retval = file($outputfile);
			Yii::log('readScriptOutput: ' . print_r($retval, true), 'profile', 'utils');
			unlink($outputfile);
		}
		return $retval;
	}

	private static function checkPid($pid){
		if (270165 == $pid) return false;
	    try{
	        $result = shell_exec(sprintf("ps %d", $pid));
	        if(count(preg_split("/\n/", $result)) > 2) {
	            return true;
	        }
	    }catch(Exception $e){}

	    return false;
	}

	public static function executeScriptASyncSsh($host, $username, $password, $scriptPath, $params) {
		$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);

		$tmpDir = '/tmp';
		$outputfile = $tmpDir . '/' . $uuid . '.out';
		$retvalfile = $tmpDir . '/' . $uuid . '.ret';
		$cmd = sprintf("(%s %s > %s 2>&1; echo $? > %s )& echo $!", $scriptPath, $params, $outputfile, $retvalfile);
		Yii::log('executeScriptASyncSsh: ' . $cmd, 'profile', 'utils');
		$connection = CPhpSsh::getInstance()->getConnection($host, $username, $password);
		$resource = $connection['resource'];
		$stream = ssh2_exec($resource, $cmd);
		stream_set_blocking($stream, true);
		$pid = stream_get_contents($stream);
		fclose($stream);

		return array('outputfile' => $outputfile, 'retvalfile' => $retvalfile, 'pid' => (int) rtrim($pid));
	}

	public static function readScriptReturnSsh($host, $username, $password, $retvalfile, $pid) {
		$retval = false;
		$count = Yii::app()->getSession()->get('utilsdummy.count', Utils::$testcount);
		Yii::app()->getSession()->add('utilsdummy.count', $count - 1);
		if (!Utils::checkPidSsh($host, $username, $password, $pid) && 0 >= $count) {
			Yii::app()->getSession()->remove('utilsdummy.count');
			$connection = CPhpSsh::getInstance()->getConnection($host, $username, $password);
			$resource = $connection['resource'];
			$stream = ssh2_exec($resource, sprintf('cat %s', $retvalfile));
			stream_set_blocking($stream, true);
			$retval = stream_get_contents($stream);
			fclose($stream);
			Yii::log('readScriptReturnSsh: ' . print_r($retval, true), 'profile', 'utils');
			//unlink($retvalfile);
		}
		return $retval;
	}

	public static function readScriptOutputSsh($host, $username, $password, $outputfile, $pid) {
		$retval = false;
		$count = Yii::app()->getSession()->get('utilsdummy.count', Utils::$testcount);
		Yii::app()->getSession()->add('utilsdummy.count', $count - 1);
		if (!Utils::checkPidSsh($host, $username, $password, $pid) && 0 >= $count) {
			Yii::app()->getSession()->remove('utilsdummy.count');
			$connection = CPhpSsh::getInstance()->getConnection($host, $username, $password);
			$resource = $connection['resource'];
			$stream = ssh2_exec($resource, sprintf('cat %s', $outputfile));
			stream_set_blocking($stream, true);
			$retval = stream_get_contents($stream);
			fclose($stream);
			Yii::log('readScriptOutputSsh: ' . print_r($retval, true), 'profile', 'utils');
			//unlink($outputfile);
		}
		return $retval;
	}

	private static function checkPidSsh($host, $username, $password, $pid){
		try{
			$connection = CPhpSsh::getInstance()->getConnection($host, $username, $password);
			$resource = $connection['resource'];
			$stream = ssh2_exec($resource, sprintf("ps %d", $pid));
			stream_set_blocking($stream, true);
			$output = stream_get_contents($stream);
			fclose($stream);
			if(count(preg_split("/\n/", $output)) > 2) {
				return true;
			}
		}catch(Exception $e){}

		return false;
	}

	public static function getIpRange($range) {
		$base = Utils::getBaseData($range);
		$hostmin = $base['network'] + 1;
		$hostmax = $base['broadcast'] - 1;
		return array('network' => long2ip($base['network']), 'broadcast' => long2ip($base['broadcast']), 'hostmin' => long2ip($hostmin), 'hostmax' => long2ip($hostmax));
	}

	public static function overlapRanges($range1, $range2) {
		$base1 = Utils::getBaseData($range1);
		//echo "$range1: ${base1['network']}-${base1['broadcast']}<br/>";
		$base2 = Utils::getBaseData($range2);
		//echo "$range2: ${base2['network']}-${base2['broadcast']}<br/>";
		return ($base2['network'] <= $base1['broadcast']) && ($base1['network'] <= $base2['broadcast']);
	}

	public static function isRangeInRange($outer, $inner) {
		$base1 = Utils::getBaseData($outer);
		//echo "$outer: ${base1['network']}-${base1['broadcast']}<br/>";
		$base2 = Utils::getBaseData($inner);
		//echo "$inner: ${base2['network']}-${base2['broadcast']}<br/>";
		return ($base1['network'] <= $base2['network']) && ($base1['broadcast'] >= $base2['broadcast']);
	}

	public static function isIpInRange($ip, $range) {
		list($range, $netmask) = explode('/', $range, 2);
		$parts = explode('.', $range);
		while(count($parts)<4) $parts[] = '0';
		list($a,$b,$c,$d) = $parts;
		$range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b,empty($c)?'0':$c,empty($d)?'0':$d);
		$range_dec = ip2long($range);
		$ip_dec = ip2long($ip);

		$wildcard_dec = pow(2, (32-$netmask)) - 1;
		$netmask_dec = ~ $wildcard_dec;

		return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
	}

	public static function getMacAddress($ip) {
		$line = exec('arp -an ' . $ip); //, $output);
		$parts = explode(' ', $line);
		if (isset($parts[3]) && 1 == preg_match('/^([0-9A-F]{2}:){5}([0-9A-F]{2})$/i', $parts[3])) {
			return strtolower($parts[3]);
		}
		return null;
	}

	public static function generatePassword() {
		return CPhpLibvirt::getInstance()->generateUUID();
	}

	private static function getBaseData($range) {
		list($range, $netmask) = explode('/', $range, 2);
		$parts = explode('.', $range);
		while(count($parts)<4) $parts[] = '0';
		list($a,$b,$c,$d) = $parts;
		$range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b,empty($c)?'0':$c,empty($d)?'0':$d);
		$range_dec = ip2long($range);

		$wildcard_dec = pow(2, (32-$netmask)) - 1;
		$netmask_dec = ~ $wildcard_dec;

		$network = $range_dec & $netmask_dec;
		$broadcast = $range_dec | $wildcard_dec;

		return array('network' => $network, 'broadcast' => $broadcast);
	}
}