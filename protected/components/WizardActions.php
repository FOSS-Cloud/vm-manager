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

/**
 * WizardActions class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.8
 */

class WizardActions extends CFormModel {
	protected $config = array();
	protected $step = '';
	protected $ajaxAction = '';
	protected $ajaxRefresh = 10;
	protected $values = array();

	public function __construct($config, $step, $ajaxAction) {
		parent::__construct();
		$this->step = $step;
		$this->ajaxAction = $ajaxAction;
		$this->config = $config[$step];
		foreach($this->config['actions'] as $action) {
			$this->values[$action['name']] = array('name' => $action['name'], 'value' => '', );
			if (isset($action['outputvars'])) {
				foreach($action['outputvars'] as $stepvar => $outputvar) {
					$this->values[$stepvar] = array('name' => $stepvar, 'value' => '', );
				}
			}
		}
	}

	/**
	 * PHP getter magic method.
	 * This method is overridden so that attributes can be accessed like properties.
	 * @param string property name
	 * @return mixed property value
	 * @see getAttribute
	 */
	public function __get($name) {
		//echo '<pre>__get ' . $name . '</pre>';
		if (isset($this->values[$name])) {
			return $this->values[$name]['value'];
		}
		else
		{
			return parent::__get($name);
		}
		return $retval;
	}

	public function __set($name, $value) {
		//echo '<pre>__set ' . $name . ', ' . print_r($value, true) . '</pre>';
		if (isset($this->values[$name])) {
			$this->values[$name]['value'] = $value;
		}
		else
		{
			parent::__set($name, $value);
		}
	}

	public function rules() {
		$valueNames = array();
		foreach($this->values as $value) {
			$valueNames[] = $value['name'];
		}
		return array( array(implode(', ', $valueNames), 'required'));
	}

	/**
	 * Returns the list of attribute names.
	 * By default, this method returns all public properties of the class.
	 * You may override this method to change the default.
	 * @return array list of attribute names. Defaults to all public properties of the class.
	 */
	public function attributeNames() {
		$attrNames = array();
		foreach($this->values as $value) {
			$attrNames[] = $value['name'];
		}
		return $attrNames;
	}

	public function getForm() {
		$form = array(
			'showErrorSummary'=>true,
			'elements'=>array(),
			'buttons'=>array(
				'previous'=>array(
					'type'=>'submit',
					'label'=>'Previous'
				),
				'refresh'=>array(
					'type'=>'submit',
					'label'=>'Run again',
					'id'=>'aagain',
					'disabled'=>'disabled'
				),
				'submit'=>array(
					'type'=>'submit',
					'label'=>'Next',
					'id'=>'anext',
					'disabled'=>'disabled'
				),
				'cancel'=>array(
					'type'=>'submit',
					'label'=>'Cancel',
					'style'=>'margin-left: 40px;'
				)
			)
		);
		$form['elements'][] = '<table id="actions" style="width: 320px">';
		$form['elements'][] = '<tr><th style="width: 300px;">Action</th><th>pass</th></tr>';

		$idx = 0;
		foreach($this->config['actions'] as $action) {
			$trclass = ($idx % 2 == 0 ? 'odd' : 'even');
			$element = '<tr class="' . $trclass . '"><td id="at' . $idx . '">' . CHtml::activeHiddenField($this, $action['name'],array('id' => $action['name']));
			if (isset($action['outputvars'])) {
				foreach($action['outputvars'] as $stepvar => $outputvar) {
					$element .= CHtml::activeHiddenField($this, $stepvar, array('id' => $stepvar));
				}
			}
			if (isset($action['ssh'])) {
				$element .= '<b>Remote: </b>';
			}
			$element .= $action['title'] . '</td><td id="ap' . $idx . '" style="text-align: center; vertical-align: top;"></td></tr>';
			$form['elements'][] = $element;
			$idx++;
		}

		$form['elements'][] = '</table>';
		return new CForm($form, $this);
	}

	public function getJScript($controller) {
		$baseUrl = Yii::app()->baseUrl;
		return <<<EOS
var pass = true;
/*
var timeoutid = setTimeout(refreshAction, 10);
*/
var timeoutid;
refreshAction();
function refreshAction()
{
	$.ajax({
		url: "{$controller->createUrl($this->ajaxAction)}",
		data: "step={$this->step}",
		success: function(xml){
			var last = $(xml).find('last');
			var lidx = $(last).attr('idx');
			if (undefined != lidx) {
				if (undefined != $(last).attr('return')) {
					if (1 == $(last).attr('return')) {
						$('#ap' + lidx).html('<img src="{$baseUrl}/images/action_pass.png" title="pass"/>');
						$('#at' + lidx).append('<br /><span style="color: green;">' + $(last).attr('message') + '</span>');
					}
					else {
						pass = false;
						$('#ap' + lidx).html('<img src="{$baseUrl}/images/action_fail.png" title="fail"/>');
						$('#at' + lidx).append('<br /><span style="color: red;">' + $(last).attr('message') + '</span>');
					}
					$('#' + $(last).attr('name')).val($(last).attr('return'));
					$(last).find('var').each(function(index) {
						var id = $(last).attr('name') + '.' + $(this).attr('name');
						var val = $(this).attr('value');
						$('#' + $(this).attr('name')).val($(this).attr('value'));
					});
				}
				else {
					timeoutid = setTimeout(refreshAction, {$this->ajaxRefresh});
				}
			}
			var next = $(xml).find('next');
			var nidx = $(next).attr('idx');
			var trclass = (nidx % 2 == 0 ? 'odd' : 'even');
			if (undefined != nidx) {
				$('#ap' + nidx).html('<img src="{$baseUrl}/images/loading.gif" title="running"/>');
				timeoutid = setTimeout(refreshAction, 5000);
			}
			else if (undefined != $(last).attr('return') && pass) {
				$('#anext').removeAttr('disabled');
			}
			else {
				$('#aagain').removeAttr('disabled');
			}
		},
		error:  function(req, status, error) {
			pass = false;
		},
		datatype: "xml"
	});
}
EOS;
	}
}