<?php
namespace Budgetcontrol\Budget\Controller;

use Budgetcontrol\Budget\Domain\Repository\BudgetRepository;
use Brick\Math\BigNumber;
use Budgetcontrol\Budget\Domain\Entity\BudgetStats;

class BudgetStatsController extends Controller {
    
        public function getStats($request, $response, $args)
        {
            $repository = new BudgetRepository($args['wsid']);
            $budget = $repository->statsOfBudget($args['uuid']);

            if(empty($budget)) {
                return response(['No budgets found'], 404);
            }

            return response($budget->toArray());
        }
    
        public function getAllStats($request, $response, $args)
        {
            $repository = new BudgetRepository($args['wsid']);
            $budgets = $repository->statsOfBuddgets();

            if(empty($budgets)) {
                return response(['No budgets found'], 404);
            }

            $results = [];
            foreach($budgets as $budget) {
                $results[] = $budget->toArray();
            }

            return response($results);

        }
}