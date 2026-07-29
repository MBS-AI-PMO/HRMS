<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select('SHOW TABLES');
$db = config('database.connections.mysql.database');
$key = 'Tables_in_'.$db;

$withCompany = [];
$withClient = [];

foreach ($tables as $t) {
    $name = $t->$key;
    try {
        $cols = Schema::getColumnListing($name);
        if (in_array('company_id', $cols, true)) {
            $withCompany[] = $name;
        }
        if (in_array('client_id', $cols, true)) {
            $withClient[] = $name;
        }
    } catch (Throwable $e) {
    }
}

echo "company_id:\n".implode("\n", $withCompany)."\n\n";
echo "client_id:\n".implode("\n", $withClient)."\n";
