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

$this->breadcrumbs=array(
	'Diagnostics'=>array('/diagnostics'),
	'VM Template Infos',
);
$this->title = 'VM Template Infos';

$vmUse = Yii::app()->user->hasRight('templateVM', 'Use', 'All');

?>
<ul>
<?php
foreach($vms as $dn => $vm) {
	echo '<li><a href="?dn=' . $dn . '">' .
	($vm['selected'] ? '<b>&gt;&gt; ' : '') .
	 $vm['name'] .
	($vm['selected'] ? '</b>' : '') .
	 '</a></li>';
}
?>
</ul>
<br/>
<ul>
<?php
if (!is_null($libvirturi)) {
	echo '<li><b>Libvirt URI</b><br/><pre>' . $libvirturi . '</pre></li>';
}
if (!is_null($spiceuri) && $vmUse) {
	echo '<li><b>SPICE URI</b><br/><a href="' . $spiceuri . '"><pre>' . $spiceuri . '</pre></a></li>';
}
if (!is_null($startxml)) {
	echo '<li><b>Start XML</b><br/><div style="overflow: auto;"><pre>' . print_r(htmlspecialchars($startxml), true) . '</pre></div></li>';
}
?>
</ul>