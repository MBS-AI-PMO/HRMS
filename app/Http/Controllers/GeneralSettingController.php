<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Http\traits\ENVFilePutContent;
use App\Models\FinanceBankCash;
use App\Models\GeneralSetting;
use App\Models\LeaveType;
use App\Notifications\EmployeeLeaveNotification;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use App\Services\MailSendLogger;
use Throwable;

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Dotenv;

use function config;
use ZipArchive;


class GeneralSettingController extends Controller
{
    use ENVFilePutContent;

	public function index()
	{
		if (auth()->user()->can('view-general-setting'))
		{
			$general_settings_data = GeneralSetting::latest()->first();
			$accounts = FinanceBankCash::all('id', 'account_name');
			$zones_array = array();
			$timestamp = time();


			foreach (timezone_identifiers_list() as $key => $zone)
			{
				date_default_timezone_set($zone);
				$zones_array[$key]['zone'] = $zone;
				$zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
			}

			return view('settings.general_settings.index', compact('general_settings_data', 'zones_array', 'accounts'));
		}

		return abort('403', __('You are not authorized'));
	}

    protected function test()
    {
        // Notification::route('mail', 'irfanchowdhury80@gmail.com')
        // ->notify(new EmployeeLeaveNotification(
        //     'Irfan Chowdhury',
        //     '12',
        //     '2023-04-19',
        //     '2023-04-24',
        //     'Test',
        // ));
        // return 'ok';
    }


	public function update(Request $request, $id)
	{

		if (auth()->user()->can('store-general-setting'))
		{
			if(!env('USER_VERIFIED'))
			{
                $this->setErrorMessage('This feature is disabled for demo!');
                return redirect()->back();
			}

			$this->validate($request, [
				'site_logo' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
			]);

			$data = $request->all();

			//writting timezone info in .env file
            $this->dataWriteInENVFile('APP_TIMEZONE',$request->timezone);
            $this->dataWriteInENVFile('Date_Format',$request->date_format);
			$js_format = config('date_format_conversion.' . $request->date_format);
            $this->dataWriteInENVFile('Date_Format_JS',$js_format);
            $this->dataWriteInENVFile('RTL_LAYOUT',$request->input('rtl_layout', NULL));
            $this->dataWriteInENVFile('ENABLE_CLOCKIN_CLOCKOUT',$request->input('enable_clockin_clockout', NULL));
            $this->dataWriteInENVFile('ENABLE_EARLY_CLOCKIN',$request->input('enable_early_clockin', NULL));
            $this->dataWriteInENVFile('ATTENDANCE_DEVICE_DATE_FORMAT',$request->Attendance_Device_date_format ? $request->Attendance_Device_date_format : 'm/d/Y');

			$path = base_path('config/variable.php');

			$searchArray = array(
				config('variable.currency'),
				config('variable.currency_format'), config('variable.account_id'));

			$replaceArray = array($request->currency, $request->currency_format, $request->account_id);

			file_put_contents($path, str_replace($searchArray, $replaceArray, file_get_contents($path)));


			$general_setting = GeneralSetting::first();
			$general_setting->id = 1;
			$general_setting->site_title = $data['site_title'];
			$general_setting->time_zone = $data['timezone'];
			$general_setting->currency = $data['currency'];
			$general_setting->currency_format = $data['currency_format'];
			$general_setting->date_format = $data['date_format'];
			$general_setting->default_payment_bank = $data['account_id'];
			$general_setting->footer = $request->footer;
			$general_setting->footer_link = $request->footer_link;
			$general_setting->latitude = $request->latitude;
            $general_setting->longitude = $request->longitude;
			$general_setting->min_radius = $request->min_radius;
            $general_setting->max_radius = $request->max_radius;
			$general_setting->late_grace_minutes = $request->late_grace_minutes;

			$logo = $request->site_logo;


			if ($logo)
			{
		    	if(!config('database.connections.peopleprosaas_landlord')){
			        $logo_dir = public_path('images/logo/');
			    }
			    else {
			        $logo_dir = public_path(tenantPath().'/images/logo/');
			    }

				$file_path = $general_setting->site_logo;

				if ($file_path)
				{
				    $file_path = $logo_dir.$file_path;

					if (file_exists($file_path))
					{
						unlink($file_path);
					}
				}

				$ext = pathinfo($logo->getClientOriginalName(), PATHINFO_EXTENSION);
				$logoName = 'logo.' . $ext;
				$logo->move($logo_dir, $logoName);
				$general_setting->site_logo = $logoName;

			}
			$general_setting->save();

            $this->setSuccessMessage('Data updated successfully');
            return redirect()->back();
		}

		return abort('403', __('You are not authorized'));
	}


	public function mailSetting()
	{
		if (auth()->user()->can('view-mail-setting'))
		{
			return view('settings.mail_setting.mail');
		}
		return abort('403', __('You are not authorized'));
	}

