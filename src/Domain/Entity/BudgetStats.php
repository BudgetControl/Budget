<?php

namespace Budgetcontrol\Budget\Domain\Entity;

use Brick\Math\BigNumber;
use Illuminate\Support\Carbon;
use Budgetcontrol\Budget\Traits\Serializer;
use Budgetcontrol\Budget\Domain\Model\Budget;
use Mlab\MathPercentage\Service\PercentCalculator;

/**
 * Represents the statistics of a budget.
 */
final class BudgetStats
{
    use Serializer;
    
    private float|BigNumber $total;
    private readonly float|BigNumber $totalSpent;
    private float $totalRemaining;
    private string $totalSpentPercentage;
    private string $totalRemainingPercentage;
    private readonly Budget $budget;

    /**
     * Constructs a new BudgetStats instance.
     *
     * @param float|BigNumber $total The total amount of the budget.
     * @param Budget $budget The budget associated with the statistics.
     */
    public function __construct(float|BigNumber $totalSpent, Budget $budget)
    {
        $this->totalSpent = round($totalSpent, 2);
        $this->budget = $budget;
        $this->total = $this->budget->amount;
        $this->totalRemaining = BigNumber::sum($this->total, $this->totalSpent)->toFloat();

        $spentPercentage = PercentCalculator::calculatePercentage(PercentCalculator::TOTAL_PERCENTAGE, $this->totalSpent * -1, $this->total)->toInt();
        $this->totalSpentPercentage = "$spentPercentage%";

        $totalRemainingPercentage = 100 - $spentPercentage;
        $this->totalRemainingPercentage = $totalRemainingPercentage < 0 ? "0%" : "$totalRemainingPercentage%";
    }

    /**
     * Gets the total amount of the budget.
     *
     * @return mixed The total amount of the budget.
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Gets the total amount spent from the budget.
     *
     * @return mixed The total amount spent from the budget.
     */
    public function getTotalSpent()
    {
        return $this->totalSpent;
    }

    /**
     * Gets the total amount remaining in the budget.
     *
     * @return mixed The total amount remaining in the budget.
     */
    public function getTotalRemaining()
    {
        return $this->totalRemaining;
    }

    /**
     * Gets the percentage of the budget spent.
     *
     * @return float The percentage of the budget spent.
     */
    public function getTotalSpentPercentage()
    {
        return $this->totalSpentPercentage;
    }

    /**
     * Gets the percentage of the budget remaining.
     *
     * @return float The percentage of the budget remaining.
     */
    public function getTotalRemainingPercentage()
    {
        return $this->totalRemainingPercentage;
    }

    /**
     * Gets the budget associated with the statistics.
     *
     * @return Budget The budget associated with the statistics.
     */
    public function getBudget()
    {
        return $this->budget;
    }

    public function isExeeded(): bool
    {
        return $this->totalRemaining < 0;
    }

    public function isExpired(): bool
    {   
        $configuration = $this->budget->configuration;
        switch($configuration->period){
            case Budget::ONE_SHOT:
                $date_end = Carbon::createFromTimestamp(strtotime($configuration->period_end));
                break;
            case Budget::DAILY:
                $date_end = Carbon::now()->endOfDay();
                break;
            case Budget::WEEKLY:
                $date_end = Carbon::now()->lastWeekDay()->endOfDay();
                break;
            case Budget::MONTHLY:
                $date_end = Carbon::now()->lastOfMonth()->endOfDay();
                break;
            case Budget::YEARLY:
                $date_end = Carbon::now()->lastOfYear()->endOfDay();
                break;
            case Budget::RECURSIVELY:
                $date_end = Carbon::createFromTimestamp(strtotime($configuration->period_end));
                break;
            default:
                $date_end = Carbon::now()->lastOfMonth();
        }

        if($date_end->toAtomString() < Carbon::now()->toAtomString()) {
            return true;
        }
        return false;
    }
}
