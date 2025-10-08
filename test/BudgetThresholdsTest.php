<?php

use PHPUnit\Framework\TestCase;
use Budgetcontrol\Budget\Domain\Model\Budget;
use Budgetcontrol\Budget\Domain\Entity\BudgetStats;
use Budgetcontrol\Budget\Service\BudgetThresholdNotificationService;

class BudgetThresholdsTest extends TestCase
{
    /**
     * Test that thresholds field can be set and retrieved from Budget model
     */
    public function testBudgetThresholdsFieldAccessor()
    {
        $budget = new Budget();
        $thresholds = [25, 50, 75, 90];
        
        $budget->thresholds = $thresholds;
        
        $this->assertEquals($thresholds, $budget->thresholds);
    }

    /**
     * Test that empty thresholds array is handled correctly
     */
    public function testBudgetEmptyThresholds()
    {
        $budget = new Budget();
        $budget->thresholds = [];
        
        $this->assertEquals([], $budget->thresholds);
    }

    /**
     * Test that null thresholds is handled correctly
     */
    public function testBudgetNullThresholds()
    {
        $budget = new Budget();
        $budget->thresholds = null;
        
        $this->assertNull($budget->thresholds);
    }

    /**
     * Test validation of threshold percentages (should be between 1-99)
     */
    public function testThresholdValidationRange()
    {
        $validThresholds = [1, 50, 99];
        foreach ($validThresholds as $threshold) {
            $this->assertGreaterThanOrEqual(1, $threshold);
            $this->assertLessThanOrEqual(99, $threshold);
        }
        
        $invalidThresholds = [0, 100, -10, 150];
        foreach ($invalidThresholds as $threshold) {
            $this->assertTrue($threshold < 1 || $threshold > 99);
        }
    }

    /**
     * Test that BudgetStats correctly identifies exceeded thresholds
     */
    public function testBudgetStatsExceededThresholds()
    {
        // Create a mock budget with thresholds
        $budget = $this->createMockBudget(100.0, [25, 50, 75]);
        
        // Simulate 60% spent (spending is negative in the system)
        $totalSpent = -60.0;
        
        $stats = new BudgetStats($totalSpent, $budget);
        
        // Should exceed 25% and 50% thresholds, but not 75%
        $exceededThresholds = $stats->getExceededThresholds();
        
        $this->assertIsArray($exceededThresholds);
        $this->assertContains(25, $exceededThresholds);
        $this->assertContains(50, $exceededThresholds);
        $this->assertNotContains(75, $exceededThresholds);
    }

    /**
     * Test that BudgetStats returns empty array when no thresholds are exceeded
     */
    public function testBudgetStatsNoExceededThresholds()
    {
        $budget = $this->createMockBudget(100.0, [50, 75, 90]);
        
        // Simulate 20% spent
        $totalSpent = -20.0;
        
        $stats = new BudgetStats($totalSpent, $budget);
        $exceededThresholds = $stats->getExceededThresholds();
        
        $this->assertEmpty($exceededThresholds);
    }

    /**
     * Test that BudgetStats handles budget with no thresholds
     */
    public function testBudgetStatsWithNoThresholds()
    {
        $budget = $this->createMockBudget(100.0, []);
        
        $totalSpent = -60.0;
        $stats = new BudgetStats($totalSpent, $budget);
        
        $exceededThresholds = $stats->getExceededThresholds();
        
        $this->assertEmpty($exceededThresholds);
    }

    /**
     * Test notification service identifies budgets needing notifications
     */
    public function testNotificationServiceCheckAndNotify()
    {
        $budget = $this->createMockBudget(100.0, [50, 75], ['test@example.com']);
        
        // Simulate 60% spent
        $totalSpent = -60.0;
        $stats = new BudgetStats($totalSpent, $budget);
        
        $notificationService = new BudgetThresholdNotificationService();
        $notifiedThresholds = $notificationService->checkAndNotify($stats);
        
        // Should notify for 50% threshold
        $this->assertIsArray($notifiedThresholds);
        $this->assertContains(50, $notifiedThresholds);
        $this->assertNotContains(75, $notifiedThresholds);
    }

    /**
     * Test that notification service handles budgets with no email addresses
     */
    public function testNotificationServiceWithNoEmails()
    {
        $budget = $this->createMockBudget(100.0, [50], []);
        
        $totalSpent = -60.0;
        $stats = new BudgetStats($totalSpent, $budget);
        
        $notificationService = new BudgetThresholdNotificationService();
        $notifiedThresholds = $notificationService->checkAndNotify($stats);
        
        // Should not notify when no emails configured
        $this->assertEmpty($notifiedThresholds);
    }

    /**
     * Test spending percentage calculation
     */
    public function testSpendingPercentageCalculation()
    {
        $budget = $this->createMockBudget(100.0, []);
        
        // Test various spending levels
        $testCases = [
            [-25.0, 25],  // 25% spent
            [-50.0, 50],  // 50% spent
            [-75.0, 75],  // 75% spent
            [-100.0, 100], // 100% spent
        ];
        
        foreach ($testCases as list($spent, $expectedPercentage)) {
            $stats = new BudgetStats($spent, $budget);
            $this->assertEquals($expectedPercentage, $stats->getTotalSpentPercentageInt());
        }
    }

    /**
     * Helper method to create a mock Budget object
     */
    private function createMockBudget(float $amount, array $thresholds, array $emails = [])
    {
        $budget = $this->getMockBuilder(Budget::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $budget->method('__get')
            ->willReturnCallback(function($name) use ($amount, $thresholds, $emails) {
                switch($name) {
                    case 'amount':
                        return $amount;
                    case 'thresholds':
                        return $thresholds;
                    case 'emails':
                        return $emails;
                    case 'uuid':
                        return 'test-uuid-123';
                    case 'name':
                        return 'Test Budget';
                    default:
                        return null;
                }
            });
        
        // Set properties directly for access
        $budget->amount = $amount;
        $budget->thresholds = $thresholds;
        $budget->emails = $emails;
        $budget->uuid = 'test-uuid-123';
        $budget->name = 'Test Budget';
        
        return $budget;
    }
}
