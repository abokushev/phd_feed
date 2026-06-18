<?php

use yii\db\Migration;

class m260618_000001_create_document_display_names_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%document_display_names}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'created_by' => $this->bigInteger()->unsigned()->notNull(),
            'language' => $this->string(2)->notNull(),
            'name' => $this->string(500)->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk_document_display_name_user',
            '{{%document_display_names}}',
            'created_by',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createIndex(
            'idx_document_display_name_user_language',
            '{{%document_display_names}}',
            ['created_by', 'language']
        );

        $this->createIndex(
            'idx_document_display_name_unique',
            '{{%document_display_names}}',
            ['created_by', 'language', 'name'],
            true
        );

        $defaults = [
            'ru' => [
                'Аннотация',
                'Диссертационная работа',
                'Заключение этической комиссии',
                'Список научных трудов и публикаций',
            ],
            'kz' => [
                'Аннотация',
                'Диссертациялық жұмыс',
                'Этикалық комиссияның қорытындысы',
                'Ғылыми еңбектер мен жарияланымдар тізімі',
            ],
            'en' => [
                'Abstract',
                'Dissertation work',
                'Ethics committee conclusion',
                'List of scientific works and publications',
            ],
        ];

        $userIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%users}}')
            ->column();

        $rows = [];
        foreach ($userIds as $userId) {
            foreach ($defaults as $language => $names) {
                $sortOrder = 10;
                foreach ($names as $name) {
                    $rows[] = [(int) $userId, $language, $name, $sortOrder];
                    $sortOrder += 10;
                }
            }
        }

        if ($rows) {
            $this->batchInsert(
                '{{%document_display_names}}',
                ['created_by', 'language', 'name', 'sort_order'],
                $rows
            );
        }
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%document_display_names}}');
    }
}
