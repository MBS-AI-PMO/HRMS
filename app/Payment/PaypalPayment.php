<?php

namespace App\Payment;

use App\Contracts\PaybleContract;
use App\Models\Landlord\Payment;

class PaypalPayment implements PaybleContract
{
    public function pay($tenantRequestData, $paymentRequestData) : object
    {
        $totalAmount = request()->session()->get('price');
        return Payment::create([
            'package_id' => $tenantRequestData->package_id,
            'amount' => $totalAmount,
            'payment_method' => $tenantRequestData->payment_method,
            'status' => 'paid',
            'subscription_type' => $tenantRequestData->subscription_type,
            'data' => json_encode($paymentRequestData)
        ]);
    }
}
