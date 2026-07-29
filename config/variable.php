<?php

return [

//	'date_format' => '',

	'currency' => 'PKR',
	'currency_format' => 'prefix',
	'account_id' => 1,

	// Used by PHP Carbon and Blade date displays (must live in config for config:cache).
	'date_format' => env('Date_Format', 'd-m-Y'),
	// Used by bootstrap-datepicker.
	'date_format_js' => env('Date_Format_JS', 'dd-mm-yyyy'),

	// Production-safe flags (use config(), not env(), outside config files).
	'user_verified' => filter_var(env('USER_VERIFIED', true), FILTER_VALIDATE_BOOLEAN),
	'enable_early_clockin' => env('ENABLE_EARLY_CLOCKIN'),
	// Default enabled so Coolify/config:cache does not hide Clock IN when .env is not writable.
	'enable_clockin_clockout' => ! in_array(
		strtolower((string) env('ENABLE_CLOCKIN_CLOCKOUT', '1')),
		['', '0', 'false', 'null', 'off', 'no'],
		true
	),

];
