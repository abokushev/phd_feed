<?php
/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $name;
?>

<div class="error-page py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-icon mb-4">
                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 4rem;"></i>
            </div>
            <h1 class="h2 text-danger mb-3"><?= Html::encode($name) ?></h1>
            <p class="lead text-muted mb-4"><?= nl2br(Html::encode($message)) ?></p>
            <a href="<?= Url::to(['/announcement']) ?>" class="btn btn-primary">
                <i class="bi bi-house me-1"></i><?= Yii::t('app', 'Главная') ?>
            </a>
        </div>
    </div>
</div>
