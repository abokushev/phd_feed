<?php

use yii\db\Migration;

class m240101_000004_add_updated_at_to_announcements extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%dissertation_announcements}}',
            'updated_at',
            'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at'
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%dissertation_announcements}}', 'updated_at');
    }
}
