<?php
	return [
		'sf.clientid' => get_cfg_var('salesforce.clientid'),
		'sf.clientsecret' => get_cfg_var('salesforce.clientsecret'),
		'sf.username' => get_cfg_var('salesforce.username'),
		'sf.password' => get_cfg_var('salesforce.pwd'),
		'sf.securitytoken' => get_cfg_var('salesforce.securitytoken'),
		'sf.loginurl' => get_cfg_var('salesforce.loginurl'),
		'sf.campaign.member.url' => get_cfg_var('salesforce.campaign.member.url'),
		'sf.lead.url' => get_cfg_var('salesforce.lead.url'),
		'sf.record.type' => get_cfg_var('salesforce.record.type'),
		'sf.seminar.report' => get_cfg_var('salesforce.seminar.report'),
		'sf.password.notification' => get_cfg_var('salesforce.password.notification'),
		'log' => get_cfg_var('shirley.log'),
		'ses.sender' => get_cfg_var('ses.sender'),
		'ses.user' => get_cfg_var('ses.user'),
		'ses.password' => get_cfg_var('ses.password'),
		'google.re' => get_cfg_var('google.re')
	];
?>