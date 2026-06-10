<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class AnnouncementSearch extends Model
{
    public ?string $title = null;
    public ?string $status = null;
    public ?string $language = null;
    public ?string $sort = null;

    public function rules(): array
    {
        return [
            [['title', 'status', 'language', 'sort'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title'    => 'Заголовок',
            'status'   => 'Статус',
            'language' => 'Язык',
        ];
    }

    /**
     * Search announcements for manage page (scoped to current user).
     */
    public function searchByUser(array $params, int $userId): ActiveDataProvider
    {
        $query = DissertationAnnouncement::find()
            ->where(['created_by' => $userId])
            ->orderBy(['created_at' => SORT_DESC]);

        $this->load($params);

        if ($this->title) {
            $query->andWhere(['like', 'title', $this->title]);
        }
        if ($this->status) {
            $query->andWhere(['status' => $this->status]);
        }
        if ($this->language) {
            $query->andWhere(['language' => $this->language]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * Search published announcements for public page.
     */
    public function searchPublished(array $params): ActiveDataProvider
    {
        $query = DissertationAnnouncement::find()
            ->with(['author'])
            ->where(['status' => DissertationAnnouncement::STATUS_PUBLISHED]);

        $this->load($params);
        if (empty($this->language) && in_array(Yii::$app->language, [DissertationAnnouncement::LANG_RU, DissertationAnnouncement::LANG_KZ, DissertationAnnouncement::LANG_EN], true)) {
            $this->language = Yii::$app->language;
        }

        switch ($this->sort) {
            case 'created_at_asc':
                $query->orderBy(['created_at' => SORT_ASC, 'defense_date' => SORT_DESC]);
                break;
            case 'defense_date_desc':
                $query->orderBy(['defense_date' => SORT_DESC, 'created_at' => SORT_DESC]);
                break;
            case 'defense_date_asc':
                $query->orderBy(['defense_date' => SORT_ASC, 'created_at' => SORT_DESC]);
                break;
            case 'created_at_desc':
            default:
                $query->orderBy(['created_at' => SORT_DESC, 'defense_date' => SORT_DESC]);
                break;
        }

        if ($this->language) {
            $query->andWhere(['language' => $this->language]);
        }

        if ($this->title) {
            $query->andWhere(['like', 'title', $this->title]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => \Yii::$app->params['pageSize'] ?? 10],
        ]);
    }
}
