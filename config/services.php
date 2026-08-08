<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Inventory -- sumber data harga modal (HPP).
    |
    | CRM_API_KEY hanya boleh hidup di env server CRM dan hanya dipakai dari sisi
    | server. Kunci ini tidak pernah dikirim ke browser: kalau ikut ter-render ke
    | JavaScript, siapa pun yang membuka DevTools bisa membacanya dan seluruh HPP
    | ikut terbuka.
    |
    | Cache sengaja mati secara bawaan. Store cache aplikasi ini adalah database
    | (lihat CACHE_STORE), jadi menyalakan cache berarti baris HPP ikut mendarat di
    | tabel `cache` milik CRM. Kalau memang perlu demi kecepatan, isi TTL-nya kecil
    | (maksimum dipagari 300 detik) dan arahkan HARGA_MODAL_CACHE_STORE ke store yang
    | tidak persisten, misalnya `array` atau `redis`.
    */
    'inventory' => [
        'base_url' => env('INVENTORY_BASE_URL'),
        'api_key' => env('CRM_API_KEY'),

        /*
        | Kode perusahaan yang boleh membuka halaman Harga Modal, dipisah koma.
        |
        | Izin per role tidak cukup untuk menyatakan ini: satu role yang sama
        | dipakai orang di perusahaan berbeda, jadi mencentang izinnya akan
        | membuka halaman itu untuk semuanya sekaligus.
        |
        | Dikosongkan berarti pembatasan perusahaan dimatikan dan izin saja yang
        | berlaku. Kodenya lihat kolom `code` di tabel companies.
        */
        'perusahaan' => array_filter(array_map(
            'trim',
            explode(',', (string) env('HARGA_MODAL_PERUSAHAAN', 'ATC')),
        )),
        'timeout' => (int) env('INVENTORY_TIMEOUT', 10),
        'harga_modal_cache_ttl' => (int) env('HARGA_MODAL_CACHE_TTL', 0),
        'harga_modal_cache_store' => env('HARGA_MODAL_CACHE_STORE'),
    ],

];
