<?php

use yii\db\Migration;

class m240101_000002_create_dissertation_announcements_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%dissertation_announcements}}', [
            'id'                  => $this->bigPrimaryKey()->unsigned(),
            'title'               => $this->string(1000)->notNull(),
            'content'             => 'LONGTEXT NOT NULL',
            'zoom_link'           => $this->string(1000)->null(),
            'zoom_conference_id'  => $this->string(255)->null(),
            'zoom_access_code'    => $this->string(255)->null(),
            'contact_email'       => $this->string(255)->null(),
            'status'              => "ENUM('draft','published','archived') NOT NULL DEFAULT 'draft'",
            'url'                 => $this->string(500)->notNull()->unique(),
            'defense_date'        => $this->dateTime()->null(),
            'created_by'          => $this->bigInteger()->unsigned()->notNull(),
            'created_at'          => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'language'            => "ENUM('kz','ru','en') NOT NULL DEFAULT 'ru'",
        ]);

        $this->addForeignKey(
            'fk_announcement_created_by',
            '{{%dissertation_announcements}}',
            'created_by',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_announcement_created_by', '{{%dissertation_announcements}}');
        $this->dropTable('{{%dissertation_announcements}}');
    }
}
