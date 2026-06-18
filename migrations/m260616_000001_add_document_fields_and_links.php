<?php

use yii\db\Migration;

class m260616_000001_add_document_fields_and_links extends Migration
{
    public function safeUp(): void
    {
        // Add display_name and is_global to announcement_documents
        $this->addColumn('{{%announcement_documents}}', 'display_name', $this->string(500)->after('document_name'));
        $this->addColumn('{{%announcement_documents}}', 'is_global', $this->boolean()->notNull()->defaultValue(true)->after('display_name'));

        // Create announcement_links junction table
        $this->createTable('{{%announcement_links}}', [
            'id'                     => $this->bigPrimaryKey()->unsigned(),
            'announcement_id'        => $this->bigInteger()->unsigned()->notNull(),
            'linked_announcement_id' => $this->bigInteger()->unsigned()->notNull(),
            'created_at'             => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk_link_announcement_id',
            '{{%announcement_links}}',
            'announcement_id',
            '{{%dissertation_announcements}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_link_linked_announcement_id',
            '{{%announcement_links}}',
            'linked_announcement_id',
            '{{%dissertation_announcements}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Add unique constraint to prevent duplicate links
        $this->createIndex(
            'idx_unique_announcement_link',
            '{{%announcement_links}}',
            ['announcement_id', 'linked_announcement_id'],
            true
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%announcement_links}}');
        $this->dropColumn('{{%announcement_documents}}', 'is_global');
        $this->dropColumn('{{%announcement_documents}}', 'display_name');
    }
}