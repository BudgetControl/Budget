<?php

/**
 *  application apps
 */
$app->get('/monitor', \Budgetcontrol\Budget\Controller\Controller::class . ':monitor');

$app->get('/{wsid}', \Budgetcontrol\Budget\Controller\BudgetController::class . ':index');
$app->get('/{wsid}/{uuid}', \Budgetcontrol\Budget\Controller\BudgetController::class . ':show');
$app->post('/{wsid}/budget', \Budgetcontrol\Budget\Controller\BudgetController::class . ':create');
$app->put('/{wsid}/budget/{uuid}', \Budgetcontrol\Budget\Controller\BudgetController::class . ':update');
$app->delete('/{wsid}/budget/{uuid}', \Budgetcontrol\Budget\Controller\BudgetController::class . ':delete');
$app->get('/{wsid}/budget/{uuid}/expired', \Budgetcontrol\Budget\Controller\BudgetController::class . ':expired');
$app->get('/{wsid}/budget/{uuid}/exceeded', \Budgetcontrol\Budget\Controller\BudgetController::class . ':exceeded');
$app->get('/{wsid}/budget/{uuid}/status', \Budgetcontrol\Budget\Controller\BudgetController::class . ':status');
$app->get('/{wsid}/budget/{uuid}/stats', \Budgetcontrol\Budget\Controller\BudgetStatsController::class . ':getStats');
$app->get('/{wsid}/budgets/stats', \Budgetcontrol\Budget\Controller\BudgetStatsController::class . ':getAllStats');

