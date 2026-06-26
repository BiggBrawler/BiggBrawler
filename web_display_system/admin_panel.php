<?php
require_once 'auth.php';
require_once 'db_connect.php';
requireAdmin();

$user = currentUser();
$tab  = $_GET['tab'] ?? 'users';
$msg  = '';
$msgType = 'success';

// ============================================================
// USER MANAGEMENT ACTIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Create user
    if (isset($_POST['action_create_user'])) {
        $uname = trim($_POST['username'] ?? '');
        $email = trim($_POST['email']    ?? '');
        $pass  = $_POST['password']      ?? '';
        $role  = in_array($_POST['role'] ?? '', ['admin','basic']) ? $_POST['role'] : 'basic';

        if (!$uname || !$email || !$pass) {
            $msg = 'All fields are required.'; $msgType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Invalid email address.'; $msgType = 'error';
        } elseif (strlen($pass) < 8) {
            $msg = 'Password must be at least 8 characters.'; $msgType = 'error';
        } else {
            try {
                $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)")
                    ->execute([$uname, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
                $msg = "User \"$uname\" created.";
            } catch (PDOException $e) {
                $msg = 'Username or email already exists.'; $msgType = 'error';
            }
        }
        $tab = 'users';
    }

    // Edit user (role, active status)
    if (isset($_POST['action_edit_user'])) {
        $uid    = intval($_POST['edit_id'] ?? 0);
        $role   = in_array($_POST['edit_role'] ?? '', ['admin','basic']) ? $_POST['edit_role'] : 'basic';
        $active = isset($_POST['edit_active']) ? 1 : 0;
        $email  = trim($_POST['edit_email'] ?? '');
        if ($uid === $user['id'] && $role !== 'admin') {
            $msg = 'You cannot demote your own account.'; $msgType = 'error';
        } else {
            $pdo->prepare("UPDATE users SET role = ?, is_active = ?, email = ? WHERE id = ?")
                ->execute([$role, $active, $email, $uid]);
            $msg = 'User updated.';
        }
        $tab = 'users';
    }

    // Reset another user's password
    if (isset($_POST['action_reset_pass'])) {
        $uid  = intval($_POST['reset_uid'] ?? 0);
        $pass = $_POST['new_pass'] ?? '';
        if (strlen($pass) < 8) {
            $msg = 'Password must be at least 8 characters.'; $msgType = 'error';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                ->execute([password_hash($pass, PASSWORD_DEFAULT), $uid]);
            $msg = 'Password reset.';
        }
        $tab = 'users';
    }

    // Delete user
    if (isset($_POST['action_delete_user'])) {
        $uid = intval($_POST['del_id'] ?? 0);
        if ($uid === $user['id']) {
            $msg = 'You cannot delete your own account.'; $msgType = 'error';
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
            $msg = 'User deleted.';
        }
        $tab = 'users';
    }

    // Save brand standards
    if (isset($_POST['action_save_styles'])) {
        $types = ['section_header','item_title','price','description'];
        $stmt  = $pdo->prepare(
            "UPDATE block_styles SET font_family=?, font_size=?, font_color=?, font_weight=?, font_style=?, line_height=? WHERE block_type=?"
        );
        foreach ($types as $t) {
            $stmt->execute([
                $_POST["bs_{$t}_family"]      ?? 'Arial',
                intval($_POST["bs_{$t}_size"] ?? 16),
                $_POST["bs_{$t}_color"]       ?? '#000000',
                $_POST["bs_{$t}_weight"]      ?? 'normal',
                $_POST["bs_{$t}_fstyle"]      ?? 'normal',
                number_format(floatval($_POST["bs_{$t}_lh"] ?? 1.4), 2),
                $t,
            ]);
        }
        $msg = 'Brand standards saved.';
        $tab = 'brand';
    }
}

