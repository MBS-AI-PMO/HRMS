<?php

namespace App\Http\Controllers\Addon;

use App\Http\Controllers\Controller;
use App\Http\Requests\BiometricInstallationRequest;
use Illuminate\Http\Request;
use App\Http\traits\ENVFilePutContent;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;


class BiometricAddonController extends Controller
{
    use ENVFilePutContent;

    public function biometricInstallStep1()
    {
        return view('addons.biometric.step_1');
    }

    public function biometricInstallStep2()
    {
        return view('addons.biometric.step_2');
    }

    public function biometricInstallStep3()
    {
        return view('addons.biometric.step_3');
    }

    public function biometricInstallProcess(BiometricInstallationRequest $request)
    {
        $isPurchaseVerified = self::purchaseVerify($request->purchasecode);

        if (!$isPurchaseVerified->codecheck) {
            return redirect()->back()->withErrors(['errors' => ['Wrong Purchase Code !']]);
        }

        try {
            $header_array = @get_headers($isPurchaseVerified->addonURL);
            if(!strpos($header_array[0], '200')) {
                throw new Exception("Something wrong. Please contact with support team.");
            }

            $this->fileTransferProcess($isPurchaseVerified->addonURL);

        } catch (Exception $e) {

            return redirect()->back()->withErrors(['errors' => [$e->getMessage()]]);
        }

        return redirect('/addons/biometric/install/step-4');
    }

    public function biometricInstallStep4()
    {
        return view('addons.biometric.step_4');
    }

    protected static function purchaseVerify(string $purchaseCode) : object
    {
        $url = 'https://peopleprohrm.com/purchaseverify_biometric/';
        $post_string = 'purchasecode='.urlencode($purchaseCode);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $object = new \stdClass();
        $object = json_decode(strip_tags($result));
        curl_close($ch);

        return $object;
    }

    public function fileTransferProcess($addonURL)
    {
        $remote_file_name = pathinfo($addonURL)['basename'];
        $local_file = base_path('/'.$remote_file_name);
        $copy = copy($addonURL, $local_file);
        if ($copy) {
            // ****** Unzip ********
            $zip = new ZipArchive;
            $file = base_path($remote_file_name);
            $res = $zip->open($file);
            if ($res === TRUE) {
                $zip->extractTo(base_path('/'));
                $zip->close();

                // ****** Delete Zip File ******
                File::delete(base_path($remote_file_name));
            }
        }
    }

}
