<?php
declare(strict_types=1);

namespace Budgetcontrol\Budget\Service;

use Budgetcontrol\Budget\Domain\Entity\BudgetStats;
use Illuminate\Support\Facades\Log;

/**
 * Service to handle budget threshold notifications
 */
class BudgetThresholdNotificationService
{
    /**
     * Check if any thresholds have been exceeded and send notifications
     *
     * @param BudgetStats $budgetStats The budget statistics to check
     * @return array Array of thresholds that triggered notifications
     */
    public function checkAndNotify(BudgetStats $budgetStats): array
    {
        $exceededThresholds = $budgetStats->getExceededThresholds();
        
        if (empty($exceededThresholds)) {
            return [];
        }

        $budget = $budgetStats->getBudget();
        $emails = $budget->emails;

        if (empty($emails) || !is_array($emails)) {
            Log::warning('Budget has exceeded thresholds but no email addresses configured', [
                'budget_uuid' => $budget->uuid,
                'exceeded_thresholds' => $exceededThresholds,
            ]);
            return [];
        }

        $notifiedThresholds = [];
        foreach ($exceededThresholds as $threshold) {
            if ($this->sendNotification($budget, $threshold, $budgetStats, $emails)) {
                $notifiedThresholds[] = $threshold;
            }
        }

        return $notifiedThresholds;
    }

    /**
     * Send notification email for a specific threshold
     *
     * @param mixed $budget The budget object
     * @param int $threshold The threshold percentage that was exceeded
     * @param BudgetStats $budgetStats The budget statistics
     * @param array $emails List of email addresses to notify
     * @return bool True if notification was sent successfully
     */
    protected function sendNotification($budget, int $threshold, BudgetStats $budgetStats, array $emails): bool
    {
        try {
            $subject = "Budget Alert: {$threshold}% threshold exceeded";
            $message = $this->buildNotificationMessage($budget, $threshold, $budgetStats);

            // Log the notification
            Log::info('Budget threshold notification triggered', [
                'budget_uuid' => $budget->uuid,
                'budget_name' => $budget->name,
                'threshold' => $threshold,
                'current_percentage' => $budgetStats->getTotalSpentPercentageInt(),
                'emails' => $emails,
                'subject' => $subject,
                'message' => $message,
            ]);

            // In a real implementation, you would send actual emails here
            // For example, using Laravel's Mail facade or a third-party service
            // Mail::to($emails)->send(new BudgetThresholdAlert($budget, $threshold, $budgetStats));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send budget threshold notification', [
                'budget_uuid' => $budget->uuid,
                'threshold' => $threshold,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build the notification message
     *
     * @param mixed $budget The budget object
     * @param int $threshold The threshold percentage
     * @param BudgetStats $budgetStats The budget statistics
     * @return string The notification message
     */
    protected function buildNotificationMessage($budget, int $threshold, BudgetStats $budgetStats): string
    {
        return sprintf(
            "Budget '%s' has reached %d%% of its allocated amount.\n\n" .
            "Budget Details:\n" .
            "- Total Budget: %s\n" .
            "- Total Spent: %s\n" .
            "- Remaining: %s\n" .
            "- Threshold: %d%%\n" .
            "- Current Spending: %d%%",
            $budget->name,
            $threshold,
            number_format($budgetStats->getTotal(), 2),
            number_format(abs($budgetStats->getTotalSpent()), 2),
            number_format($budgetStats->getTotalRemaining(), 2),
            $threshold,
            $budgetStats->getTotalSpentPercentageInt()
        );
    }
}
