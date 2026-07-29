<?php

/**
 * Fix leftover lowercase type-hints after model renames.
 * office_shift ≠ OfficeShift (underscore), so those MUST be fixed.
 * leave/company/location also normalized for consistency.
 */

$root = dirname(__DIR__);

$files = [
    $root.'/app/Http/Controllers/OfficeShiftController.php',
    $root.'/app/Services/LeaveNotifier.php',
    $root.'/app/Http/Controllers/LeaveController.php',
    $root.'/app/Services/EntityDashboardService.php',
    $root.'/app/Http/Controllers/PublicEmployeeRegistrationController.php',
    $root.'/app/Services/NotificationRecipientResolver.php',
    $root.'/app/Services/LeaveMailPresenter.php',
    $root.'/app/Http/Controllers/LocationController.php',
    $root.'/app/Http/Controllers/Api/ApiController.php',
];

$replacements = [
    '?office_shift ' => '?OfficeShift ',
    'office_shift $' => 'OfficeShift $',
    '(leave $' => '(Leave $',
    '?leave $' => '?Leave $',
    '(company $' => '(Company $',
    'fn (company $' => 'fn (Company $',
    '(location $' => '(Location $',
];

foreach ($files as $path) {
    if (! is_file($path)) {
        echo "Missing: $path\n";
        continue;
    }

    $original = file_get_contents($path);
    $updated = $original;

    foreach ($replacements as $search => $replace) {
        $updated = str_replace($search, $replace, $updated);
    }

    if ($updated !== $original) {
        file_put_contents($path, $updated);
        echo 'Updated: '.basename($path)."\n";
    } else {
        echo 'OK: '.basename($path)."\n";
    }
}

echo "Done.\n";
