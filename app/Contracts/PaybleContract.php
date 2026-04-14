<?php

namespace App\Contracts;

interface PaybleContract
{
    public function pay($paymentRequestData);
}
