<?php
// Konfigurasi — sesuaikan dengan panel Hostinger
return [
    'db' => [
        'host' => 'auth-db1987.hstgr.io',   // dari panel Hostinger
        'name' => 'u123456789_kyn',         // ganti sesuai DB Anda
        'user' => 'u123456789_kyn',         // ganti
        'pass' => 'PASSWORD_DB_ANDA',       // ganti
        'port' => 3306,
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url'  => 'https://kyn.or.id', // ganti domain
        'debug'     => false,               // true hanya di lokal
        'upload_dir'=> __DIR__ . '/../uploads',
        'upload_url'=> '/uploads',
        'session_name' => 'KYNSESSID',
        'session_idle' => 1800,             // 30 menit
    ],
];