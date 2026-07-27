<?php

$map = [
    'leave' => 'Leave',
    'company' => 'Company',
    'department' => 'Department',
    'designation' => 'Designation',
    'location' => 'Location',
    'office_shift' => 'OfficeShift',
    'status' => 'Status',
    'performance' => 'Performance',
];

$dir = __DIR__.'/../app/Models';

foreach ($map as $old => $new) {
    $oldPath = $dir.'/'.$old.'.php';
    $newPath = $dir.'/'.$new.'.php';

    if (! is_file($newPath)) {
        echo "MISSING {$new}.php\n";
        continue;
    }

    if (! is_file($oldPath)) {
        echo "OK: no duplicate {$old}.php\n";
        continue;
    }

    if (realpath($oldPath) === realpath($newPath)) {
        echo "SAME file on disk: {$old}.php\n";
        continue;
    }

    unlink($oldPath);
    echo "Deleted duplicate: {$old}.php\n";
}
