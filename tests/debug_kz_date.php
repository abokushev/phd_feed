<?php
$url = 'https://www.kstu.kz/amangeldieva-gulmadina-bulatovnany-doktorly-dissertatsiyasyn-au-turaly/?lang=kz';
$html = file_get_contents($url);
if (!$html) { echo "FAILED\n"; exit(1); }

$dom = new DOMDocument('1.0', 'UTF-8');
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

$contentNode = $xpath->query('//div[contains(@class,"entry-content")]')->item(0);
if (!$contentNode) { echo "No entry-content\n"; exit(1); }

$fullText = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($contentNode->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));

echo "=== Full text (last 500 chars) ===\n";
echo mb_substr($fullText, -500, 500, 'UTF-8') . "\n\n";

echo "=== Searching for year patterns ===\n";
if (preg_match_all('/(\d{4})\s*ж\.?\s*(\d{1,2})\s+(қаңтар|ақпан|наурыз|сәуір|мамыр|маусым|шілде|тамыз|қыркүйек|қазан|қараша|желтоқсан)/ui', $fullText, $m)) {
    echo "KZ year-first pattern found: " . implode(', ', $m[0]) . "\n";
} else {
    echo "KZ year-first pattern NOT found\n";
}

// Also search for any text around 2026
if (preg_match_all('/.{0,50}2026.{0,50}/', $fullText, $m)) {
    echo "\nText around 2026:\n";
    foreach ($m[0] as $match) {
        echo "  ...{$match}...\n";
    }
}