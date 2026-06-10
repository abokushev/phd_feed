<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property int    $id
 * @property string $login
 * @property string $password_hash
 * @property string $last_name
 * @property string $first_name
 * @property string|null $middle_name
 * @property string|null $department
 * @property string|null $faculty
 * @property string $auth_key
 * @property string $created_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return '{{%users}}';
    }

    public function rules(): array
    {
        return [
            [['login', 'password_hash', 'last_name', 'first_name'], 'required'],
            [['login'], 'string', 'max' => 100],
            [['login'], 'unique'],
            [['password_hash', 'auth_key'], 'string', 'max' => 255],
            [['last_name', 'first_name', 'middle_name'], 'string', 'max' => 100],
            [['department', 'faculty'], 'string', 'max' => 255],
            [['middle_name', 'department', 'faculty'], 'default', 'value' => null],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => Yii::t('app', 'ID'),
            'login'       => Yii::t('app', 'Логин'),
            'last_name'   => Yii::t('app', 'Фамилия'),
            'first_name'  => Yii::t('app', 'Имя'),
            'middle_name' => Yii::t('app', 'Отчество'),
            'department'  => Yii::t('app', 'Кафедра'),
            'faculty'     => Yii::t('app', 'Факультет'),
            'created_at'  => Yii::t('app', 'Дата регистрации'),
        ];
    }

    // ---- IdentityInterface ----

    public static function findIdentity($id): ?static
    {
        return static::findOne(['id' => $id]);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?static
    {
        return null; // not used
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getAuthKey(): string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }

    // ---- Custom methods ----

    public static function findByLogin(string $login): ?static
    {
        return static::findOne(['login' => $login]);
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function generatePasswordHash(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function getFullName(): string
    {
        $parts = array_filter([$this->last_name, $this->first_name, $this->middle_name]);
        return implode(' ', $parts);
    }

    public function getShortName(): string
    {
        $parts = [$this->last_name];
        if ($this->first_name) {
            $parts[] = mb_substr($this->first_name, 0, 1) . '.';
        }
        if ($this->middle_name) {
            $parts[] = mb_substr($this->middle_name, 0, 1) . '.';
        }
        return implode(' ', $parts);
    }

    // ---- Relations ----

    public function getAnnouncements(): \yii\db\ActiveQuery
    {
        return $this->hasMany(DissertationAnnouncement::class, ['created_by' => 'id']);
    }
}
