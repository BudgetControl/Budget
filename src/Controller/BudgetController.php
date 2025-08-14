<?php
namespace Budgetcontrol\Budget\Controller;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Carbon;
use Budgetcontrol\Budget\Domain\Model\Budget;
use Budgetcontrol\Budget\Domain\Repository\BudgetRepository;
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
        $data = $request->getParsedBody();
        $errors = $this->validateBudget($data);
        if (!empty($errors)) {
            return response(['errors' => $errors], 422);
        }

        $budget = new Budget();
        $budget->uuid = Uuid::uuid4();
        $budget->name = $data['name'];
        $budget->amount = $data['amount'];
        $budget->configuration = $data['configuration'];
        $budget->notification = $data['notification'];
        $budget->workspace_id = $args['wsid'];
        $budget->emails = $data['emails'] ?? [];
        $budget->thresholds = $data['thresholds'] ?? [];
        $budget->description = $data['description'] ?? '';
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
        $data = $request->getParsedBody();
        $errors = $this->validateBudget($data);
        if (!empty($errors)) {
            return response(['errors' => $errors], 422);
        }

        $budget = Budget::where('uuid',$args['uuid'])->first();
        if(!$budget){
            return response(["Budget not found"], 404);
        }
        $budget->name = $data['name'];
        $budget->amount = $data['amount'];
        $budget->configuration = $data['configuration'];
        $budget->notification = $data['notification'];
        $budget->workspace_id = $args['wsid'];
        $budget->emails = $data['emails'] ?? [];
        $budget->thresholds = $data['thresholds'] ?? [];
        $budget->description = $data['description'] ?? '';
        $budget->save();

        return response($budget->toArray(), 200);
    /**
     * Validazione campi emails e thresholds
     */
    private function validateBudget($data) {
        $errors = [];
        // Validazione thresholds
        if (isset($data['thresholds'])) {
            foreach ($data['thresholds'] as $t) {
                if (!is_numeric($t) || $t < 1 || $t > 99) {
                    $errors[] = "Le soglie devono essere numeri tra 1 e 99";
                    break;
                }
            }
        }
        // Validazione emails
        if (isset($data['emails'])) {
            foreach ($data['emails'] as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Email non valida: $email";
                }
            }
        }
        return $errors;
    }

    /**
     * Logica di notifica: invia email se la percentuale supera una soglia
     */
    private function checkAndNotify(Budget $budget, $percentSpent) {
        if (empty($budget->thresholds) || empty($budget->emails)) return;
        foreach ($budget->thresholds as $threshold) {
            if ($percentSpent >= $threshold) {
                // Qui va la logica di invio email/notifica
                // es: Mailer::send($budget->emails, "Budget superato $threshold%", ...);
                // Logga o invia notifica
            }
        }
    }
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