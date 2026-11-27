<?php

namespace App\Services\Finance;

use App\Models\CustomerInstallmentPlan;
use Carbon\CarbonImmutable;

class InstallmentScheduleCalculator
{
    /**
     * Calculate accurate VND installment amortization schedule & summary for a customer plan.
     *
     * @return array{
     *     vehicle_price: int|null,
     *     down_payment: int|null,
     *     loan_amount: int,
     *     interest_rate: float,
     *     fixed_rate_months: int,
     *     floating_rate_adjustment_months: int,
     *     floating_rate_adjustment_label: string,
     *     floating_interest_rate: float|null,
     *     early_settlement_fee_note: string|null,
     *     interest_method: string,
     *     interest_method_label: string,
     *     term_months: int,
     *     paid_installments: int,
     *     remaining_installments: int,
     *     paid_principal: int,
     *     remaining_principal: int,
     *     estimated_remaining_total: int,
     *     total_interest_estimated: int,
     *     progress_percent: int,
     *     next_due_date_label: string,
     *     schedule: list<array{
     *         period: int,
     *         month_label: string,
     *         due_date_label: string,
     *         opening_balance: int,
     *         principal_amount: int,
     *         interest_amount: int,
     *         interest_rate_applied: float,
     *         rate_phase: string,
     *         total_payment: int,
     *         closing_balance: int,
     *         status: string
     *     }>
     * }
     */
    public function calculate(CustomerInstallmentPlan $plan): array
    {
        $loanAmount = max(0, (int) $plan->loan_amount);
        $termMonths = max(1, (int) $plan->term_months);
        $isCompleted = $plan->status === CustomerInstallmentPlan::STATUS_COMPLETED;
        $paidInstallments = $isCompleted
            ? $termMonths
            : min($termMonths, max(0, (int) $plan->paid_installments));
        $remainingInstallments = max(0, $termMonths - $paidInstallments);

        $annualRate = max(0.0, (float) $plan->interest_rate);
        $fixedRateMonths = max(0, min($termMonths, (int) ($plan->fixed_rate_months ?? 12)));
        $adjustmentMonths = in_array((int) ($plan->floating_rate_adjustment_months ?? 3), [0, 3, 6], true)
            ? (int) ($plan->floating_rate_adjustment_months ?? 3)
            : 3;
        $floatingRate = $plan->floating_interest_rate !== null
            ? max(0.0, (float) $plan->floating_interest_rate)
            : null;
        $effectiveFloatingRate = ($adjustmentMonths > 0 && $floatingRate !== null)
            ? $floatingRate
            : $annualRate;

        $interestMethod = $plan->interest_method === CustomerInstallmentPlan::METHOD_FLAT
            ? CustomerInstallmentPlan::METHOD_FLAT
            : CustomerInstallmentPlan::METHOD_REDUCING_BALANCE;

        $todayVn = CarbonImmutable::now('Asia/Ho_Chi_Minh')->startOfDay();
        $firstDueDate = $this->resolveFirstDueDate($plan, $paidInstallments, $termMonths, $todayVn);

        $basePrincipal = intdiv($loanAmount, $termMonths);
        $openingBalance = $loanAmount;

        $paidPrincipal = 0;
        $estimatedRemainingTotal = 0;
        $totalInterestEstimated = 0;
        $nextDueDateLabel = null;
        $schedule = [];

        for ($period = 1; $period <= $termMonths; $period++) {
            $dueDate = $firstDueDate->addMonthsNoOverflow($period - 1);
            $principalAmount = $period === $termMonths ? $openingBalance : $basePrincipal;
            $isFixedPhase = $fixedRateMonths === 0 ? false : $period <= $fixedRateMonths;
            $periodAnnualRate = $isFixedPhase ? $annualRate : $effectiveFloatingRate;
            $periodMonthlyRate = ($periodAnnualRate / 100.0) / 12.0;

            $interestAmount = $interestMethod === CustomerInstallmentPlan::METHOD_FLAT
                ? (int) round($loanAmount * $periodMonthlyRate)
                : (int) round($openingBalance * $periodMonthlyRate);

            $totalPayment = $principalAmount + $interestAmount;
            $closingBalance = max(0, $openingBalance - $principalAmount);

            if ($period <= $paidInstallments) {
                $status = 'paid';
                $paidPrincipal += $principalAmount;
            } elseif ($period === $paidInstallments + 1) {
                $isPastDue = $plan->status === CustomerInstallmentPlan::STATUS_OVERDUE || $dueDate->lt($todayVn);
                $status = $isPastDue ? 'overdue' : 'current';
                $estimatedRemainingTotal += $totalPayment;
                $nextDueDateLabel = $dueDate->format('d/m/Y');
            } else {
                $status = $dueDate->lt($todayVn) ? 'overdue' : 'upcoming';
                $estimatedRemainingTotal += $totalPayment;
            }

            $totalInterestEstimated += $interestAmount;

            $schedule[] = [
                'period' => $period,
                'month_label' => 'Tháng '.$dueDate->format('m/Y'),
                'due_date_label' => $dueDate->format('d/m/Y'),
                'opening_balance' => $openingBalance,
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'interest_rate_applied' => $periodAnnualRate,
                'rate_phase' => $isFixedPhase || $adjustmentMonths === 0 || $floatingRate === null ? 'fixed' : 'floating',
                'total_payment' => $totalPayment,
                'closing_balance' => $closingBalance,
                'status' => $status,
            ];

            $openingBalance = $closingBalance;
        }

        $remainingPrincipal = max(0, $loanAmount - $paidPrincipal);
        $progressPercent = (int) min(100, max(0, round(($paidInstallments / $termMonths) * 100)));

        if ($remainingInstallments === 0) {
            $resolvedNextDueLabel = 'Đã tất toán';
        } elseif ($plan->next_due_date !== null) {
            $resolvedNextDueLabel = $plan->next_due_date->format('d/m/Y');
        } else {
            $resolvedNextDueLabel = $nextDueDateLabel ?? 'Đang cập nhật';
        }

        return [
            'vehicle_price' => $plan->vehicle_price !== null ? (int) $plan->vehicle_price : null,
            'down_payment' => $plan->down_payment !== null ? (int) $plan->down_payment : null,
            'loan_amount' => $loanAmount,
            'interest_rate' => $annualRate,
            'fixed_rate_months' => $fixedRateMonths,
            'floating_rate_adjustment_months' => $adjustmentMonths,
            'floating_rate_adjustment_label' => $plan->adjustmentFrequencyLabel(),
            'floating_interest_rate' => $floatingRate,
            'early_settlement_fee_note' => filled($plan->early_settlement_fee_note) ? (string) $plan->early_settlement_fee_note : null,
            'interest_method' => $interestMethod,
            'interest_method_label' => $plan->interestMethodLabel(),
            'term_months' => $termMonths,
            'paid_installments' => $paidInstallments,
            'remaining_installments' => $remainingInstallments,
            'paid_principal' => $paidPrincipal,
            'remaining_principal' => $remainingPrincipal,
            'estimated_remaining_total' => $estimatedRemainingTotal,
            'total_interest_estimated' => $totalInterestEstimated,
            'progress_percent' => $progressPercent,
            'next_due_date_label' => $resolvedNextDueLabel,
            'schedule' => $schedule,
        ];
    }

    private function resolveFirstDueDate(
        CustomerInstallmentPlan $plan,
        int $paidInstallments,
        int $termMonths,
        CarbonImmutable $todayVn,
    ): CarbonImmutable {
        if ($plan->next_due_date !== null && $paidInstallments < $termMonths) {
            return CarbonImmutable::parse($plan->next_due_date->format('Y-m-d'), 'Asia/Ho_Chi_Minh')
                ->startOfDay()
                ->subMonthsNoOverflow($paidInstallments);
        }

        if ($plan->start_date !== null) {
            return CarbonImmutable::parse($plan->start_date->format('Y-m-d'), 'Asia/Ho_Chi_Minh')
                ->startOfDay()
                ->addMonthNoOverflow();
        }

        if ($plan->next_due_date !== null) {
            return CarbonImmutable::parse($plan->next_due_date->format('Y-m-d'), 'Asia/Ho_Chi_Minh')
                ->startOfDay()
                ->subMonthsNoOverflow(max(0, $termMonths - 1));
        }

        return $todayVn
            ->addMonthNoOverflow()
            ->subMonthsNoOverflow($paidInstallments);
    }
}
