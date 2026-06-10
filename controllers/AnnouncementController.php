<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\AnnouncementSearch;
use app\models\DissertationAnnouncement;

class AnnouncementController extends Controller
{
    public $layout = 'main';

    public function actionIndex(): string
    {
        $searchModel = new AnnouncementSearch();
        $dataProvider = $searchModel->searchPublished(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'announcements' => $dataProvider->getModels(),
            'pagination'    => $dataProvider->getPagination(),
        ]);
    }

    public function actionView(string $url): string
    {
        $announcement = DissertationAnnouncement::find()
            ->with(['author', 'documents'])
            ->where([
                'url'    => $url,
                'status' => DissertationAnnouncement::STATUS_PUBLISHED,
            ])
            ->one();

        if (!$announcement) {
            throw new NotFoundHttpException(Yii::t('app', 'Объявление не найдено.'));
        }

        $lang = Yii::$app->request->get('lang');
        if ($lang && $lang !== $announcement->language && in_array($lang, [DissertationAnnouncement::LANG_RU, DissertationAnnouncement::LANG_KZ, DissertationAnnouncement::LANG_EN], true)) {
            $translation = $announcement->getTranslation($lang);
            if ($translation) {
                return $this->redirect(['view', 'url' => $translation->url, 'lang' => $lang]);
            }
        }

        return $this->render('view', ['announcement' => $announcement]);
    }
}
