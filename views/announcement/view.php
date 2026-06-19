<?php
/** @var yii\web\View $this */
/** @var app\models\DissertationAnnouncement $announcement */

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\DissertationAnnouncement;

$this->title = $announcement->title;
$this->params['breadcrumbs'] = [
    Yii::t('app', 'Объявления о защите') => Url::to(['/announcement']),
    $announcement->title,
];

foreach ([DissertationAnnouncement::LANG_RU, DissertationAnnouncement::LANG_KZ, DissertationAnnouncement::LANG_EN] as $language) {
    $translation = $announcement->language === $language ? $announcement : $announcement->getTranslation($language);
    if ($translation) {
        $this->params['languageUrls'][$language] = Url::to([
            '/announcement/view',
            'url' => $translation->url,
            'lang' => $language,
        ]);
    }
}

if (!Yii::$app->user->isGuest && (int) $announcement->created_by === (int) Yii::$app->user->id) {
    $this->params['manageUrl'] = Url::to([
        '/manage/update',
        'id' => $announcement->id,
        'lang' => $announcement->language,
    ]);
}
?>

<div class="announcement-view-page py-4">

    <article class="announcement-detail">

        <!-- Title -->
        <h1 class="announcement-detail-title mb-3">
            <?= Html::encode($announcement->title) ?>
        </h1>

        <!-- Meta info row -->
        <div class="announcement-detail-meta mb-4 p-3 bg-light rounded">
            <div class="row g-2">
                <?php if ($announcement->defense_date): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="meta-box">
                        <i class="bi bi-calendar-check-fill text-primary me-2"></i>
                        <strong><?= Yii::t('app', 'Дата защиты') ?>:</strong><br>
                        <span><?= Html::encode($announcement->getFormattedDefenseDate('d.m.Y H:i')) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($announcement->author): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="meta-box">
                        <i class="bi bi-person-fill text-secondary me-2"></i>
                        <strong><?= Yii::t('app', 'Автор') ?>:</strong><br>
                        <span><?= Html::encode($announcement->author->getFullName()) ?></span>
                        <?php if ($announcement->author->faculty): ?>
                            <br><small class="text-muted"><?= Html::encode($announcement->author->faculty) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-md-4 col-sm-6">
                    <div class="meta-box">
                        <i class="bi bi-clock text-muted me-2"></i>
                        <strong><?= Yii::t('app', 'Дата публикации') ?>:</strong><br>
                        <span><?= Html::encode($announcement->getFormattedCreatedAt('d.m.Y')) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="announcement-content mb-4">
            <?= $announcement->content ?>
        </div>

        <!-- Zoom section -->
        <?php if ($announcement->zoom_link || $announcement->zoom_conference_id || $announcement->zoom_access_code): ?>
        <div class="zoom-info-section mb-4 p-4 border border-primary rounded">
            <h3 class="h5 text-primary mb-3">
                <i class="bi bi-camera-video-fill me-2"></i><?= Yii::t('app', 'Информация для подключения через Zoom') ?>
            </h3>
            <div class="row">
                <?php if ($announcement->zoom_link): ?>
                <div class="col-md-12 mb-2">
                    <strong><?= Yii::t('app', 'Ссылка для входа') ?>:</strong>
                    <a href="<?= Html::encode($announcement->zoom_link) ?>" target="_blank" rel="noopener" class="ms-2">
                        <?= Html::encode($announcement->zoom_link) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($announcement->zoom_conference_id): ?>
                <div class="col-md-6 mb-2">
                    <strong><?= Yii::t('app', 'ID конференции') ?>:</strong>
                    <code class="ms-2"><?= Html::encode($announcement->zoom_conference_id) ?></code>
                </div>
                <?php endif; ?>
                <?php if ($announcement->zoom_access_code): ?>
                <div class="col-md-6 mb-2">
                    <strong><?= Yii::t('app', 'Код доступа') ?>:</strong>
                    <code class="ms-2"><?= Html::encode($announcement->zoom_access_code) ?></code>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Documents section -->
        <?php if (!empty($announcement->documents)): ?>
        <div class="documents-section mb-4">
            <h3 class="h5 mb-3">
                <i class="bi bi-paperclip me-2"></i><?= Yii::t('app', 'Прикреплённые документы') ?>
            </h3>
            <ul class="list-group">
                <?php foreach ($announcement->documents as $doc): ?>
                <?php $documentLabel = $doc->display_name ?: $doc->document_name; ?>
                <li class="list-group-item d-flex align-items-center">
                    <i class="bi <?= $doc->getFileIcon() ?> me-3 fs-5"></i>
                    <a href="<?= Html::encode($doc->getDownloadUrl()) ?>"
                       target="_blank" rel="noopener"
                       class="text-decoration-none"
                       title="<?= Html::encode($doc->document_name) ?>">
                        <?= Html::encode($documentLabel) ?>
                    </a>
                    <small class="text-muted ms-auto">
                        <?= Html::encode((new DateTime($doc->uploaded_at))->format('d.m.Y')) ?>
                    </small>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Contact email -->
        <?php if ($announcement->contact_email): ?>
        <div class="contact-section mb-4">
            <strong><i class="bi bi-envelope-fill me-2 text-primary"></i><?= Yii::t('app', 'Контактный Email') ?>:</strong>
            <a href="mailto:<?= Html::encode($announcement->contact_email) ?>">
                <?= Html::encode($announcement->contact_email) ?>
            </a>
        </div>
        <?php endif; ?>

        <!-- Back link -->
        <div class="back-link mt-4 pt-3 border-top">
            <a href="<?= Url::to(['/announcement']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i><?= Yii::t('app', '← Назад к списку') ?>
            </a>
        </div>

    </article>
</div>
