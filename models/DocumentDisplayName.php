<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * User-managed document display-name option.
 *
 * @property int    $id
 * @property int    $created_by
 * @property string $language
 * @property string $name
 * @property int    $sort_order
 * @property string $created_at
 */
class DocumentDisplayName extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%document_display_names}}';
    }

    public function rules(): array
    {
        return [
            [['created_by', 'language', 'name'], 'required'],
            [['created_by', 'sort_order'], 'integer'],
            [['name'], 'trim'],
            [['name'], 'string', 'max' => 500],
            [['language'], 'in', 'range' => [
                DissertationAnnouncement::LANG_RU,
                DissertationAnnouncement::LANG_KZ,
                DissertationAnnouncement::LANG_EN,
            ]],
            [['sort_order'], 'default', 'value' => 0],
            [['created_by', 'language', 'name'], 'unique', 'targetAttribute' => ['created_by', 'language', 'name']],
            [['created_by'], 'exist', 'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'created_by' => Yii::t('app', 'Пользователь'),
            'language' => Yii::t('app', 'Язык'),
            'name' => Yii::t('app', 'Отображаемое имя'),
            'sort_order' => Yii::t('app', 'Порядок'),
            'created_at' => Yii::t('app', 'Создано'),
        ];
    }

    public static function getDefaults(): array
    {
        return [
            DissertationAnnouncement::LANG_RU => [
                'Аннотация',
                'Диссертационная работа',
                'Заключение этической комиссии',
                'Список научных трудов и публикаций',
            ],
            DissertationAnnouncement::LANG_KZ => [
                'Аннотация',
                'Диссертациялық жұмыс',
                'Этикалық комиссияның қорытындысы',
                'Ғылыми еңбектер мен жарияланымдар тізімі',
            ],
            DissertationAnnouncement::LANG_EN => [
                'Abstract',
                'Dissertation work',
                'Ethics committee conclusion',
                'List of scientific works and publications',
            ],
        ];
    }

    public static function getList(int $userId, string $language): array
    {
        return static::find()
            ->where(['created_by' => $userId, 'language' => $language])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->select(['name', 'id'])
            ->indexBy('id')
            ->column();
    }

    public function getUser(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}
