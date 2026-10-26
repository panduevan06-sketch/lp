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
{
    global $ROOT;
    return $path === $ROOT || str_starts_with($path, $ROOT . DIRECTORY_SEPARATOR);
}

function dirFromRelative(string $relative): string
{
    global $ROOT;
    if (str_contains($relative, "\0")) {
        fail('Lokasi tidak valid.');
    }
    $path = realpath($ROOT . DIRECTORY_SEPARATOR . $relative);
    if ($path === false || !is_dir($path) || !inRoot($path)) {
        fail('Folder tidak ditemukan atau berada di luar lokasi yang diizinkan.');
    }
    return $path;
}

function childPath(string $dir, string $name, bool $mustExist = true): string
{
    $candidate = $dir . DIRECTORY_SEPARATOR . validName($name);
    // Do not follow symbolic links, even ones pointing to locations within root.
    if (is_link($candidate)) {
        fail('Symbolic link tidak didukung.');
    }
    if (!$mustExist) {
        if (file_exists($candidate) || is_link($candidate)) {
            fail('Nama tersebut sudah digunakan.');
        }
        return $candidate;
    }
    $resolved = realpath($candidate);
    if ($resolved === false || !inRoot($resolved)) {
        fail('File/folder tidak ditemukan atau tidak diizinkan.');
    }
    return $resolved;
}

function relativeDir(string $dir): string
{
    global $ROOT;
    return ltrim(substr($dir, strlen($ROOT)), DIRECTORY_SEPARATOR);
}

function urlFor(string $relative, array $more = []): string
{
    return '?' . http_build_query(array_merge(['p' => $relative], $more));
}

function goBack(string $relative): void
{
    header('Location: ' . urlFor($relative), true, 303);
    exit;
}

function sizeLabel(int|float $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return $i === 0 ? (int) $bytes . ' B' : number_format($bytes, 1) . ' ' . $units[$i];
}

function permissionLabel(string $path): string
{
    $perms = @fileperms($path);
    return $perms === false ? '---' : substr(sprintf('%o', $perms), -3);
}

function commandOutput(string $command, string $dir): string
{
    $command = trim($command);
    [$verb, $argument] = array_pad(preg_split('/\s+/', $command, 2), 2, '');
    switch (strtolower($verb)) {
        case 'help':
        case '':
            return "Perintah yang tersedia:\nhelp   - daftar perintah\npwd    - lokasi folder\nls     - daftar isi folder\nstat NAMA - informasi file/folder\ndisk   - kapasitas penyimpanan\nphp    - versi PHP";
        case 'pwd':
            return $dir;
        case 'ls':
            $entries = scandir($dir);
            if ($entries === false) fail('Folder gagal dibaca.');
            $lines = [];
            foreach (array_slice(array_diff($entries, ['.', '..']), 0, 200) as $name) {
                if (is_link($dir . DIRECTORY_SEPARATOR . $name)) continue;
                $lines[] = (is_dir($dir . DIRECTORY_SEPARATOR . $name) ? '[DIR]  ' : '[FILE] ') . $name;
            }
            return $lines ? implode("\n", $lines) : '(folder kosong)';
        case 'stat':
            $path = childPath($dir, trim($argument));
            return implode("\n", [
                'Nama: ' . basename($path),
                'Jenis: ' . (is_dir($path) ? 'Directory' : 'File'),
                'Ukuran: ' . sizeLabel((float) (@filesize($path) ?: 0)),
                'Permission: ' . permissionLabel($path),
                'Diubah: ' . date('Y-m-d H:i:s', (int) (@filemtime($path) ?: 0)),
            ]);
        case 'disk':
            $free = disk_free_space($dir);
            $total = disk_total_space($dir);
            if ($free === false || $total === false) fail('Statistik disk tidak tersedia.');
            return 'Kosong: ' . sizeLabel($free) . "\nTotal: " . sizeLabel($total);
        case 'php':
            return 'PHP ' . PHP_VERSION . ' (' . PHP_SAPI . ')';
        default:
            return 'Perintah tidak tersedia. Ketik "help" untuk daftar perintah. Tidak ada akses shell.';
    }
}

