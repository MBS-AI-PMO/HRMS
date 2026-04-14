<?php

namespace App\Providers;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (Schema::hasTable('general_settings')){

            $isCrmModuleExist = false;

            $this->loadGeneralSettingAndCheckCRM($isCrmModuleExist);
            $this->loadAddFormLinkOnHeader($isCrmModuleExist);

        }
    }

    protected function loadGeneralSettingAndCheckCRM(bool $isCrmModuleExist) : void
    {
        $general_settings = GeneralSetting::latest()->first();

        view()->composer([
            'layout.main',
            'layout.main_partials.header',
            'layout.main_partials.sidebar',
            'layout.main_partials.footer',
            'projects.invoices.show',
            'dashboard',
            'layout.client',
            'frontend.Layout.navigation',
            'documentation.index',
            'vendor.translation.layout',
            'all_user.index',
            'addons.index',
            'vendor.translation.languages.create'
        ], function ($view) use ($general_settings, $isCrmModuleExist) {
            // $view->with('general_settings', $general_settings);
            $view->with(['general_settings'=>$general_settings, 'isCrmModuleExist'=> $isCrmModuleExist]);
        });
    }

    protected function loadAddFormLinkOnHeader(bool $isCrmModuleExist) : void
    {
        $hrmAddFrom = [
            [
                'url' => url('/users-list').'#formModal',
                'title' => 'Admin',
            ],
            [
                'url' => url('staff/employees').'#formModal',
                'title' => 'Employee',
            ],
            [
                'url' => $isCrmModuleExist ? url('clients').'#formModal' : url('project-management/clients').'#formModal',
                'title' => 'Client',
            ],
            [
                'url' => url('organization/locations').'#formModal',
                'title' => 'Location',
            ],
            [
                'url' => url('organization/companies').'#formModal',
                'title' => 'Company',
            ],
            [
                'url' => url('organization/departments').'#formModal',
                'title' => 'Department',
            ],
            [
                'url' => url('organization/designations').'#formModal',
                'title' => 'Designation',
            ],
            [
                'url' => url('organization/announcements').'#formModal',
                'title' => 'Announcement',
            ],
            [
                'url' => url('organization/policy').'#formModal',
                'title' => 'Policy',
            ],
            [
                'url' => url('settings/roles').'#createModal',
                'title' => 'Role',
            ],
            [
                'url' => url('settings/ip_settings').'#createModal',
                'title' => 'IP',
            ],
            [
                'url' => url('core_hr/promotions').'#formModal',
                'title' => 'Promotion',
            ],
            [
                'url' => url('core_hr/awards').'#formModal',
                'title' => 'Award',
            ],
            [
                'url' => url('core_hr/travels').'#formModal',
                'title' => 'Travel',
            ],
            [
                'url' => url('core_hr/transfers').'#formModal',
                'title' => 'Transfer',
            ],
            [
                'url' => url('core_hr/resignations').'#formModal',
                'title' => 'Resignation',
            ],
            [
                'url' => url('core_hr/complaints').'#formModal',
                'title' => 'Complaint',
            ],
            [
                'url' => url('core_hr/warnings').'#formModal',
                'title' => 'Warning',
            ],
            [
                'url' => url('core_hr/terminations').'#formModal',
                'title' => 'Termination',
            ],
            [
                'url' => url('training/training_lists').'#formModal',
                'title' => 'Training',
            ],
            [
                'url' => url('training/trainers').'#formModal',
                'title' => 'Trainers',
            ],
            [
                'url' => url('events').'#formModal',
                'title' => 'Event',
            ],
            [
                'url' => url('meetings').'#formModal',
                'title' => 'Meeting',
            ],
            [
                'url' => url('project-management/projects').'#formModal',
                'title' => 'Project',
            ],
            [
                'url' => url('project-management/tasks').'#formModal',
                'title' => 'Task',
            ],
            [
                'url' => url('project-management/invoices/create'),
                'title' => 'Invoice',
            ],
            [
                'url' => url('accounting/accounting_list').'#formModal',
                'title' => 'Account',
            ],
            [
                'url' => url('accounting/payees').'#formModal',
                'title' => 'Payee',
            ],
            [
                'url' => url('accounting/payers').'#formModal',
                'title' => 'Payer',
            ],
            [
                'url' => url('accounting/deposit').'#formModal',
                'title' => 'Deposit',
            ],
            [
                'url' => url('accounting/expense').'#formModal',
                'title' => 'Expense',
            ],
            [
                'url' => url('file_manager/files').'#formModal',
                'title' => 'File',
            ],
            [
                'url' => url('file_manager/official_documents').'#formModal',
                'title' => 'Official Document',
            ]
        ];

        $crmAddForm = [];
        if ($isCrmModuleExist) {
            $crmAddForm = [
                [
                    'url' => url('leads').'#createModal',
                    'title' => 'Lead'
                ],
                [
                    'url' => url('subscriptions').'#createModal',
                    'title' => 'Subscription'
                ],
                [
                    'url' => url('sales/item-categories').'#createModal',
                    'title' => 'Item Category'
                ],
                [
                    'url' => url('sales/items').'#createModal',
                    'title' => 'Item'
                ],
                [
                    'url' => url('sales/invoice-payments').'#createModal',
                    'title' => 'Payment'
                ],
                [
                    'url' => url('sales/contracts').'#createModal',
                    'title' => 'Contract'
                ],
                [
                    'url' => url('prospects/proposals').'#createModal',
                    'title' => 'Proposal'
                ],
                [
                    'url' => url('prospects/estimates').'#createModal',
                    'title' => 'Estimate'
                ],
            ];
        }

        $addFrom = array_merge($hrmAddFrom, $crmAddForm);

        usort($addFrom, function($a, $b) {
            return strcmp($a['title'], $b['title']);
        });

        view()->composer([
            'layout.main_partials.header',
        ],
        function ($view) use ($addFrom) {
            $view->with(['addFrom'=>$addFrom]);
        });
    }
}



