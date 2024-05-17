<?php
namespace Budgetcontrol\Budget\Controller;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Carbon;
use Budgetcontrol\Budget\Domain\Model\Budget;
use Budgetcontrol\Budget\Traits\RequestFilters;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Budgetcontrol\Budget\Domain\Repository\BudgetRepository;

class BudgetController extends Controller {

    use RequestFilters;

    protected $filters = ['name', 'budget', 'configuration', 'notification', 'emails', 'description'];

    /**
     * Handles the index action of the BudgetController.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route parameters.
     * @return Response The HTTP response object.
     */
    public function index(Request $request, Response $response, $args)
    {
        $budgets = Budget::where('workspace_id', $args['wsid']);
        $filters = $this->extractFilters($request);
        foreach ($filters as $key => $value) {
            $budgets->where($key, $value);
        }

        if($budgets->count() === 0){
            return response([], 204);
        }
        
        return response($budgets->get()->toArray(), 200);
    }

    /**
     * Show method for the BudgetController.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route parameters.
     * @return Response The HTTP response object.
     */
    public function show(Request $request, Response $response, $args)
    {
        $budget = Budget::where('uuid', $args['uuid'])->where('workspace_id', $args['wsid'])->get();

        if($budget->isEmpty()){
            return response(["Budget not found"], 404);
        }

        return response($budget->toArray()[0], 200);
    }

    /**
     * Creates a new budget.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route parameters.
     * @return Response The HTTP response object.
     */
    public function create(Request $request, Response $response, $args)
    {
        $this->validate($request);

        $budget = new Budget();
        $budget->uuid = Uuid::uuid4();
        $budget->name = $request->getParsedBody()['name'];
        $budget->amount = $request->getParsedBody()['amount'];
        $budget->configuration = $request->getParsedBody()['configuration'];
        $budget->notification = $request->getParsedBody()['notification'];
        $budget->workspace_id = $args['wsid'];
        $budget->emails = $request->getParsedBody()['emails'];
        $budget->description = $request->getParsedBody()['description'];
        $budget->save();

        return response($budget->toArray(), 201);
    }

    /**
     * Update a budget.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route parameters.
     * @return Response The updated HTTP response object.
     */
    public function update(Request $request, Response $response, $args)
    {
        $this->validate($request);

        $budget = Budget::where('uuid',$args['uuid'])->first();

        if($budget->count() == 0){
            return response(["Budget not found"], 404);
        }

        $budget->name = $request->getParsedBody()['name'];
        $budget->amount = $request->getParsedBody()['amount'];
        $budget->configuration = $request->getParsedBody()['configuration'];
        $budget->notification = $request->getParsedBody()['notification'];
        $budget->workspace_id = $args['wsid'];
        $budget->emails = $request->getParsedBody()['emails'];
        $budget->description = $request->getParsedBody()['description'];
        $budget->save();

        return response($budget->toArray(), 200);
    }

    /**
     * Deletes a budget.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param array $args The route parameters.
     * @return Response The updated HTTP response object.
     */
    public function delete(Request $request, Response $response, $args)
    {
        $budget = Budget::where('workspace_id', $args['wsid'])->where('uuid', $args['uuid']);
        $budget->delete();

        return response([], 204);
    }

    /**
     * Handle the expired budget request.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param mixed $arg Additional arguments.
     * @return Response The HTTP response object.
     */
    public function expired(Request $request, Response $response, $arg)
    {
        $budgets = Budget::where('uuid',$arg['uuid'])->get();

        if($budgets->isEmpty()){
            return response(['message' => 'Budget not found'], 404);
        }

        $stats = new BudgetRepository($arg['wsid']);
        $budget = $stats->statsOfBudget($arg['uuid']);

        return response(['expired' => $budget->isExpired()]);
    }

    /**
     * Handle the request for the "exceeded" action.
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     * @param mixed $arg Additional argument for the action.
     * @return Response The updated HTTP response object.
     */
    public function exceeded(Request $request, Response $response, $arg)
    {
        $budgets = Budget::where('uuid',$arg['uuid'])->get();

        if($budgets->isEmpty()){
            return response(['message' => 'Budget not found'], 404);
        }

        $stats = new BudgetRepository($arg['wsid']);
        $budget = $stats->statsOfBudget($arg['uuid']);

        return response(['exceeded' => $budget->isExeeded()]);
        
    }

}