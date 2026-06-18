<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * AnnouncementDocument model
 *
 * @property int    $id
 * @property int    $announcement_id
 * @property string $document_name
 * @property string|null $display_name
 * @property bool   $is_global
 * @property string $file_path
 * @property string $uploaded_at
 */
class AnnouncementDocument extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%announcement_documents}}';
    }

    public function rules(): array
    {
        return [
            [['announcement_id', 'document_name', 'file_path'], 'required'],
            [['announcement_id'], 'integer'],
            [['is_global'], 'boolean'],
            [['document_name', 'display_name'], 'string', 'max' => 500],
            [['file_path'], 'string', 'max' => 2000],
            [['display_name'], 'default', 'value' => null],
            [['is_global'], 'default', 'value' => true],
            [['announcement_id'], 'exist', 'skipOnError' => true,
                'targetClass' => DissertationAnnouncement::class,
                'targetAttribute' => ['announcement_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => Yii::t('app', 'ID'),
            'announcement_id' => Yii::t('app', 'Объявление'),
            'document_name'   => Yii::t('app', 'Название документа'),
            'display_name'    => Yii::t('app', 'Отображаемое имя'),
            'is_global'       => Yii::t('app', 'Относится к объявлению на любом языке'),
            'file_path'       => Yii::t('app', 'Путь к файлу'),
            'uploaded_at'     => Yii::t('app', 'Загружено'),
        ];
    }

    // ---- Relations ----

    public function getAnnouncement(): \yii\db\ActiveQuery
    {
        return $this->hasOne(DissertationAnnouncement::class, ['id' => 'announcement_id']);
    }

    // ---- Helpers ----

    public function getDownloadUrl(): string
    {
        if (preg_match('#^https?://#i', $this->file_path)) {
            return $this->file_path;
        }

        if (str_starts_with($this->file_path, '/')) {
            return Yii::$app->request->baseUrl . $this->file_path;
        }

        return Yii::$app->request->baseUrl . '/' . ltrim($this->file_path, '/');
    }

    public function getFileExtension(): string
    {
        return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
    }

    public function getFileIcon(): string
    {
        return match ($this->getFileExtension()) {
            'pdf'        => 'bi-file-earmark-pdf text-danger',
            'doc', 'docx'=> 'bi-file-earmark-word text-primary',
            'xls', 'xlsx'=> 'bi-file-earmark-excel text-success',
            'ppt', 'pptx'=> 'bi-file-earmark-ppt text-warning',
            'zip', 'rar' => 'bi-file-earmark-zip text-secondary',
            default      => 'bi-file-earmark text-muted',
        };
    }
}
