<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../commands/ScraperController.php';

$reflection = new ReflectionClass(app\commands\ScraperController::class);
$controller = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod('parseDefenseDate');
$method->setAccessible(true);

$tests = [
    'ru month' => [
        'Защита диссертации состоится 2 июля 2026 года в 10:30.',
        '2026-07-02 10:30:00',
    ],
    'kz month' => [
        'Диссертация қорғау 15 шілде 2026 жылы сағат 14:00 өтеді.',
        '2026-07-15 14:00:00',
    ],
    'en month' => [
        'The dissertation defense will be held on 7 August 2026 at 09:15.',
        '2026-08-07 09:15:00',
    ],
    'numeric avoids publication date' => [
        'Дата публикации: 01.06.2026. Защита состоится 02.07.2026 в 16:00.',
        '2026-07-02 16:00:00',
    ],
    'iso avoids publication date' => [
        'Published 2026-01-01. Dissertation defense will be held 2026-03-04 at 11:00.',
        '2026-03-04 11:00:00',
    ],
    'numeric without time' => [
        'Защита диссертации состоится 12.09.2026.',
        '2026-09-12',
    ],
    'ru compact year with time' => [
        'Защита состоится 8 июля 2026г., в 13:00 в аудитории 319.',
        '2026-07-08 13:00:00',
    ],
    'ru compact year with hour suffix' => [
        'Защита состоится: 8 июля 2026г., в 13:00 ч. в НАО.',
        '2026-07-08 13:00:00',
    ],
];

foreach ($tests as $name => [$input, $expected]) {
    $actual = $method->invoke($controller, $input);
    if ($actual !== $expected) {
        fwrite(STDERR, "{$name}: expected {$expected}, got " . var_export($actual, true) . PHP_EOL);
        exit(1);
    }

    echo "{$name}: {$actual}" . PHP_EOL;
}

echo 'OK' . PHP_EOL;
