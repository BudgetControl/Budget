<?php
namespace Budgetcontrol\Budget\Controller;

use Budgetcontrol\Budget\Domain\Repository\BudgetRepository;
use Brick\Math\BigNumber;
use Budgetcontrol\Budget\Domain\Entity\BudgetStats;

class BudgetStatsController extends Controller {
    
        /**
         * Retrieves the statistics for the budget.
         *
         * @param $request The HTTP request object.
         * @param $response The HTTP response object.
         * @param $args The route parameters.
         */
        public function getStats($request, $response, $args)
        {
            $repository = new BudgetRepository($args['wsid']);
            $budget = $repository->statsOfBudget($args['uuid']);

            if(empty($budget)) {
                return response(['No budgets found'], 404);
            }

            return response($budget->toArray());
        }
    
        /**
         * Retrieves all statistics.
         *
         * @param $request The HTTP request object.
         * @param $response The HTTP response object.
         * @param $args The route parameters.
         * @return void
         */
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

        /**
         * Retrieves all entries.
         *
         * @param $request The HTTP request object.
         * @param $response The HTTP response object.
         * @param $args The route parameters.
         * @return The HTTP response with the retrieved entries.
         */
        public function getAllEntry($request, $response, $args)
        {
            $repository = new BudgetRepository($args['wsid']);
            $entries = $repository->enryList($args['uuid']);

            if(empty($entries)) {
                return response([]);
            }

            return response($entries->toArray());

        }
}