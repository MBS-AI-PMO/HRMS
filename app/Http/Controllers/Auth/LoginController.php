<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmployeeActivityLog;
use App\Models\IpSetting;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{

    use AuthenticatesUsers;

    //redirect to the login page

    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

     // over riding the method for custom redirecting after login
     protected function authenticated(Request $request, $user)
     {
        //saving login timestamps and ip after login
        $user->timestamps = false;
        $user->last_login_date = Carbon::now()->toDateTimeString();
        $user->last_login_ip = $request->ip();
        $user->save();

        try {
            EmployeeActivityLog::write(
                (int) $user->id,
                (int) $user->id,
                'auth.login',
                'User logged in successfully.',
                [
                    'username' => $user->username,
                    'role_users_id' => $user->role_users_id,
                ],
                $request->ip()
            );
        } catch (\Throwable $e) {
            // Do not block login if activity log fails (e.g. missing employee row).
            report($e);
        }

        if ($user->role_users_id == 1)
        {
            return redirect('/admin/executive');
        }
        elseif ($user->role_users_id == 2)
        {
            return redirect('/admin/dashboard');
        }
        elseif ($user->role_users_id == 3)
        {
            return redirect('/employee/dashboard');
        } //if employee
        else
        {
            return redirect('/employee/dashboard');
        }
    }


	public function username()
	{
		return 'username';
	}

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => __('Username is required.'),
            'password.required' => __('Password is required.'),
        ]);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

}
