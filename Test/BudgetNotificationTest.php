<?php
use PHPUnit\Framework\TestCase;
use Budgetcontrol\Budget\Domain\Model\Budget;

class BudgetNotificationTest extends TestCase {
    public function testThresholdsValidation() {
        $budget = new Budget([
            'thresholds' => [10, 50, 100, 0, 99],
            'emails' => ['test@email.com', 'invalid-email']
        ]);
        $controller = new \Budgetcontrol\Budget\Controller\BudgetController();
        $data = [
            'thresholds' => [10, 50, 100, 0, 99],
            'emails' => ['test@email.com', 'invalid-email']
        ];
        $errors = (new \ReflectionClass($controller))
            ->getMethod('validateBudget')
            ->invoke($controller, $data);
        $this->assertContains('Le soglie devono essere numeri tra 1 e 99', $errors);
        $this->assertContains('Email non valida: invalid-email', $errors);
    }

    public function testNotificationLogic() {
        $budget = new Budget([
            'thresholds' => [50, 75],
            'emails' => ['test@email.com']
        ]);
        $controller = $this->getMockBuilder('Budgetcontrol\Budget\Controller\BudgetController')
            ->onlyMethods(['checkAndNotify'])
            ->getMock();
        $controller->expects($this->once())
            ->method('checkAndNotify')
            ->with($budget, 80);
        $controller->checkAndNotify($budget, 80);
    }
}
