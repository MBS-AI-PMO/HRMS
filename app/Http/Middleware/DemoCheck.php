<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() === 'POST') {
            if (env('USER_VERIFIED')!=1) {
                if($request->ajax()){
                    return response()->json(['errorMsg' => 'Disabled for demo !'], 422);
                }else {
                    return redirect()->back()->withErrors(['errors' => ['Disabled for demo !']]);
                }
            }
        }
        return $next($request);
    }
}
