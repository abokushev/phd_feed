<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * DissertationAnnouncement model
 *
 * @property int    $id
 * @property string $title
 * @property string $content
 * @property string|null $zoom_link
 * @property string|null $zoom_conference_id
 * @property string|null $zoom_access_code
 * @property string|null $contact_email
 * @property string $status  draft|published|archived
 * @property string $url
 * @property string|null $group_key
 * @property string|null $defense_date
 * @property int    $created_by
 * @property string $created_at
 * @property string|null $updated_at
 * @property string $language  kz|ru|en
 */
class DissertationAnnouncement extends ActiveRecord
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED  = 'archived';

    public const LANG_RU = 'ru';
    public const LANG_KZ = 'kz';
    public const LANG_EN = 'en';

    public static function tableName(): string
    {
        return '{{%dissertation_announcements}}';
    }

    public function rules(): array
    {
        return [
            [['title', 'content', 'url'], 'required'],
            [['title'], 'string', 'max' => 1000],
            [['content'], 'string'],
            [['zoom_link'], 'string', 'max' => 1000],
            [['zoom_conference_id', 'zoom_access_code', 'contact_email'], 'string', 'max' => 255],
            [['contact_email'], 'email'],
            [['url'], 'string', 'max' => 500],
            [['url'], 'unique'],
            [['url'], 'match', 'pattern' => '/^[a-z0-9\-]+$/', 'message' => 'URL может содержать только строчные латинские буквы, цифры и дефис.'],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED]],
            [['language'], 'in', 'range' => [self::LANG_RU, self::LANG_KZ, self::LANG_EN]],
            [['group_key'], 'string', 'max' => 255],
            [['defense_date'], 'safe'],
            [['created_by'], 'integer'],
            [['zoom_link', 'zoom_conference_id', 'zoom_access_code', 'contact_email', 'defense_date', 'group_key', 'updated_at'], 'default', 'value' => null],
            [['status'], 'default', 'value' => self::STATUS_DRAFT],
            [['language'], 'default', 'value' => self::LANG_RU],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                 => Yii::t('app', 'ID'),
            'title'              => Yii::t('app', 'Заголовок'),
            'content'            => Yii::t('app', 'Содержание'),
            'zoom_link'          => Yii::t('app', 'Ссылка Zoom'),
            'zoom_conference_id' => Yii::t('app', 'ID конференции Zoom'),
            'zoom_access_code'   => Yii::t('app', 'Код доступа Zoom'),
            'contact_email'      => Yii::t('app', 'Контактный Email'),
            'status'             => Yii::t('app', 'Статус'),
            'url'                => Yii::t('app', 'URL (slug)'),
            'defense_date'       => Yii::t('app', 'Дата защиты'),
            'created_by'         => Yii::t('app', 'Автор'),
            'created_at'         => Yii::t('app', 'Дата создания'),
            'updated_at'         => Yii::t('app', 'Дата обновления'),
            'language'           => Yii::t('app', 'Язык'),
            'group_key'          => Yii::t('app', 'Группа перевода'),
        ];
    }

    // ---- Relations ----

    public function getAuthor(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getDocuments(): \yii\db\ActiveQuery
    {
        return $this->hasMany(AnnouncementDocument::class, ['announcement_id' => 'id']);
    }

    public function getLinkedAnnouncements(): \yii\db\ActiveQuery
    {
        return $this->hasMany(DissertationAnnouncement::class, ['id' => 'linked_announcement_id'])
            ->viaTable('{{%announcement_links}}', ['announcement_id' => 'id']);
    }

    public function getLinkedByAnnouncements(): \yii\db\ActiveQuery
    {
        return $this->hasMany(DissertationAnnouncement::class, ['id' => 'announcement_id'])
            ->viaTable('{{%announcement_links}}', ['linked_announcement_id' => 'id']);
    }

    public function getAnnouncementLinks(): \yii\db\ActiveQuery
    {
        return $this->hasMany(AnnouncementLink::class, ['announcement_id' => 'id']);
    }

    /**
     * Get IDs of linked announcements for form population.
     */
    public function getLinkedIds(): array
    {
        return $this->linkedAnnouncements
            ? $this->linkedAnnouncements->select('id')->column()
            : [];
    }

    public function getTranslations(): \yii\db\ActiveQuery
    {
        return static::find()
            ->where(['status' => self::STATUS_PUBLISHED])
            ->andWhere(['or',
                ['group_key' => $this->getTranslationGroupKey()],
                ['url' => $this->getTranslationUrl(self::LANG_RU)],
                ['url' => $this->getTranslationUrl(self::LANG_KZ)],
                ['url' => $this->getTranslationUrl(self::LANG_EN)],
            ]);
    }

    public function getTranslation(string $language): ?self
    {
        if ($this->language === $language) {
            return null;
        }

        return static::find()
            ->where(['language' => $language, 'status' => self::STATUS_PUBLISHED])
            ->andWhere(['or',
                ['group_key' => $this->getTranslationGroupKey()],
                ['url' => $this->getTranslationUrl($language)],
            ])
            ->one();
    }

    public function getTranslationGroupKey(): string
    {
        if (!empty($this->group_key)) {
            return $this->group_key;
        }

        return preg_replace('/-(ru|kz|en)$/', '', $this->url);
    }

    public function getTranslationUrl(string $language): string
    {
        $baseUrl = $this->getTranslationGroupKey();
        return $language === self::LANG_RU ? $baseUrl : $baseUrl . '-' . $language;
    }
    // ---- Scopes ----

    public static function published(): \yii\db\ActiveQuery
    {
        return static::find()->where(['status' => self::STATUS_PUBLISHED])->orderBy(['defense_date' => SORT_ASC, 'created_at' => SORT_DESC]);
    }

    // ---- Helpers ----

    public static function getStatusList(): array
    {
        return [
            self::STATUS_DRAFT     => Yii::t('app', 'Черновик'),
            self::STATUS_PUBLISHED => Yii::t('app', 'Опубликовано'),
            self::STATUS_ARCHIVED  => Yii::t('app', 'В архиве'),
        ];
    }

    public static function getLanguageList(): array
    {
        return [
            self::LANG_RU => 'Русский',
            self::LANG_KZ => 'Қазақша',
            self::LANG_EN => 'English',
        ];
    }

    public function getStatusLabel(): string
    {
        return static::getStatusList()[$this->status] ?? $this->status;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'badge bg-success',
            self::STATUS_ARCHIVED  => 'badge bg-secondary',
            default                => 'badge bg-warning text-dark',
        };
    }

    /**
     * Generate SEO-friendly URL slug from title.
     */
    public static function generateUrl(string $title): string
    {
        $title = mb_strtolower($title, 'UTF-8');

        // Cyrillic transliteration table
        $cyr = [
            'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
            'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
            // Kazakh-specific
            'ә','ғ','қ','ң','ө','ұ','ү','һ','і',
        ];
        $lat = [
            'a','b','v','g','d','e','yo','zh','z','i','j','k','l','m','n','o','p',
            'r','s','t','u','f','kh','ts','ch','sh','shch','','y','','e','yu','ya',
            // Kazakh-specific
            'a','g','k','ng','o','u','u','h','i',
        ];

        $slug = str_replace($cyr, $lat, $title);
        // Remove non-alphanumeric except hyphens and spaces
        $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
        // Replace multiple spaces/hyphens with single hyphen
        $slug = preg_replace('/[\s\-]+/', '-', trim($slug));
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $base  = $slug ?: 'announcement';
        $final = $base;
        $i     = 1;
        while (static::find()->where(['url' => $final])->exists()) {
            $final = $base . '-' . $i;
            $i++;
        }

        return $final;
    }

    public function getExcerpt(int $length = 300): string
    {
        $text = strip_tags($this->content);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', trim($text));
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length, 'UTF-8') . '...';
    }

    public function getFormattedDefenseDate(string $format = 'd.m.Y H:i'): string
    {
        if (!$this->defense_date) {
            return '';
        }
        return (new \DateTime($this->defense_date))->format($format);
    }

    public function getFormattedCreatedAt(string $format = 'd.m.Y'): string
    {
        return (new \DateTime($this->created_at))->format($format);
    }
}
