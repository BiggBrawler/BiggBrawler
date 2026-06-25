<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

// ---- Allowed image types for upload ----
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_MIME_TYPES',  ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_UPLOAD_BYTES',    10 * 1024 * 1024); // 10 MB

function validateImageFile(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'msg' => 'Upload error code ' . $file['error']];
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'msg' => 'File exceeds 10 MB limit'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return ['ok' => false, 'msg' => 'File type not allowed. Use JPG, PNG, GIF, or WEBP.'];
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_MIME_TYPES, true)) {
        return ['ok' => false, 'msg' => 'File MIME type rejected.'];
    }
    return ['ok' => true, 'ext' => $ext];
}

function ensureUploadsDir(): void {
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
    }
}

// ============================================================
// GET: get_layout
// ============================================================
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_layout') {
    $settings  = $pdo->query("SELECT * FROM canvas_settings WHERE id = 1")->fetch();
    $elements  = $pdo->query(
        "SELECT ce.*, a.content AS db_content
         FROM canvas_elements ce
         LEFT JOIN assets a ON ce.asset_id = a.id
         ORDER BY ce.id ASC"
    )->fetchAll();
    echo json_encode(['settings' => $settings, 'elements' => $elements]);
    exit;
}

// ============================================================
// GET: get_assets
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_assets') {
    $stmt = $pdo->query("SELECT * FROM assets ORDER BY id DESC");
    echo json_encode($stmt->fetchAll());
    exit;
}

// ============================================================
// POST: publish  – saves layout to DB
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'publish') {
    $data  = json_decode($_POST['layout_data'] ?? '[]', true) ?: [];
    $bgType = $_POST['bg_type'] ?? 'color';
    $bgVal  = $_POST['bg_val']  ?? '#1a1a2e';

    // Handle background image upload
    if ($bgType === 'image' && isset($_FILES['bg_file'])) {
        $check = validateImageFile($_FILES['bg_file']);
        if ($check['ok']) {
            ensureUploadsDir();
            $fileName = 'bg_' . time() . '.' . $check['ext'];
            if (move_uploaded_file($_FILES['bg_file']['tmp_name'], 'uploads/' . $fileName)) {
                $bgVal = 'uploads/' . $fileName;
            }
        }
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE canvas_settings SET bg_type = ?, bg_val = ? WHERE id = 1")
            ->execute([$bgType, $bgVal]);

        $pdo->exec("DELETE FROM canvas_elements");

        foreach ($data as $el) {
            $assetId       = !empty($el['asset_id']) ? intval($el['asset_id']) : null;
            $manualContent = $el['manual_content'] ?? '';

            // Auto-save new standalone content to assets pool (no duplicates)
            if (!$assetId && !empty($manualContent) && !empty($el['save_to_db_pool'])) {
                $dup = $pdo->prepare("SELECT id FROM assets WHERE type = ? AND content = ? LIMIT 1");
                $dup->execute([$el['type'], $manualContent]);
                $existing = $dup->fetch();
                if ($existing) {
                    $assetId       = $existing['id'];
                    $manualContent = null;
                } else {
                    $ins = $pdo->prepare("INSERT INTO assets (type, content, label) VALUES (?, ?, ?)");
                    $preview = substr(strip_tags($manualContent), 0, 20);
                    $ins->execute([$el['type'], $manualContent, 'Auto-Saved: ' . $preview]);
                    $assetId       = $pdo->lastInsertId();
                    $manualContent = null;
                }
            }

            $pdo->prepare(
                "INSERT INTO canvas_elements
                 (type, x_pos, y_pos, width, height, manual_content, asset_id, font_family, font_size, font_color)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $el['type'],
                intval($el['x_pos']),
                intval($el['y_pos']),
                intval($el['width']),
                intval($el['height']),
                $manualContent ?: null,
                $assetId,
                $el['font_family'] ?? 'Arial',
                intval($el['font_size'] ?? 16),
                $el['font_color'] ?? '#000000',
            ]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Publish failed. Changes were not saved.']);
    }
    exit;
}

// ============================================================
// POST: upload_file  – direct image upload from builder
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_file') {
    if (!isset($_FILES['file'])) {
        echo json_encode(['status' => 'error', 'message' => 'No file received.']);
        exit;
    }
    $check = validateImageFile($_FILES['file']);
    if (!$check['ok']) {
        echo json_encode(['status' => 'error', 'message' => $check['msg']]);
        exit;
    }
    ensureUploadsDir();
    $fileName = 'asset_' . uniqid('', true) . '.' . $check['ext'];
    $dest     = 'uploads/' . $fileName;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        echo json_encode(['status' => 'success', 'path' => $dest]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Could not save file.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
?>
