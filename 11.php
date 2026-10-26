<?php
/**
 * Simple File Manager (PHP 8.0+)
 *
 * INSTALLATION
 * 1. In cPanel, make a separate folder (e.g. public_html/private-manager).
 * 2. Enable cPanel > Files > Directory Privacy on JUST that folder and create
 *    an authorized user there. Do not protect all of public_html.
 * 3. Upload this file to that protected folder and open it over HTTPS.
 *    There is no login or IP filter in this PHP page. It checks REMOTE_USER,
 *    which must be set by successful web-server authentication. If the web
 *    server does not expose REMOTE_USER, ask your host to enable it.
 * 4. Optional: set FM_ROOT to a directory you own. Keep it OUTSIDE your public
 *    web/document root. Default: file-manager-data beside the document root.
 *    Set FM_ROOT explicitly if you need to manage an existing site directory.
 *
 * The Command panel intentionally supports diagnostics only, not OS shell code.
 */

declare(strict_types=1);

const MAX_UPLOAD_BYTES = 10485760; // 10 MiB
const MAX_EDIT_BYTES = 1048576;    // 1 MiB

$documentRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
$storageParent = $documentRoot !== '' && is_dir($documentRoot)
    ? dirname(realpath($documentRoot) ?: $documentRoot)
    : dirname(__DIR__);
$rootConfig = getenv('FM_ROOT') ?: $storageParent . DIRECTORY_SEPARATOR . 'file-manager-data';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('SIMPLE_FILE_MANAGER');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
header('Cache-Control: no-store');

// Fail closed unless Apache/LiteSpeed has already authenticated the request.
// Do not trust PHP_AUTH_USER or user-supplied HTTP headers as proof of login.
$serverAuthenticatedUser = (string) ($_SERVER['REMOTE_USER'] ?? '');
if ($serverAuthenticatedUser === '') {
    http_response_code(403);
    echo '<!doctype html><html lang="id"><meta charset="UTF-8"><title>Akses ditolak</title>';
    echo '<body><h1>Akses belum dikonfigurasi</h1><p>Aktifkan Directory Privacy pada folder ini di cPanel. Server harus mengirim REMOTE_USER setelah autentikasi berhasil.</p></body></html>';
    exit;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fail(string $message): void
{
    throw new RuntimeException($message);
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function checkCsrf(): void
{
    if (!is_string($_POST['csrf'] ?? null) ||
        !hash_equals(csrfToken(), $_POST['csrf'])) {
        fail('Sesi tidak valid. Muat ulang halaman dan coba lagi.');
    }
}

function validName(string $name): string
{
    if ($name === '' || $name === '.' || $name === '..' || strlen($name) > 255 ||
        str_contains($name, '/') || str_contains($name, chr(92)) ||
        preg_match('/[\x00-\x1F\x7F]/', $name)) {
        fail('Nama file atau folder tidak valid.');
    }
    return $name;
}

function inRoot(string $path): bool
