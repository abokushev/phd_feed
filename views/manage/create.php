<?php
/** @var yii\web\View $this */
/** @var app\models\DissertationAnnouncement $model */

use yii\helpers\Url;

$this->title = Yii::t('app', 'Создать объявление');
$this->params['breadcrumbs'] = [
    Yii::t('app', 'Управление') => Url::to(['/manage']),
    Yii::t('app', 'Создать объявление'),
];
?>

<div class="manage-create-page py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= Url::to(['/manage']) ?>" class="btn btn-sm btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="page-title h3 mb-0"><?= Yii::t('app', 'Создать объявление') ?></h1>
    </div>

    <?= $this->render('_form', ['model' => $model]) ?>
</div>
