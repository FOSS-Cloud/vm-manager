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
 * http://www.osor.eu/eupl
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
	'LDAP AttrTypes',
);
$this->title = 'List LDAP AttributeTypes';

?>
<ul>
<?php
function cmp($a, $b)
{
    return strcasecmp($a, $b);
}
uksort($attrtypes, 'cmp');
foreach($attrtypes as $name => $attr) {
	echo '<li><b>' . $name . ' (' . implode(', ', $attr->getNames()) . ')</b>';
	if (!is_null($attr->getSup())) {
		$sup = $attr->getSup();
		while (!is_null($sup)) {
			echo ' -> ' . $sup;
			if (isset($attrtypes[$sup])) {
				$parent = $attrtypes[$sup];
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
	echo '</li>';
}
?>
</ul>
