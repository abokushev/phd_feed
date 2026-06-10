<?php

namespace app\models;

use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    public string $login = '';
    public string $password = '';
    public bool $rememberMe = true;

    private ?User $_user = null;

    public function rules(): array
    {
        return [
            [['login', 'password'], 'required'],
            [['login'], 'string', 'max' => 100],
            [['password'], 'string', 'max' => 255],
            [['rememberMe'], 'boolean'],
            [['password'], 'validatePassword'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'login'      => Yii::t('app', 'Логин'),
            'password'   => Yii::t('app', 'Пароль'),
            'rememberMe' => Yii::t('app', 'Запомнить меня'),
        ];
    }

    public function validatePassword(string $attribute): void
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, Yii::t('app', 'Неверный логин или пароль.'));
            }
        }
    }

    public function login(): bool
    {
        if ($this->validate()) {
            return Yii::$app->user->login(
                $this->getUser(),
                $this->rememberMe ? 3600 * 24 * 30 : 0
            );
        }
        return false;
    }

    protected function getUser(): ?User
    {
        if ($this->_user === null) {
            $this->_user = User::findByLogin($this->login);
        }
        return $this->_user;
    }
}
