<?php

/**
 *  application apps
 */

$app->get('/{wsid}', \Budgetcontrol\Budget\Controller\BudgetController::class . ':index');
$app->get('/{wsid}/{uuid}', \Budgetcontrol\Budget\Controller\BudgetController::class . ':show');
$app->post('/{wsid}/budget', \Budgetcontrol\Budget\Controller\BudgetController::class . ':create');
$app->put('/{wsid}/budgets/{uuid}', \Budgetcontrol\Budget\Controller\BudgetController::class . ':update');
$app->delete('/{wsid}/budgets/{uuid}', \Budgetcontrol\Budget\Controller\BudgetController::class . ':delete');
$app->get('/{wsid}/budgets/{uuid}/expired', \Budgetcontrol\Budget\Controller\BudgetController::class . ':expired');
$app->get('/{wsid}/budgets/{uuid}/exceeded', \Budgetcontrol\Budget\Controller\BudgetController::class . ':exceeded');
$app->get('/{wsid}/budgets/{uuid}/status', \Budgetcontrol\Budget\Controller\BudgetController::class . ':status');
