<?php
namespace Budgetcontrol\Budget\Controller;

use Budgetcontrol\Budget\Domain\Model\Budget;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

abstract class Controller {

    protected function validate(Request $request)
    {
        $validator = Validator::make($request->getParsedBody(), [
            'name' => 'required|string',
            'amount' => 'required|numeric',
            'configuration' => 'required|array',
            'notification' => 'boolean',
            'emails' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed.', $validator->errors()->toArray());
            throw new \Exception("Validation failed.");
        }
        

        //validate emails field must a valid email
        if (isset($request->getParsedBody()['emails'])) {
            $emails = $request->getParsedBody()['emails'];
            foreach ($emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    Log::error('Validation failed.', ['emails' => 'Invalid email address']);
                    throw new \Exception("Validation failed.");
                }
            }
        }

        // validate configuration json
        $this->configurationFieldValidator($request->getParsedBody()['configuration']);

    }

    private function configurationFieldValidator(array $configuration)
    {
        $validator = Validator::make($configuration, [
            'period' => 'required|string|in:one_shot,monthly,weekly,daily,recursively',
            'categories' => 'array',
            'accounts' => 'array',
            'tags' => 'array',
            'types' => 'array',
        ]);
        
        //if period is one_shot check period_date
        if ($configuration['period'] === Budget::ONE_SHOT) {
            $validator->addRules(['period_start' => 'required|date']);
            $validator->addRules(['period_end' => 'required|date']);
        }

        if ($validator->fails()) {
            Log::error('Validation failed.', $validator->errors()->toArray());
            throw new \Exception("Validation failed.");
        }
    }
    
}