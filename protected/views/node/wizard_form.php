<?php
echo $event->sender->menu->run();
echo '<div>Step '.$event->sender->currentStep.' of '.$event->sender->stepCount;
echo '<h3>'.$event->sender->getStepLabel($event->step).'</h3>';
if(Yii::app()->user->hasFlash('notice')) {
	echo CHtml::tag('div',array('class'=>'flash-success'),Yii::app()->user->getFlash('notice'));
}
echo CHtml::tag('div',array('class'=>'form'),$form);
if (!is_null($jscript)) {
	Yii::app()->clientScript->registerScript('wizardjscript', $jscript, CClientScript::POS_END);
}
echo '</div>';