<?php
echo '<ul>';
foreach($patches as $key => $patch) {
	echo '<li><a href="' . $this->createUrl('patch/start', array('name' => $key)) . '">' . $patch['title'] . '</a><br />' . $patch['description'] . '</li>';
}
echo '</ul>';
