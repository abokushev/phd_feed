<?php
/** @var yii\web\View $this */
/** @var app\models\DissertationAnnouncement $model */
/** @var yii\bootstrap5\ActiveForm $form */

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\DissertationAnnouncement;
use app\models\AnnouncementDocument;

$isNew = $model->isNewRecord;
?>

<?php $form = \yii\bootstrap5\ActiveForm::begin([
    'id' => 'announcement-form',
    'options' => [
        'enctype' => 'multipart/form-data',
        'class' => 'announcement-form',
    ],
]); ?>

<!-- ================================================================
     TABS
================================================================ -->
<ul class="nav nav-tabs mb-4" id="formTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab"
                data-bs-target="#tab-basic" type="button" role="tab">
            <i class="bi bi-info-circle me-1"></i><?= Yii::t('app', 'Основная информация') ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="content-tab" data-bs-toggle="tab"
                data-bs-target="#tab-content" type="button" role="tab">
            <i class="bi bi-file-text me-1"></i><?= Yii::t('app', 'Содержание') ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="zoom-tab" data-bs-toggle="tab"
                data-bs-target="#tab-zoom" type="button" role="tab">
            <i class="bi bi-camera-video me-1"></i><?= Yii::t('app', 'Zoom') ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="docs-tab" data-bs-toggle="tab"
                data-bs-target="#tab-docs" type="button" role="tab">
            <i class="bi bi-paperclip me-1"></i><?= Yii::t('app', 'Документы') ?>
            <?php if (!$isNew && count($model->documents)): ?>
                <span class="badge bg-secondary ms-1"><?= count($model->documents) ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content" id="formTabsContent">

    <!-- ============================================================
         TAB 1: Basic Info
    ============================================================ -->
    <div class="tab-pane fade show active" id="tab-basic" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title mb-4 text-primary">
                    <i class="bi bi-info-circle-fill me-2"></i><?= Yii::t('app', 'Основная информация') ?>
                </h5>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold" for="title">
                            <?= Yii::t('app', 'Заголовок') ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="title" name="DissertationAnnouncement[title]"
                               class="form-control <?= $model->hasErrors('title') ? 'is-invalid' : '' ?>"
                               value="<?= Html::encode($model->title) ?>"
                               oninput="autoGenerateUrl(this.value)"
                               required>
                        <?php if ($model->hasErrors('title')): ?>
                            <div class="invalid-feedback"><?= Html::encode($model->getFirstError('title')) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold"><?= Yii::t('app', 'Язык') ?></label>
                        <?= Html::dropDownList(
                            'DissertationAnnouncement[language]',
                            $model->language,
                            DissertationAnnouncement::getLanguageList(),
                            ['class' => 'form-select' . ($model->hasErrors('language') ? ' is-invalid' : '')]
                        ) ?>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold"><?= Yii::t('app', 'Статус') ?></label>
                        <?= Html::dropDownList(
                            'DissertationAnnouncement[status]',
                            $model->status,
                            DissertationAnnouncement::getStatusList(),
                            ['class' => 'form-select' . ($model->hasErrors('status') ? ' is-invalid' : '')]
                        ) ?>
                        <div class="form-text"><?= Yii::t('app', '"Опубликовано" — объявление видно на сайте') ?></div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold"><?= Yii::t('app', 'Дата защиты') ?></label>
                        <input type="datetime-local" name="DissertationAnnouncement[defense_date]"
                               class="form-control <?= $model->hasErrors('defense_date') ? 'is-invalid' : '' ?>"
                               value="<?= $model->defense_date ? date('Y-m-d\TH:i', strtotime($model->defense_date)) : '' ?>">
                        <?php if ($model->hasErrors('defense_date')): ?>
                            <div class="invalid-feedback"><?= Html::encode($model->getFirstError('defense_date')) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-semibold"><?= Yii::t('app', 'URL (slug)') ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text text-muted small">/announcement/view/</span>
                            <input type="text" id="url-field" name="DissertationAnnouncement[url]"
                                   class="form-control <?= $model->hasErrors('url') ? 'is-invalid' : '' ?>"
                                   value="<?= Html::encode($model->url) ?>"
                                   pattern="[a-z0-9\-]+"
                                   placeholder="my-announcement-slug">
                        </div>
                        <?php if ($model->hasErrors('url')): ?>
                            <div class="text-danger small mt-1"><?= Html::encode($model->getFirstError('url')) ?></div>
                        <?php endif; ?>
                        <div class="form-text"><?= Yii::t('app', 'Только строчные латинские буквы, цифры и дефис. Генерируется автоматически.') ?></div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold"><?= Yii::t('app', 'Контактный Email') ?></label>
                        <input type="email" name="DissertationAnnouncement[contact_email]"
                               class="form-control <?= $model->hasErrors('contact_email') ? 'is-invalid' : '' ?>"
                               value="<?= Html::encode($model->contact_email) ?>"
                               placeholder="example@kartu.kz">
                        <?php if ($model->hasErrors('contact_email')): ?>
                            <div class="invalid-feedback"><?= Html::encode($model->getFirstError('contact_email')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         TAB 2: Content
    ============================================================ -->
    <div class="tab-pane fade" id="tab-content" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title mb-4 text-primary">
                    <i class="bi bi-file-text-fill me-2"></i><?= Yii::t('app', 'Текст объявления') ?>
                </h5>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= Yii::t('app', 'Содержание') ?> <span class="text-danger">*</span></label>
                    <textarea id="content-editor" name="DissertationAnnouncement[content]"
                              class="form-control <?= $model->hasErrors('content') ? 'is-invalid' : '' ?>"
                              rows="20"
                              style="font-family: monospace; font-size: 14px;"
                              placeholder="<?= Yii::t('app', 'Введите текст объявления. Поддерживается HTML-разметка.') ?>"><?= Html::encode($model->content) ?></textarea>
                    <?php if ($model->hasErrors('content')): ?>
                        <div class="invalid-feedback"><?= Html::encode($model->getFirstError('content')) ?></div>
                    <?php endif; ?>
                    <div class="form-text"><?= Yii::t('app', 'Поддерживается HTML-разметка: &lt;p&gt;, &lt;br&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;a&gt;, &lt;h2&gt;–&lt;h4&gt; и т.д.') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         TAB 3: Zoom
    ============================================================ -->
    <div class="tab-pane fade" id="tab-zoom" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title mb-4 text-primary">
                    <i class="bi bi-camera-video-fill me-2"></i><?= Yii::t('app', 'Информация Zoom') ?>
                </h5>
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= Yii::t('app', 'Эти данные будут показаны на странице объявления для подключения онлайн.') ?>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold"><?= Yii::t('app', 'Ссылка для подключения Zoom') ?></label>
                        <input type="url" name="DissertationAnnouncement[zoom_link]"
                               class="form-control"
                               value="<?= Html::encode($model->zoom_link) ?>"
                               placeholder="https://zoom.us/j/...">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold"><?= Yii::t('app', 'ID конференции') ?></label>
                        <input type="text" name="DissertationAnnouncement[zoom_conference_id]"
                               class="form-control"
                               value="<?= Html::encode($model->zoom_conference_id) ?>"
                               placeholder="123 456 7890">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold"><?= Yii::t('app', 'Код доступа') ?></label>
                        <input type="text" name="DissertationAnnouncement[zoom_access_code]"
                               class="form-control"
                               value="<?= Html::encode($model->zoom_access_code) ?>"
                               placeholder="123456">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         TAB 4: Documents
    ============================================================ -->
    <div class="tab-pane fade" id="tab-docs" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title mb-4 text-primary">
                    <i class="bi bi-paperclip me-2"></i><?= Yii::t('app', 'Документы') ?>
                </h5>

                <?php if (!$isNew && !empty($model->documents)): ?>
                <div class="existing-docs mb-4">
                    <h6 class="fw-semibold mb-3"><?= Yii::t('app', 'Прикреплённые документы') ?>:</h6>
                    <ul class="list-group">
                        <?php foreach ($model->documents as $doc): ?>
                        <li class="list-group-item d-flex align-items-center gap-3">
                            <i class="bi <?= $doc->getFileIcon() ?> fs-5"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?= Html::encode($doc->document_name) ?></div>
                                <small class="text-muted"><?= Html::encode($doc->file_path) ?></small>
                            </div>
                            <small class="text-muted me-2">
                                <?= Html::encode((new DateTime($doc->uploaded_at))->format('d.m.Y')) ?>
                            </small>
                            <a href="<?= Html::encode($doc->getDownloadUrl()) ?>"
                               target="_blank" class="btn btn-sm btn-outline-secondary me-1"
                               title="<?= Yii::t('app', 'Скачать') ?>">
                                <i class="bi bi-download"></i>
                            </a>
                            <?= Html::beginForm(['/manage/delete-document', 'id' => $doc->id], 'post', [
                                'onsubmit' => 'return confirm("' . Yii::t('app', 'Удалить документ?') . '")',
                                'style' => 'display:inline',
                            ]) ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    title="<?= Yii::t('app', 'Удалить') ?>">
                                <i class="bi bi-trash3"></i>
                            </button>
                            <?= Html::endForm() ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if ($isNew): ?>
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <?= Yii::t('app', 'Сначала сохраните объявление, затем можно загружать документы.') ?>
                </div>
                <?php else: ?>
                <div class="upload-section">
                    <h6 class="fw-semibold mb-3"><?= Yii::t('app', 'Загрузить новые файлы') ?>:</h6>
                    <div class="mb-3">
                        <input type="file" name="documents[]" id="documents"
                               class="form-control"
                               multiple
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt">
                        <div class="form-text">
                            <?= Yii::t('app', 'Поддерживаемые форматы: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR. Можно выбрать несколько файлов.') ?>
                        </div>
                    </div>
                    <div id="file-preview" class="mt-2"></div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div><!-- /tab-content -->

<!-- ============================================================
     Submit buttons
============================================================ -->
<div class="form-actions d-flex gap-2 mt-4 py-3 border-top">
    <?= Html::submitButton(
        '<i class="bi bi-check-circle me-1"></i>' . ($isNew ? Yii::t('app', 'Создать') : Yii::t('app', 'Сохранить изменения')),
        ['class' => 'btn btn-primary btn-lg']
    ) ?>
    <a href="<?= Url::to(['/manage']) ?>" class="btn btn-outline-secondary btn-lg">
        <i class="bi bi-x-circle me-1"></i><?= Yii::t('app', 'Отмена') ?>
    </a>
</div>

<?php \yii\bootstrap5\ActiveForm::end(); ?>

<!-- JS: auto-generate URL from title -->
<script>
function cyrToLat(str) {
    var cyr = {'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'zh','з':'z',
               'и':'i','й':'j','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r',
               'с':'s','т':'t','у':'u','ф':'f','х':'kh','ц':'ts','ч':'ch','ш':'sh',
               'щ':'shch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya',
               'ә':'a','ғ':'g','қ':'k','ң':'ng','ө':'o','ұ':'u','ү':'u','һ':'h','і':'i'};
    return str.toLowerCase().split('').map(function(c){ return cyr[c] !== undefined ? cyr[c] : c; }).join('');
}

function autoGenerateUrl(title) {
    var urlField = document.getElementById('url-field');
    if (!urlField || urlField.dataset.manual === 'true') return;
    var slug = cyrToLat(title)
        .replace(/[^a-z0-9\s\-]/g, '')
        .replace(/[\s\-]+/g, '-')
        .replace(/^-+|-+$/g, '');
    urlField.value = slug;
}

// Mark field as manually edited
document.addEventListener('DOMContentLoaded', function() {
    var urlField = document.getElementById('url-field');
    if (urlField) {
        urlField.addEventListener('input', function() {
            this.dataset.manual = 'true';
        });
    }

    // File preview
    var fileInput = document.getElementById('documents');
    var preview   = document.getElementById('file-preview');
    if (fileInput && preview) {
        fileInput.addEventListener('change', function() {
            preview.innerHTML = '';
            if (this.files.length === 0) return;
            var ul = document.createElement('ul');
            ul.className = 'list-group';
            for (var i = 0; i < this.files.length; i++) {
                var li = document.createElement('li');
                li.className = 'list-group-item d-flex align-items-center small py-1';
                li.innerHTML = '<i class="bi bi-file-earmark me-2"></i>' +
                    '<span>' + this.files[i].name + '</span>' +
                    '<span class="ms-auto text-muted">' + (this.files[i].size / 1024).toFixed(1) + ' KB</span>';
                ul.appendChild(li);
            }
            preview.appendChild(ul);
        });
    }
});
</script>