	public function mailSettingStore(Request $request)
	{

		if(!env('USER_VERIFIED')) {
			return redirect()->back()->with('msg', 'This feature is disable for demo!');
		}

		if (auth()->user()->can('view-mail-setting')) {

            $this->dataWriteInENVFile('MAIL_ENCRYPTION',$request->encryption);
            $this->dataWriteInENVFile('MAIL_FROM_ADDRESS',$request->mail_address);
            $this->dataWriteInENVFile('MAIL_FROM_NAME',$request->mail_name);
            $this->dataWriteInENVFile('MAIL_HOST',$request->mail_host);
            $this->dataWriteInENVFile('MAIL_PORT',$request->port);
            if ($request->filled('password')) {
                $this->dataWriteInENVFile('MAIL_PASSWORD',$request->password);
            }
            $this->dataWriteInENVFile('MAIL_USERNAME',$request->mail_address);


			return redirect()->back()->with('message', 'Data updated successfully');
		}
		return abort('403', __('You are not authorized'));
	}

	public function mailSettingSendTest(Request $request)
	{
		if (! auth()->user()->can('view-mail-setting')) {
			return response()->json(['error' => __('You are not authorized')], 403);
		}

		$validator = Validator::make($request->all(), [
			'mail_host' => 'required|string|max:255',
			'mail_address' => 'required|email|max:255',
			'mail_name' => 'required|string|max:255',
			'port' => 'required',
			'encryption' => 'required|string|max:50',
			'test_email' => 'required|email|max:255',
			'password' => 'nullable|string',
		]);

		if ($validator->fails()) {
			return response()->json(['error' => $validator->errors()->first()], 422);
		}

		$password = $request->filled('password') ? $request->password : env('MAIL_PASSWORD');

		if ($password === null || $password === '') {
			return response()->json([
				'error' => __('Enter SMTP password in the form, or save mail settings first.'),
			], 422);
		}

		$mailConfig = $this->applyMailConfigFromRequest($request, $password);
		$testEmail = strtolower(trim($request->test_email));
		$sentAt = now()->toDateTimeString();
		$testedBy = auth()->user();

		$body = implode("\n", [
			__('Hello'),
			'',
			__('This is a test email from HRMS Mail Settings.'),
			'',
			__('Sent at').': '.$sentAt,
			__('Sent by').': '.trim($testedBy->first_name.' '.$testedBy->last_name).' (#'.$testedBy->id.')',
			__('SMTP host').': '.$mailConfig['host'],
			__('Mail from').': '.$mailConfig['from_address'],
			'',
			__('If you received this message, SMTP configuration is working.'),
		]);

		try {
			$mailLogs = (new MailSendLogger)->wrap(
				'Mail settings test email',
				[
					'test_email' => $testEmail,
					'tested_by' => $testedBy->id,
					'mail_host' => $mailConfig['host'],
					'mail_port' => $mailConfig['port'],
					'mail_encryption' => $mailConfig['encryption'],
					'mail_from' => $mailConfig['from_address'],
				],
				function () use ($body, $testEmail, $mailConfig, $sentAt) {
					Mail::raw($body, function ($message) use ($testEmail, $mailConfig, $sentAt) {
						$message->from($mailConfig['from_address'], $mailConfig['from_name'])
							->replyTo($mailConfig['from_address'], $mailConfig['from_name'])
							->to($testEmail)
							->subject(__('HRMS mail test').' - '.$sentAt);
					});
				}
			);

			$message = __('Test email sent to :email. Check inbox and spam folder.', ['email' => $testEmail]);
			$hint = MailSendLogger::recipientDomainHint($testEmail);

			if ($hint) {
				$message .= ' '.$hint;
			}

			return response()->json([
				'success' => $message,
				'mail_logs' => $mailLogs,
			]);
		} catch (Throwable $e) {
			Log::error('[MAIL TEST] Mail settings test failed', [
				'stage' => 'MAIL_SETTINGS_TEST_FAILED',
				'test_email' => $testEmail,
				'tested_by' => $testedBy->id,
				'mail_host' => $mailConfig['host'],
				'mail_port' => $mailConfig['port'],
				'mail_encryption' => $mailConfig['encryption'],
				'mail_from' => $mailConfig['from_address'],
				'error' => $e->getMessage(),
				'exception' => get_class($e),
			]);

			return response()->json([
				'error' => __('Mail test failed: ').$e->getMessage(),
			], 422);
		}
	}

