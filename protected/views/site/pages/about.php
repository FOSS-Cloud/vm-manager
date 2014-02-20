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

$this->pageTitle=Yii::app()->name . ' - About';
$this->breadcrumbs=array(
	'About',
);

$this->title = 'About';
?>
<p>The FOSS-Cloud is the foundation to build Windows or Linux based SaaS-, Terminal Server-, Virtual Desktop Infrastructure (VDI) or virtual Server-Environments.</p>
<p>The FOSS-Cloud solution is the most advanced pure Open Source Cloud in the marketplace today. </p>
<p>Before using, the FOSS-Cloud team would like to remind you that the primary means of sustaining the development of FOSS-Cloud is via contributions by users such as yourself. FOSS-Cloud is now and will continue to be totally free of charge; however, it takes money and resources to make FOSS-Cloud available. If you are able, please consider donating to the FOSS-Cloud Project.</p>
<div  style="float: left;">
<p><a href="http://sourceforge.net/donate/index.php?group_id=390416"><img src="<?php echo Yii::app()->baseUrl;?>/images/project-support.jpg" width="88" height="32" border="0" alt="Support This Project" /> </a></p>
<p>Thank you for using FOSS-Cloud</p>
<p>The FOSS-Cloud Team</p>
</div>
<div style="float: right;">
<h2>Links</h2>
<a target="blank" href="http://www.foss-cloud.org/">Documentation</a><br/>
<a target="blank" href="http://www.foss-cloud.org/en/wiki/Spice-Client">Spice-Client (with protocol handler) download</a>
</div> <br style="clear: both;" />
<br/><br/>

<h2>Version <i><?= Yii::app()->getSession()->get('version', ''); ?></i></h2><br/>
<h2>FOSS-Cloud ID <i><?= Yii::app()->getSession()->get('cloudid', ''); ?></i></h2><br/>
<h2>Projects incorporated within the FOSS-Cloud</h2>
<table bgcolor="white"    border="1" cellpadding="10" cellspacing="0" >
<tr>
<td><b>Open Source Solution</b></td><td><b>Information</b></td><td><b>License</b></td>
</tr>
<tr>
<td>Gentoo</td><td> <a target="blank" href="http://www.gentoo.org">Web-Site</a></td><td> <a target="blank" href="http://www.gnu.org/licenses">GPL-2</a>
</tr>
<tr>
<td>SystemRescueCd</td><td> <a target="blank" href="http://www.sysresccd.org">Web-Site</a></td><td> <a target="blank" href="http://www.opensource.org/licenses/gpl-license.html">GPL-2</a>
</tr>
<tr>
<td>KVM, Kernel Based Virtual Machine</td><td> <a target="blank" href="http://www.linux-kvm.org">Web-Site</a></td><td> <a target="blank" href="http://www.gnu.org/licenses">GPL</a>
</tr>
<tr>
<td>Qemu, generic and open source machine emulator and virtualizer</td><td> <a target="blank" href="http://wiki.qemu.org/Index.html">Web-Site</a></td><td> <a target="blank" href="http://www.gnu.org/licenses">GPL</a>
</tr>
<tr>
<td>Spice, protocol</td><td> <a target="blank" href="http://www.spice-space.org">Web-Site</a></td><td> <a target="blank" href="http://www.gnu.org/licenses">GPL</a>
</tr>
<tr>
<td>libvirt</td><td> <a target="blank" href="http://www.libvirt.org/">Web-Site</a></td><td> <a target="blank" href="http://www.gnu.org/licenses/lgpl-2.1.html">LGPLv2</a>
</tr>
<tr>
<td>PHP-libvirt</td><td> <a target="blank" href="http://libvirt.org/php/">Web-Site</a></td><td> <a target="blank" href="http://www.php.net/license/3_01.txt">PHP-3.01</a>
</tr>
<tr>
<td>PHP, scripting language</td><td> <a target="blank" href="http://www.php.net">Web-Site</a></td><td> <a target="blank" href="http://www.php.net/license/3_01.txt">PHP-3.01</a>
</tr>
<tr>
<td>Yii Framework (<?=Yii::getVersion();?>)</td><td> <a target="blank" href="http://www.yiiframework.com">Web-Site</a></td><td> <a target="blank" href="http://www.yiiframework.com/license/">BSD</a>
</tr>
<tr>
<td>FOSS-Cloud, Cloud technology</td><td> <a target="blank" href="http://www.foss-cloud.org">Web-Site</a></td><td> <a target="blank" href="http://ec.europa.eu/idabc/eupl.html">EUPL</a>
</tr>
</table>


<h2>Companies involved into the FOSS-Cloud-Project</h2>
<table bgcolor="white"    border="1" cellpadding="10" cellspacing="0" >
<tr>
<td><b>Company</b></td><td><b>Informations</b></td>
</tr>
<tr>
<td><a target="blank" href="http://www.foss-group.com/en/home/">FOSS-Group</a></td><td>Location Switzerland and Germany</td>
</tr>
<tr>
<td><a target="blank" href="http://www.devroom.de">devroom.de</a></td><td>Location Germany</td>
</tr>
<tr>
<td><a target="blank" href="http://www.limbas.com">Limbas GmbH</a></td><td>Location Germany</td>
</tr>
</table>
