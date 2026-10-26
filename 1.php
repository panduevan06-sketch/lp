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
}

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
input,textarea,button{font:inherit}input[type=text],input[type=password],input[type=file],textarea{width:100%;background:#0b1526;border:1px solid #334562;border-radius:9px;color:#edf3ff;padding:11px 12px;min-width:0}
textarea{min-height:300px;resize:vertical;font:13px/1.6 ui-monospace,SFMono-Regular,Consolas,monospace;tab-size:4}input:focus,textarea:focus{outline:2px solid #537cfd;outline-offset:0}
button,.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;text-decoration:none;border:1px solid #3c5173;background:#1c2b46;color:#e7efff;border-radius:9px;padding:9px 12px;cursor:pointer;font-size:13px;font-weight:650;white-space:nowrap}
button:hover,.btn:hover{background:#2b4169}.primary{background:#3164e8;border-color:#3164e8;color:white}.primary:hover{background:#4479fb}.danger{background:#512735;border-color:#783749;color:#ffdbe4}.danger:hover{background:#70364c}
.file-table{overflow:auto}.file-row{min-width:650px;padding:13px 5px;border-bottom:1px solid #20304a}.file-row:last-child{border-bottom:0}.file-name{display:flex;align-items:center;gap:12px;min-width:0}
.glyph{flex-shrink:0;display:grid;place-items:center;width:39px;height:39px;border-radius:10px;background:#182f4e;font-size:20px}.filename{font-weight:650;color:#e8f0ff;text-decoration:none;overflow-wrap:anywhere}.filename:hover{color:#78a4ff}
.info{display:grid;grid-template-columns:85px 105px 55px;gap:12px;text-align:right;align-items:center}.file-actions{min-width:75px;text-align:right}.folder .glyph{background:#3c321c}
.crumbs{display:flex;align-items:center;gap:7px;flex-wrap:wrap;font-size:13px;color:#a7b4cb}.crumbs a{color:#9dbbff;text-decoration:none}.crumbs a:hover{text-decoration:underline}
.alert{padding:13px 16px;border-radius:10px;background:#29394d;border:1px solid #49647c;margin-top:20px;color:#dceaff}.small{font-size:12px;color:#92a0b8}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.action-box{border:1px solid #283951;border-radius:11px;padding:14px;background:#122039}.action-box form{display:grid;gap:9px}
.terminal{background:#070e1a;border:1px solid #304667;border-radius:10px;color:#aed9c5;white-space:pre-wrap;overflow-wrap:anywhere;padding:15px;line-height:1.6;font:13px ui-monospace,SFMono-Regular,Consolas,monospace}
.login{max-width:430px;margin:10vh auto 0}.login .panel{padding:30px}.login form{display:grid;gap:13px}.login h1{font-size:24px;margin:16px 0 4px}
.hint{background:#142841;border:1px solid #264869;padding:12px;border-radius:9px;font-size:13px;color:#abc9f7}.empty{text-align:center;padding:45px 10px;color:#9fb0c7}
.foot{margin-top:28px;color:#687e9e;font-size:12px;text-align:center}label{font-size:13px;color:#c7d6ee}.checkbox{display:flex;align-items:center;gap:8px}code{font-family:ui-monospace,Consolas,monospace}
@media(max-width:700px){.wrap{padding:20px 14px 50px}.toolbar,.form-grid{grid-template-columns:1fr}.top{align-items:flex-start}.brand{gap:9px}.panel{padding:15px}.file-row{min-width:600px}.login{margin:4vh auto 0}}
</style>
</head>
<body>
<?php if ($loginScreen): ?>
<main class="wrap login"><section class="panel">
    <div class="brand-mark">🗂️</div><h1>Simple File Manager</h1><p>Masuk untuk mengelola file di direktori yang telah ditentukan.</p>
    <?php if (!$configured): ?>
        <div class="hint">Admin belum dikonfigurasi. Buat password hash dengan <code>password_hash()</code>, lalu atur <code>FM_PASSWORD_HASH</code> atau variabel <code>$passwordHash</code> di bagian atas file PHP ini.</div>
    <?php else: ?>
        <?php if ($loginError): ?><div class="alert"><?= h($loginError) ?></div><?php endif; ?>
        <form method="post"><input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="login">
            <label for="password">Password admin</label><input id="password" name="password" type="password" autocomplete="current-password" required autofocus>
            <button type="submit" class="primary">Masuk →</button>
        </form>
    <?php endif; ?>
</section></main>
<?php else: ?>
<main class="wrap">
    <header class="top">
        <div class="brand"><div class="brand-mark">🗂️</div><div><div class="eyebrow">Workspace</div><h1>Simple File Manager</h1></div></div>
        <form method="post"><input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="logout"><button type="submit">Keluar</button></form>
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
<?php endif; ?>
</body>
</html>
