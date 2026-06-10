<?php
/** @var yii\web\View $this */
/** @var app\models\AnnouncementSearch $searchModel */
/** @var app\models\DissertationAnnouncement[] $announcements */
/** @var yii\data\Pagination $pagination */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = Yii::t('app', 'Объявления о защите докторской диссертации');
$this->params['breadcrumbs'] = [
    Yii::t('app', 'Объявления о защите докторской диссертации'),
];
?>

<div class="announcement-list-page py-4">

    <!-- Page heading -->
    <div class="page-heading mb-4">
        <h1 class="page-title"><?= Html::encode($this->title) ?></h1>
        <div class="heading-divider"></div>
    </div>

    <?php $searchForm = ActiveForm::begin([
        'method' => 'get',
        'action' => Url::to(['announcement/index', 'lang' => Yii::$app->language]),
        'options' => ['class' => 'mb-4'],
    ]); ?>
    <?= Html::hiddenInput('AnnouncementSearch[language]', Yii::$app->language) ?>
    <div class="row g-3 align-items-end mb-4">
        <div class="col-md-5">
            <?= $searchForm->field($searchModel, 'title', ['options' => ['class' => 'mb-0']])
                ->textInput(['placeholder' => Yii::t('app', 'Поиск в объявлениях...')])
                ->label(false) ?>
        </div>
        <div class="col-md-4">
            <?= $searchForm->field($searchModel, 'sort', ['options' => ['class' => 'mb-0']])
                ->dropDownList([
                    'created_at_desc' => Yii::t('app', 'Сначала новые'),
                    'created_at_asc' => Yii::t('app', 'Сначала старые'),
                    'defense_date_asc' => Yii::t('app', 'Дата защиты: ближайшие'),
                    'defense_date_desc' => Yii::t('app', 'Дата защиты: поздние'),
                ])
                ->label(false) ?>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <?= Html::submitButton('<i class="bi bi-search me-1"></i>' . Yii::t('app', 'Фильтровать'), [
                'class' => 'btn btn-secondary w-100',
            ]) ?>
            <a href="<?= Url::to(['/announcement', 'lang' => Yii::$app->language]) ?>" class="btn btn-outline-secondary w-100">
                <?= Yii::t('app', 'Сбросить') ?>
            </a>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <?php if (empty($announcements)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <?= Yii::t('app', 'Объявлений пока нет.') ?>
        </div>
    <?php else: ?>

        <?php foreach ($announcements as $item): ?>
        <article class="announcement-card mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="announcement-title h5 mb-2">
                        <a href="<?= Url::to(['/announcement/view', 'url' => $item->url]) ?>" class="announcement-link">
                            <?= Html::encode($item->title) ?>
                        </a>
                    </h2>

                    <div class="announcement-meta mb-3">
                        <?php if ($item->defense_date): ?>
                            <span class="meta-item">
                                <i class="bi bi-calendar-event me-1 text-primary"></i>
                                <strong><?= Yii::t('app', 'Дата защиты') ?>:</strong>
                                <?= Html::encode($item->getFormattedDefenseDate()) ?>
                            </span>
                            &nbsp;&nbsp;
                        <?php endif; ?>
                        <span class="meta-item">
                            <i class="bi bi-person me-1 text-secondary"></i>
                            <?= Html::encode($item->author ? $item->author->getShortName() : '') ?>
                        </span>
                        &nbsp;&nbsp;
                        <span class="meta-item text-muted small">
                            <i class="bi bi-clock me-1"></i>
                            <?= Html::encode($item->getFormattedCreatedAt()) ?>
                        </span>
                    </div>

                    <div class="announcement-excerpt text-muted mb-3">
                        <?= Html::encode($item->getExcerpt(300)) ?>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <a href="<?= Url::to(['/announcement/view', 'url' => $item->url]) ?>"
                           class="read-more-link">
                            <?= Yii::t('app', 'Читать далее...') ?>
                            <i class="bi bi-arrow-right-short"></i>
                        </a>

                        <?php if ($item->zoom_link): ?>
                            <a href="<?= Html::encode($item->zoom_link) ?>"
                               target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-camera-video me-1"></i>Zoom
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>

        <!-- Pagination -->
        <div class="pagination-wrapper d-flex justify-content-center mt-4">
            <?= LinkPager::widget([
                'pagination' => $pagination,
                'options'    => ['class' => 'pagination'],
                'linkOptions'=> ['class' => 'page-link'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'activePageCssClass'   => 'active',
                'disabledPageCssClass' => 'disabled',
                'prevPageLabel'        => '«',
                'nextPageLabel'        => '»',
                'firstPageLabel'       => null,
                'lastPageLabel'        => null,
                'maxButtonCount'       => 7,
            ]) ?>
        </div>

        <div class="text-center text-muted small mt-2">
            <?= Yii::t('app', 'Всего записей') ?>: <?= $pagination->totalCount ?>
        </div>

    <?php endif; ?>
</div>
