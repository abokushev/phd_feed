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
                              rows="20"><?= $model->content ?></textarea>
                    <div class="form-text mb-2">
                        <?= Yii::t('app', 'Используйте встроенный редактор, чтобы редактировать как в Word: форматирование, списки, ссылки и вставка из Word.') ?>
                    </div>
                    <?php if ($model->hasErrors('content')): ?>
                        <div class="invalid-feedback"><?= Html::encode($model->getFirstError('content')) ?></div>
                    <?php endif; ?>
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

<!-- Custom Word-Like Editor CSS -->
<style>
.wle-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    padding: 8px 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-bottom: none;
    border-radius: 0.375rem 0.375rem 0 0;
    align-items: center;
}
.wle-toolbar .wle-sep {
    width: 1px;
    height: 24px;
    background: #dee2e6;
    margin: 0 4px;
}
.wle-toolbar button,
.wle-toolbar select {
    border: 1px solid transparent;
    background: transparent;
    border-radius: 4px;
    padding: 4px 6px;
    cursor: pointer;
    font-size: 13px;
    color: #333;
    line-height: 1;
    min-width: 28px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.wle-toolbar button:hover,
.wle-toolbar select:hover {
    background: #e2e6ea;
    border-color: #ced4da;
}
.wle-toolbar button.active {
    background: #cfe2ff;
    border-color: #9ec5fe;
    color: #084298;
}
.wle-toolbar select {
    min-width: auto;
    padding: 2px 4px;
    font-size: 12px;
}
.wle-toolbar input[type="color"] {
    width: 28px;
    height: 28px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 1px;
    cursor: pointer;
    background: transparent;
}
.wle-toolbar input[type="color"]:hover {
    border-color: #9ec5fe;
}
.wle-content {
    min-height: 400px;
    max-height: 600px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0 0 0.375rem 0.375rem;
    padding: 16px 20px;
    font-family: "Times New Roman", Times, serif;
    font-size: 14px;
    line-height: 1.6;
    outline: none;
    background: #fff;
}
.wle-content:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
}
.wle-content table {
    border-collapse: collapse;
    width: 100%;
    margin: 8px 0;
}
.wle-content table td,
.wle-content table th {
    border: 1px solid #333;
    padding: 6px 8px;
    min-width: 60px;
}
.wle-content table th {
    background: #f0f0f0;
    font-weight: bold;
}
.wle-content img {
    max-width: 100%;
    height: auto;
}
.wle-content h1, .wle-content h2, .wle-content h3,
.wle-content h4, .wle-content h5, .wle-content h6 {
    margin-top: 0.5em;
    margin-bottom: 0.3em;
}
.wle-content ul, .wle-content ol {
    margin: 0.5em 0;
    padding-left: 2em;
}
.wle-content blockquote {
    border-left: 3px solid #6c757d;
    padding-left: 12px;
    margin-left: 0;
    color: #555;
}
.wle-toolbar-wrap { margin-bottom: 8px; }
</style>

<!-- JS: auto-generate URL + Custom Word-Like Editor -->
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

