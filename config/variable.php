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

];
