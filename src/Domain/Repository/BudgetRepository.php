<?php
namespace Budgetcontrol\Budget\Domain\Repository;

use Budgetcontrol\Budget\Domain\Entity\BudgetStats;
use Budgetcontrol\Budget\Domain\Model\Budget;
use Illuminate\Database\Capsule\Manager as DB;

class BudgetRepository extends Repository {

    private int $workspaceId;

    public function __construct(int $workspaceId)
    {
        $this->workspaceId = $workspaceId;
    }

    /**
     * Finds and returns the budgets that have been exceeded.
     *
     * @return array The budgets that have been exceeded.
     */
    public function findExceededBudget()
    {
        $query = "SELECT * FROM budgets where notification = 1 and deleted_at is null and workspace_id = $this->workspaceId;";
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
     * Finds and returns the budgets that have been exceeded.
     * @return array <BudgetStats> The budgets that have been exceeded.
     */
    public function statsOfBuddgets(): array
    {
        $budgets = Budget::where('workspace_id', $this->workspaceId)->where('deleted_at',null)->get();
        $budgetsStats = [];
        foreach($budgets as $budget) {

            $stats = $this->budgetStats(
                $budget
            );

            if($stats === null) {
                continue;
            }

            $budgetsStats[] = new BudgetStats($stats->total ?? 0, $budget);

        }

        return $budgetsStats;
    }

    /**
     * Retrieves the statistics of a budget.
     *
     * @param string $budgetUuid The UUID of the budget.
     * @return BudgetStats The statistics of the budget.
     */
    public function statsOfBudget(string $budgetUuid): ?BudgetStats
    {
        $budget = Budget::where('uuid', $budgetUuid)->where('workspace_id', $this->workspaceId)->where('deleted_at',null)->first();

        $stats = $this->budgetStats(
            $budget
        );

        if($stats === null) {
            return null;
        }

        return new BudgetStats($stats->total ?? 0, $budget);
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

        $accounts = $configuration['accounts'] ?? [];
        $types = $configuration['types'] ?? [];
        $tags = $configuration['tags'] ?? [];
        $categories = $configuration['categories'] ?? [];
        $period = $configuration['period'] ?? null;
        $endDate = $configuration['end_date'] ?? null;
        $startDate = $configuration['start_date'] ?? null;

        $query = "SELECT sum(amount) as total FROM entries where deleted_at is null and workspace_id = $this->workspaceId";

        if(!empty($accounts)) {
            $accounts = implode(',', $accounts);
            $query .= " and account_id in ($accounts)";
        }

        if(!empty($categories)) {
            $categories = implode(',', $categories);
            $query .= " and category_id in ($categories)";
        }

        if(!empty($types)) {
            foreach($types as $_ => $value) {
                $typesOf[] = "'$value'";
            }
            $typesOf = implode(',', $typesOf);
            $query .= " and type in ($typesOf)";
        }

        if(!empty($tags)) {
            $tags = $this->entriesFromTags($tags);
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
                    $query .= " and date_time = CURDATE() and YEAR(date_time) = YEAR(CURDATE())";
                    break;
                case Budget::WEEKLY:
                    $query .= " and WEEK(date_time) = WEEK(CURDATE()) and YEAR(date_time) = YEAR(CURDATE())";
                    break;
                case Budget::MONTHLY:
                    $query .= " and MONTH(date_time) = MONTH(CURDATE()) and YEAR(date_time) = YEAR(CURDATE())";
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