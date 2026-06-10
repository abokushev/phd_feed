<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\ErrorAction;

class SiteController extends Controller
{
    public $layout = 'main';

    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }

    public function actionIndex(): \yii\web\Response
    {
        return $this->redirect(['/announcement']);
    }
}
