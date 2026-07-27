<?php

/**
 * One-off script: normalize lowercase models and env() calls for Linux/production deploy.
 * Run: php scripts/fix-linux-deploy.php
 */

$root = dirname(__DIR__);

$scanDirs = [
    $root.'/app',
    $root.'/resources',
    $root.'/routes',
    $root.'/database',
    $root.'/config',
];

$extensions = ['php', 'blade.php'];

$replacements = [
    // env → config (must run before config:cache on Linux)
    "env('Date_Format_JS')" => "config('variable.date_format_js', 'dd-mm-yyyy')",
    'env("Date_Format_JS")' => "config('variable.date_format_js', 'dd-mm-yyyy')",
    "env('Date_Format', 'd-m-Y')" => "config('variable.date_format', 'd-m-Y')",
    'env("Date_Format", "d-m-Y")' => "config('variable.date_format', 'd-m-Y')",
    "env('Date_Format')" => "config('variable.date_format', 'd-m-Y')",
    'env("Date_Format")' => "config('variable.date_format', 'd-m-Y')",
    "env('USER_VERIFIED')" => "config('variable.user_verified')",
    'env("USER_VERIFIED")' => "config('variable.user_verified')",
    "env('ENABLE_EARLY_CLOCKIN')" => "config('variable.enable_early_clockin')",
    'env("ENABLE_EARLY_CLOCKIN")' => "config('variable.enable_early_clockin')",

    // PSR-4 model namespaces (longer names first)
    'use App\\Models\\office_shift;' => 'use App\\Models\\OfficeShift;',
    'use App\\Models\\performance;' => 'use App\\Models\\Performance;',
    'use App\\Models\\designation;' => 'use App\\Models\\Designation;',
    'use App\\Models\\department;' => 'use App\\Models\\Department;',
    'use App\\Models\\location;' => 'use App\\Models\\Location;',
    'use App\\Models\\company;' => 'use App\\Models\\Company;',
    'use App\\Models\\leave;' => 'use App\\Models\\Leave;',
    'use App\\Models\\status;' => 'use App\\Models\\Status;',

    'App\\Models\\office_shift' => 'App\\Models\\OfficeShift',
    'App\\Models\\performance' => 'App\\Models\\Performance',
    'App\\Models\\designation' => 'App\\Models\\Designation',
    'App\\Models\\department' => 'App\\Models\\Department',
    'App\\Models\\location' => 'App\\Models\\Location',
    'App\\Models\\company' => 'App\\Models\\Company',
    'App\\Models\\leave' => 'App\\Models\\Leave',
    'App\\Models\\status' => 'App\\Models\\Status',

    'office_shift::' => 'OfficeShift::',
    'designation::' => 'Designation::',
    'department::' => 'Department::',
    'location::' => 'Location::',
    'company::' => 'Company::',
    'leave::' => 'Leave::',
    'status::' => 'Status::',
];

$modelRenames = [
    'leave.php' => ['leave', 'Leave'],
    'company.php' => ['company', 'Company'],
    'department.php' => ['department', 'Department'],
    'designation.php' => ['designation', 'Designation'],
    'location.php' => ['location', 'Location'],
    'office_shift.php' => ['office_shift', 'OfficeShift'],
    'status.php' => ['status', 'Status'],
    'performance.php' => ['performance', 'Performance'],
];

$modelsDir = $root.'/app/Models';

foreach ($modelRenames as $oldFile => [$oldClass, $newClass]) {
    $oldPath = $modelsDir.'/'.$oldFile;
    $newPath = $modelsDir.'/'.$newClass.'.php';

    if (! is_file($oldPath)) {
        if (is_file($newPath)) {
            echo "Skip model (already renamed): {$newClass}.php\n";
            continue;
        }

        echo "Missing model file: {$oldFile}\n";
        continue;
    }

    if (is_file($newPath) && realpath($oldPath) === realpath($newPath)) {
        // Case-only rename on case-insensitive FS: stage via temp file.
        $tempPath = $modelsDir.'/_rename_'.$newClass.'_temp.php';
        copy($oldPath, $tempPath);
        unlink($oldPath);
        rename($tempPath, $newPath);
    } elseif (! is_file($newPath)) {
        rename($oldPath, $newPath);
    } else {
        unlink($oldPath);
    }

    $content = file_get_contents($newPath);
    $content = preg_replace('/\bclass\s+'.preg_quote($oldClass, '/').'\b/', 'class '.$newClass, $content, 1);
    file_put_contents($newPath, $content);
    echo "Renamed model: {$oldFile} → {$newClass}.php\n";
}

$changedFiles = 0;

foreach ($scanDirs as $dir) {
    if (! is_dir($dir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $ext = $file->getExtension();
        if ($ext === 'php' || str_ends_with($file->getFilename(), '.blade.php')) {
            // ok
        } else {
            continue;
        }

        $path = $file->getPathname();
        $original = file_get_contents($path);
        $updated = $original;

        foreach ($replacements as $search => $replace) {
            $updated = str_replace($search, $replace, $updated);
        }

        if ($updated !== $original) {
            file_put_contents($path, $updated);
            $changedFiles++;
            echo "Updated: ".str_replace($root.'\\', '', str_replace($root.'/', '', $path))."\n";
        }
    }
}

echo "\nDone. {$changedFiles} files updated.\n";
