<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\DissertationAnnouncement;
use app\models\AnnouncementDocument;

/**
 * Scrapes doctoral dissertation defense announcements from kstu.kz.
 *
 * Usage:
 *   php yii scraper/run
 *   php yii scraper/run --files=download
 *   php yii scraper/run --pages=3 --files=links
 */
class ScraperController extends Controller
{
    /** Category URL per language (KSTU uses different slugs per language) */
    private const CATEGORY_URLS = [
        'ru' => 'https://www.kstu.kz/category/obyavleniya-o-zashhite-doktorskoj-dissertatsii/',
        'kz' => 'https://www.kstu.kz/category/doktorly-dissertatsiyany-or-au-turaly-habarlandyru/',
        'en' => 'https://www.kstu.kz/category/the-announcement-of-defense-of-doctoral-dissertation/?lang=en',
    ];
    private const BASE_URL = self::CATEGORY_URLS['ru'];
    private const LANG_RU  = 'ru';
    private const LANG_KZ  = 'kz';
    private const LANG_EN  = 'en';
    private const LANGUAGES = [self::LANG_RU, self::LANG_KZ, self::LANG_EN];

    /** 'links' — store PDF URLs as-is; 'download' — fetch and save files locally */
    public string $files = 'links';

    /** Language parameter for KSTU pages. Can be ru, kz, en, or all. */
    public string $lang = self::LANG_RU;

    /** How many category pages to process (0 = all) */
    public int $pages = 0;

    /** Seconds to sleep between HTTP requests (be polite to the server) */
    public int $delay = 2;

    public function options($actionID): array
    {
        return ['files', 'pages', 'delay', 'lang'];
    }

    public function optionAliases(): array
    {
        return ['f' => 'files', 'p' => 'pages', 'd' => 'delay', 'l' => 'lang'];
    }

    // -------------------------------------------------------------------------

