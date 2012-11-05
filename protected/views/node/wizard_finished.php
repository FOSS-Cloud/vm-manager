<?php
if ($event->step):
	echo CHtml::tag('p', array(), 'The wizard finished on step '.$event->sender->getStepLabel($event->step));
	echo CHtml::tag('p', array(), 'Data collected so far is:');
	foreach ($event->data as $step=>$data):
		echo CHtml::tag('h2', array(), $event->sender->getStepLabel($step));
		echo ('<ul>');
		foreach ($data as $k=>$v)
			echo "<li>$k: $v</li>";
	endforeach;
else:
	echo '<p>The wizard did not start</p>';
endif;
