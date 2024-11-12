<?php
declare(strict_types=1);

namespace Budgetcontrol\Budget\Domain\Repository;

use Budgetcontrol\Budget\Domain\Entity\BudgetStats;
use Budgetcontrol\Library\Definition\Period;
use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Library\Model\Budget;
use Illuminate\Database\Capsule\Manager as DB;
use Webit\Wrapper\BcMath\BcMathNumber;

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

            $stats = $this->budgetTotalSum(
                $budget
            );

            if($stats === null) {
                continue;
            }

            if($stats * -1 < $budget->budget) {
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

            $stats = $this->budgetTotalSum(
                $budget
            );

            if($stats === null) {
                continue;
            }

            $budgetsStats[] = new BudgetStats($stats ?? 0, $budget);

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
        $budget = Budget::where('uuid', $budgetUuid)->first();

        $stats = $this->budgetTotalSum(
            $budget
        );

        if($stats === null) {
            return null;
        }

        return new BudgetStats($stats ?? 0, $budget);
    }

    /**
     * Retrieves the entry list for a specific budget.
     *
     * @param string $budgetUuid The UUID of the budget.
     * @return Entry The entry list for the budget.
     */
    public function enryList(string $budgetUuid)
    {
        $budget = Budget::where('uuid', $budgetUuid)->where('workspace_id', $this->workspaceId)->where('deleted_at',null)->first();

        if(empty($budget)) {
            return [];
        }

        $entryList = $this->budgetEntryList($budget);

        foreach($entryList as $entry) {
            $listId[] = $entry->id;
        }

        if(empty($listId)) {
            return [];
        }

        $list = Entry::WithRelations()->whereIn('id', $listId)->orderBy('date_time', 'desc')->get();

        return $list;
    }

    /**
     * Calculates the statistics for a given budget.
     *
     * @param Budget $budget The budget for which to calculate the statistics.
     * @return object An array containing the calculated statistics.
     */
    protected function budgetEntryList(Budget $budget)
    {
        /** @var \Budgetcontrol\Library\ValueObject\BudgetConfiguration $configuration */
        $configuration = $budget->configuration;

        $accounts = $configuration->getAccounts();
        $types = $configuration->getTypes();
        $tags = $configuration->getTags();
        $categories = $configuration->getCategories();
        $period = $configuration->getPeriod();
        $endDate = $configuration->getPeriodEnd();
        $startDate = $configuration->getPeriodStart();

        $query = "SELECT id, amount FROM entries where deleted_at is null and workspace_id = $this->workspaceId";

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
            $entries = str_replace(',,','',$entries); // Work Around fixme:
            if(!empty($entries)) {
                $query .= " and id in ($entries)";
            }
        }

        if($period === Period::oneShot->value || $period === Period::recursively->value) {
            $start = $startDate->format('Y-m-d H:i:s');
            $end = $endDate->format('Y-m-d H:i:s');
            $query .= " and date_time between '$start' and '$end'";
        } else {
            switch($period) {
                case Period::daily->value:
                    $query .= " and date_time = CURDATE() and YEAR(date_time) = YEAR(CURDATE())";
                    break;
                case Period::weekly->value:
                    $query .= " and WEEK(date_time) = WEEK(CURDATE()) and YEAR(date_time) = YEAR(CURDATE())";
                    break;
                case Period::monthly->value:
                    $query .= " and MONTH(date_time) = MONTH(CURDATE()) and YEAR(date_time) = YEAR(CURDATE())";
                    break;
                case Period::yearly->value:
                    $query .= " and YEAR(date_time) = YEAR(CURDATE())";
                    break;
            }
        }

        $results = DB::select($query);

        if(empty($results)) {
            return null;
        }

        return $results;

    }

    /**
     * Calculates the total sum of a budget.
     *
     * @param Budget $budget The budget object for which to calculate the total sum.
     * @return float The total sum of the budget.
     */
    private function budgetTotalSum(Budget $budget): float
    {
        $entryList = $this->budgetEntryList($budget);

        $sum = new BcMathNumber(0);
        foreach($entryList as $entry) {
            $sum = $sum->add($entry->amount);
        }

        return $sum->toFloat();
    }

}
