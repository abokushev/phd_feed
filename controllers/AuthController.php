<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\LoginForm;

class AuthController extends Controller
{
    public $layout = 'main';

    public function actionLogin(): string|Response
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/manage']);
        }

        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['/manage']);
        }

        $model->password = '';

        return $this->render('login', ['model' => $model]);
    }

    public function actionLogout(): Response
    {
        Yii::$app->user->logout();
        return $this->redirect(['/announcement']);
    }
}
