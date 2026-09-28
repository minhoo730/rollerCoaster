<?php

return [
    'app_key' => env('KIS_APP_KEY'),
    'app_secret' => env('KIS_APP_SECRET'),
    'base_url' => env('KIS_BASE_URL', 'https://openapi.koreainvestment.com:9443'),
    'timeout' => (int) env('KIS_TIMEOUT', 10),

    'stock_meta' => [
        'directory' => env('KIS_STOCK_META_DIR', 'kis/stock-meta'),
        'chunk_size' => (int) env('KIS_STOCK_META_CHUNK_SIZE', 1000),
        'use_upsert' => filter_var(env('KIS_STOCK_META_USE_UPSERT', true), FILTER_VALIDATE_BOOL),
        'markets' => [
            'kospi' => [
                'market' => 'kospi', 'venue' => 'krx', 'provider' => 'kis', 'format' => 'kis_mst', 'archive' => 'zip',
                'file_name' => 'kospi_code.mst', 'encoding' => 'CP949', 'tail_length' => 228,
                'url' => env('KIS_KOSPI_MASTER_URL') ?: 'https://new.real.download.dws.co.kr/common/master/kospi_code.mst.zip',
            ],
            'kosdaq' => [
                'market' => 'kosdaq', 'venue' => 'krx', 'provider' => 'kis', 'format' => 'kis_mst', 'archive' => 'zip',
                'file_name' => 'kosdaq_code.mst', 'encoding' => 'CP949', 'tail_length' => 222,
                'url' => env('KIS_KOSDAQ_MASTER_URL') ?: 'https://new.real.download.dws.co.kr/common/master/kosdaq_code.mst.zip',
            ],
            'konex' => [
                'market' => 'konex', 'venue' => 'krx', 'provider' => 'krx', 'format' => 'kind_html', 'archive' => null,
                'encoding' => 'EUC-KR',
                'url' => env('KIS_KONEX_MASTER_URL') ?: 'https://kind.krx.co.kr/corpgeneral/corpList.do?method=download&pageIndex=1&currentPageSize=5000&orderMode=3&orderStat=D&marketType=konexMkt&searchType=13&fiscalYearEnd=all&location=all',
            ],
        ],
        'nxt' => [
            'enabled' => filter_var(env('KIS_NXT_MASTER_ENABLED', false), FILTER_VALIDATE_BOOL),
            'market' => null, 'venue' => 'nxt', 'provider' => 'kis', 'format' => env('KIS_NXT_MASTER_FORMAT', 'kis_mst'),
            'archive' => env('KIS_NXT_MASTER_ARCHIVE', 'zip'), 'file_name' => env('KIS_NXT_MASTER_FILE_NAME'),
            'encoding' => env('KIS_NXT_MASTER_ENCODING', 'CP949'), 'tail_length' => (int) env('KIS_NXT_MASTER_TAIL_LENGTH', 222),
            'url' => env('KIS_NXT_MASTER_URL'),
        ],
    ],
];
