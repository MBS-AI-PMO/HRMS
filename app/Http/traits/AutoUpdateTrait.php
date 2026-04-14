<?php
namespace App\Http\traits;

trait AutoUpdateTrait{
    /*
    |============================================================
    | # For Version Upgrade - you should follow these point in DEMO :
    |       1. clientVersionNumber >= minimumRequiredVersion
    |       2. latestVersionUpgradeEnable === true
    |       3. demoVersionNumber > clientVersionNumber
    |
    |===========================================================
    */

    public function isUpdateAvailable()
    {
        $versionUpgradeData = [];
        $versionUpgradeData['alert_version_upgrade_enable'] = false;

        $ch = curl_init('https://peopleprohrm.com/demo/api/is-update-available');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        $data = json_decode($response);

        $isServerConnectionOk = isset($data) && !empty($data) ? true : false;

        if ($isServerConnectionOk) {
            $clientVersionNumber = $this->stringToNumberConvert(env('VERSION'));
            $demoVersionNumber      = $this->stringToNumberConvert($data->demo_version);
            $minimumRequiredVersion = $this->stringToNumberConvert($data->minimum_required_version);
            if ($demoVersionNumber > $clientVersionNumber && $clientVersionNumber >= $minimumRequiredVersion) {
                $versionUpgradeData['alert_version_upgrade_enable'] = true;
            }
            $versionUpgradeData['demo_version'] = $data->demo_version;
            $versionUpgradeData['version_upgrade_file_url'] = $data->version_upgrade_file_url;
            $versionUpgradeData['latest_version_db_migrate_enable'] = $data->latest_version_db_migrate_enable;
        };

        return $versionUpgradeData;
    }

    private function stringToNumberConvert($dataString) {
        $myArray = explode(".", $dataString);
        $versionString = "";
        foreach($myArray as $element) {
          $versionString .= $element;
        }
        $versionConvertNumber = intval($versionString);
        return $versionConvertNumber;
    }
}
