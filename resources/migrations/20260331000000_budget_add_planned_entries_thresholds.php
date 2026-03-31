<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BudgetAddPlannedEntriesThresholds extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('budgets');
        $table->addColumn('planned_entries', 'json', ['null' => true])
              ->addColumn('thresholds', 'json', ['null' => true])
              ->update();
    }
}
