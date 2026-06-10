<?php
/** @var yii\web\View $this */
/** @var app\models\DissertationAnnouncement $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Редактировать') . ': ' . mb_strimwidth($model->title, 0, 60, '...', 'UTF-8');
$this->params['breadcrumbs'] = [
    Yii::t('app', 'Управление') => Url::to(['/manage']),
    Yii::t('app', 'Редактировать'),
];
?>

<div class="manage-update-page py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <a href="<?= Url::to(['/manage']) ?>" class="btn btn-sm btn-outline-secondary me-3">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title h3 mb-0"><?= Yii::t('app', 'Редактировать объявление') ?></h1>
                <small class="text-muted">ID: <?= $model->id ?></small>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="<?= $model->getStatusBadgeClass() ?> fs-6">
                <?= Html::encode($model->getStatusLabel()) ?>
            </span>
            <?php if ($model->status === \app\models\DissertationAnnouncement::STATUS_PUBLISHED): ?>
                <a href="<?= Url::to(['/announcement/view', 'url' => $model->url]) ?>"
                   target="_blank" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-eye me-1"></i><?= Yii::t('app', 'Просмотр') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?= $this->render('_form', ['model' => $model]) ?>
</div>
