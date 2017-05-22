<?php

/*
 * Copyright (C) 2006 - 2017 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Sören Busse <soeren.2011@live.de>
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

class HttpStatusCode
{
	// The request was successful
	const SUCCESS = 200;

	// The client send something wrong
	const BAD_REQUEST = 400;

	// There was an authorization problem
	const UNAUTHORIZED = 401;

	// There was an authorization problem
	const FORBIDDEN = 403;

	// The requested action wasn't available
	const NOT_FOUND = 404;

	// There was a server problem
	const INTERNAL_SERVER_ERROR = 500;
}