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

/**
 * WizardController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.8
 */

/**
 * WizardController is the customized base controller class for all controllers including a wizard.
 */
class WizardController extends Controller
{
	protected function getParameter($param, $steps) {
		if (false !== strpos($param, 'func')) {
			if (1 == preg_match('/^func ([^\(\)]*) ?\(\)$/', $param, $matches)) {
				$func = $matches[1];
				return $func();
			}
			else {
				throw new Exception('Not a function in \'' . $param . '\'');
			}
		}
		else if (false !== strpos($param, '$') && 0 == strpos($param, '$')) {
			$params = explode('.', substr($param, 1));
			if (isset($steps[$params[0]])) {
				return $steps[$params[0]][$params[1]];
			}
		}
		return $param;
	}
}