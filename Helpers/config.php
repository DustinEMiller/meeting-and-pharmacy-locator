<?php
	return [
		'sf.clientid' => get_cfg_var('salesforce.clientid'),
		'sf.clientsecret' => get_cfg_var('salesforce.clientsecret'),
		'sf.username' => get_cfg_var('salesforce.username'),
		'sf.password' => get_cfg_var('salesforce.pwd'),
		'sf.securitytoken' => get_cfg_var('salesforce.securitytoken'),
		'sf.loginurl' => 'https://test.salesforce.com/services/oauth2/token',//'https://login.salesforce.com/services/oauth2/token',
		'log' => 'log.txt'//'/var/log/sf/sf.log'
	];
?>