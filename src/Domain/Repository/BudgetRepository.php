<?php
namespace Budgetcontrol\Budget\Domain\Repository;

use Budgetcontrol\Budget\Domain\Model\Budget;
use Illuminate\Database\Capsule\Manager as DB;

class BudgetRepository extends Repository {

    /**
     * Finds and returns the budgets that have been exceeded.
     *
     * @return array The budgets that have been exceeded.
     */
    public function findExceededBudget()
    {
        $query = "SELECT * FROM budgets where notification = 1 and deleted_at is null;";
        $results = DB::select($query);

        if(empty($results)) {
            return null;
        }

        $budgets = [];
        foreach($results as $budget) {
            $budget = new Budget([
                'uuid' => $budget->uuid,
                'budget' => $budget->budget,
                'configuration' => $budget->configuration,
                'notification' => $budget->notification,
                'workspace_id' => $budget->workspace_id,
                'emails' => $budget->emails
            ]);

            $stats = $this->budgetStats(
                $budget
            );

            if($stats === null) {
                continue;
            }

            if($stats->total * -1 < $budget->budget) {
                continue;
            }

            $budgets[] = $budget;

        }

        return $budgets;
    }

    /**
     * Calculates the statistics for a given budget.
     *
     * @param Budget $budget The budget for which to calculate the statistics.
     * @return object An array containing the calculated statistics.
     */
    protected function budgetStats(Budget $budget)
    {
        $configuration = $budget->configuration;

        $account = $configuration?->account ?? null;
        $type = $configuration?->type ?? null;
        $tag = $configuration?->tags ?? null;
        $category = $configuration?->category ?? null;
        $period = $configuration?->period ?? null;
        $endDate = $configuration?->end_date ?? null;
        $startDate = $configuration?->start_date ?? null;

        $query = "SELECT sum(amount) as total FROM entries where deleted_at is null";

        if(!empty($account)) {
            $account = implode(',', $account);
            $query .= " and account_id in ($account)";
        }

        if($type == 'category') {
            $category = implode(',', $category);
            $query .= " and category_id in ($category)";
        }

        if(!empty($type)) {
            foreach($type as $_ => $value) {
                $types[] = "'$value'";
            }
            $types = implode(',', $types);
            $query .= " and type in ($types)";
        }

        if(!empty($tag)) {
            $tags = $this->entriesFromTags($tag);
            $entries = array_map(function($entry) {
                return $entry->id;
            }, $tags);
            $entries = implode(',', $entries);
            if(!empty($entries)) {
                $query .= " and id in ($entries)";
            }
        }

        if($period === Budget::ONE_SHOT) {
            $query .= " and date_time between '$startDate' and '$endDate'";
        } else {
            switch($period) {
                case Budget::DAILY:
                    $query .= " and date_time = CURDATE()";
                    break;
                case Budget::WEEKLY:
                    $query .= " and WEEK(date_time) = WEEK(CURDATE())";
                    break;
                case Budget::MONTHLY:
                    $query .= " and MONTH(date_time) = MONTH(CURDATE())";
                    break;
                case Budget::YEARLY:
                    $query .= " and YEAR(date_time) = YEAR(CURDATE())";
                    break;
            }
        }

        $results = DB::select($query);

        if(empty($results)) {
            return null;
        }

        return $results[0];

    }

}