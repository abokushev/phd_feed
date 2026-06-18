<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use app\models\DissertationAnnouncement;
use app\models\AnnouncementDocument;
use app\models\DocumentDisplayName;
use app\models\AnnouncementSearch;

class ManageController extends Controller
{
    public $layout = 'main';

    /**
     * Require authentication for all actions.
     */
    public function beforeAction($action): bool
    {
        try {
            if (!parent::beforeAction($action)) {
                return false;
            }
        } catch (BadRequestHttpException $e) {
            if ($this->shouldReturnJson($action->id)) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->statusCode = 400;
                Yii::$app->response->data = [
                    'success' => false,
                    'error' => Yii::t('app', 'Не удалось проверить запрос. Обновите страницу и попробуйте ещё раз.'),
                ];
                return false;
            }

            throw $e;
        }

        if (Yii::$app->user->isGuest) {
            if ($this->shouldReturnJson($action->id)) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->statusCode = 401;
                Yii::$app->response->data = [
                    'success' => false,
                    'error' => Yii::t('app', 'Сессия истекла. Войдите в систему ещё раз.'),
                ];
                return false;
            }

            Yii::$app->user->loginRequired();
            return false;
        }

        return true;
    }

    public function actionIndex(): string
    {
        $searchModel  = new AnnouncementSearch();
        $dataProvider = $searchModel->searchByUser(
            Yii::$app->request->queryParams,
            (int) Yii::$app->user->id
        );

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate(): string|Response
    {
        $model = new DissertationAnnouncement();
        $model->created_by = Yii::$app->user->id;
        $model->status     = DissertationAnnouncement::STATUS_DRAFT;
        $requestLanguage = Yii::$app->request->get('lang', DissertationAnnouncement::LANG_RU);
        $model->language = $this->isValidLanguage($requestLanguage)
            ? $requestLanguage
            : DissertationAnnouncement::LANG_RU;

        if ($model->load(Yii::$app->request->post())) {
            // Auto-generate URL if empty
            if (empty($model->url)) {
                $model->url = DissertationAnnouncement::generateUrl($model->title);
            }
            // Normalize datetime-local input (Y-m-dTH:i → Y-m-d H:i:s)
            $this->normalizeDefenseDate($model);

            if ($model->save()) {
                $this->handleFileUploads($model);
                Yii::$app->session->setFlash('success', Yii::t('app', 'Объявление успешно создано.'));
                return $this->redirect(['update', 'id' => $model->id, 'lang' => $model->language]);
            }
        }

        // For create page, set default languageUrls
        $langUrls = [];
        foreach ([DissertationAnnouncement::LANG_RU, DissertationAnnouncement::LANG_KZ, DissertationAnnouncement::LANG_EN] as $lang) {
            $langUrls[$lang] = \yii\helpers\Url::to(['/manage/create', 'lang' => $lang]);
        }

        $this->view->params['languageUrls'] = $langUrls;

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);
        Yii::$app->language = $model->language;
        Yii::$app->session->set('lang', $model->language);

        if ($model->load(Yii::$app->request->post())) {
            $model->language = $this->findModel($id)->language;
            // Normalize datetime-local input (Y-m-dTH:i → Y-m-d H:i:s)
            $this->normalizeDefenseDate($model);

            if ($model->save()) {
                $this->handleFileUploads($model);
                Yii::$app->session->setFlash('success', Yii::t('app', 'Объявление успешно обновлено.'));
                return $this->redirect(['update', 'id' => $model->id, 'lang' => $model->language]);
            }
        }

        $langUrls = $this->buildManageLanguageUrls($model);

        // Set languageUrls in view params so layout switchers use them
        $this->view->params['languageUrls'] = $langUrls;

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete(int $id): Response
    {
        if (!Yii::$app->request->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException();
        }

        $model = $this->findModel($id);

        $filePaths = [];
        foreach ($model->documents as $doc) {
            $filePaths[] = $doc->file_path;
        }

        $model->delete();
        foreach (array_unique($filePaths) as $filePath) {
            $this->deleteDocumentFileIfUnused($filePath);
        }

        Yii::$app->session->setFlash('success', Yii::t('app', 'Объявление удалено.'));
        return $this->redirect(['index']);
    }

    public function actionDeleteDocument(int $id): Response|array
    {
        if (!Yii::$app->request->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException();
        }

        $doc = AnnouncementDocument::findOne($id);
        if (!$doc) {
            throw new NotFoundHttpException();
        }

        // Ensure current user owns the announcement
        $announcement = $doc->announcement;
        if (!$announcement || $announcement->created_by != Yii::$app->user->id) {
            throw new ForbiddenHttpException();
        }

        $announcementId = $doc->announcement_id;
        $filePath = $doc->file_path;
        $doc->delete();
        $this->deleteDocumentFileIfUnused($filePath);

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['success' => true];
        }

        Yii::$app->session->setFlash('success', Yii::t('app', 'Документ удалён.'));
        return $this->redirect(['update', 'id' => $announcementId]);
    }

    /**
     * Save current announcement and switch to the specified language version.
     * If the language version doesn't exist, create it automatically.
     */
    public function actionSwitchLanguage(int $id, string $language): Response
    {
        $currentModel = $this->findModel($id);
        $currentLanguage = $currentModel->language;

        // Save current form data first if POST
        if ($currentModel->load(Yii::$app->request->post())) {
            $currentModel->language = $currentLanguage;
            $this->normalizeDefenseDate($currentModel);
            $currentModel->save(false);
            $this->handleFileUploads($currentModel);
        }

        // Validate target language
        if (!in_array($language, [
            DissertationAnnouncement::LANG_RU,
            DissertationAnnouncement::LANG_KZ,
            DissertationAnnouncement::LANG_EN,
        ], true)) {
            return $this->redirect(['update', 'id' => $currentModel->id, 'lang' => $currentModel->language]);
        }

        // If already on this language, just redirect back
        if ($currentModel->language === $language) {
            return $this->redirect(['update', 'id' => $currentModel->id, 'lang' => $currentModel->language]);
        }

        // Find existing translation by group_key or URL pattern
        $translation = $this->findManageTranslation($currentModel, $language);

        if ($translation) {
            $this->copyMissingGlobalDocuments($currentModel, $translation);
            // Redirect to existing translation
            return $this->redirect(['update', 'id' => $translation->id, 'lang' => $translation->language]);
        }

        // Create new announcement in the target language
        $newModel = new DissertationAnnouncement();
        $newModel->attributes = $currentModel->getAttributes([
            'content', 'zoom_link', 'zoom_conference_id', 'zoom_access_code',
            'contact_email', 'defense_date',
        ]);
        $newModel->created_by = Yii::$app->user->id;
        $newModel->status     = $currentModel->status;
        $newModel->language   = $language;

        // Generate URL for the new language
        $groupKey = $currentModel->getTranslationGroupKey();
        if (empty($currentModel->group_key)) {
            $currentModel->group_key = $groupKey;
            $currentModel->save(false, ['group_key']);
        }
        $newModel->group_key = $groupKey;
        $newModel->url = $groupKey . ($language === DissertationAnnouncement::LANG_RU ? '' : '-' . $language);

        // Ensure URL uniqueness
        $base  = $newModel->url;
        $final = $base;
        $i     = 1;
        while (DissertationAnnouncement::find()->where(['url' => $final])->exists()) {
            $final = $base . '-' . $i;
            $i++;
        }
        $newModel->url = $final;

        // Copy title with language suffix
        $langLabels = DissertationAnnouncement::getLanguageList();
        $newModel->title = $currentModel->title;

        if ($newModel->save()) {
            // Copy global documents to the new announcement
            foreach ($currentModel->documents as $doc) {
                if ($doc->is_global) {
                    $newDoc = new AnnouncementDocument();
                    $newDoc->announcement_id = $newModel->id;
                    $newDoc->document_name   = $doc->document_name;
                    $newDoc->file_path       = $doc->file_path;
                    $newDoc->display_name    = $doc->display_name;
                    $newDoc->is_global       = true;
                    $newDoc->save(false);
                }
            }

            Yii::$app->session->setFlash('success',
                Yii::t('app', 'Создана версия на языке {lang}.', ['lang' => $langLabels[$language] ?? $language])
            );
            return $this->redirect(['update', 'id' => $newModel->id, 'lang' => $newModel->language]);
        }

        // If creation failed, redirect back with error
        Yii::$app->session->setFlash('error', Yii::t('app', 'Не удалось создать версию на другом языке.'));
        return $this->redirect(['update', 'id' => $currentModel->id, 'lang' => $currentModel->language]);
    }

    /**
     * AJAX: Upload a single document to an announcement.
     */
    public function actionUploadDocument(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'POST required'];
        }

        $announcementId = Yii::$app->request->post('announcement_id');
        $displayName    = Yii::$app->request->post('display_name') ?: null;
        $isGlobal       = Yii::$app->request->post('is_global', '1') === '1';

        $model = DissertationAnnouncement::findOne([
            'id'         => $announcementId,
            'created_by' => Yii::$app->user->id,
        ]);
        if (!$model) {
            return $this->jsonError(Yii::t('app', 'Объявление не найдено.'), 404);
        }

        $file = UploadedFile::getInstanceByName('document_file');
        if (!$file) {
            return $this->jsonError(Yii::t('app', 'Файл не был передан на сервер.'));
        }
        if ($file->error !== UPLOAD_ERR_OK) {
            return $this->jsonError($this->getUploadErrorMessage($file->error));
        }

        $uploadDir = Yii::getAlias('@webroot') . '/uploads/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->baseName);
        $filename = time() . '_' . $safeName . '.' . $file->extension;
        $destPath = $uploadDir . $filename;
        $dbPath   = 'uploads/documents/' . $filename;

        if (!$file->saveAs($destPath)) {
            return ['success' => false, 'error' => 'Failed to save file'];
        }

        $doc = new AnnouncementDocument();
        $doc->announcement_id = $model->id;
        $doc->document_name   = $file->name;
        $doc->file_path       = $dbPath;
        $doc->display_name    = $displayName;
        $doc->is_global       = $isGlobal;
        $doc->save(false);

        if ($doc->is_global) {
            $this->copyDocumentToExistingTranslations($model, $doc);
        }

        return [
            'success' => true,
            'document' => [
                'id'           => $doc->id,
                'document_name' => $doc->document_name,
                'display_name' => $doc->display_name,
                'is_global'    => $doc->is_global,
                'file_path'    => $doc->file_path,
                'download_url' => $doc->getDownloadUrl(),
                'file_icon'    => $doc->getFileIcon(),
                'uploaded_at'  => $doc->uploaded_at,
            ],
        ];
    }

    /**
     * AJAX: Add a document display-name option for current user and language.
     */
    public function actionAddDocumentDisplayName(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return $this->jsonError(Yii::t('app', 'POST required'), 405);
        }

        $language = (string) Yii::$app->request->post('language', DissertationAnnouncement::LANG_RU);
        if (!$this->isValidLanguage($language)) {
            return $this->jsonError(Yii::t('app', 'Неверный язык.'));
        }

        $name = trim((string) Yii::$app->request->post('name', ''));
        if ($name === '') {
            return $this->jsonError(Yii::t('app', 'Введите отображаемое имя.'));
        }

        $userId = (int) Yii::$app->user->id;
        $existing = DocumentDisplayName::findOne([
            'created_by' => $userId,
            'language' => $language,
            'name' => $name,
        ]);

        if ($existing) {
            return [
                'success' => true,
                'displayName' => [
                    'id' => $existing->id,
                    'name' => $existing->name,
                ],
            ];
        }

        $maxSortOrder = (int) DocumentDisplayName::find()
            ->where(['created_by' => $userId, 'language' => $language])
            ->max('sort_order');

        $displayName = new DocumentDisplayName();
        $displayName->created_by = $userId;
        $displayName->language = $language;
        $displayName->name = $name;
        $displayName->sort_order = $maxSortOrder + 10;

        if (!$displayName->save()) {
            return $this->jsonError($displayName->getFirstError('name') ?: Yii::t('app', 'Не удалось добавить имя.'));
        }

        return [
            'success' => true,
            'displayName' => [
                'id' => $displayName->id,
                'name' => $displayName->name,
            ],
        ];
    }

    /**
     * AJAX: Delete a document display-name option owned by current user.
     */
    public function actionDeleteDocumentDisplayName(int $id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return $this->jsonError(Yii::t('app', 'POST required'), 405);
        }

        $displayName = DocumentDisplayName::findOne([
            'id' => $id,
            'created_by' => Yii::$app->user->id,
        ]);

        if (!$displayName) {
            return $this->jsonError(Yii::t('app', 'Имя не найдено.'), 404);
        }

        $displayName->delete();

        return ['success' => true];
    }

    // ---- Helpers ----

    protected function shouldReturnJson(string $actionId): bool
    {
        return in_array($actionId, [
            'upload-document',
            'delete-document',
            'add-document-display-name',
            'delete-document-display-name',
        ], true)
            && Yii::$app->request->isAjax;
    }

    protected function jsonError(string $error, int $statusCode = 400): array
    {
        Yii::$app->response->statusCode = $statusCode;
        return ['success' => false, 'error' => $error];
    }

    protected function normalizeDefenseDate(DissertationAnnouncement $model): void
    {
        if (!empty($model->defense_date)) {
            // datetime-local sends Y-m-dTH:i; convert to Y-m-d H:i:s for MySQL
            $model->defense_date = str_replace('T', ' ', $model->defense_date);
            if (strlen($model->defense_date) === 16) {
                $model->defense_date .= ':00';
            }
        } else {
            $model->defense_date = null;
        }
    }

    protected function findModel(int $id): DissertationAnnouncement
    {
        $model = DissertationAnnouncement::findOne([
            'id'         => $id,
            'created_by' => Yii::$app->user->id,
        ]);
        if (!$model) {
            throw new NotFoundHttpException(Yii::t('app', 'Объявление не найдено.'));
        }
        return $model;
    }

    protected function isValidLanguage(string $language): bool
    {
        return in_array($language, [
            DissertationAnnouncement::LANG_RU,
            DissertationAnnouncement::LANG_KZ,
            DissertationAnnouncement::LANG_EN,
        ], true);
    }

    protected function buildManageLanguageUrls(DissertationAnnouncement $model): array
    {
        $urls = [];
        foreach ([DissertationAnnouncement::LANG_RU, DissertationAnnouncement::LANG_KZ, DissertationAnnouncement::LANG_EN] as $language) {
            $translation = $model->language === $language
                ? $model
                : $this->findManageTranslation($model, $language);

            $urls[$language] = $translation
                ? \yii\helpers\Url::to(['/manage/update', 'id' => $translation->id, 'lang' => $language])
                : \yii\helpers\Url::to(['/manage/switch-language', 'id' => $model->id, 'language' => $language, 'lang' => $language]);
        }

        return $urls;
    }

    protected function findManageTranslation(DissertationAnnouncement $model, string $language): ?DissertationAnnouncement
    {
        $groupKeys = array_values(array_unique(array_filter([
            $model->group_key,
            $model->getTranslationGroupKey(),
        ])));

        return DissertationAnnouncement::find()
            ->where([
                'created_by' => Yii::$app->user->id,
                'language' => $language,
            ])
            ->andWhere(['or',
                ['group_key' => $groupKeys],
                ['url' => $model->getTranslationUrl($language)],
            ])
            ->one();
    }

    protected function copyDocumentToExistingTranslations(DissertationAnnouncement $model, AnnouncementDocument $doc): void
    {
        foreach ([DissertationAnnouncement::LANG_RU, DissertationAnnouncement::LANG_KZ, DissertationAnnouncement::LANG_EN] as $language) {
            if ($language === $model->language) {
                continue;
            }

            $translation = $this->findManageTranslation($model, $language);
            if ($translation) {
                $this->copyDocumentIfMissing($doc, $translation);
            }
        }
    }

    protected function copyMissingGlobalDocuments(DissertationAnnouncement $source, DissertationAnnouncement $target): void
    {
        foreach ($source->documents as $doc) {
            if ($doc->is_global) {
                $this->copyDocumentIfMissing($doc, $target);
            }
        }
    }

    protected function copyDocumentIfMissing(AnnouncementDocument $sourceDoc, DissertationAnnouncement $target): void
    {
        $exists = AnnouncementDocument::find()
            ->where([
                'announcement_id' => $target->id,
                'file_path' => $sourceDoc->file_path,
            ])
            ->exists();

        if ($exists) {
            return;
        }

        $newDoc = new AnnouncementDocument();
        $newDoc->announcement_id = $target->id;
        $newDoc->document_name   = $sourceDoc->document_name;
        $newDoc->file_path       = $sourceDoc->file_path;
        $newDoc->display_name    = $sourceDoc->display_name;
        $newDoc->is_global       = true;
        $newDoc->save(false);
    }

    protected function deleteDocumentFileIfUnused(string $filePath): void
    {
        if (AnnouncementDocument::find()->where(['file_path' => $filePath])->exists()) {
            return;
        }

        $fullPath = Yii::getAlias('@webroot') . '/' . ltrim($filePath, '/');
        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    protected function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE => Yii::t('app', 'Файл слишком большой. Максимальный размер: {size}.', [
                'size' => ini_get('upload_max_filesize'),
            ]),
            UPLOAD_ERR_PARTIAL => Yii::t('app', 'Файл загрузился не полностью. Попробуйте ещё раз.'),
            UPLOAD_ERR_NO_FILE => Yii::t('app', 'Выберите файл для загрузки.'),
            UPLOAD_ERR_NO_TMP_DIR => Yii::t('app', 'На сервере не настроена временная папка для загрузки.'),
            UPLOAD_ERR_CANT_WRITE => Yii::t('app', 'Сервер не смог записать загруженный файл.'),
            UPLOAD_ERR_EXTENSION => Yii::t('app', 'Загрузка остановлена расширением PHP.'),
            default => Yii::t('app', 'Не удалось загрузить файл. Код ошибки: {code}.', ['code' => $errorCode]),
        };
    }

    protected function handleFileUploads(DissertationAnnouncement $model): void
    {
        $uploadedFiles = UploadedFile::getInstancesByName('documents');
        $displayNames  = Yii::$app->request->post('document_display_names', []);
        $isGlobals     = Yii::$app->request->post('document_is_global', []);

        if (empty($uploadedFiles)) {
            return;
        }

        $uploadDir = Yii::getAlias('@webroot') . '/uploads/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $index = 0;
        foreach ($uploadedFiles as $file) {
            if ($file->error !== UPLOAD_ERR_OK) {
                continue;
            }

            $safeName  = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->baseName);
            $filename  = time() . '_' . $safeName . '.' . $file->extension;
            $destPath  = $uploadDir . $filename;
            $dbPath    = 'uploads/documents/' . $filename;

            if ($file->saveAs($destPath)) {
                $doc = new AnnouncementDocument();
                $doc->announcement_id = $model->id;
                $doc->document_name   = $file->name;
                $doc->file_path       = $dbPath;
                $doc->display_name    = !empty($displayNames[$index]) ? $displayNames[$index] : null;
                $doc->is_global       = isset($isGlobals[$index]) ? (bool)$isGlobals[$index] : true;
                $doc->save(false);
            }
            $index++;
        }
    }
}