    public function actionRun(): int
    {
        if (!in_array($this->files, ['links', 'download'], true)) {
            $this->stderr("Ошибка: параметр --files может быть 'links' или 'download'\n");
            return ExitCode::USAGE;
        }

        $languages = $this->getLanguages();
        if (empty($languages)) {
            $this->stderr("Ошибка: параметр --lang может быть 'ru', 'kz', 'en', 'all' или их комбинация через запятую\n");
            return ExitCode::USAGE;
        }

        $this->stdout("=== Скрапер объявлений КарТУ ===\n");
        $this->stdout("Режим документов : {$this->files}\n");
        $this->stdout("Язык             : " . implode(',', $languages) . "\n");
        $this->stdout("Страниц          : " . ($this->pages ?: 'все') . "\n");
        $this->stdout("Пауза между запросами: {$this->delay} сек.\n\n");

        // Collect announcement URLs from category pages
        $announcementUrls = $this->collectAnnouncementUrls();

        if (empty($announcementUrls)) {
            $this->stdout("Объявлений не найдено. Проверьте доступность сайта.\n");
            return ExitCode::OK;
        }

        $this->stdout("Найдено объявлений: " . count($announcementUrls) . "\n\n");

        $stats = ['new' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        $seenUrls = [];   // all URLs already processed or queued
        $queue    = [];   // pending URLs to process [['url' => ..., 'language' => ..., 'group_key' => ...]]

        // Seed the queue with category-collected URLs
        foreach ($announcementUrls as $item) {
            $queue[] = ['url' => $item['url'], 'language' => $item['language'], 'group_key' => null];
            $seenUrls[$item['url']] = true;
        }

        $totalCount = count($queue);
        $current    = 0;

        while (!empty($queue)) {
            $item = array_shift($queue);
            $current++;
            $url      = $item['url'];
            $language = $item['language'];

            $this->stdout("[{$current}] {$url} ({$language})\n");

            try {
                $result = $this->processAnnouncement($url, $language, $item['group_key']);
                $stats[$result]++;

                // If we successfully fetched the page, look for hreflang links
                // to discover kz/en variants and add them to the queue
                if ($result !== 'skipped') {
                    $this->discoverLanguageVariants($url, $language, $queue, $seenUrls, $languages);
                }

                $this->stdout("  → {$result}\n");
            } catch (\Throwable $e) {
                $stats['errors']++;
                $this->stderr("  → ОШИБКА: " . $e->getMessage() . "\n");
            }

            // Re-count total for progress display
            $totalCount = $current + count($queue);

            if (!empty($queue)) {
                sleep($this->delay);
            }
        }

        $this->stdout("\n=== Готово ===\n");
        $this->stdout("Новых     : {$stats['new']}\n");
        $this->stdout("Обновлено : {$stats['updated']}\n");
        $this->stdout("Без изменений: {$stats['skipped']}\n");
        $this->stdout("Ошибок    : {$stats['errors']}\n");

        return ExitCode::OK;
    }

    /**
     * After processing an announcement, fetch the page again (if needed) and extract
     * hreflang links to discover Kazakh / English variants.  Queue any new URLs.
     */
    private function discoverLanguageVariants(
        string $sourceUrl,
        string $currentLang,
        array &$queue,
        array &$seenUrls,
        array $targetLangs
    ): void {
        // Only discover variants if the user requested more than one language
        if (count($targetLangs) < 2) {
            return;
        }

        // Fetch the page to extract hreflang links
        $fetchUrl = $this->appendLangParam($sourceUrl, $currentLang);
        $html = $this->fetch($fetchUrl, false, $currentLang);
        if ($html === null) {
            return;
        }

        $hreflangLinks = $this->extractHreflangLinks($html);
        if (empty($hreflangLinks)) {
            return;
        }

        // Use the Russian URL as canonical group_key (fall back to current URL slug)
        $canonicalUrl = $hreflangLinks[self::LANG_RU] ?? $sourceUrl;
        $groupKey     = $this->extractGroupKey($canonicalUrl);

        foreach ($targetLangs as $targetLang) {
            if ($targetLang === $currentLang) {
                continue;
            }
            if (isset($hreflangLinks[$targetLang]) && !isset($seenUrls[$hreflangLinks[$targetLang]])) {
                $variantUrl = $hreflangLinks[$targetLang];
                $seenUrls[$variantUrl] = true;
                $queue[] = ['url' => $variantUrl, 'language' => $targetLang, 'group_key' => $groupKey];
                $this->stdout("  → найден перевод ({$targetLang}): {$variantUrl}\n");
            }
        }
    }

    /**
     * Parse <link rel="alternate" hreflang="..." href="..."> from HTML.
     * Returns [lang_code => url] e.g. ['ru' => '...?lang=ru', 'kz' => '...', 'en' => '...?lang=en']
     */
    private function extractHreflangLinks(string $html): array
    {
        $links = [];
        // Match both attribute orders: hreflang first or href first
        if (preg_match_all('/<link[^>]+rel=["\']alternate["\'][^>]*hreflang=["\']([^"\']+)["\'][^>]*href=["\']([^"\']+)["\']/iu', $html, $m)) {
            foreach ($m[1] as $i => $langCode) {
                $links[strtolower(substr($langCode, 0, 2))] = $m[2][$i];
            }
        }
        if (preg_match_all('/<link[^>]+href=["\']([^"\']+)["\'][^>]*hreflang=["\']([^"\']+)["\'][^>]*rel=["\']alternate["\']/iu', $html, $m)) {
            foreach ($m[2] as $i => $langCode) {
                $links[strtolower(substr($langCode, 0, 2))] = $m[1][$i];
            }
        }
        if (preg_match_all('/<link[^>]+hreflang=["\']([^"\']+)["\'][^>]*href=["\']([^"\']+)["\'][^>]*rel=["\']alternate["\']/iu', $html, $m)) {
            foreach ($m[1] as $i => $langCode) {
                $links[strtolower(substr($langCode, 0, 2))] = $m[2][$i];
            }
        }
        if (preg_match_all('/<link[^>]+href=["\']([^"\']+)["\'][^>]*rel=["\']alternate["\'][^>]*hreflang=["\']([^"\']+)["\']/iu', $html, $m)) {
            foreach ($m[2] as $i => $langCode) {
                $links[strtolower(substr($langCode, 0, 2))] = $m[1][$i];
            }
        }

        return $links;
    }

    // -------------------------------------------------------------------------
    // Step 1: collect all announcement URLs from the category pages
    // -------------------------------------------------------------------------

    private function collectAnnouncementUrls(): array
    {
        $allUrls = [];
        $languages = $this->getLanguages();
        
        // Collect URLs separately for each language (they may have different URLs per language)
        foreach ($languages as $language) {
            $this->stdout("\n--- Сбор объявлений для языка: {$language} ---\n");
            $urls = $this->collectAnnouncementUrlsForLanguage($language);
            foreach ($urls as $url) {
                $allUrls[] = ['url' => $url, 'language' => $language];
            }
        }
        
        // Remove duplicates (same URL, keep first language found)
        $seen = [];
        $unique = [];
        foreach ($allUrls as $item) {
            if (!in_array($item['url'], $seen, true)) {
                $unique[] = $item;
                $seen[] = $item['url'];
            }
        }
        
        // Reverse to start from oldest announcements (from last page)
        $unique = array_reverse($unique);
        
        return $unique;
    }

    private function collectAnnouncementUrlsForLanguage(string $language): array
    {
        $urls    = [];
        $page    = 1;
        $maxPage = $this->pages ?: PHP_INT_MAX;
        $categoryUrl = self::CATEGORY_URLS[$language] ?? self::BASE_URL;

        while ($page <= $maxPage) {
            $pageUrl = $categoryUrl . ($page > 1 ? "page/{$page}/" : '') . '?lang=' . $language;
            $this->stdout("  Страница {$page}: {$pageUrl}\n");

            $html = $this->fetch($pageUrl, false, $language);
            if ($html === null) {
                $this->stderr("    Не удалось получить страницу {$page}\n");
                break;
            }

            $found = $this->extractPostUrls($html);
            if (empty($found)) {
                $this->stdout("    Объявлений не найдено — конец списка.\n");
                break;
            }

            $urls = array_merge($urls, $found);
            $this->stdout("    Найдено на странице: " . count($found) . " (итого: " . count($urls) . ")\n");

            // Check if "next page" link exists
            if (!$this->hasNextPage($html)) {
                break;
            }

            $page++;
            sleep($this->delay);
        }

        return $urls;
    }

    private function extractPostUrls(string $html): array
    {
        $dom = $this->loadDom($html);
        if (!$dom) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $urls  = [];

        // WordPress post titles are usually in <h2 class="entry-title"> or <h3> with <a>
        $nodes = $xpath->query('//h2[contains(@class,"entry-title")]/a | //h3[contains(@class,"entry-title")]/a | //article//h2/a | //article//h3/a');
        foreach ($nodes as $node) {
            $href = trim($node->getAttribute('href'));
            if ($href && strpos($href, 'kstu.kz') !== false) {
                $urls[] = $href;
            }
        }

        // Fallback: any link inside .post or article that looks like a post permalink
        if (empty($urls)) {
            $nodes = $xpath->query('//div[contains(@class,"post")]//a[@href] | //article//a[@href]');
            foreach ($nodes as $node) {
                $href = trim($node->getAttribute('href'));
                if ($href && preg_match('#https?://www\.kstu\.kz/[a-z0-9\-]+/?#', $href)) {
                    $urls[] = $href;
                }
            }
        }

        return array_unique($urls);
    }

    private function hasNextPage(string $html): bool
    {
        return (bool) preg_match('/<a[^>]+class=["\'][^"\']*next[^"\']*["\']/', $html);
    }
    private function getLanguages(): array
    {
        $lang = trim(strtolower($this->lang));
        if ($lang === 'all') {
            return self::LANGUAGES;
        }

        $languages = array_filter(array_map('trim', explode(',', $lang)));
        foreach ($languages as $language) {
            if (!in_array($language, self::LANGUAGES, true)) {
                return [];
            }
        }

        return array_values(array_unique($languages));
    }

    private function getCategoryLanguage(): string
    {
        $languages = $this->getLanguages();
        return $languages[0] ?? self::LANG_RU;
    }

    private function appendLangParam(string $url, string $lang): string
    {
        $parts = parse_url($url);
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $query['lang'] = $lang;

        $scheme = $parts['scheme'] ?? 'https';
        $host   = $parts['host'] ?? '';
        $port   = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user   = '';
        if (!empty($parts['user'])) {
            $user = $parts['user'];
            if (!empty($parts['pass'])) {
                $user .= ':' . $parts['pass'];
            }
            $user .= '@';
        }

        $path = $parts['path'] ?? '';
        $url  = $scheme . '://' . $user . $host . $port . $path;
        if ($query) {
            $url .= '?' . http_build_query($query);
        }
        if (!empty($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }

    private function extractGroupKey(string $sourceUrl): string
    {
        $path = trim(parse_url($sourceUrl, PHP_URL_PATH), '/');
        $path = preg_replace('/[^a-z0-9\-]/', '-', strtolower($path));
        $path = preg_replace('/-+/', '-', $path);
        return trim($path, '-');
    }

    // -------------------------------------------------------------------------
    // Step 2: parse a single announcement and save to DB
    // -------------------------------------------------------------------------

    private function processAnnouncement(string $sourceUrl, string $language, ?string $groupKey = null): string
    {
        // For Kazakh pages, don't append lang parameter (they're standalone)
        // Kazakh URLs typically start with "habarlandyru-"
        $isKazakhUrl = (strpos($sourceUrl, 'habarlandyru-') !== false);
        $sourceUrlWithLang = $isKazakhUrl ? $sourceUrl : $this->appendLangParam($sourceUrl, $language);
        $html = $this->fetch($sourceUrlWithLang, false, $language);
        if ($html === null) {
            throw new \RuntimeException("Не удалось загрузить: {$sourceUrlWithLang}");
        }

        $data = $this->parseAnnouncementPage($html, $sourceUrlWithLang);

        // Look up existing record by source URL slug and language
        $slug     = $this->urlToSlug($sourceUrl, $language);
        $model    = DissertationAnnouncement::findOne(['url' => $slug]);
        $isNew    = ($model === null);

        // Use provided group_key (from hreflang linking) or derive from URL
        if ($groupKey === null) {
            // Try to extract from hreflang links on the page itself
            $hreflangLinks = $this->extractHreflangLinks($html);
            $canonicalUrl  = $hreflangLinks[self::LANG_RU] ?? $sourceUrl;
            $groupKey      = $this->extractGroupKey($canonicalUrl);
        }

        if ($isNew) {
            $model = new DissertationAnnouncement();
            $model->url        = $slug;
            $model->group_key  = $groupKey;
            $model->status     = DissertationAnnouncement::STATUS_PUBLISHED;
            $model->language   = $language;
            $model->created_by = 1; // system/admin user
        } else {
            $model->language  = $language;
        }

        // Track whether group_key needs updating (for language switching)
        $oldGroupKey = $model->group_key ?? null;
        if ($oldGroupKey !== $groupKey) {
            $model->group_key = $groupKey;
        }
        $groupKeyChanged = !$isNew && $oldGroupKey !== $groupKey;

        // Detect changes for existing records
        $contentHash = md5($data['title'] . $data['content']);
        $storedHash  = !$isNew ? md5($model->title . $model->content) : null;
        $createdAtChanged = !$isNew && !empty($data['created_at']) && $data['created_at'] !== $model->created_at;
        $dateChanged = !$isNew && $data['defense_date'] !== null && $data['defense_date'] !== $model->defense_date;

        if (!$isNew && $contentHash === $storedHash && !$createdAtChanged && !$groupKeyChanged && !$dateChanged) {
            return 'skipped';
        }

        $model->title        = $data['title'];
        $model->content      = $data['content'];
        $model->defense_date = $data['defense_date'];
        $model->contact_email = $data['contact_email'];
        $model->zoom_link    = $data['zoom_link'];
        $model->zoom_conference_id = $data['zoom_conference_id'];
        $model->zoom_access_code   = $data['zoom_access_code'];

        if (!empty($data['created_at'])) {
            $model->created_at = $data['created_at'];
        }

        if (!$model->save()) {
            throw new \RuntimeException('Ошибка сохранения: ' . json_encode($model->errors, JSON_UNESCAPED_UNICODE));
        }

        // Documents
        $this->saveDocuments($model, $data['documents']);

        return $isNew ? 'new' : 'updated';
    }

    private function parseAnnouncementPage(string $html, string $sourceUrl): array
    {
        $dom = $this->loadDom($html);

        $data = [
            'title'            => '',
            'content'          => '',
            'defense_date'     => null,
            'created_at'       => null,
            'contact_email'    => null,
            'zoom_link'        => null,
            'zoom_conference_id' => null,
            'zoom_access_code' => null,
            'documents'        => [],
        ];

        if (!$dom) {
            return $data;
        }

        $xpath = new \DOMXPath($dom);

        // Title
        $titleNode = $xpath->query('//h1[contains(@class,"entry-title")] | //h1[@class] | //h1')->item(0);
        if ($titleNode) {
            $data['title'] = trim($titleNode->textContent);
        }

        // Main content area (WordPress typical structure)
        $contentNode = $xpath->query('//div[contains(@class,"entry-content")] | //div[contains(@class,"post-content")]')->item(0);
        if ($contentNode) {
            // Extract content as HTML for rich text editing in admin panel
            $rawHtml = $this->extractContentBeforeZoom($contentNode);
            if ($rawHtml === '') {
                $rawHtml = $this->innerHtml($contentNode);
            }
            $data['content'] = $rawHtml;

            // Plain text version for email/date extraction
            $text = trim(html_entity_decode(strip_tags($rawHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $text = preg_replace('/\s+/', ' ', $text);

            $data['created_at'] = $this->parseSourceCreatedAt($xpath, $contentNode->textContent . ' ' . $data['title']);

            // Use the FULL content text (not truncated) for date parsing,
            // because the date may appear after the Zoom section
            $fullText = trim(html_entity_decode(strip_tags($contentNode->textContent), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $fullText = preg_replace('/\s+/', ' ', $fullText);

            // Defense date: look for patterns like "2 июля 2026" or "02.07.2026"
            if (preg_match('/(\d{1,2})\s+(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)\s+(\d{4})/ui', $text, $m)) {
                $months = [
                    'января'=>'01','февраля'=>'02','марта'=>'03','апреля'=>'04','мая'=>'05','июня'=>'06',
                    'июля'=>'07','августа'=>'08','сентября'=>'09','октября'=>'10','ноября'=>'11','декабря'=>'12',
                ];
                $month = $months[mb_strtolower($m[2])] ?? '01';
                $data['defense_date'] = $m[3] . '-' . $month . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);

                // Try to find time nearby
                if (preg_match('/в\s+(\d{1,2})[:\.](\d{2})/u', $text, $tm)) {
                    $data['defense_date'] .= ' ' . str_pad($tm[1], 2, '0', STR_PAD_LEFT) . ':' . $tm[2] . ':00';
                }
            } elseif (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $text, $m)) {
                $data['defense_date'] = $m[3] . '-' . $m[2] . '-' . $m[1];
            }

            $data['defense_date'] = $this->parseDefenseDate($fullText);

            // Contact email
            if (preg_match('/[\w.\-]+@[\w.\-]+\.[a-z]{2,}/i', $text, $em)) {
                $data['contact_email'] = $em[0];
            }

            // Zoom link
            if (preg_match('#https?://[\w.]*zoom\.us/[^\s"<>]+#i', $text, $zm)) {
                $data['zoom_link'] = $zm[0];
            }

            // Zoom conference ID (digits with spaces)
            if (preg_match('/(?:ID|конференции|meeting)[^\d]*(\d[\d\s]{6,14}\d)/ui', $text, $zm)) {
                $data['zoom_conference_id'] = preg_replace('/\s+/', ' ', trim($zm[1]));
            }

            // Zoom access code / password
            if (preg_match('/(?:пароль|код доступа|passcode|password)[^\d]*(\d{4,10})/ui', $text, $zm)) {
                $data['zoom_access_code'] = trim($zm[1]);
            }

            // Documents: PDF/DOC links
            $linkNodes = $xpath->query('.//a[@href]', $contentNode);
            foreach ($linkNodes as $link) {
                $href = trim($link->getAttribute('href'));
                $name = trim($link->textContent) ?: basename($href);
                if ($href && preg_match('/\.(pdf|doc|docx|xls|xlsx|ppt|pptx|zip|rar)(\?.*)?$/i', $href)) {
                    $data['documents'][] = ['name' => $name, 'url' => $href];
                }
            }
        }

        if (empty($data['title'])) {
            $data['title'] = $this->urlToSlug($sourceUrl);
        }

        return $data;
    }

    private function parseDefenseDate(string $text): ?string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return null;
        }

        $candidates = [];
        $monthPattern = implode('|', array_map('preg_quote', array_keys($this->monthMap()), array_fill(0, count($this->monthMap()), '/')));

        // Pattern 1: "day month year" — Russian "04 июля 2026", English "4 July 2026"
        if (preg_match_all('/(\d{1,2})\s+(' . $monthPattern . ')\s*,?\s*(\d{4})/ui', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $monthName = mb_strtolower($match[2][0], 'UTF-8');
                $candidates[] = [
                    'day' => (int)$match[1][0],
                    'month' => $this->monthMap()[$monthName] ?? null,
                    'year' => (int)$match[3][0],
                    'offset' => $match[0][1],
                    'length' => strlen($match[0][0]),
                    'text' => $match[0][0],
                ];
            }
        }

        // Pattern 2: "month day, year" — English "July 4, 2026"
        if (preg_match_all('/(' . $monthPattern . ')\s+(\d{1,2}),?\s*(\d{4})/ui', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $monthName = mb_strtolower($match[1][0], 'UTF-8');
                $candidates[] = [
                    'day' => (int)$match[2][0],
                    'month' => $this->monthMap()[$monthName] ?? null,
                    'year' => (int)$match[3][0],
                    'offset' => $match[0][1],
                    'length' => strlen($match[0][0]),
                    'text' => $match[0][0],
                ];
            }
        }

        // Pattern 3: "year ж(ылғы) day month(case suffix)" — Kazakh "2026 жылғы 28 мамырда"
        if (preg_match_all('/(\d{4})\s*(?:жыл?ғ?ы?|ж\.?)\s*(\d{1,2})\s+(\S+)/ui', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $monthWord = mb_strtolower($match[3][0], 'UTF-8');
                // Strip punctuation and common Kazakh case suffixes to get base month name
                $monthWord = preg_replace('/[,;:.]+$/u', '', $monthWord);
                $baseMonth = preg_replace('/(да|де|дан|ден|ға|ге|ды|ді|ны|ні|ғы|гі)$/u', '', $monthWord);
                $monthNum = $this->monthMap()[$baseMonth] ?? null;
                if ($monthNum !== null) {
                    $candidates[] = [
                        'day' => (int)$match[2][0],
                        'month' => $monthNum,
                        'year' => (int)$match[1][0],
                        'offset' => $match[0][1],
                        'length' => strlen($match[0][0]),
                        'text' => $match[0][0],
                    ];
                }
            }
        }

        // Pattern 4: "dd.mm.yyyy" numeric dates
        if (preg_match_all('/(\d{1,2})[\.\/-](\d{1,2})[\.\/-](\d{4})/u', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $candidates[] = [
                    'day' => (int)$match[1][0],
                    'month' => (int)$match[2][0],
                    'year' => (int)$match[3][0],
                    'offset' => $match[0][1],
                    'length' => strlen($match[0][0]),
                    'text' => $match[0][0],
                ];
            }
        }

        if (preg_match_all('/(\d{4})-(\d{1,2})-(\d{1,2})/u', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $candidates[] = [
                    'day' => (int)$match[3][0],
                    'month' => (int)$match[2][0],
                    'year' => (int)$match[1][0],
                    'offset' => $match[0][1],
                    'length' => strlen($match[0][0]),
                    'text' => $match[0][0],
                ];
            }
        }

        $candidates = array_values(array_filter($candidates, static function (array $candidate): bool {
            return $candidate['month'] >= 1 && $candidate['month'] <= 12
                && $candidate['day'] >= 1 && $candidate['day'] <= 31
                && $candidate['year'] >= 2000 && $candidate['year'] <= 2100;
        }));

        if (empty($candidates)) {
            return null;
        }

        foreach ($candidates as &$candidate) {
            $candidate['score'] = $this->scoreDefenseDateCandidate($text, $candidate);
        }
        unset($candidate);

        usort($candidates, static function (array $a, array $b): int {
            return ($b['score'] <=> $a['score']) ?: ($a['offset'] <=> $b['offset']);
        });

        $best = $candidates[0];
        $time = $this->findTimeNearDate($text, $best['offset'], $best['length']);

        return sprintf(
            '%04d-%02d-%02d%s',
            $best['year'],
            $best['month'],
            $best['day'],
            $time ? ' ' . $time : ''
        );
    }

    private function monthMap(): array
    {
        return [
            'января' => 1, 'январь' => 1, 'jan' => 1, 'january' => 1, 'қаңтар' => 1,
            'февраля' => 2, 'февраль' => 2, 'feb' => 2, 'february' => 2, 'ақпан' => 2,
            'марта' => 3, 'март' => 3, 'mar' => 3, 'march' => 3, 'наурыз' => 3,
            'апреля' => 4, 'апрель' => 4, 'apr' => 4, 'april' => 4, 'сәуір' => 4,
            'мая' => 5, 'май' => 5, 'may' => 5, 'мамыр' => 5,
            'июня' => 6, 'июнь' => 6, 'jun' => 6, 'june' => 6, 'маусым' => 6,
            'июля' => 7, 'июль' => 7, 'jul' => 7, 'july' => 7, 'шілде' => 7,
            'августа' => 8, 'август' => 8, 'aug' => 8, 'august' => 8, 'тамыз' => 8,
            'сентября' => 9, 'сентябрь' => 9, 'sep' => 9, 'sept' => 9, 'september' => 9, 'қыркүйек' => 9,
            'октября' => 10, 'октябрь' => 10, 'oct' => 10, 'october' => 10, 'қазан' => 10,
            'ноября' => 11, 'ноябрь' => 11, 'nov' => 11, 'november' => 11, 'қараша' => 11,
            'декабря' => 12, 'декабрь' => 12, 'dec' => 12, 'december' => 12, 'желтоқсан' => 12,
        ];
    }

    private function scoreDefenseDateCandidate(string $text, array $candidate): int
    {
        $before = mb_strcut($text, max(0, $candidate['offset'] - 180), min(180, $candidate['offset']), 'UTF-8');
        $before = preg_replace('/^.*[\.;]/us', '', $before);
        $after = mb_strcut($text, $candidate['offset'] + $candidate['length'], 180, 'UTF-8');
        $after = preg_replace('/[\.;].*$/us', '', $after);
        $window = mb_strtolower($before . ' ' . $candidate['text'] . ' ' . $after, 'UTF-8');
        $score = 0;

        if (preg_match('/защит|диссертац|состо[ия]т|өтеді|қорға|диссертац|defen[cs]e|dissertation|will be held|phd/ui', $window)) {
            $score += 10;
        }

        if (preg_match('/опублик|дата публикации|published|posted|жариялан/ui', $window)) {
            $score -= 8;
        }

        if ($this->findTimeNearDate($text, $candidate['offset'], $candidate['length']) !== null) {
            $score += 2;
        }

        return $score;
    }

    private function findTimeNearDate(string $text, int $offset, int $length): ?string
    {
        $after = mb_strcut($text, $offset + $length, 180, 'UTF-8');
        if (preg_match('/^\s*[\.;]/u', $after)) {
            $after = '';
        }

        $monthPattern = implode('|', array_map('preg_quote', array_keys($this->monthMap()), array_fill(0, count($this->monthMap()), '/')));
        if (preg_match('/(?:\d{1,2}[\.\/-]\d{1,2}[\.\/-]\d{4})|(?:\d{4}-\d{1,2}-\d{1,2})|(?:\d{1,2}\s+(?:' . $monthPattern . ')\s*,?\s*\d{4})/ui', $after, $nextDate, PREG_OFFSET_CAPTURE)) {
            $after = mb_strcut($after, 0, $nextDate[0][1], 'UTF-8');
        }

        $window = $after;

        if (preg_match('/(?<![\d\.\/-])(?:в|сағат|сағ\.?|at|time|начало|басталуы)?\s*(\d{1,2}):(\d{2})(?!\d)/ui', $window, $match)
            || preg_match('/(?<![\d\.\/-])(?:в|сағат|сағ\.?|at|time|начало|басталуы)\s+(\d{1,2})\.(\d{2})(?!\.\d)/ui', $window, $match)) {
            $hour = (int)$match[1];
            $minute = (int)$match[2];
            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                return sprintf('%02d:%02d:00', $hour, $minute);
            }
        }

        return null;
    }

    private function extractContentBeforeZoom(\DOMNode $contentNode): string
    {
        $html = '';
        foreach ($contentNode->childNodes as $child) {
            if ($this->nodeContainsZoomInfo($child)) {
                break;
            }
            $html .= $contentNode->ownerDocument->saveHTML($child);
        }
        return trim($html);
    }

    private function nodeContainsZoomInfo(\DOMNode $node): bool
    {
        $text = mb_strtolower($node->textContent, 'UTF-8');
        if (str_contains($text, 'zoom') || str_contains($text, 'id конференции') || str_contains($text, 'код доступа') || str_contains($text, 'пароль') || str_contains($text, 'passcode') || str_contains($text, 'password')) {
            return true;
        }

        if ($node instanceof \DOMElement && $node->hasAttribute('href')) {
            $href = trim($node->getAttribute('href'));
            if ($href && preg_match('#https?://[\w.]*zoom\.us/#i', $href)) {
                return true;
            }
        }

        return false;
    }

    private function parseSourceCreatedAt(\DOMXPath $xpath, string $text): ?string
    {
        $candidates = [];

        $metaNodes = $xpath->query('//meta[@property="article:published_time" or @name="article:published_time" or @name="date" or @name="pubdate"]');
        foreach ($metaNodes as $node) {
            if ($node instanceof \DOMElement && $node->hasAttribute('content')) {
                $candidates[] = trim($node->getAttribute('content'));
            }
        }

        $timeNodes = $xpath->query('//time | //span[contains(@class,"published") or contains(@class,"entry-date") or contains(@class,"post-date")] | //div[contains(@class,"posted-on")]');
        foreach ($timeNodes as $node) {
            if ($node instanceof \DOMElement && $node->hasAttribute('datetime')) {
                $candidates[] = trim($node->getAttribute('datetime'));
            }
            $candidates[] = trim($node->textContent);
        }

        // Look for explicit publish date labels in the text
        if (preg_match('/(Опубликовано|Опубликовано:|Дата публикации|Дата:)[^\n]*?(\d{1,2}\.\d{1,2}\.\d{4}(?:\s+\d{1,2}:\d{2})?)/ui', $text, $m)) {
            $candidates[] = $m[2];
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if (!$candidate) {
                continue;
            }
            if ($date = $this->normalizeDateString($candidate)) {
                return $date;
            }
        }

        return null;
    }

    private function normalizeDateString(string $value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value));

        // ISO / datetime formats
        if (preg_match('/^(\d{4}-\d{2}-\d{2})(?:[T\s](\d{2}:\d{2}(?::\d{2})?))?/u', $value, $m)) {
            $date = $m[1] . ' ' . ($m[2] ?? '00:00:00');
            return (new \DateTime($date))->format('Y-m-d H:i:s');
        }

        // Russian month names
        if (preg_match('/^(\d{1,2})\s+(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)\s+(\d{4})(?:\s+в?\s*(\d{1,2})[:\.](\d{2}))?/ui', $value, $m)) {
            $months = [
                'января'=>'01','февраля'=>'02','марта'=>'03','апреля'=>'04','мая'=>'05','июня'=>'06',
                'июля'=>'07','августа'=>'08','сентября'=>'09','октября'=>'10','ноября'=>'11','декабря'=>'12',
            ];
            $month = $months[mb_strtolower($m[2], 'UTF-8')] ?? '01';
            $hour = isset($m[4]) ? str_pad($m[4], 2, '0', STR_PAD_LEFT) : '00';
            $minute = isset($m[5]) ? $m[5] : '00';
            return sprintf('%04d-%02d-%02d %02d:%02d:00', $m[3], $month, $m[1], $hour, $minute);
        }

        // Numeric dates like 02.07.2026 or 02.07.2026 14:00
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})(?:\s+(\d{1,2}):?(\d{2}))?/u', $value, $m)) {
            $hour = isset($m[4]) ? str_pad($m[4], 2, '0', STR_PAD_LEFT) : '00';
            $minute = isset($m[5]) ? $m[5] : '00';
            return sprintf('%04d-%02d-%02d %02d:%02d:00', $m[3], $m[2], $m[1], $hour, $minute);
        }

        // Try PHP strtotime fallback
        try {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private function saveDocuments(DissertationAnnouncement $model, array $docs): void
    {
        if (empty($docs)) {
            return;
        }

        // Get existing document URLs to avoid duplicates
        $existing = [];
        foreach ($model->documents as $d) {
            $existing[] = $d->file_path;
        }

        foreach ($docs as $doc) {
            $filePath = $this->files === 'download'
                ? $this->downloadFile($doc['url'], $model->id)
                : $doc['url']; // store original URL as path

            if ($filePath === null) {
                $this->stderr("    Не удалось скачать: {$doc['url']}\n");
                continue;
            }

            if (in_array($filePath, $existing, true)) {
                continue; // already saved
            }

            $record = new AnnouncementDocument();
            $record->announcement_id = $model->id;
            $record->document_name   = $doc['name'] ?: basename($doc['url']);
            $record->file_path       = $filePath;
            $record->save();
        }
    }

    private function downloadFile(string $url, int $announcementId): ?string
    {
        $uploadDir = Yii::getAlias('@webroot') . '/uploads/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $ext      = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'pdf';
        $filename = 'scraped_' . $announcementId . '_' . time() . '_' . mt_rand(100, 999) . '.' . strtolower($ext);
        $dest     = $uploadDir . $filename;

        $content = $this->fetch($url, true);
        if ($content === null) {
            return null;
        }

        if (file_put_contents($dest, $content) === false) {
            return null;
        }

        return 'uploads/documents/' . $filename;
    }

    // -------------------------------------------------------------------------
    // HTTP / DOM helpers
    // -------------------------------------------------------------------------

    private function fetch(string $url, bool $binary = false, ?string $lang = null): ?string
    {
        $language = $lang ?? $this->lang;
        $accept   = match ($language) {
            self::LANG_EN => 'en-US,en;q=0.9',
            self::LANG_KZ => 'kk-KZ,kk;q=0.9,ru;q=0.8',
            default      => 'ru-RU,ru;q=0.9',
        };

        $ctx = stream_context_create([
            'http' => [
                'timeout'          => 30,
                'follow_location'  => 1,
                'max_redirects'    => 5,
                'user_agent'       => 'Mozilla/5.0 (compatible; KarTU-Scraper/1.0)',
                'header'           => "Accept-Language: {$accept}\r\n",
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $result = @file_get_contents($url, false, $ctx);
        return ($result === false) ? null : $result;
    }

    private function loadDom(string $html): ?\DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        return $loaded ? $dom : null;
    }

    private function innerHtml(\DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }
        return trim($html);
    }

    private function urlToSlug(string $url, string $language = self::LANG_RU): string
    {
        // Extract path segment: https://www.kstu.kz/my-post/ → my-post
        $path = trim(parse_url($url, PHP_URL_PATH), '/');
        $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($path));
        $slug = preg_replace('/-+/', '-', trim($slug, '-'));
        $slug = $slug ?: 'announcement';

        if ($language !== self::LANG_RU) {
            $slug .= '-' . $language;
        }

        return $slug;
    }
}
