<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * AnnouncementLink model — junction table for linking announcements together.
 *
 * @property int    $id
 * @property int    $announcement_id
 * @property int    $linked_announcement_id
 * @property string $created_at
 */
class AnnouncementLink extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%announcement_links}}';
    }

    public function rules(): array
    {
        return [
            [['announcement_id', 'linked_announcement_id'], 'required'],
            [['announcement_id', 'linked_announcement_id'], 'integer'],
            [['announcement_id'], 'exist', 'skipOnError' => true,
                'targetClass' => DissertationAnnouncement::class,
                'targetAttribute' => ['announcement_id' => 'id']],
            [['linked_announcement_id'], 'exist', 'skipOnError' => true,
                'targetClass' => DissertationAnnouncement::class,
                'targetAttribute' => ['linked_announcement_id' => 'id']],
            [['announcement_id', 'linked_announcement_id'], 'unique',
                'targetAttribute' => ['announcement_id', 'linked_announcement_id']],
            [['announcement_id'], 'compare', 'compareAttribute' => 'linked_announcement_id',
                'operator' => '!=', 'message' => 'Нельзя связать объявление с самим собой.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                     => Yii::t('app', 'ID'),
            'announcement_id'        => Yii::t('app', 'Объявление'),
            'linked_announcement_id' => Yii::t('app', 'Связанное объявление'),
            'created_at'             => Yii::t('app', 'Дата создания'),
        ];
    }

    // ---- Relations ----

    public function getAnnouncement(): \yii\db\ActiveQuery
    {
        return $this->hasOne(DissertationAnnouncement::class, ['id' => 'announcement_id']);
    }

    public function getLinkedAnnouncement(): \yii\db\ActiveQuery
    {
        return $this->hasOne(DissertationAnnouncement::class, ['id' => 'linked_announcement_id']);
    }
}