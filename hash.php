<?php

/*
 * Utility CLI untuk membuat hash password.
 * Penggunaan:  php hash.php "password_anda"
 *
 * CATATAN KEAMANAN:
 * - File ini HANYA boleh dijalankan via command line, tidak via web,
 *   agar tidak membocorkan hash/kredensial.
 * - Jangan pernah menaruh password default di dalam kode.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Akses ditolak.');
}

$plain = $argv[1] ?? null;
if ($plain === null || $plain === '') {
    fwrite(STDERR, "Penggunaan: php hash.php \"password\"\n");
    exit(1);
}

echo password_hash($plain, PASSWORD_DEFAULT) . PHP_EOL;
