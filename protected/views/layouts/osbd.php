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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />
	<link rel="icon" href="<?= Yii::app()->baseUrl;?>/favicon.png" type="image/png" />
	<!-- blueprint CSS framework -->
	<link rel="stylesheet" type="text/css" href="<?php echo $this->cssBase ?>/screen.css" media="screen, projection" />
	<link rel="stylesheet" type="text/css" href="<?php echo $this->cssBase ?>/print.css" media="print" />
	<!--[if lt IE 8]>
	<link rel="stylesheet" type="text/css" href="<?php echo $this->cssBase ?>/ie.css" media="screen, projection" />
	<![endif]-->

	<link rel="stylesheet" type="text/css" href="<?php echo $this->cssBase ?>/main.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo $this->cssBase ?>/form.css" />

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>
<?php foreach ($this->header as $header) {
	echo "\t" . $header . "\n";
}
?>
</head>
<body>
<?php
	$lang = Yii::app()->user->getState('lang', 'en');
	$version = Yii::app()->getSession()->get('version', null);
	if (is_null($version)) {
		$filename = '/etc/os-release';
		if (is_file($filename)) {
			$params = parse_ini_file($filename);
			$version = $params['VERSION'];
		}
		else {
			$version = Yii::app()->params['virtualization']['version'];
		}
		Yii::app()->getSession()->add('version', $version);
	}
	$cloudid = Yii::app()->getSession()->get('cloudid', null);
	if (is_null($cloudid)) {
		$cloudid = LdapNameless::model()->findByDn('ou=version,ou=configuration,ou=virtualization,ou=services');
		if (!is_null($cloudid)) {
			$cloudid = $cloudid->sstFOSSCloudID;
		}
		Yii::app()->getSession()->add('cloudid', $cloudid);
	}
?>
<div class="container" id="page">
	<div id="header">
		<div id="logo"></div>
		<div class="message">
		<?php if (file_exists(Yii::app()->basePath . '/messages/.fc-message')) readfile(Yii::app()->basePath . '/messages/.fc-message'); ?>
		</div>
		<div id="languages">
			<form action="<?php echo $this->createUrl('/site/changeLanguage')?>" method="post">
			<?php echo $this->getLanguageSelector($lang); ?>
			</form>
		</div>

 		<br style="clear: both;" />
	</div><!-- header -->
	<div id="mainmenu">
		<?php $this->widget('zii.widgets.CMenu',array(
			'items'=>array(
				array('label'=>Yii::t('menu', 'Home'), 'url'=>array('/site/login')),
				array('label'=>Yii::t('menu', 'About'), 'url'=>array('/site/page', 'view'=>'about')),
				array('label'=>Yii::t('menu', 'Contact'), 'url'=>array('/site/page', 'view'=>'contact')),
/*
				array('label'=>'Admin', 'url'=>array('/site/admin'),
					'visible'=>Yii::app()->user->isGuest,
					'itemOptions' => array('style' => 'float: right')
				),
*/
				array('label'=>'Logout ('.Yii::app()->user->name.')', 'url'=>array('/site/logout'),
					'visible'=>!Yii::app()->user->isGuest,
					'itemOptions' => array('style' => 'float: right')
				),
			),
		)); ?>
	</div><!-- mainmenu -->
	<!--
	<?php $this->widget('zii.widgets.CBreadcrumbs', array(
		'links'=>$this->breadcrumbs,
	)); ?>  --><!-- breadcrumbs -->

	<br/>
	<div class="container">
<?php
	if (isset($this->submenu) && 0 < count($this->submenu) && !Yii::app()->user->isGuest) {
		if (!is_null(Yii::app()->getSession()->get('simpleLink', null))) {
			$simplelink = Yii::app()->getSession()->get('simpleLink');
			//echo '<pre>' . print_r($simplelink, true) . '</pre>';
			Yii::app()->controller->submenu['simpleLink'] = array(
				'label' => 'Temporary links',
				'items' => array('s1' => array(
					'label' => $simplelink['label'],
					'url' => $simplelink['url'],
				))
			);
		}
?>
		<div id="submenu" class="span-5">
<?php
		$js = "";
		foreach($this->submenu as $name => $items) {
?>
    		<div class="sidebar">
<?php
			$this->beginWidget('ext.zii.CSubMenuPortlet', array(
				'title'=>$items['label'],
				'name' => $name,
				'show' => $name == $this->activesubmenu,
				'static' => (isset($items['static']) && $items['static']),
				'htmlOptions' => array('style'=>'margin-top: ' . ( isset($items['static']) &&  $items['static'] ? 30 : 0) . 'px;')
			));
			if (isset($items['items'])) {
				$this->widget('zii.widgets.CMenu', array(
					'items'=>$items['items'],
					'htmlOptions'=>array('class'=>'submenu'),
				));
			}
			$this->endWidget();
?>
			</div>
<?php
		}
?>
		</div>
<?php
	}
?>
<?php if (isset($this->submenu) && 0 < count($this->submenu) && !Yii::app()->user->isGuest) : ?>
		<div class="span-22 last">
<?php else : ?>
		<div class="span-27 last">
<?php endif; ?>
			<div id="content">
				<h1 style="float: left;"><?=$this->title;?></h1>
<?php if (!is_null($this->helpurl)) : ?>
				<form action="<?=$this->helpurl;?>" target="_blank"><button id="helpurl" type="submit" style="float: right;"><?=Yii::t('osbd', 'Help');?></button></form><br class="clear"/>
<?php
	Yii::app()->clientScript->registerScript('helpButton', <<<EOS
	$("#helpurl").button({
		icons: { primary: "ui-icon-help" },
	});
EOS
, CClientScript::POS_END);
?>
<?php else : ?>
<br class="clear" />
<?php endif; ?>
			<?php echo $content; ?>
			</div><!-- content -->
		</div>
	</div>
	<div id="footer">
		Version <?= $version; ?><br/>
		on server <i><?= gethostname();?></i><br/>
		Copyright &copy; <?php echo date('Y'); ?> by FOSS-Group.<br/>
		All Rights Reserved.<br/>
	</div><!-- footer -->
</div><!-- page -->
</body>
</html>