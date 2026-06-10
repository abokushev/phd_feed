<?php
$url = 'https://www.kstu.kz/obyavlenie-o-zashhite-dissertatsionnoj-raboty-buzyakova-r-r/?lang=ru';
$html = file_get_contents($url);
if ($html === false) { echo "FAILED\n"; exit(1); }

$dom = new DOMDocument('1.0', 'UTF-8');
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

$contentNode = $xpath->query('//div[contains(@class,"entry-content")]')->item(0);
if (!$contentNode) { echo "No entry-content found\n"; exit(1); }

$rawHtml = $dom->saveHTML($contentNode);
$plainText = strip_tags($rawHtml);
$plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$plainText = trim(preg_replace('/\s+/', ' ', $plainText));

echo "=== RAW HTML (first 500 chars) ===\n";
echo substr($rawHtml, 0, 500) . "\n\n";

echo "=== PLAIN TEXT ===\n";
echo $plainText . "\n\n";

echo "=== SEARCHING FOR DATES ===\n";
// Try month name patterns
$patterns = [
    '/(\d{1,2})\s+(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)\s*,?\s*(\d{4})/ui',
    '/(\d{1,2})[\.\/-](\d{1,2})[\.\/-](\d{4})/u',
    '/(\d{4})-(\d{1,2})-(\d{1,2})/u',
];

foreach ($patterns as $i => $pat) {
    if (preg_match_all($pat, $plainText, $m)) {
        echo "Pattern $i matched: " . implode(', ', $m[0]) . "\n";
    }
}

// Also check the full HTML for date-related content
echo "\n=== Full text around '2026' ===\n";
if (preg_match_all('/.{0,80}2026.{0,80}/', $plainText, $m)) {
    foreach ($m[0] as $match) {
        echo "  ...{$match}...\n";
    }
}

echo "\n=== Kazakh version ===\n";
$urlKz = 'https://www.kstu.kz/r-r-buzyakovty-dissertatsiyaly-zh-mysyny-auy-turaly-habarlandyru/?lang=kz';
$htmlKz = file_get_contents($urlKz);
if ($htmlKz) {
    $domKz = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $domKz->loadHTML('<?xml encoding="UTF-8">' . $htmlKz, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    $xpathKz = new DOMXPath($domKz);
    $cnKz = $xpathKz->query('//div[contains(@class,"entry-content")]')->item(0);
    if ($cnKz) {
        $textKz = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($domKz->saveHTML($cnKz), ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        echo "Plain text: " . mb_substr($textKz, 0, 500, 'UTF-8') . "\n\n";
        // Search for dates
        if (preg_match_all('/(\d{1,2})\s+(қаңтар|ақпан|наурыз|сәуір|мамыр|маусым|шілде|тамыз|қыркүйек|қазан|қараша|желтоқсан)\s*,?\s*(\d{4})/ui', $textKz, $m)) {
            echo "KZ dates found: " . implode(', ', $m[0]) . "\n";
        }
        if (preg_match_all('/.{0,80}\d{4}.{0,80}/', $textKz, $m)) {
            echo "Text around years:\n";
            foreach (array_slice($m[0], 0, 5) as $match) {
                echo "  ...{$match}...\n";
            }
        }
    }
}

echo "\n=== English version ===\n";
$urlEn = 'https://www.kstu.kz/announcement-defense-r-r-buzyakovs-dissertation/?lang=en';
$htmlEn = file_get_contents($urlEn);
if ($htmlEn) {
    $domEn = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $domEn->loadHTML('<?xml encoding="UTF-8">' . $htmlEn, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();
    $xpathEn = new DOMXPath($domEn);
    $cnEn = $xpathEn->query('//div[contains(@class,"entry-content")]')->item(0);
    if ($cnEn) {
        $textEn = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($domEn->saveHTML($cnEn), ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        echo "Plain text: " . mb_substr($textEn, 0, 500, 'UTF-8') . "\n\n";
        if (preg_match_all('/(\d{1,2})\s+(january|february|march|april|may|june|july|august|september|october|november|december)\s*,?\s*(\d{4})/ui', $textEn, $m)) {
            echo "EN dates found: " . implode(', ', $m[0]) . "\n";
        }
        if (preg_match_all('/.{0,80}\d{4}.{0,80}/', $textEn, $m)) {
            echo "Text around years:\n";
            foreach (array_slice($m[0], 0, 5) as $match) {
                echo "  ...{$match}...\n";
            }
        }
    }
}