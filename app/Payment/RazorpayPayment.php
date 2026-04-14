<?php
// declare(strict_types=1);

namespace App\Payment;

use App\Contracts\PaybleContract;
use App\Models\Landlord\Payment;
use Razorpay\Api\Api;


class RazorpayPayment implements PaybleContract
{
    public function pay($tenantRequestData, $paymentRequestData) : object
    {
        $input = $paymentRequestData;
        $api = new Api (config('payment_gateway.razorpay.key'), config('payment_gateway.razorpay.secret'));
        $razorPayment = $api->payment->fetch($input['razorpay_payment_id']);

        if(count($input) && !empty($input['razorpay_payment_id'])) {
            $razorpayResponse = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount' => $razorPayment['amount']));

            $totalAmount = request()->session()->get('price');

            $payment = Payment::create([
                'package_id' => $tenantRequestData->package_id,
                'amount' => $totalAmount,
                'payment_method' => $tenantRequestData->payment_method,
                'status' => 'paid',
                'subscription_type' => $tenantRequestData->subscription_type,
                'data' => json_encode(array_merge($paymentRequestData, ['razorpayResponse' => (array)$razorpayResponse]))
            ]);
        }

        return $payment;
    }

    public function cancel()
    {

    }
}
