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
	'LDAP Objclasses',
);

$this->title = 'List LDAP ObjectClasses';

?>
<ul>
<?php
function cmp($a, $b)
{
    return strcasecmp($a, $b);
}
uksort($objclasses, 'cmp');
foreach($objclasses as $class) {
	echo '<li><b>' . implode(', ', $class->getNames()) . '</b>';
	if (!is_null($class->getSup())) {
		$sup = $class->getSup();
		while (!is_null($sup)) {
			echo ' -> ' . $sup;
			if (isset($objclasses[$sup])) {
				$parent = $objclasses[$sup];
				if (!is_null($parent)) {
					$sup = $parent->getSup();
				}
			}
			else {
				echo '(???)';
				$sup = null;
			}
		}
	}
	echo '<br/>';
	$attrs = 'MUST:<br/><ul>';
	foreach($class->getAttributes() as $name => $attribute) {
		if (isset($attribute['mandatory']) && $attribute['mandatory']) {
			$attrs .= "<li>$name</li>";
		}
	}
	if ($attrs != 'MUST:<br/><ul>') {
		echo $attrs . '</ul>';
	}
	$attrs = 'MAY:<br/><ul>';
	foreach($class->getAttributes() as $name => $attribute) {
		if (!isset($attribute['mandatory']) || !$attribute['mandatory']) {
			$attrs .=  "<li>$name</li>";
		}
	}
	if ($attrs != 'MAY:<br/><ul>') {
		echo $attrs . '</ul>';
	}
	echo '</li>';
}
?>
</ul>
