<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Sitemap Generator migration',
    'description' => 'Migration for EXT:sitemap_generator to fetch EXT:dd_googlesitemap calculated change frequency',
    'category' => 'misc',
    'author' => 'J.Kummer',
    'author_email' => 'typo3 et enobe dot de',
    'author_company' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'php' => '5.6.0-7.2.99',
            'typo3' => '7.6.0-8.7.9',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
