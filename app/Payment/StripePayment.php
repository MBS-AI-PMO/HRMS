<?php
// declare(strict_types=1);

namespace App\Payment;

use App\Contracts\PaybleContract;
use App\Models\Landlord\Payment;
use Exception;
use Stripe;

class StripePayment implements PaybleContract
{
    public function pay($paymentRequestData)
    {
        // $totalAmount = request()->session()->get('price');
        // $this->stripe($totalAmount * 100, $paymentRequestData['stripeToken']);

        // $payment = Payment::create([
        //     'package_id' => $tenantRequestData->package_id,
        //     'amount' => $totalAmount,
        //     'payment_method' => $tenantRequestData->payment_method,
        //     'status' => 'paid',
        //     'subscription_type' => $tenantRequestData->subscription_type,
        //     'data' => json_encode($paymentRequestData)
        // ]);

        // return $payment;
    }



    public function cancel()
    {
        // $this->orderCancel();
        // return response()->json('success');

        /*
        |----------------------------------------------------------
        | Store your data here.
        | This section is common for all. Suggest you to use Trait.
        |----------------------------------------------------------
        */
    }

    protected function stripe($totalAmount, $stripeToken)
    {
        Stripe\Stripe::setApiKey(config('payment_gateway.stripe.secret'));
        Stripe\Charge::create ([
                "amount" => (int)implode(explode(',',$totalAmount)),
                "currency" => config('payment_gateway.stripe.currency'),
                "source" => $stripeToken,
                "description" => "Stripe Payment Successfull."
        ]);
    }
}

