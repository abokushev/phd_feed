<?php
/** @var yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;
use yii\helpers\Url;

$lang    = Yii::$app->language;
$baseUrl = Yii::$app->request->baseUrl;

$langUrl = static function (string $l): string {
    if (!empty(Yii::$app->view->params['languageUrls'][$l])) {
        return Yii::$app->view->params['languageUrls'][$l];
    }

    $params       = Yii::$app->request->queryParams;
    $params['lang'] = $l;
    $params[0]    = '/' . (Yii::$app->controller->getRoute() ?: 'announcement/index');
    return Url::to($params);
};
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Html::encode($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Html::encode($this->title ?? Yii::$app->params['siteTitle']) ?></title>
    <?php $this->head() ?>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom KarTU CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/kstu.css">
</head>
<body>
<?php $this->beginBody() ?>

<!-- ============================================================
     HEADER
============================================================ -->
<header class="site-header">
    <div class="container-fluid px-4">
        <div class="row align-items-center py-2">
            <!-- Logo + Title -->
            <div class="col-md-8 col-sm-12 d-flex align-items-center">
                <a href="<?= Url::to(['/announcement']) ?>" class="header-logo-link d-flex align-items-center text-white text-decoration-none">
                    <div class="header-logo me-3">
                        <div class="logo-circle">КарТУ</div>
                    </div>
                    <div class="header-title">
                        <div class="site-name-main">Карагандинский технический университет</div>
                        <div class="site-name-sub">имени Абылкаса Сагинова</div>
                    </div>
                </a>
            </div>
            <!-- Right: contacts + language switcher -->
            <div class="col-md-4 col-sm-12 text-md-end mt-2 mt-md-0">
                <div class="header-contacts text-white small mb-1">
                    <i class="bi bi-telephone-fill me-1"></i>+7 (7212) 56-59-32
                    &nbsp;|&nbsp;
                    <i class="bi bi-envelope-fill me-1"></i>info@kartu.kz
                </div>
                <div class="lang-switcher">
                    <a href="<?= $langUrl('ru') ?>" class="lang-link <?= $lang === 'ru' ? 'active' : '' ?>">RU</a>
                    <span class="lang-sep">|</span>
                    <a href="<?= $langUrl('kz') ?>" class="lang-link <?= $lang === 'kz' ? 'active' : '' ?>">ҚАЗ</a>
                    <span class="lang-sep">|</span>
                    <a href="<?= $langUrl('en') ?>" class="lang-link <?= $lang === 'en' ? 'active' : '' ?>">EN</a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ============================================================
     NAVIGATION BAR
============================================================ -->
<nav class="site-nav navbar navbar-expand-lg">
    <div class="container-fluid px-4">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-0">
                <li class="nav-item">
                    <a class="nav-link text-white" href="<?= Url::to(['/announcement']) ?>">
                        <?= Yii::t('app', 'Главная') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="<?= Url::to(['/announcement']) ?>">
                        <?= Yii::t('app', 'Объявления о защите') ?>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto mb-0">
                <?php if (Yii::$app->user->isGuest): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?= Url::to(['/auth/login']) ?>">
                            <i class="bi bi-person-fill me-1"></i><?= Yii::t('app', 'Войти') ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?= Url::to(['/manage']) ?>">
                            <i class="bi bi-pencil-square me-1"></i><?= Yii::t('app', 'Управление') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?= Html::beginForm(['/auth/logout'], 'post', ['class' => 'd-inline']) ?>
                        <button type="submit" class="nav-link btn btn-link text-white border-0 p-0 ps-3">
                            <i class="bi bi-box-arrow-right me-1"></i><?= Yii::t('app', 'Выйти') ?>
                        </button>
                        <?= Html::endForm() ?>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ============================================================
     BREADCRUMBS
============================================================ -->
<?php if (!empty($this->params['breadcrumbs'])): ?>
<div class="breadcrumbs-bar">
    <div class="container-fluid px-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 py-2">
                <li class="breadcrumb-item">
                    <a href="<?= Url::to(['/announcement']) ?>"><?= Yii::t('app', 'Главная') ?></a>
                </li>
                <?php foreach ($this->params['breadcrumbs'] as $label => $url): ?>
                    <?php if (is_string($label) && $url): ?>
                        <li class="breadcrumb-item"><a href="<?= Html::encode($url) ?>"><?= Html::encode($label) ?></a></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active"><?= Html::encode(is_string($label) ? $label : $url) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     MAIN CONTENT
============================================================ -->
<main class="site-main">
    <div class="container-fluid px-4">
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= Html::encode(Yii::$app->session->getFlash('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= Html::encode(Yii::$app->session->getFlash('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </div>
</main>

<!-- ============================================================
     FOOTER
============================================================ -->
<footer class="site-footer">
    <div class="container-fluid px-4">
        <div class="row py-4">
            <div class="col-md-4 mb-3">
                <h6 class="footer-heading">Карагандинский технический университет</h6>
                <p class="footer-text small">
                    имени Абылкаса Сагинова<br>
                    100027, Республика Казахстан,<br>
                    г. Караганда, Бульвар Мира, 56
                </p>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="footer-heading"><?= Yii::t('app', 'Контакты') ?></h6>
                <p class="footer-text small">
                    <i class="bi bi-telephone-fill me-1"></i>+7 (7212) 56-59-32<br>
                    <i class="bi bi-envelope-fill me-1"></i>
                    <a href="mailto:info@kartu.kz" class="footer-link">info@kartu.kz</a>
                </p>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="footer-heading"><?= Yii::t('app', 'Объявления о защите') ?></h6>
                <p class="footer-text small">
                    <a href="<?= Url::to(['/announcement']) ?>" class="footer-link">
                        <?= Yii::t('app', 'Список объявлений') ?>
                    </a>
                </p>
                <?php if (Yii::$app->user->isGuest): ?>
                    <a href="<?= Url::to(['/auth/login']) ?>" class="footer-link small">
                        <?= Yii::t('app', 'Войти') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <hr class="footer-hr">
        <div class="row pb-3">
            <div class="col-12 text-center">
                <small class="footer-copyright">© КарТУ <?= date('Y') ?></small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