// The web server owns access control; this page has no internal login or IP list.
    if (!is_dir($rootConfig) && !@mkdir($rootConfig, 0700, true)) {
        fail('Folder penyimpanan tidak dapat dibuat. Periksa konfigurasi FM_ROOT.');
    }
    $ROOT = realpath($rootConfig);
    if ($ROOT === false) fail('FM_ROOT tidak tersedia.');

    $requested = is_string($_REQUEST['p'] ?? null) ? $_REQUEST['p'] : '';
    try {
        $dir = dirFromRelative($requested);
        $relative = relativeDir($dir);
    } catch (Throwable $e) {
        $dir = $ROOT;
        $relative = '';
        $_SESSION['flash'] = $e->getMessage();
    }

    $terminalOutput = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            checkCsrf();
            $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
            $name = is_string($_POST['name'] ?? null) ? $_POST['name'] : '';
            switch ($action) {
                case 'new_file':
                    $path = childPath($dir, $name, false);
                    $handle = @fopen($path, 'x');
                    if ($handle === false) fail('Gagal membuat file.');
                    fclose($handle);
                    @chmod($path, 0600);
                    $_SESSION['flash'] = 'File berhasil dibuat.';
                    break;
                case 'new_dir':
                    if (!@mkdir(childPath($dir, $name, false), 0700)) fail('Gagal membuat folder.');
                    $_SESSION['flash'] = 'Folder berhasil dibuat.';
                    break;
                case 'upload':
                    $upload = $_FILES['upload'] ?? null;
                    if (!is_array($upload) || !is_int($upload['error'] ?? null) || $upload['error'] !== UPLOAD_ERR_OK ||
                        !is_string($upload['name'] ?? null) || !is_string($upload['tmp_name'] ?? null)) {
                        fail('Upload gagal. Periksa batas upload pada konfigurasi PHP.');
                    }
                    if ((int) $upload['size'] > MAX_UPLOAD_BYTES) fail('File melebihi batas 10 MB.');
                    $destination = childPath($dir, $upload['name'], false);
                    if (!is_uploaded_file($upload['tmp_name']) || !@move_uploaded_file($upload['tmp_name'], $destination)) {
                        fail('Gagal menyimpan upload.');
                    }
                    @chmod($destination, 0600);
                    $_SESSION['flash'] = 'Upload berhasil.';
                    break;
                case 'save':
                    $path = childPath($dir, $name);
                    if (!is_file($path) || !is_writable($path) || filesize($path) > MAX_EDIT_BYTES) {
                        fail('File tidak dapat diedit (ukuran maksimal 1 MB).');
                    }
                    $content = $_POST['content'] ?? null;
                    if (!is_string($content) || strlen($content) > MAX_EDIT_BYTES || str_contains($content, "\0")) {
                        fail('Konten bukan teks valid atau lebih dari 1 MB.');
                    }
                    if (@file_put_contents($path, $content, LOCK_EX) === false) fail('Gagal menyimpan file.');
                    $_SESSION['flash'] = 'Perubahan disimpan.';
                    break;
                case 'rename':
                    $old = childPath($dir, $name);
                    $newName = is_string($_POST['new_name'] ?? null) ? $_POST['new_name'] : '';
                    if (!@rename($old, childPath($dir, $newName, false))) fail('Gagal mengganti nama.');
                    $_SESSION['flash'] = 'Nama berhasil diubah.';
                    break;
                case 'chmod':
                    $path = childPath($dir, $name);
                    $mode = is_string($_POST['mode'] ?? null) ? $_POST['mode'] : '';
                    if (!preg_match('/^[0-7]{3}$/', $mode)) fail('Mode harus tiga angka oktal, misalnya 644 atau 755.');
                    if (!@chmod($path, octdec($mode))) fail('Gagal mengubah permission.');
                    $_SESSION['flash'] = 'Permission diubah.';
                    break;
                case 'delete':
                    if (($_POST['confirm'] ?? '') !== 'yes') fail('Centang konfirmasi sebelum menghapus.');
                    $path = childPath($dir, $name);
                    $removed = is_dir($path) ? @rmdir($path) : @unlink($path);
                    if (!$removed) fail('Gagal menghapus. Folder harus kosong terlebih dahulu.');
                    $_SESSION['flash'] = 'Item dihapus.';
                    break;
                case 'command':
                    $input = is_string($_POST['command'] ?? null) ? $_POST['command'] : '';
                    if (strlen($input) > 300) fail('Perintah terlalu panjang.');
                    $terminalOutput = commandOutput($input, $dir);
                    break;
                default:
                    fail('Tindakan tidak dikenal.');
            }
            if ($action !== 'command') goBack($relative);
        } catch (Throwable $e) {
            $_SESSION['flash'] = $e->getMessage();
            goBack($relative);
        }
    }

    if (isset($_GET['download']) && is_string($_GET['download'])) {
        try {
            $path = childPath($dir, $_GET['download']);
            if (!is_file($path) || !is_readable($path)) fail('File tidak bisa diunduh.');
            header('Content-Type: application/octet-stream');
            header('Content-Length: ' . filesize($path));
            header('Content-Disposition: attachment; filename="download"; filename*=UTF-8\'\'' . rawurlencode(basename($path)));
            readfile($path);
            exit;
        } catch (Throwable $e) {
            $_SESSION['flash'] = $e->getMessage();
            goBack($relative);
        }
    }

    $selected = is_string($_GET['item'] ?? null) ? $_GET['item'] : '';
    $edit = is_string($_GET['edit'] ?? null) ? $_GET['edit'] : '';
    $selectedPath = null;
    $editContent = null;
    try {
        if ($selected !== '') $selectedPath = childPath($dir, $selected);
        if ($edit !== '') {
            $editPath = childPath($dir, $edit);
            if (!is_file($editPath) || !is_readable($editPath) || filesize($editPath) > MAX_EDIT_BYTES) {
                fail('File tidak dapat diedit atau lebih dari 1 MB.');
            }
            $editContent = file_get_contents($editPath);
            if ($editContent === false || str_contains($editContent, "\0")) fail('File biner tidak dapat diedit.');
        }
    } catch (Throwable $e) {
        $_SESSION['flash'] = $e->getMessage();
        $selectedPath = null;
        $editContent = null;
    }

    $items = [];
    $names = scandir($dir);
    if ($names === false) fail('Tidak dapat membaca folder.');
    foreach ($names as $entry) {
        if ($entry === '.' || $entry === '..' || is_link($dir . DIRECTORY_SEPARATOR . $entry)) continue;
        $full = $dir . DIRECTORY_SEPARATOR . $entry;
        $items[] = ['name' => $entry, 'dir' => is_dir($full), 'path' => $full];
    }
    usort($items, static fn (array $a, array $b): int =>
        ($b['dir'] <=> $a['dir']) ?: strnatcasecmp($a['name'], $b['name']));

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Simple File Manager</title>
<style>
:root{color-scheme:dark;font-family:Inter,ui-sans-serif,system-ui,-apple-system,Segoe UI,sans-serif;background:#0b1220;color:#e4eaf5}
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(ellipse at 70% -15%,#1f365c 0,transparent 45%),#0b1220}
.wrap{max-width:1170px;margin:auto;padding:28px 24px 75px}.top,.section-head,.file-row,.between{display:flex;align-items:center;justify-content:space-between;gap:14px}
.brand{display:flex;align-items:center;gap:13px}.brand-mark{width:43px;height:43px;display:grid;place-items:center;border-radius:13px;background:#3467ec;font-size:23px}
h1{font-size:20px;line-height:1.2;margin:0}h2{font-size:18px;margin:0 0 15px}h3{font-size:15px;margin:0 0 10px}p{color:#9faec8;line-height:1.6}
.eyebrow{font-size:11px;color:#8c9bb8;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px}.muted,.meta{color:#91a0bb;font-size:12px}
.panel{background:#111c2e;border:1px solid #25334c;box-shadow:0 14px 35px #02081725;border-radius:16px;padding:20px;margin-top:22px}
.toolbar{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}.tool{border:1px solid #2d3c58;border-radius:12px;padding:13px;background:#14233a}
summary{cursor:pointer;font-weight:650;color:#cbd9fa;list-style:none}summary::-webkit-details-marker{display:none}summary:after{content:'+';float:right;font-size:20px;line-height:1}
details[open] summary:after{content:'−'}form{margin:0}.tool form{margin-top:13px}.stack{display:grid;gap:10px}
input,textarea,button{font:inherit}input[type=text],input[type=file],textarea{width:100%;background:#0b1526;border:1px solid #334562;border-radius:9px;color:#edf3ff;padding:11px 12px;min-width:0}
textarea{min-height:300px;resize:vertical;font:13px/1.6 ui-monospace,SFMono-Regular,Consolas,monospace;tab-size:4}input:focus,textarea:focus{outline:2px solid #537cfd;outline-offset:0}
button,.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;text-decoration:none;border:1px solid #3c5173;background:#1c2b46;color:#e7efff;border-radius:9px;padding:9px 12px;cursor:pointer;font-size:13px;font-weight:650;white-space:nowrap}
button:hover,.btn:hover{background:#2b4169}.primary{background:#3164e8;border-color:#3164e8;color:white}.primary:hover{background:#4479fb}.danger{background:#512735;border-color:#783749;color:#ffdbe4}.danger:hover{background:#70364c}
.file-table{overflow:auto}.file-row{min-width:650px;padding:13px 5px;border-bottom:1px solid #20304a}.file-row:last-child{border-bottom:0}.file-name{display:flex;align-items:center;gap:12px;min-width:0}
.glyph{flex-shrink:0;display:grid;place-items:center;width:39px;height:39px;border-radius:10px;background:#182f4e;font-size:20px}.filename{font-weight:650;color:#e8f0ff;text-decoration:none;overflow-wrap:anywhere}.filename:hover{color:#78a4ff}
.info{display:grid;grid-template-columns:85px 105px 55px;gap:12px;text-align:right;align-items:center}.file-actions{min-width:75px;text-align:right}.folder .glyph{background:#3c321c}
.crumbs{display:flex;align-items:center;gap:7px;flex-wrap:wrap;font-size:13px;color:#a7b4cb}.crumbs a{color:#9dbbff;text-decoration:none}.crumbs a:hover{text-decoration:underline}
.alert{padding:13px 16px;border-radius:10px;background:#29394d;border:1px solid #49647c;margin-top:20px;color:#dceaff}.small{font-size:12px;color:#92a0b8}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.action-box{border:1px solid #283951;border-radius:11px;padding:14px;background:#122039}.action-box form{display:grid;gap:9px}
.terminal{background:#070e1a;border:1px solid #304667;border-radius:10px;color:#aed9c5;white-space:pre-wrap;overflow-wrap:anywhere;padding:15px;line-height:1.6;font:13px ui-monospace,SFMono-Regular,Consolas,monospace}
.hint{background:#142841;border:1px solid #264869;padding:12px;border-radius:9px;font-size:13px;color:#abc9f7}.empty{text-align:center;padding:45px 10px;color:#9fb0c7}
.foot{margin-top:28px;color:#687e9e;font-size:12px;text-align:center}label{font-size:13px;color:#c7d6ee}.checkbox{display:flex;align-items:center;gap:8px}code{font-family:ui-monospace,Consolas,monospace}
@media(max-width:700px){.wrap{padding:20px 14px 50px}.toolbar,.form-grid{grid-template-columns:1fr}.top{align-items:flex-start}.brand{gap:9px}.panel{padding:15px}.file-row{min-width:600px}}
</style>
</head>
<body>
<main class="wrap">
    <header class="top">
        <div class="brand"><div class="brand-mark">🗂️</div><div><div class="eyebrow">Workspace</div><h1>Simple File Manager</h1></div></div>
        <span class="muted">Dilindungi Directory Privacy</span>
    </header>
    <?php if ($flash): ?><div class="alert"><?= h((string) $flash) ?></div><?php endif; ?>
    <section class="panel">
        <div class="section-head"><div><div class="eyebrow">Lokasi aktif</div><nav class="crumbs" aria-label="Breadcrumb"><a href="<?= h(urlFor('')) ?>">Home</a>
            <?php $segments = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $relative)); $walk = ''; foreach ($segments as $segment): if ($segment === '') continue; $walk = ltrim($walk . '/' . $segment, '/'); ?>
                <span>/</span><a href="<?= h(urlFor($walk)) ?>"><?= h($segment) ?></a>
            <?php endforeach; ?>
        </nav></div><span class="muted"><?= count($items) ?> item</span></div>
    </section>
    <section class="panel"><h2>Tambah file & folder</h2>
        <div class="toolbar">
            <details class="tool"><summary>＋ File baru</summary><form class="stack" method="post">
                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="p" value="<?= h($relative) ?>"><input type="hidden" name="action" value="new_file">
                <input type="text" name="name" placeholder="contoh: catatan.txt" required><button type="submit" class="primary">Buat file</button>
            </form></details>
            <details class="tool"><summary>📁 Folder baru</summary><form class="stack" method="post">
                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="p" value="<?= h($relative) ?>"><input type="hidden" name="action" value="new_dir">
                <input type="text" name="name" placeholder="nama-folder" required><button type="submit" class="primary">Buat folder</button>
            </form></details>
            <details class="tool"><summary>↥ Upload file</summary><form class="stack" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="p" value="<?= h($relative) ?>"><input type="hidden" name="action" value="upload">
                <input type="file" name="upload" required><span class="small">Maksimal 10 MB • tidak menimpa file lama</span><button type="submit" class="primary">Upload</button>
            </form></details>
        </div>
    </section>
    <section class="panel"><div class="section-head"><h2>Daftar file</h2><span class="muted">Folder ditampilkan lebih dahulu</span></div>
        <div class="file-table">
            <?php if (!$items): ?><div class="empty">📭 Folder ini masih kosong.</div><?php endif; ?>
            <?php foreach ($items as $item): $name = $item['name']; $isDir = $item['dir']; ?>
            <div class="file-row <?= $isDir ? 'folder' : '' ?>">
                <div class="file-name"><span class="glyph"><?= $isDir ? '📁' : '📄' ?></span><div>
                    <?php if ($isDir): ?><a class="filename" href="<?= h(urlFor(ltrim($relative . '/' . $name, '/'))) ?>"><?= h($name) ?></a>
                    <?php else: ?><a class="filename" href="<?= h(urlFor($relative, ['item' => $name])) ?>"><?= h($name) ?></a><?php endif; ?>
                    <div class="meta"><?= $isDir ? 'Folder' : 'File' ?></div></div>
                </div>
                <div class="info"><span class="meta"><?= $isDir ? '—' : h(sizeLabel((float) (@filesize($item['path']) ?: 0))) ?></span>
                    <span class="meta"><?= h(date('d M Y', (int) (@filemtime($item['path']) ?: 0))) ?></span>
                    <code class="meta"><?= h(permissionLabel($item['path'])) ?></code></div>
                <div class="file-actions"><a class="btn" href="<?= h(urlFor($relative, ['item' => $name])) ?>">Kelola</a></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($selectedPath !== null): ?>
    <section class="panel"><div class="section-head"><h2>Kelola: <?= h($selected) ?></h2><a class="btn" href="<?= h(urlFor($relative)) ?>">Tutup</a></div>
        <div class="form-grid">
            <div class="action-box"><h3>✏️ Ganti nama</h3><form method="post"><input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="p" value="<?= h($relative) ?>"><input type="hidden" name="action" value="rename"><input type="hidden" name="name" value="<?= h($selected) ?>">
                <input name="new_name" type="text" value="<?= h($selected) ?>" required><button type="submit">Rename</button></form></div>
            <div class="action-box"><h3>🔒 CHMOD</h3><form method="post"><input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="p" value="<?= h($relative) ?>"><input type="hidden" name="action" value="chmod"><input type="hidden" name="name" value="<?= h($selected) ?>">
                <input name="mode" type="text" value="<?= h(permissionLabel($selectedPath)) ?>" pattern="[0-7]{3}" maxlength="3" required><button type="submit">Simpan permission</button></form></div>
            <?php if (is_file($selectedPath)): ?>
            <div class="action-box"><h3>📄 Aksi file</h3><div class="between"><a class="btn" href="<?= h(urlFor($relative, ['edit' => $selected])) ?>">Edit teks</a><a class="btn" href="<?= h(urlFor($relative, ['download' => $selected])) ?>">Download</a></div></div>
            <?php endif; ?>
            <div class="action-box"><h3>🗑️ Hapus</h3><form method="post"><input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="p" value="<?= h($relative) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="name" value="<?= h($selected) ?>">
                <label class="checkbox"><input type="checkbox" name="confirm" value="yes" required> Saya yakin ingin menghapus</label>
                <button type="submit" class="danger">Delete</button><span class="small">Folder harus kosong; tindakan ini tidak dapat dibatalkan.</span></form></div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($editContent !== null): ?>
    <section class="panel"><div class="section-head"><h2>Edit: <?= h($edit) ?></h2><a class="btn" href="<?= h(urlFor($relative)) ?>">Tutup</a></div>
        <form class="stack" method="post"><input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="p" value="<?= h($relative) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="name" value="<?= h($edit) ?>">
            <textarea name="content" spellcheck="false" maxlength="<?= MAX_EDIT_BYTES ?>"><?= h($editContent) ?></textarea><div class="between"><span class="small">Editor teks • maksimal 1 MB</span><button type="submit" class="primary">Simpan perubahan</button></div>
        </form>
    </section>
    <?php endif; ?>

    <section class="panel"><h2>⌘ Command</h2><p class="small">Hanya perintah diagnostik bawaan. Input di sini tidak dijalankan oleh shell sistem operasi.</p>
        <form method="post" class="stack"><input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="p" value="<?= h($relative) ?>"><input type="hidden" name="action" value="command">
            <div class="between"><input type="text" name="command" placeholder="Ketik help, ls, pwd, stat nama.txt, disk, php" maxlength="300" aria-label="Perintah" required><button class="primary" type="submit">Jalankan</button></div>
        </form>
        <?php if ($terminalOutput !== null): ?><pre class="terminal"><?= h($terminalOutput) ?></pre><?php endif; ?>
    </section>
    <p class="foot">Simple File Manager • Gunakan hanya pada server dan direktori milik Anda.</p>
</main>
</body>
</html>
