<?php

/**
 * Force Git to track PascalCase model filenames (required on Linux).
 * Run once on Windows before pushing: php scripts/git-rename-models.php
 */

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
    $oldPath = "app/Models/{$old}.php";
    $tempPath = "app/Models/_{$new}_git_temp.php";
    $newPath = "app/Models/{$new}.php";

    if (! is_file($dir.'/'.$old.'.php') && is_file($dir.'/'.$new.'.php')) {
        echo "Already PascalCase on disk: {$new}.php\n";
        passthru('git mv '.escapeshellarg($newPath).' '.escapeshellarg($tempPath), $code1);
        passthru('git mv '.escapeshellarg($tempPath).' '.escapeshellarg($newPath), $code2);
        continue;
    }

    if (! is_file($dir.'/'.$old.'.php')) {
        echo "Skip missing: {$old}.php\n";
        continue;
    }

    passthru('git mv '.escapeshellarg($oldPath).' '.escapeshellarg($tempPath), $code1);
    passthru('git mv '.escapeshellarg($tempPath).' '.escapeshellarg($newPath), $code2);
    echo "Git renamed {$old}.php → {$new}.php\n";
}

echo "Done.\n";
