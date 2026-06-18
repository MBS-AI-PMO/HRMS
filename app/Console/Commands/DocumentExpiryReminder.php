<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\department;
use App\Models\EmployeeDocument;
use App\Services\NotificationRecipientResolver;
use App\Notifications\DocumentExpiry;
use App\Notifications\EmployeeDocumentExpiryNotifyToAdmin;
use App\Notifications\EmployeeDocumentExpiryNotifyToDeptartmentHead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class DocumentExpiryReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'document:expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if any document is being expired(3 days remaining)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /*
    | -----------------------------
    | This is for testing purpose
    | -----------------------------
    */

    //  public function handle()
    //  {
    //      Notification::route('mail', 'irfanchowdhury80@gmail.com')
    //      ->notify((new DocumentExpiry(
    //          "testing",
    //          "2023-05-23",
    //          'Test')));
    //      $this->info('Successfully sent.');
    // }

    /*
    | -----------------------------
    | Original Code
    | -----------------------------
    */

    public function handle()
    {
		$document_employee = EmployeeDocument::with('employee','DocumentType')
			->where('expiry_date','=',now()->addDays(3)->format('Y-m-d'))
			->where('is_notify','=',1)
			->get();

		if($document_employee->isNotEmpty())
		{
			foreach ($document_employee as $key=> $document)
			{
                $employeeEmail = NotificationRecipientResolver::resolveUserEmailAddress((int) $document->employee_id);

                if ($employeeEmail) {
                    Notification::route('mail', $employeeEmail)
                        ->notify(new DocumentExpiry(
                            $document->document_title,
                            $document->expiry_date,
                            $document->DocumentType->document_type));
                }

                //Send to department-head
                $department = department::with('DepartmentHead:id')->where('id',$document->employee->department_id)->first();
                $data[$key]['document_title'] = $document->document_title;
                $data[$key]['expiry_date']     = $document->expiry_date;
                $data[$key]['document_type']   = $document->DocumentType->document_type;
                $departmentHeadEmail = $department?->DepartmentHead
                    ? NotificationRecipientResolver::resolveUserEmailAddress((int) $department->DepartmentHead->id)
                    : null;
                $data[$key]['department_head-email'] = $departmentHeadEmail;

                if ($departmentHeadEmail) {
                Notification::route('mail', $departmentHeadEmail)
					->notify(new EmployeeDocumentExpiryNotifyToDeptartmentHead(
						$data[$key]['document_title'],
                        $data[$key]['expiry_date'],
                        $data[$key]['document_type']));
                }


                //New
                $notifiable = User::where('role_users_id',1)->get();
                foreach ($notifiable as $item) {
                    $item->notify(new EmployeeDocumentExpiryNotifyToAdmin());
                }
            }
		}
		else
		{
			return $this->info('Not available right now');
		}
        $this->info('Successfully sent.');
    }
}


//Employee Document Expiry Notification send to employee through mail

//Employee Document Expiry Notification send to Dept.Head through mail

//Employee Document Expiry Notification send to Admin through the system default notification


// How to test in the loacally
// Check Artisan List: php artisan list
// php artisan document:expiry
//https://www.positronx.io/laravel-cron-job-task-scheduling-tutorial-with-example/
