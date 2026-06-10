<?php

use yii\db\Migration;

class m240101_000005_add_group_key_to_dissertation_announcements extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%dissertation_announcements}}', 'group_key', $this->string(255)->null()->after('url'));
        $this->createIndex('idx-dissertation_announcements-group_key', '{{%dissertation_announcements}}', 'group_key');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx-dissertation_announcements-group_key', '{{%dissertation_announcements}}');
        $this->dropColumn('{{%dissertation_announcements}}', 'group_key');
    }
}
