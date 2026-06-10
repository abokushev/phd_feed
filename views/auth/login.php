<?php
/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Вход в систему');
$this->params['breadcrumbs'] = [
    Yii::t('app', 'Вход в систему'),
];
?>

<div class="login-page py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">

            <div class="card shadow-sm">
                <div class="card-header text-center py-3" style="background:#1c3f73; color:#fff;">
                    <i class="bi bi-shield-lock fs-2 mb-2 d-block"></i>
                    <h4 class="mb-0"><?= Html::encode($this->title) ?></h4>
                    <small class="opacity-75"><?= Yii::t('app', 'Система управления объявлениями') ?></small>
                </div>
                <div class="card-body p-4">

                    <?php $form = ActiveForm::begin([
                        'id'     => 'login-form',
                        'action' => Url::to(['/auth/login']),
                        'method' => 'post',
                    ]); ?>

                    <?= $form->field($model, 'login')->textInput([
                        'autofocus' => true,
                        'class' => 'form-control',
                        'placeholder' => Yii::t('app', 'Введите логин'),
                    ])->label(Yii::t('app', 'Логин')) ?>

                    <?= $form->field($model, 'password')->passwordInput([
                        'class' => 'form-control',
                        'placeholder' => Yii::t('app', 'Введите пароль'),
                    ])->label(Yii::t('app', 'Пароль')) ?>

                    <?= $form->field($model, 'rememberMe')->checkbox([
                        'class' => 'form-check-input',
                    ])->label(Yii::t('app', 'Запомнить меня')) ?>

                    <div class="d-grid mt-3">
                        <?= Html::submitButton(
                            '<i class="bi bi-box-arrow-in-right me-2"></i>' . Yii::t('app', 'Войти'),
                            [
                                'class' => 'btn btn-primary',
                                'name' => 'login-button',
                            ]
                        ) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                </div>
                <div class="card-footer text-center py-3">
                    <a href="<?= Url::to(['/announcement']) ?>" class="text-muted small">
                        <i class="bi bi-arrow-left me-1"></i><?= Yii::t('app', '← На главную') ?>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
