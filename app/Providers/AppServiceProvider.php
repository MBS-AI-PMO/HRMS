<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Appraisal;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Award;
use App\Models\company;
use App\Models\Complaint;
use App\Models\department;
use App\Models\designation;
use App\Models\Employee;
use App\Models\Event;
use App\Models\ExpenseType;
use App\Models\FinanceDeposit;
use App\Models\FinanceExpense;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransfer;
use App\Models\GoalTracking;
use App\Models\Holiday;
use App\Models\Indicator;
use App\Models\JobPost;
use App\Models\leave;
use App\Models\LeaveType;
use App\Models\location;
use App\Models\Meeting;
use App\Models\office_shift;
use App\Models\OfficialDocument;
use App\Models\PaymentMethod;
use App\Models\Payslip;
use App\Models\Policy;
use App\Models\Project;
use App\Models\Promotion;
use App\Models\QualificationEducationLevel;
use App\Models\QualificationLanguage;
use App\Models\QualificationSkill;
use App\Models\Resignation;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\Team;
use App\Models\Termination;
use App\Models\Trainer;
use App\Models\TrainingList;
use App\Models\Transfer;
use App\Models\Travel;
use App\Models\TravelType;
use App\Models\Warning;
use App\Scopes\AuthCompanyLocationScope;
use App\Scopes\AuthCompanyScope;
use App\Scopes\AuthCompanySelfScope;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
     
     
    public function register()
    {
        if ($this->app->isLocal() && class_exists(\Barryvdh\Debugbar\ServiceProvider::class)) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCompanyScopes();

		// Default application language is English.
		App::setLocale('English');

		if (($_COOKIE['language'] ?? '') !== 'English') {
			setcookie('language', 'English', time() + (86400 * 365), '/');
		}

//		if (!isset(env('Date_Format')) && !isset($_COOKIE['date_format_js'])){
//
//			setcookie('date_format', 'Y-m-d', time() + (86400 * 365),'/');
//
//			setcookie('date_format_js', 'yyyy-mm-dd', time() + (86400 * 365),'/');
//
//		}

    }

    protected function registerCompanyScopes(): void
    {
        $companyScopedModels = [
            Employee::class,
            department::class,
            designation::class,
            office_shift::class,
            Announcement::class,
            Holiday::class,
            Promotion::class,
            Award::class,
            Travel::class,
            Transfer::class,
            Resignation::class,
            Complaint::class,
            Warning::class,
            Termination::class,
            leave::class,
            Payslip::class,
            Meeting::class,
            Event::class,
            Policy::class,
            OfficialDocument::class,
            Asset::class,
            SupportTicket::class,
            Project::class,
            Task::class,
            Team::class,
            Indicator::class,
            GoalTracking::class,
            Appraisal::class,
            TrainingList::class,
            Trainer::class,
            JobPost::class,
            FinanceDeposit::class,
            FinanceExpense::class,
            FinanceTransaction::class,
            FinanceTransfer::class,
            PaymentMethod::class,
            LeaveType::class,
            ExpenseType::class,
            TravelType::class,
            AssetCategory::class,
            QualificationEducationLevel::class,
            QualificationLanguage::class,
            QualificationSkill::class,
        ];

        foreach ($companyScopedModels as $model) {
            $model::addGlobalScope(new AuthCompanyScope);
        }

        company::addGlobalScope(new AuthCompanySelfScope);
        location::addGlobalScope(new AuthCompanyLocationScope);
    }
}