/* ===== Custom Word-Like Editor (WLE) ===== */
function initWordEditor() {
    if (window._wleInitialized) return;
    window._wleInitialized = true;

    var textarea = document.getElementById('content-editor');
    if (!textarea) return;
    var container = textarea.parentNode;

    // Hide original textarea
    textarea.style.display = 'none';

    // Build toolbar
    var toolbarWrap = document.createElement('div');
    toolbarWrap.className = 'wle-toolbar-wrap';
    toolbarWrap.id = 'wle-toolbar-wrap';

    var toolbar = document.createElement('div');
    toolbar.className = 'wle-toolbar';
    toolbar.id = 'wle-toolbar';

    function makeBtn(cmd, title, icon) {
        var b = document.createElement('button');
        b.type = 'button';
        b.title = title;
        b.innerHTML = icon;
        b.addEventListener('mousedown', function(e) { e.preventDefault(); });
        b.addEventListener('click', function(e) {
            e.preventDefault();
            execCmd(cmd);
            refreshToolbar();
        });
        return b;
    }
    function makeSep() {
        var s = document.createElement('span');
        s.className = 'wle-sep';
        return s;
    }
    function execCmd(cmd, val) {
        document.execCommand(cmd, false, val || null);
    }

    // --- Row 1: Block format, Font, Size ---
    // Paragraph format
    var blockSel = document.createElement('select');
    blockSel.title = 'Стиль абзаца';
    [
        ['p', 'Обычный текст'],
        ['h1', 'Заголовок 1'],
        ['h2', 'Заголовок 2'],
        ['h3', 'Заголовок 3'],
        ['h4', 'Заголовок 4'],
        ['h5', 'Заголовок 5'],
        ['h6', 'Заголовок 6'],
        ['blockquote', 'Цитата']
    ].forEach(function(opt) {
        var o = document.createElement('option');
        o.value = opt[0]; o.textContent = opt[1];
        blockSel.appendChild(o);
    });
    blockSel.addEventListener('change', function() {
        var val = this.value;
        if (val === 'blockquote') {
            execCmd('formatBlock', 'blockquote');
        } else {
            execCmd('formatBlock', val);
        }
        refreshToolbar();
    });
    toolbar.appendChild(blockSel);

    toolbar.appendChild(makeSep());

    // Font family
    var fontSel = document.createElement('select');
    fontSel.title = 'Шрифт';
    ['Times New Roman','Arial','Calibri','Cambria','Georgia','Verdana','Courier New','Tahoma','Trebuchet MS','Comic Sans MS','Impact','Lucida Console'].forEach(function(f) {
        var o = document.createElement('option');
        o.value = f; o.textContent = f;
        o.style.fontFamily = f;
        fontSel.appendChild(o);
    });
    fontSel.addEventListener('change', function() {
        execCmd('fontName', this.value);
        refreshToolbar();
    });
    toolbar.appendChild(fontSel);

    // Font size
    var sizeSel = document.createElement('select');
    sizeSel.title = 'Размер шрифта';
    [[1,'8'],[2,'10'],[3,'12'],[4,'14'],[5,'18'],[6,'24'],[7,'36']].forEach(function(s) {
        var o = document.createElement('option');
        o.value = s[0]; o.textContent = s[1] + ' pt';
        sizeSel.appendChild(o);
    });
    sizeSel.addEventListener('change', function() {
        execCmd('fontSize', this.value);
        refreshToolbar();
    });
    toolbar.appendChild(sizeSel);

    toolbar.appendChild(makeSep());

    // --- Row 1 cont: Bold, Italic, Underline, Strikethrough ---
    toolbar.appendChild(makeBtn('bold', 'Полужирный (Ctrl+B)', '<b>B</b>'));
    toolbar.appendChild(makeBtn('italic', 'Курсив (Ctrl+I)', '<i>I</i>'));
    toolbar.appendChild(makeBtn('underline', 'Подчёркнутый (Ctrl+U)', '<u>U</u>'));
    toolbar.appendChild(makeBtn('strikeThrough', 'Зачёркнутый', '<s>S</s>'));

    toolbar.appendChild(makeSep());

    // --- Colors ---
    var fgWrap = document.createElement('span');
    fgWrap.title = 'Цвет текста';
    fgWrap.style.position = 'relative';
    fgWrap.style.display = 'inline-flex';
    fgWrap.style.alignItems = 'center';
    var fgIcon = document.createElement('span');
    fgIcon.innerHTML = '<b>A</b>';
    fgIcon.style.borderBottom = '3px solid #000';
    fgIcon.style.padding = '0 2px';
    fgIcon.style.cursor = 'pointer';
    fgIcon.style.fontSize = '14px';
    var fgInput = document.createElement('input');
    fgInput.type = 'color';
    fgInput.value = '#000000';
    fgInput.style.position = 'absolute';
    fgInput.style.opacity = '0';
    fgInput.style.width = '0';
    fgInput.style.height = '0';
    fgInput.addEventListener('input', function() {
        execCmd('foreColor', this.value);
        fgIcon.style.borderBottomColor = this.value;
    });
    fgIcon.addEventListener('click', function() { fgInput.click(); });
    fgWrap.appendChild(fgInput);
    fgWrap.appendChild(fgIcon);
    toolbar.appendChild(fgWrap);

    var bgWrap = document.createElement('span');
    bgWrap.title = 'Цвет выделения';
    bgWrap.style.position = 'relative';
    bgWrap.style.display = 'inline-flex';
    bgWrap.style.alignItems = 'center';
    var bgIcon = document.createElement('span');
    bgIcon.innerHTML = '<b>A</b>';
    bgIcon.style.background = '#ffff00';
    bgIcon.style.padding = '0 2px';
    bgIcon.style.cursor = 'pointer';
    bgIcon.style.fontSize = '14px';
    bgIcon.style.lineHeight = '1';
    var bgInput = document.createElement('input');
    bgInput.type = 'color';
    bgInput.value = '#ffff00';
    bgInput.style.position = 'absolute';
    bgInput.style.opacity = '0';
    bgInput.style.width = '0';
    bgInput.style.height = '0';
    bgInput.addEventListener('input', function() {
        execCmd('hiliteColor', this.value);
        bgIcon.style.background = this.value;
    });
    bgIcon.addEventListener('click', function() { bgInput.click(); });
    bgWrap.appendChild(bgInput);
    bgWrap.appendChild(bgIcon);
    toolbar.appendChild(bgWrap);

    toolbar.appendChild(makeSep());

    // --- Alignment ---
    toolbar.appendChild(makeBtn('justifyLeft', 'Выровнять по левому краю', '⫷'));
    toolbar.appendChild(makeBtn('justifyCenter', 'По центру', '⫿'));
    toolbar.appendChild(makeBtn('justifyRight', 'По правому краю', '⫸'));
    toolbar.appendChild(makeBtn('justifyFull', 'По ширине', '☰'));

    toolbar.appendChild(makeSep());

    // --- Lists ---
    toolbar.appendChild(makeBtn('insertUnorderedList', 'Маркированный список', '• ≡'));
    toolbar.appendChild(makeBtn('insertOrderedList', 'Нумерованный список', '1. ≡'));

    toolbar.appendChild(makeSep());

    // --- Indent / Outdent ---
    toolbar.appendChild(makeBtn('indent', 'Увеличить отступ', '→│'));
    toolbar.appendChild(makeBtn('outdent', 'Уменьшить отступ', '│←'));

    toolbar.appendChild(makeSep());

    // --- Line height ---
    var lhSel = document.createElement('select');
    lhSel.title = 'Межстрочный интервал';
    [['1','1.0'],['1.5','1.5'],['2','2.0'],['2.5','2.5'],['3','3.0']].forEach(function(v) {
        var o = document.createElement('option');
        o.value = v[0]; o.textContent = v[1];
        lhSel.appendChild(o);
    });
    lhSel.addEventListener('change', function() {
        // Apply line-height via wrapping selection in span
        var sel = window.getSelection();
        if (!sel.rangeCount) return;
        var range = sel.getRangeAt(0);
        var span = document.createElement('span');
        span.style.lineHeight = this.value;
        try { range.surroundContents(span); } catch(e) {}
        refreshToolbar();
    });
    toolbar.appendChild(lhSel);

    toolbar.appendChild(makeSep());

    // --- Insert: Link, Image, Table, HR ---
    toolbar.appendChild(makeBtn('createLink', 'Вставить ссылку', '🔗'));
    toolbar.appendChild(makeBtn('insertImage', 'Вставить изображение', '🖼'));
    toolbar.appendChild(makeBtn('insertHorizontalRule', 'Горизонтальная линия', '―'));

    // Table button
    var tblBtn = document.createElement('button');
    tblBtn.type = 'button';
    tblBtn.title = 'Вставить таблицу';
    tblBtn.innerHTML = '⊞';
    tblBtn.addEventListener('mousedown', function(e) { e.preventDefault(); });
    tblBtn.addEventListener('click', function(e) {
        e.preventDefault();
        var rows = prompt('Количество строк:', '3');
        var cols = prompt('Количество столбцов:', '3');
        if (!rows || !cols) return;
        rows = parseInt(rows) || 3;
        cols = parseInt(cols) || 3;
        var html = '<table>';
        for (var r = 0; r < rows; r++) {
            html += '<tr>';
            for (var c = 0; c < cols; c++) {
                html += (r === 0) ? '<th>&nbsp;</th>' : '<td>&nbsp;</td>';
            }
            html += '</tr>';
        }
        html += '</table><p></p>';
        document.execCommand('insertHTML', false, html);
    });
    toolbar.appendChild(tblBtn);

    toolbar.appendChild(makeSep());

    // --- Remove format ---
    toolbar.appendChild(makeBtn('removeFormat', 'Очистить форматирование', '⊘'));

    toolbar.appendChild(makeSep());

    // --- Undo / Redo ---
    toolbar.appendChild(makeBtn('undo', 'Отменить (Ctrl+Z)', '↩'));
    toolbar.appendChild(makeBtn('redo', 'Повторить (Ctrl+Y)', '↪'));

    toolbar.appendChild(makeSep());

    // --- Fullscreen ---
    var fsBtn = document.createElement('button');
    fsBtn.type = 'button';
    fsBtn.title = 'Полный экран';
    fsBtn.innerHTML = '⛶';
    fsBtn.addEventListener('mousedown', function(e) { e.preventDefault(); });
    fsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        var wrap = document.getElementById('wle-toolbar-wrap');
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            (wrap.requestFullscreen || wrap.webkitRequestFullscreen).call(wrap);
        }
    });
    toolbar.appendChild(fsBtn);

    // --- HTML source ---
    var htmlBtn = document.createElement('button');
    htmlBtn.type = 'button';
    htmlBtn.title = 'Показать HTML-код';
    htmlBtn.innerHTML = '</>';
    htmlBtn.addEventListener('mousedown', function(e) { e.preventDefault(); });
    htmlBtn.addEventListener('click', function(e) {
        e.preventDefault();
        var editor = document.getElementById('wle-editor');
        if (htmlBtn.dataset.mode === 'html') {
            // Switch back to visual
            editor.innerHTML = htmlBtn.dataset.savedContent;
            editor.contentEditable = 'true';
            htmlBtn.dataset.mode = 'visual';
            htmlBtn.classList.remove('active');
        } else {
            // Switch to HTML source
            htmlBtn.dataset.savedContent = editor.innerHTML;
            editor.textContent = editor.innerHTML;
            editor.contentEditable = 'false';
            htmlBtn.dataset.mode = 'html';
            htmlBtn.classList.add('active');
        }
    });
    toolbar.appendChild(htmlBtn);

    toolbarWrap.appendChild(toolbar);
    container.insertBefore(toolbarWrap, textarea);

    // Build contenteditable div
    var editor = document.createElement('div');
    editor.id = 'wle-editor';
    editor.className = 'wle-content';
    editor.contentEditable = 'true';
    editor.innerHTML = textarea.value || '<p></p>';
    container.insertBefore(editor, textarea.nextSibling);

    // Sync content back to hidden textarea
    function syncToTextarea() {
        textarea.value = editor.innerHTML;
    }
    editor.addEventListener('input', syncToTextarea);
    editor.addEventListener('keyup', refreshToolbar);
    editor.addEventListener('mouseup', refreshToolbar);

    // Handle keydown for paste
    editor.addEventListener('paste', function(e) {
        var html = (e.clipboardData || window.clipboardData).getData('text/html');
        var text = (e.clipboardData || window.clipboardData).getData('text/plain');
        if (html) {
            // Clean Word paste - remove Word-specific junk
            var cleaned = cleanWordPaste(html);
            e.preventDefault();
            document.execCommand('insertHTML', false, cleaned);
        } else if (text) {
            e.preventDefault();
            document.execCommand('insertText', false, text);
        }
        syncToTextarea();
    });

    function cleanWordPaste(html) {
        // Remove Word namespace tags
        html = html.replace(/<xml>[\s\S]*?<\/xml>/gi, '');
        html = html.replace(/<style>[\s\S]*?<\/style>/gi, '');
        html = html.replace(/<meta[\s\S]*?>/gi, '');
        html = html.replace(/<link[\s\S]*?>/gi, '');
        // Remove Word-specific class names
        html = html.replace(/class="Mso[^"]*"/gi, '');
        // Remove Word comments
        html = html.replace(/<!--[\s\S]*?-->/g, '');
        // Convert Word lists to standard HTML lists
        html = html.replace(/<p[^>]*>\s*<span[^>]*>●\s*<\/span>/gi, '<li>');
        html = html.replace(/<p[^>]*>\s*<span[^>]*>\d+\.\s*<\/span>/gi, '<li>');
        // Remove empty paragraphs
        html = html.replace(/<p[^>]*>\s*&nbsp;\s*<\/p>/gi, '');
        return html;
    }

    // Intercept form submission to sync
    var form = document.getElementById('announcement-form');
    if (form) {
        form.addEventListener('submit', function() {
            syncToTextarea();
        });
    }

    // Handle link/image buttons with prompts
    document.getElementById('wle-toolbar').addEventListener('click', function(e) {
        var btn = e.target.closest('button');
        if (!btn) return;
        var title = btn.title;
        if (title === 'Вставить ссылку') {
            var url = prompt('Введите URL ссылки:', 'https://');
            if (url) {
                document.execCommand('createLink', false, url);
                syncToTextarea();
            }
        } else if (title === 'Вставить изображение') {
            var imgUrl = prompt('Введите URL изображения:', 'https://');
            if (imgUrl) {
                document.execCommand('insertImage', false, imgUrl);
                syncToTextarea();
            }
        }
    });

    // Refresh toolbar active states
    function refreshToolbar() {
        var cmds = {
            'bold': null, 'italic': null, 'underline': null, 'strikeThrough': null,
            'justifyLeft': null, 'justifyCenter': null, 'justifyRight': null, 'justifyFull': null,
            'insertUnorderedList': null, 'insertOrderedList': null
        };
        var btns = toolbar.querySelectorAll('button');
        btns.forEach(function(b) {
            var title = b.title;
            if (title === 'Полужирный (Ctrl+B)') b.classList.toggle('active', document.queryCommandState('bold'));
            else if (title === 'Курсив (Ctrl+I)') b.classList.toggle('active', document.queryCommandState('italic'));
            else if (title === 'Подчёркнутый (Ctrl+U)') b.classList.toggle('active', document.queryCommandState('underline'));
            else if (title === 'Зачёркнутый') b.classList.toggle('active', document.queryCommandState('strikeThrough'));
            else if (title === 'Выровнять по левому краю') b.classList.toggle('active', document.queryCommandState('justifyLeft'));
            else if (title === 'По центру') b.classList.toggle('active', document.queryCommandState('justifyCenter'));
            else if (title === 'По правому краю') b.classList.toggle('active', document.queryCommandState('justifyRight'));
            else if (title === 'По ширине') b.classList.toggle('active', document.queryCommandState('justifyFull'));
            else if (title === 'Маркированный список') b.classList.toggle('active', document.queryCommandState('insertUnorderedList'));
            else if (title === 'Нумерованный список') b.classList.toggle('active', document.queryCommandState('insertOrderedList'));
        });
    }

    refreshToolbar();
}

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

    // Init editor when Content tab is shown
    var contentTab = document.getElementById('content-tab');
    if (contentTab) {
        contentTab.addEventListener('shown.bs.tab', function() {
            initWordEditor();
        });
    }

    // Also init if content tab is already active
    var contentTabPane = document.getElementById('tab-content');
    if (contentTabPane && contentTabPane.classList.contains('active')) {
        initWordEditor();
    }
});
</script>
