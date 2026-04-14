<?php


namespace App\Http\traits;





Trait TotalSalaryTrait {

	public function totalSalary($employee, $payslip_type , $basic_salary, $allowance_amount, $deduction_amount, $pension_amount, $total_minutes =1){

        $total_commission_amount = $employee->commissions->sum (fn($commission) => (float) $commission->commission_amount);
	    $total_monthly_payable = $employee->loans->sum (fn($loan) => (float) $loan->monthly_payable);
        $total_other_payment_amount = $employee->otherPayments->sum (fn($otherPayment) => (float) $otherPayment->other_payment_amount);
        $total_overtime_amount = $employee->overtimes->sum (fn($overtime) => (float) $overtime->overtime_amount);

		if($payslip_type == 'Monthly')
		{
			$total = $basic_salary + $allowance_amount + $total_commission_amount
				- $total_monthly_payable - $deduction_amount - $pension_amount // (basic_salary - pension_amount)
				+ $total_other_payment_amount + $total_overtime_amount;
		}
		else
		{
			$total =  ($basic_salary / 60) * $total_minutes + $allowance_amount +  $total_commission_amount
				- $total_monthly_payable - $deduction_amount - $pension_amount // (basic_salary - pension_amount)
				+ $total_other_payment_amount + $total_overtime_amount;
		}

        if($total<0)
        {
            $total=0;
        }
		return $total;
	}
}