// ---- READ DATA ----
$users  = $pdo->query("SELECT * FROM users ORDER BY role DESC, username ASC")->fetchAll();
$styles = [];
foreach ($pdo->query("SELECT * FROM block_styles")->fetchAll() as $s) {
    $styles[$s['block_type']] = $s;
}
$typeLabels = [
    'section_header' => 'Section Header',
    'item_title'     => 'Item Title',
    'price'          => 'Price',
    'description'    => 'Description',
];
$fontFamilies = ['Arial','Georgia','Verdana','Tahoma','Trebuchet MS','Times New Roman','Courier New','Impact'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel — <?= htmlspecialchars(SITE_NAME) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { background: #f0f2f5; min-height: 100vh; }

        /* --- Nav --- */
        nav { background: #1a252f; padding: 0 20px; display: flex; align-items: center; gap: 20px; height: 52px; }
        nav .brand { color: #fff; font-weight: bold; font-size: 15px; margin-right: auto; }
        nav a { color: #bdc3c7; text-decoration: none; font-size: 13px; padding: 6px 10px; border-radius: 4px; }
        nav a:hover, nav a.active { background: #2c3e50; color: #fff; }
        nav .role-badge { background: #e74c3c; color: #fff; font-size: 11px; font-weight: bold;
                          padding: 2px 8px; border-radius: 10px; }

        /* --- Tabs --- */
        .tabs { display: flex; gap: 2px; background: #dde3ea; padding: 6px 24px 0; }
        .tab-btn { padding: 9px 20px; border: none; cursor: pointer; font-size: 14px; font-weight: 600;
                   background: transparent; color: #555; border-radius: 6px 6px 0 0; }
        .tab-btn.active { background: #fff; color: #2c3e50; }

        /* --- Content --- */
        .content { padding: 24px; max-width: 1100px; margin: 0 auto; }
        .msg-box { padding: 11px 16px; border-radius: 5px; margin-bottom: 18px; font-size: 14px; }
        .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg-error   { background: #fdecea; color: #c0392b; border: 1px solid #e74c3c; }

        /* --- Cards --- */
        .card { background: #fff; border-radius: 8px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,.07); margin-bottom: 20px; }
        .card h2 { font-size: 16px; color: #2c3e50; margin-bottom: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; }

        /* --- Forms --- */
        .form-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 12px; }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group label { font-size: 12px; font-weight: 600; color: #666; }
        input[type="text"], input[type="email"], input[type="password"],
        input[type="number"], select {
            padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;
        }
        input[type="color"] { padding: 2px; height: 34px; cursor: pointer; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 13px; }
        .btn-blue   { background: #3498db; color: #fff; }
        .btn-blue:hover   { background: #2980b9; }
        .btn-green  { background: #27ae60; color: #fff; }
        .btn-green:hover  { background: #219a52; }
        .btn-red    { background: #e74c3c; color: #fff; }
        .btn-red:hover    { background: #c0392b; }
        .btn-gray   { background: #95a5a6; color: #fff; }

        /* --- User table --- */
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; font-weight: 600; color: #555; }
        tr:hover td { background: #fafbfc; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
        .badge-admin { background: #e8d5fb; color: #6c3483; }
        .badge-basic { background: #d6eaf8; color: #1a5276; }
        .badge-active   { background: #d4efdf; color: #1e8449; }
        .badge-inactive { background: #fdecea; color: #c0392b; }

        /* --- Edit row --- */
        .edit-row { display: none; background: #f8f9fa; }
        .edit-row.open { display: table-row; }
        .edit-row td { padding: 12px; }

        /* --- Brand standards --- */
        .bs-table { width: 100%; border-collapse: collapse; }
        .bs-table th, .bs-table td { padding: 10px 8px; border-bottom: 1px solid #eee; font-size: 13px; }
        .bs-table th { background: #f8f9fa; font-weight: 600; color: #555; }
        .bs-table input[type="number"] { width: 70px; }
        .bs-table select { min-width: 130px; }
        .preview-text { padding: 4px 8px; border: 1px solid #eee; border-radius: 3px; white-space: nowrap; }
    </style>
</head>
<body>

<nav>
    <span class="brand"><?= htmlspecialchars(SITE_NAME) ?></span>
    <a href="builder.php">Builder</a>
    <a href="crud.php">Asset Manager</a>
    <a href="admin_panel.php" class="active">Admin Panel</a>
    <span style="color:#bdc3c7; font-size:13px;">
        <?= htmlspecialchars($user['username']) ?>
        <span class="role-badge">ADMIN</span>
    </span>
    <a href="logout.php">Sign Out</a>
</nav>

<div class="tabs">
    <button class="tab-btn <?= $tab==='users' ?'active':'' ?>" onclick="showTab('users')">User Management</button>
    <button class="tab-btn <?= $tab==='brand' ?'active':'' ?>" onclick="showTab('brand')">Brand Standards</button>
</div>

<div class="content">

<?php if ($msg): ?>
    <div class="msg-box msg-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- USERS TAB                                                     -->
<!-- ============================================================ -->
<div id="tab-users" style="display:<?= $tab==='users'?'block':'none' ?>">
    <div class="card">
        <h2>Add New User</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="janedoe" required style="width:150px;">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="jane@store.com" required style="width:200px;">
                </div>
                <div class="form-group">
                    <label>Temp Password</label>
                    <input type="password" name="password" placeholder="min 8 chars" required style="width:160px;">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" style="width:100px;">
                        <option value="basic">Basic User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" name="action_create_user" class="btn btn-green">Add User</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>All Users (<?= count($users) ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Username</th><th>Email</th><th>Role</th>
                    <th>Status</th><th>Created</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['username']) ?></strong>
                        <?php if ($u['id'] === $user['id']): ?>
                            <span style="font-size:11px; color:#7f8c8d;">(you)</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
                    <td><span class="badge badge-<?= $u['is_active']?'active':'inactive' ?>">
                        <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                    </span></td>
                    <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-blue" style="font-size:11px; padding:5px 10px;"
                                onclick="toggleEdit(<?= $u['id'] ?>)">Edit</button>

                        <?php if ($u['id'] !== $user['id']): ?>
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>?')">
                            <input type="hidden" name="del_id" value="<?= $u['id'] ?>">
                            <button type="submit" name="action_delete_user"
                                    class="btn btn-red" style="font-size:11px; padding:5px 10px;">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <!-- Inline edit row -->
                <tr class="edit-row" id="edit-<?= $u['id'] ?>">
                    <td colspan="6">
                        <form method="POST">
                            <div class="form-row">
                                <input type="hidden" name="edit_id" value="<?= $u['id'] ?>">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="edit_email"
                                           value="<?= htmlspecialchars($u['email']) ?>" style="width:200px;" required>
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <select name="edit_role" style="width:100px;">
                                        <option value="basic"  <?= $u['role']==='basic' ?'selected':'' ?>>Basic</option>
                                        <option value="admin"  <?= $u['role']==='admin' ?'selected':'' ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="edit_active" value="1"
                                               <?= $u['is_active']?'checked':'' ?>> Active
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" name="action_edit_user"
                                            class="btn btn-green" style="font-size:12px;">Save</button>
                                </div>
                            </div>
                        </form>
                        <form method="POST" style="margin-top:8px;">
                            <div class="form-row">
                                <input type="hidden" name="reset_uid" value="<?= $u['id'] ?>">
                                <div class="form-group">
                                    <label>Set New Password</label>
                                    <input type="password" name="new_pass" placeholder="min 8 characters" style="width:200px;">
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" name="action_reset_pass"
                                            class="btn btn-gray" style="font-size:12px;">Reset Password</button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================ -->
<!-- BRAND STANDARDS TAB                                          -->
<!-- ============================================================ -->
<div id="tab-brand" style="display:<?= $tab==='brand'?'block':'none' ?>">
    <div class="card">
        <h2>Brand Standards — Locked Text Styles</h2>
        <p style="font-size:13px; color:#7f8c8d; margin-bottom:16px;">
            These styles apply to the four typed text blocks. Basic users cannot change them.
            Changes take effect immediately on the display screen.
        </p>
        <form method="POST">
            <table class="bs-table">
                <thead>
                    <tr>
                        <th>Block Type</th>
                        <th>Font Family</th>
                        <th>Size (px)</th>
                        <th>Color</th>
                        <th>Weight</th>
                        <th>Style</th>
                        <th>Line Height</th>
                        <th>Live Preview</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($typeLabels as $t => $label):
                    $s = $styles[$t] ?? [];
                    $ff = $s['font_family'] ?? 'Arial';
                    $fs = $s['font_size']   ?? 16;
                    $fc = $s['font_color']  ?? '#000000';
                    $fw = $s['font_weight'] ?? 'normal';
                    $fi = $s['font_style']  ?? 'normal';
                    $lh = $s['line_height'] ?? 1.4;
                ?>
                <tr id="bs-row-<?= $t ?>">
                    <td><strong><?= htmlspecialchars($label) ?></strong></td>
                    <td>
                        <select name="bs_<?= $t ?>_family" onchange="updatePreview('<?= $t ?>')">
                            <?php foreach ($fontFamilies as $ff_opt): ?>
                                <option value="<?= htmlspecialchars($ff_opt) ?>"
                                    <?= $ff === $ff_opt ? 'selected' : '' ?>><?= $ff_opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="bs_<?= $t ?>_size" value="<?= intval($fs) ?>"
                               min="8" max="200" onchange="updatePreview('<?= $t ?>')">
                    </td>
                    <td>
                        <input type="color" name="bs_<?= $t ?>_color" value="<?= htmlspecialchars($fc) ?>"
                               oninput="updatePreview('<?= $t ?>')">
                    </td>
                    <td>
                        <select name="bs_<?= $t ?>_weight" onchange="updatePreview('<?= $t ?>')">
                            <option value="normal" <?= $fw==='normal'?'selected':'' ?>>Normal</option>
                            <option value="bold"   <?= $fw==='bold'  ?'selected':'' ?>>Bold</option>
                        </select>
                    </td>
                    <td>
                        <select name="bs_<?= $t ?>_fstyle" onchange="updatePreview('<?= $t ?>')">
                            <option value="normal" <?= $fi==='normal' ?'selected':'' ?>>Normal</option>
                            <option value="italic" <?= $fi==='italic' ?'selected':'' ?>>Italic</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="bs_<?= $t ?>_lh" value="<?= number_format(floatval($lh),2) ?>"
                               min="0.8" max="4" step="0.1" style="width:70px;" onchange="updatePreview('<?= $t ?>')">
                    </td>
                    <td>
                        <span class="preview-text" id="preview-<?= $t ?>"
                              style="font-family:<?= htmlspecialchars($ff) ?>; font-size:<?= intval($fs) ?>px;
                                     color:<?= htmlspecialchars($fc) ?>; font-weight:<?= htmlspecialchars($fw) ?>;
                                     font-style:<?= htmlspecialchars($fi) ?>; line-height:<?= floatval($lh) ?>;">
                            <?= htmlspecialchars($label) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:16px;">
                <button type="submit" name="action_save_styles" class="btn btn-green">
                    Save Brand Standards
                </button>
            </div>
        </form>
    </div>
</div>

</div><!-- .content -->

<script>
    function showTab(name) {
        document.getElementById('tab-users').style.display = name==='users' ? 'block' : 'none';
        document.getElementById('tab-brand').style.display = name==='brand' ? 'block' : 'none';
        document.querySelectorAll('.tab-btn').forEach(function(b, i) {
            b.classList.toggle('active', ['users','brand'][i] === name);
        });
    }

    function toggleEdit(id) {
        var row = document.getElementById('edit-' + id);
        row.classList.toggle('open');
    }

    function updatePreview(type) {
        var row     = document.getElementById('bs-row-' + type);
        var preview = document.getElementById('preview-' + type);
        preview.style.fontFamily  = row.querySelector('[name$="_family"]').value;
        preview.style.fontSize    = row.querySelector('[name$="_size"]').value + 'px';
        preview.style.color       = row.querySelector('[name$="_color"]').value;
        preview.style.fontWeight  = row.querySelector('[name$="_weight"]').value;
        preview.style.fontStyle   = row.querySelector('[name$="_fstyle"]').value;
        preview.style.lineHeight  = row.querySelector('[name$="_lh"]').value;
    }
</script>
</body>
</html>
