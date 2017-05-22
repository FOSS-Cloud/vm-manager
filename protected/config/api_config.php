<?php
return array(
	// application components
	'components'=>array(
		'urlManager'=>array(
			'rules' => array(
				// Foss-REST API patterns
				'api/user/login(/realm/<realm:\w+>)?' => array('api/api/userLogin', 'verb' => 'GET'),
				'api/server/realms(/realm/<realm:\w+>)?' => array('api/api/serverRealms', 'verb' => 'GET'),
				'api/vm/list(/<type:\w+>)?(/realm/<realm:\w+>)?' => array('api/api/vmList', 'verb' => 'GET'),
				'api/vm/assign/<pool:([^\/]+)>(/realm/<realm:\w+>)?' => array('api/api/vmAssign', 'verb' => 'GET'),
				'api/vm/mapping/list(/<mac:([^\/]+)>)?(/realm/<realm:\w+>)?' => array('api/api/vmMappingList', 'verb' => 'GET'),
				'api/vm/mapping/assign/<pool:([^\/]+)>(/<mac:([^\/]+)>)?(/realm/<realm:\w+>)?' => array('api/api/vmMappingAssign', 'verb' => 'GET'),
			),
		),
	),
	'modules' => array(
		'api' => array(
			'enable' => false,
			'defaultRealm' => "4000013",
			'macByParameter' => false,
		),
	),
);
