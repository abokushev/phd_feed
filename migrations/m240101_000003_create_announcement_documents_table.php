<?php

use yii\db\Migration;

class m240101_000003_create_announcement_documents_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%announcement_documents}}', [
            'id'              => $this->bigPrimaryKey()->unsigned(),
            'announcement_id' => $this->bigInteger()->unsigned()->notNull(),
            'document_name'   => $this->string(500)->notNull(),
            'file_path'       => $this->string(2000)->notNull(),
            'uploaded_at'     => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk_document_announcement_id',
            '{{%announcement_documents}}',
            'announcement_id',
            '{{%dissertation_announcements}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_document_announcement_id', '{{%announcement_documents}}');
        $this->dropTable('{{%announcement_documents}}');
    }
}
