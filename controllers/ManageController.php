<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use app\models\DissertationAnnouncement;
use app\models\AnnouncementDocument;
use app\models\AnnouncementSearch;

class ManageController extends Controller
{
    public $layout = 'main';

    /**
     * Require authentication for all actions.
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (Yii::$app->user->isGuest) {
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
        $model->language   = DissertationAnnouncement::LANG_RU;

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
                return $this->redirect(['update', 'id' => $model->id]);
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            // Normalize datetime-local input (Y-m-dTH:i → Y-m-d H:i:s)
            $this->normalizeDefenseDate($model);

            if ($model->save()) {
                $this->handleFileUploads($model);
                Yii::$app->session->setFlash('success', Yii::t('app', 'Объявление успешно обновлено.'));
                return $this->redirect(['update', 'id' => $model->id]);
            }
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete(int $id): Response
    {
        if (!Yii::$app->request->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException();
        }

        $model = $this->findModel($id);

        // Delete associated files
        foreach ($model->documents as $doc) {
            $fullPath = Yii::getAlias('@webroot') . '/' . $doc->file_path;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $model->delete();
        Yii::$app->session->setFlash('success', Yii::t('app', 'Объявление удалено.'));
        return $this->redirect(['index']);
    }

    public function actionDeleteDocument(int $id): Response
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
        $fullPath = Yii::getAlias('@webroot') . '/' . $doc->file_path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $doc->delete();

        Yii::$app->session->setFlash('success', Yii::t('app', 'Документ удалён.'));
        return $this->redirect(['update', 'id' => $announcementId]);
    }

    // ---- Helpers ----

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

    protected function handleFileUploads(DissertationAnnouncement $model): void
    {
        $uploadedFiles = UploadedFile::getInstancesByName('documents');
        if (empty($uploadedFiles)) {
            return;
        }

        $uploadDir = Yii::getAlias('@webroot') . '/uploads/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

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
                $doc->save();
            }
        }
    }
}
