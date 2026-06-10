<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../commands/ScraperController.php';

$url = $argv[1] ?? null;
if (!$url) {
    fwrite(STDERR, "Usage: php tests/scraper_parse_url.php <url>\n");
    exit(1);
}

$reflection = new ReflectionClass(app\commands\ScraperController::class);
$controller = $reflection->newInstanceWithoutConstructor();

$fetch = $reflection->getMethod('fetch');
$fetch->setAccessible(true);
$html = $fetch->invoke($controller, $url, false, 'ru');
if ($html === null) {
    fwrite(STDERR, "Failed to fetch {$url}\n");
    exit(1);
}

$parse = $reflection->getMethod('parseAnnouncementPage');
$parse->setAccessible(true);
$data = $parse->invoke($controller, $html, $url);
$text = trim(preg_replace('/\s+/u', ' ', strip_tags($data['content'])));
$position = mb_strpos($text, '2026', 0, 'UTF-8');
$aroundDate = $position === false ? null : mb_substr($text, max(0, $position - 140), 340, 'UTF-8');
$parseDate = $reflection->getMethod('parseDefenseDate');
$parseDate->setAccessible(true);
$findTime = $reflection->getMethod('findTimeNearDate');
$findTime->setAccessible(true);
$bytePosition = strpos($text, '2026');
$after2026 = $bytePosition === false ? null : substr($text, $bytePosition + 4, 80);
$simpleTimeMatch = $after2026 !== null && preg_match('/(\d{1,2}):(\d{2})/u', $after2026, $simpleMatch) ? $simpleMatch[0] : null;

echo json_encode([
    'title' => $data['title'],
    'defense_date' => $data['defense_date'],
    'full_text_defense_date' => $parseDate->invoke($controller, $text),
    'around_date_defense_date' => $aroundDate === null ? null : $parseDate->invoke($controller, $aroundDate),
    'time_after_2026' => $bytePosition === false ? null : $findTime->invoke($controller, $text, $bytePosition, 4),
    'simple_time_after_2026' => $simpleTimeMatch,
    'after_2026' => $after2026,
    'created_at' => $data['created_at'],
    'around_date' => $aroundDate,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL;