	/**
	 * @return array{host: string, port: mixed, encryption: string, username: string, password: string, from_address: string, from_name: string}
	 */
	protected function applyMailConfigFromRequest(Request $request, string $password): array
	{
		$config = [
			'host' => trim((string) $request->mail_host),
			'port' => $request->port,
			'encryption' => trim((string) $request->encryption),
			'username' => strtolower(trim((string) $request->mail_address)),
			'password' => $password,
			'from_address' => strtolower(trim((string) $request->mail_address)),
			'from_name' => trim((string) $request->mail_name),
		];

		config([
			'mail.default' => 'smtp',
			'mail.mailers.smtp.transport' => 'smtp',
			'mail.mailers.smtp.host' => $config['host'],
			'mail.mailers.smtp.port' => $config['port'],
			'mail.mailers.smtp.encryption' => $config['encryption'],
			'mail.mailers.smtp.username' => $config['username'],
			'mail.mailers.smtp.password' => $config['password'],
			'mail.from.address' => $config['from_address'],
			'mail.from.name' => $config['from_name'],
		]);

		return $config;
	}

	public function emptyDatabase()
	{
		if(!env('USER_VERIFIED')) {
			return redirect()->back()->with('msg', 'This feature is disabled for demo!');
		}
		DB::statement("SET foreign_key_checks=0");
		$tables = DB::select('SHOW TABLES');
		$str = 'Tables_in_' . env('DB_DATABASE');

        $employeeIds =  Employee::get()->pluck('id');
        User::whereIn('id',$employeeIds)->delete();

		foreach ($tables as $table) {
			// if($table->$str != 'countries' && $table->$str != 'model_has_roles' && $table->$str != 'role_users' && $table->$str != 'general_settings'  && $table->$str != 'migrations' && $table->$str != 'password_resets' && $table->$str != 'permissions' &&  $table->$str != 'roles' && $table->$str != 'role_has_permissions' && $table->$str != 'users') {
			if($table->$str != 'countries' && $table->$str != 'model_has_roles' && $table->$str != 'general_settings'  && $table->$str != 'migrations' && $table->$str != 'password_resets' && $table->$str != 'permissions' &&  $table->$str != 'roles' && $table->$str != 'role_has_permissions' && $table->$str != 'users') {
				DB::table($table->$str)->truncate();
			}
		}
        LeaveType::create(['leave_type'=>'Others','allocated_day'=>0]);

		DB::statement("SET foreign_key_checks=1");

		return redirect()->back()->with('msg', 'Database cleared successfully');
	}

	public function exportDatabase()
	{
		if(!env('USER_VERIFIED'))
		{
			return redirect()->back()->with('msg', 'This feature is disabled for demo!');
		}
		// Database configuration
		$host = env('DB_HOST');
		$username = env('DB_USERNAME');
		$password = env('DB_PASSWORD');
		$database_name = env('DB_DATABASE');

		// Get connection object and set the charset
		$conn = mysqli_connect($host, $username, $password, $database_name);
		$conn->set_charset("utf8");


		// Get All Table Names From the Database
		$tables = array();
		$sql = "SHOW TABLES";
		$result = mysqli_query($conn, $sql);

		while ($row = mysqli_fetch_row($result)) {
			$tables[] = $row[0];
		}

		$sqlScript = "SET foreign_key_checks = 0;";

		foreach ($tables as $table) {
			// Prepare SQLscript for creating table structure
			$query = "SHOW CREATE TABLE $table";
			$result = mysqli_query($conn, $query);
			$row = mysqli_fetch_row($result);

			$sqlScript .= "\n\n" . $row[1] . ";\n\n";


			$query = "SELECT * FROM $table";
			$result = mysqli_query($conn, $query);

			$columnCount = mysqli_num_fields($result);

			// Prepare SQLscript for dumping data for each table
			for ($i = 0; $i < $columnCount; $i ++) {
				while ($row = mysqli_fetch_row($result)) {
					$sqlScript .= "INSERT INTO $table VALUES(";
					for ($j = 0; $j < $columnCount; $j ++) {
						if (isset($row[$j])) {
							$sqlScript .= "'" . addslashes($row[$j]) . "'";
						} else {
							$sqlScript .= "''";
						}
						if ($j < ($columnCount - 1)) {
							$sqlScript .= ',';
						}
					}
					$sqlScript .= ");\n";
				}
			}

			$sqlScript .= "\n";
		}
        $sqlScript .= "SET foreign_key_checks = 1;";

		if(!empty($sqlScript))
		{
			// Save the SQL script to a backup file
			$backup_file_name = public_path().'/'.$database_name . '_backup_' . time() . '.sql';
			//return $backup_file_name;
			$fileHandler = fopen($backup_file_name, 'w+');
			$number_of_lines = fwrite($fileHandler, $sqlScript);
			fclose($fileHandler);

			$zip = new ZipArchive();
			$zipFileName = $database_name . '_backup_' . time() . '.zip';
			$zip->open(public_path() . '/' . $zipFileName, ZipArchive::CREATE);
			$zip->addFile($backup_file_name, $database_name . '_backup_' . time() . '.sql');
			$zip->close();

			// Download the SQL backup file to the browser
			/*header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($backup_file_name));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($backup_file_name));
			ob_clean();
			flush();
			readfile($backup_file_name);
			exec('rm ' . $backup_file_name); */
		}
		return redirect('/' . $zipFileName);
	}
}
