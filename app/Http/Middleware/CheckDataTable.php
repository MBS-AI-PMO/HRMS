<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDataTable
{
    public function handle(Request $request, Closure $next): Response
    {
        $isDataTableExist = true;

        if (in_array(request()->url(), $this->expectedURL())) {
            $isDataTableExist = false;
        }

        view()->composer([
            'layout.main',
            'layout.client',
        ], function ($view) use ($isDataTableExist) {
            $view->with('isDataTableExist', $isDataTableExist);
        });

        return $next($request);
    }


    protected function expectedURL() : array
    {
        return [
            url('admin/dashboard'),
            url('staff/employees/page/import'),
            url('settings/general_settings'),
            url('settings/mail_setting'),
            url('settings/roles/role-permission/'.$this->getParameter()),
            url('languages/English/translations'),
            url('timesheet/attendances/page/import'),
            url('calendar/hr'),
            url('recruitment/cms'),
            url('clients/overview'),
            url('tickets/'.$this->getParameter()),
            url('project-management/invoices/'.$this->getParameter()),
            url('file_manager/file_config'),
            url('sales/store'),
            url('client/store'),
            url('messages/inbox'),
            url('sales/store/chekout'),
            url('client/store/chekout'),
            url('sales/orders/details/'.$this->getParameter()),
            // url('profile'),
            url('addons/'),
            url('employee/dashboard'),
            url('client/dashboard'),
            url('client/contracts/'.$this->getParameter()),
            url('client/proposals/'.$this->getParameter()),
            url('client/estimates/'.$this->getParameter()),
        ];
    }

    protected function getParameter() : string
    {
        $convertedToArray = explode('/',request()->url());
        return end($convertedToArray);
    }

}
