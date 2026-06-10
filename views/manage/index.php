<?php
/** @var yii\web\View $this */
/** @var app\models\AnnouncementSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\models\DissertationAnnouncement;

$this->title = Yii::t('app', 'Управление объявлениями');
$this->params['breadcrumbs'] = [
    Yii::t('app', 'Управление объявлениями'),
];

/** @var app\models\DissertationAnnouncement[] $items */
$items = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
?>

<div class="manage-index-page py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title h3 mb-0"><?= Html::encode($this->title) ?></h1>
        <a href="<?= Url::to(['/manage/create']) ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i><?= Yii::t('app', 'Создать объявление') ?>
        </a>
    </div>

    <!-- Search / filter -->
    <div class="card mb-3 border-0 bg-light">
        <div class="card-body py-2 px-3">
            <?php $searchForm = \yii\bootstrap5\ActiveForm::begin([
                'method' => 'get',
                'action' => Url::to(['/manage']),
                'options' => ['class' => 'row g-2 align-items-end'],
            ]); ?>
            <div class="col-md-5">
                <?= $searchForm->field($searchModel, 'title', ['options' => ['class' => 'mb-0']])
                    ->textInput(['placeholder' => Yii::t('app', 'Поиск по заголовку...')])
                    ->label(false) ?>
            </div>
            <div class="col-md-3">
                <?= $searchForm->field($searchModel, 'status', ['options' => ['class' => 'mb-0']])
                    ->dropDownList(
                        ['' => Yii::t('app', 'Все статусы')] + DissertationAnnouncement::getStatusList()
                    )
                    ->label(false) ?>
            </div>
            <div class="col-md-2">
                <?= Html::submitButton('<i class="bi bi-search me-1"></i>' . Yii::t('app', 'Найти'), ['class' => 'btn btn-secondary btn-sm w-100']) ?>
            </div>
            <div class="col-md-2">
                <a href="<?= Url::to(['/manage']) ?>" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="bi bi-x-circle me-1"></i><?= Yii::t('app', 'Сбросить') ?>
                </a>
            </div>
            <?php \yii\bootstrap5\ActiveForm::end(); // $searchForm ?>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <?= Yii::t('app', 'У вас пока нет объявлений.') ?>
            <a href="<?= Url::to(['/manage/create']) ?>" class="alert-link">
                <?= Yii::t('app', 'Создайте первое') ?>.
            </a>
        </div>
    <?php else: ?>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width:40px">#</th>
                        <th><?= Yii::t('app', 'Заголовок') ?></th>
                        <th><?= Yii::t('app', 'Статус') ?></th>
                        <th><?= Yii::t('app', 'Язык') ?></th>
                        <th><?= Yii::t('app', 'Дата защиты') ?></th>
                        <th><?= Yii::t('app', 'Создано') ?></th>
                        <th style="width:150px"><?= Yii::t('app', 'Действия') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="text-muted small"><?= $item->id ?></td>
                        <td>
                            <a href="<?= Url::to(['/manage/update', 'id' => $item->id]) ?>"
                               class="text-decoration-none fw-semibold">
                                <?= Html::encode(mb_strimwidth($item->title, 0, 80, '...', 'UTF-8')) ?>
                            </a>
                            <?php if ($item->status === DissertationAnnouncement::STATUS_PUBLISHED): ?>
                                <br><a href="<?= Url::to(['/announcement/view', 'url' => $item->url]) ?>"
                                       class="text-muted small" target="_blank">
                                    <i class="bi bi-box-arrow-up-right me-1"></i><?= Html::encode($item->url) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= $item->getStatusBadgeClass() ?>">
                                <?= Html::encode($item->getStatusLabel()) ?>
                            </span>
                        </td>
                        <td>
                            <?php $langMap = ['ru' => 'RU', 'kz' => 'ҚАЗ', 'en' => 'EN']; ?>
                            <span class="badge bg-light text-dark border">
                                <?= $langMap[$item->language] ?? $item->language ?>
                            </span>
                        </td>
                        <td class="small">
                            <?= $item->defense_date ? Html::encode($item->getFormattedDefenseDate('d.m.Y H:i')) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="small text-muted">
                            <?= Html::encode($item->getFormattedCreatedAt('d.m.Y')) ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= Url::to(['/manage/update', 'id' => $item->id]) ?>"
                                   class="btn btn-outline-primary" title="<?= Yii::t('app', 'Редактировать') ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?= Html::beginForm(['/manage/delete', 'id' => $item->id], 'post', [
                                    'onsubmit' => 'return confirm("' . Yii::t('app', 'Удалить объявление?') . '")',
                                    'style' => 'display:inline',
                                ]) ?>
                                <button type="submit" class="btn btn-outline-danger" title="<?= Yii::t('app', 'Удалить') ?>">
                                    <i class="bi bi-trash3"></i>
                                </button>
                                <?= Html::endForm() ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination->pageCount > 1): ?>
        <div class="d-flex justify-content-center mt-3">
            <?= LinkPager::widget([
                'pagination' => $pagination,
                'options'    => ['class' => 'pagination'],
                'linkOptions'=> ['class' => 'page-link'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'prevPageLabel' => '«',
                'nextPageLabel' => '»',
            ]) ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
