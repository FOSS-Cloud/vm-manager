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
	'VM Counter',
);
$this->title = 'List of VM\'s';

Yii::app()->clientScript->registerCss('vmcounter', <<<EOS
ul
{
	padding-left: 25px;
	margin: 0;
}
ul li
{
	position: relative;
}
li.poolline
{
	border-bottom: 1px dotted black;
	margin-bottom: 4px;
}
		
span.title
{
	font-weight: bold;
}

span.pname
{
	position: absolute;
	left: 160px;
}
span.vname
{
	position: absolute;
	left: 135px;
}
		
span.vmtype
{
	position: absolute;
	left: 350px;
}

span.tcount
{
	position: absolute;
	bottom: 2px;
	right: 160px;
}

span.dcount
{
	position: absolute;
	bottom: 2px;
	right: 100px;
}

span.scount
{
	position: absolute;
	bottom: 2px;
	right: 20px;
}

EOS
);

if (!isset($_GET['print'])) {
?>
<a href="?print" target="_blank">print</a><br/><br/>
<?php 
}
else {
	$user = LdapUser::model()->findByDn('uid=' . Yii::app()->user->getUID() . ',ou=people');
?>
<pre>



                                                           Firma         ____________________________________

                                                           Strasse       ____________________________________

                                                           PLZ City      ____________________________________

                                                           Country       ____________________________________
                                                     

FOSS-Group GmbH
Bismarckalle 9
D-79098 Freiburg i.Br.






To get support, send this form duly signed to support@foss-group.com and via mail to the address above. 
We will get in contact with you! The form has also to be sent for the annual renewal of the subscription.


Subscription Report:

Introduction
The Subscription Report specifies the number of subscription services for which the subscription fee is
paid for the following periods.

The Subscription Report is subject to the customer’s Volume Subscription Agreement (VSA), Volume Sub-
scription Agreement for Schools (VSAS), Corporate Subscription Agreement (CSA) or Master Subscription 
Agreement (MSA) and the regional version of the FOSS Group Business Customer Agreement.

Subscription Agreement
The customer has concluded the following Subscription Agreement with the FOSS Group:

   Volume Subscription Agreement (VSA)         Volume Subscription Agreement for Schools (VSAS)
   Corporate Subscription Agreement (CSA)
   Master Subscription Agreement (MSA)


FOSS-Cloud ID:                    <?= Yii::app()->getSession()->get('cloudid', ''); ?>


Admin:                            <?php echo $user->cn; ?>

First Name:                       <?= $user->gn; ?>

Surname:                          <?= $user->sn; ?>

Email:                            <?= $user->mail; ?>


</pre>
<?php
} 
?>

<div style="position: relative; border-bottom: 1px solid black; padding-bottom: 4px;">Description
<span class="pname" style="left: 180px;">Name</span>
<span class="tcount" style="right: 140px;">Template</span>
<span class="dcount" style="right: 70px;">Desktop</span>
<span class="scount" style="right: 10px;">Server</span>
<br style="clear: both;" />
</div>
<ul style="list-style: none; padding-left: 0;">
	<li><span class="title"><br/>Templates</span>
		<ul>
		<?php
			$tcount = $dcount = $scount = 0;
			foreach($tpools as $pool) {
				echo '<li class="poolline"><span class="title">Pool</span> <span class="pname">' . $pool->sstDisplayName . '</span>';
				echo '<ul>';
				foreach($pool->vms as $vm) {
					echo '<li><span class="title">VM</span> <span class="vname">' . $vm->sstDisplayName . '</span>';
				}
				$tcount += count($pool->vms);
				echo '</ul><span class="title">Total No. of VM\'s</span>' . '<span class="tcount">' . count($pool->vms) . '</span>';
				echo '</li>';
			}
		?>
		</ul>
	</li>
	<li><span class="title"><br/>Persistent VM's</span>
		<ul>
		<?php
			foreach($ppools as $pool) {
				$pdcount = 0;
				$pscount = 0;
				echo '<li class="poolline"><span class="title">Pool</span> <span class="pname">' . $pool->sstDisplayName . '</span>';
				echo '<ul>';
				foreach($pool->vms as $vm) {
					echo '<li><span class="title">VM</span> <span class="vname">' . $vm->sstDisplayName . '</span><span class="vmtype">' . $vm->sstVirtualMachineSubType . '</span>';
					$pdcount += 'Server' === $vm->sstVirtualMachineSubType ? 0 : 1;
					$pscount += 'Server' === $vm->sstVirtualMachineSubType ? 1 : 0;
				}
				$dcount += $pdcount;
				$scount += $pscount;
				echo '</ul><span class="title">Total No. of VM\'s</span>' . '<span class="dcount">' . (0 === $pdcount ? '&nbsp;' : $pdcount) . '</span><span class="scount">' . (0 === $pscount ? '&nbsp;' : $pscount) . '</span>';
				echo '</li>';
			}
		?>
		</ul>
	</li>
	<li><span class="title"><br/>Dynamic VM's</span>
		<ul>
		<?php
			foreach($dpools as $pool) {
				echo '<li class="poolline"><span class="title">Pool</span> <span class="pname">' . $pool->sstDisplayName . '</span>';
				echo '<ul>';
				foreach($pool->goldenVms as $vm) {
					echo '<li><span class="title">Golden Image</span> <span class="vname">' . $vm->sstDisplayName . '</span>';
				}
				$dcount += $pool->sstBrokerMaximalNumberOfVirtualMachines;
				echo '</ul><span class="title">Max No. of VM\'s</span>' . '<span class="dcount">' . $pool->sstBrokerMaximalNumberOfVirtualMachines . '</span>';
				echo '</li>';
			}
		?>
		</ul>
	</li>
</ul>
<br/>
<div style="position: relative; padding-right: 20px; border-top: 1px solid black; padding-top: 4px;">Total No. of VM's
<span class="tcount"><?php echo $tcount?></span>
<span class="dcount"><?php echo $dcount?></span>
<span class="scount"><?php echo $scount?></span>
</div>

<?php 
if (isset($_GET['print'])) {
?>
<pre>








Place: __________________________     Date: _____________________________    Signature: ________________________

</pre>
<?php 
}
?>