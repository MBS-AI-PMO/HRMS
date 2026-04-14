<?php

namespace App\Payment;

use App\Contracts\PaybleContract;
use App\Models\Landlord\Payment;
use Illuminate\Support\Facades\Session;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalPayment implements PaybleContract
{
    // public function pay($tenantRequestData, $paymentRequestData) : object
    public function pay($tenantRequestData, $paymentRequestData)
    {
        $domainURL = $tenantRequestData->tenant.'.'.env('CENTRAL_DOMAIN');

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $token = $provider->getAccessToken();
        $provider->setAccessToken($token);

        $response = $provider->createOrder([
            "intent"=> "CAPTURE",
            "application_context" => [ //Create Subscription
                // "return_url" => route('payment.success',$domainURL),
                "return_url" => route('payment.paypal.success'),
                "cancel_url" => route('payment.paypal.cancel'),
            ],
            "purchase_units"=> [
                 [
                    "amount"=> [
                        "currency_code"=> "USD",
                        "value"=> 0.01,
                    ]
                ]
            ],
        ]);


        if(isset($response['id'])) {
            foreach ($response['links'] as $link) {
                if($link['rel'] === 'approve')
                    $payment = self::paymentDataCreate($tenantRequestData, $paymentRequestData);
                    Session::put('paymentId', $payment->id);

                    return redirect()->away($link['href']);
            }
        }




        // $totalAmount = request()->session()->get('price');
        // $payment = Payment::create([
        //     'package_id' => $tenantRequestData->package_id,
        //     'amount' => $totalAmount,
        //     'payment_method' => $tenantRequestData->payment_method,
        //     'status' => 'pending',
        //     'subscription_type' => $tenantRequestData->subscription_type,
        //     'data' => json_encode($paymentRequestData)
        // ]);

        // return $payment;
    }

    protected static function paymentDataCreate(object $tenantRequestData, array $paymentRequestData) :object
    {
        $totalAmount = request()->session()->get('price');
        return Payment::create([
            'package_id' => $tenantRequestData->package_id,
            'amount' => $totalAmount,
            'payment_method' => $tenantRequestData->payment_method,
            'status' => 'pending',
            'subscription_type' => $tenantRequestData->subscription_type,
            'data' => json_encode($paymentRequestData)
        ]);
    }


    public function cancel()
    {

    }

    public function success($request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $token = $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request->token); //Capture Payment for Order

        // return $provider->listProducts();


        if(isset($response['status']) && $response['status']==='COMPLETED') {
            return 'Payment is Successfull';
        }else{
            return redirect()->route('paypal.cancel');
        }
    }
}
