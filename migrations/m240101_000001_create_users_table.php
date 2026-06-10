<?php

use yii\db\Migration;

class m240101_000001_create_users_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%users}}', [
            'id'            => $this->bigPrimaryKey()->unsigned(),
            'login'         => $this->string(100)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'last_name'     => $this->string(100)->notNull(),
            'first_name'    => $this->string(100)->notNull(),
            'middle_name'   => $this->string(100)->null(),
            'department'    => $this->string(255)->null(),
            'faculty'       => $this->string(255)->null(),
            'auth_key'      => $this->string(32)->notNull()->defaultValue(''),
            'created_at'    => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Insert a default admin user (password: admin123)
        $this->insert('{{%users}}', [
            'login'         => 'admin',
            'password_hash' => Yii::$app->security->generatePasswordHash('admin123'),
            'last_name'     => 'Администратор',
            'first_name'    => 'Системный',
            'auth_key'      => Yii::$app->security->generateRandomString(),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%users}}');
    }
}
