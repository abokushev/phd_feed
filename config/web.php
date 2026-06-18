<?php

$params = require __DIR__ . '/params.php';
$db     = require __DIR__ . '/db.php';

$config = [
    'id' => 'phd-feed',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower'   => '@vendor/bower-asset',
        '@npm'     => '@vendor/npm-asset',
        '@webroot' => '@app/web',
        '@web'     => '',
    ],
    'language' => 'ru',
    'sourceLanguage' => 'ru',
    'timeZone' => 'Asia/Almaty',
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'pHdF33d-S3cr3tK3y-KarTU-2024',
            'baseUrl' => '',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['/auth/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'announcement/index',
                'announcement' => 'announcement/index',
                'announcement/view/<url:[a-z0-9\-]+>' => 'announcement/view',
                'auth/login'  => 'auth/login',
                'auth/logout' => 'auth/logout',
                'manage'               => 'manage/index',
                'manage/create'        => 'manage/create',
                'manage/update/<id:\d+>' => 'manage/update',
                'manage/delete/<id:\d+>' => 'manage/delete',
                'manage/delete-document/<id:\d+>' => 'manage/delete-document',
                'manage/upload-document' => 'manage/upload-document',
                'manage/document-display-name/add' => 'manage/add-document-display-name',
                'manage/document-display-name/delete/<id:\d+>' => 'manage/delete-document-display-name',
                'manage/switch-language/<id:\d+>/<language:(ru|kz|en)>' => 'manage/switch-language',
            ],
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'ru',
                    'fileMap' => [
                        'app' => 'app.php',
                    ],
                ],
            ],
        ],
        'session' => [
            'class' => 'yii\web\Session',
            'name' => 'phd_feed_session',
        ],
    ],
    'params' => $params,
    'on beforeRequest' => function ($event) {
        $lang = Yii::$app->request->get('lang');
        $allowedLangs = ['ru', 'kz', 'en'];
        if ($lang && in_array($lang, $allowedLangs)) {
            Yii::$app->language = $lang;
            Yii::$app->session->set('lang', $lang);
        } elseif (Yii::$app->session->has('lang')) {
            Yii::$app->language = Yii::$app->session->get('lang');
        }
    },
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
