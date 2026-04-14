<?php

namespace App\Services;

use App\Payment\PaypalPayment;
use App\Payment\PaystackPayment;
use App\Payment\RazorpayPayment;
use App\Payment\StripePayment;

class PaymentService
{
    public function initialize($paymentType)
    {
        switch ($paymentType) {
            case 'stripe':
                return new StripePayment();
            // case 'paypal':
            //     return new PaypalPayment();
            // case 'razorpay':
            //     return new RazorpayPayment();
            // case 'paystack':
            //     return new PaystackPayment();
            default:
                break;
        }
    }
}
?>
