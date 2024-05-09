<?php
namespace Budgetcontrol\Budget\Controller;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Carbon;
use Budgetcontrol\Budget\Domain\Model\Budget;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BudgetController extends Controller {


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
        $budgets = Budget::where('workspace_id', $args['wsid'])->get();

        if($budgets->isEmpty()){
            return response([], 204);
        }
        
        return response($budgets->toArray(), 200);
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

        return response($budget->toArray(), 200);
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

        $budget = Budget::find($args['uuid']);

        if($budget->isEmpty()){
            return response(["Budget not found"], 404);
        }

        $budget->name = $request->getParsedBody()['name'];
        $budget->amount = $request->getParsedBody()['amount'];
        $budget->configuration = $request->getParsedBody()['configuration'];
        $budget->notification = $request->getParsedBody()['notification'];
        $budget->workspace_id = $args['wsid'];
        $budget->emails = $request->getParsedBody()['emails'];
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

        $budget = $budgets->first();
        $configuration = json_decode($budget->configurations, true);

        $date_end = Carbon::createFromFormat('Y-m-d h:i:s', $configuration->period_end);
        if($date_end < Carbon::now()->toAtomString()) {
            return response(['expired' => true]);
        }

        $budget->save();

        return response($budget->toArray());
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

        $budget = $budgets->first();
        if($budget->amount < $budget->balance) {
            return response(['exceeded' => true]);
        }

        $budget->save();

        return response($budget->toArray());
    }

}