<?php
require_once 'auth.php';
require_once 'db_connect.php';
requireLogin();
$me      = currentUser();
$isAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Builder — <?= htmlspecialchars(SITE_NAME) ?></title>
<script src="https://cdn.jsdelivr.net/npm/interactjs@1.10.27/dist/interact.min.js"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }

body { background: #2c3e50; display: flex; flex-direction: column; height: 100vh; overflow: hidden; color: #fff; }

/* ── Nav ── */
#top-nav {
    background: #1a252f; padding: 0 16px; display: flex; align-items: center;
    gap: 14px; height: 46px; flex-shrink: 0; border-bottom: 1px solid #0d1b24;
}
#top-nav .brand { font-weight: bold; font-size: 14px; color: #fff; margin-right: auto; }
#top-nav a { color: #bdc3c7; text-decoration: none; font-size: 12px; padding: 5px 9px; border-radius: 3px; }
#top-nav a:hover { background: #2c3e50; color: #fff; }
#top-nav .user-info { font-size: 12px; color: #bdc3c7; }
.role-tag { background: <?= $isAdmin ? '#e74c3c' : '#3498db' ?>; color: #fff;
            font-size: 10px; font-weight: bold; padding: 1px 6px; border-radius: 8px;
            text-transform: uppercase; margin-left: 4px; }

/* ── Control bar ── */
#control-bar {
    background: #1a252f; padding: 8px 14px; display: flex; gap: 8px;
    align-items: center; flex-wrap: wrap; flex-shrink: 0; border-bottom: 2px solid #34495e;
}
.btn { background: #3498db; border: none; color: #fff; padding: 6px 12px;
       border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; }
.btn:hover { filter: brightness(1.15); }
.btn.green  { background: #27ae60; }
.btn.purple { background: #8e44ad; }
.btn.orange { background: #e67e22; }
.btn.danger { background: #e74c3c; }
.btn.gray   { background: #7f8c8d; }
.btn:disabled { opacity: 0.4; cursor: not-allowed; filter: none; }
.sep { border-left: 1px solid #34495e; height: 24px; margin: 0 4px; }

/* Align toolbar – hidden until multi-select */
#align-bar {
    background: #1a252f; padding: 6px 14px; display: none; gap: 6px;
    align-items: center; flex-shrink: 0; border-bottom: 1px solid #34495e;
}
#align-bar span { font-size: 11px; color: #bdc3c7; margin-right: 4px; }
.align-btn { background: #2c3e50; border: 1px solid #4a6278; color: #fff;
             width: 32px; height: 28px; border-radius: 3px; cursor: pointer;
             font-size: 13px; display: inline-flex; align-items: center; justify-content: center; }
.align-btn:hover { background: #3d5166; }

/* Section target banner (basic users) */
#section-banner {
    background: #d35400; color: #fff; text-align: center; font-size: 12px;
    font-weight: 600; padding: 5px; display: none; flex-shrink: 0;
}

/* ── Canvas wrapper ── */
#editor-frame { flex: 1; overflow: auto; padding: 40px; display: flex;
                justify-content: flex-start; align-items: flex-start; }

#builder-canvas {
    width: 1920px; height: 1080px; background: #fff; position: relative;
    flex-shrink: 0; box-shadow: 0 10px 30px rgba(0,0,0,.5);
    background-size: cover; background-position: center;
}

/* ── Blocks ── */
.editable-block {
    position: absolute; min-width: 40px; min-height: 24px;
    cursor: default; touch-action: none;
}
.editable-block.draggable-block { cursor: move; }
.editable-block.selected  { outline: 2px solid #e74c3c; box-shadow: 0 0 8px rgba(231,76,60,.5); }
.editable-block.multi-sel { outline: 2px solid #f39c12; box-shadow: 0 0 6px rgba(243,156,18,.4); }
.editable-block.locked-block { cursor: default; }
.lock-icon {
    position: absolute; top: 2px; right: 2px; font-size: 11px; color: rgba(255,255,255,.8);
    background: rgba(0,0,0,.4); border-radius: 2px; padding: 1px 3px; pointer-events: none; z-index: 5;
}

/* Section blocks */
.section-block {
    border: 2px solid #8e44ad; overflow: hidden;
    background-size: cover; background-position: center;
}
.section-block.targeted { border: 3px solid #e67e22; box-shadow: 0 0 12px rgba(230,126,34,.6); }
.section-label {
    position: absolute; top: 2px; left: 4px; font-size: 10px; color: rgba(255,255,255,.7);
    background: rgba(142,68,173,.7); padding: 1px 5px; border-radius: 2px;
    pointer-events: none; z-index: 5;
}

/* Text blocks */
.text-inner {
    width: 100%; height: 100%; padding: 4px; outline: none;
    word-break: break-word; overflow: hidden;
}
.text-inner[contenteditable="true"] { cursor: text; }

/* Image / video blocks */
.editable-block img, .editable-block video {
    width: 100%; height: 100%; object-fit: fill; display: block; pointer-events: none;
}

/* ── Inspector ── */
#inspector {
    position: fixed; right: 16px; top: 100px; width: 290px;
    background: #1a252f; border: 1px solid #34495e; border-radius: 6px;
    padding: 14px; display: none; flex-direction: column; gap: 10px;
    z-index: 300; box-shadow: 0 4px 20px rgba(0,0,0,.4);
    max-height: calc(100vh - 120px); overflow-y: auto;
}
#inspector h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px;
                color: #f39c12; border-bottom: 1px solid #34495e; padding-bottom: 6px; }
#inspector label { font-size: 11px; text-transform: uppercase; letter-spacing: .7px;
                   color: #bdc3c7; display: block; margin-bottom: 3px; }
#inspector input, #inspector select {
    width: 100%; padding: 6px 8px; border-radius: 4px; border: 1px solid #34495e;
    background: #2c3e50; color: #fff; font-size: 13px;
}
#inspector input[type="color"]  { height: 32px; padding: 2px; cursor: pointer; }
#inspector input[type="file"]   { font-size: 12px; color: #aaa; }
#inspector input[type="number"] { width: 80px; }
.insp-section { border-top: 1px solid #2c3e50; padding-top: 8px; }
.insp-row { display: flex; gap: 8px; align-items: flex-end; }
.insp-row > * { flex: 1; }

/* WYSIWYG bar */
#wysiwyg-bar { display: flex; gap: 3px; flex-wrap: wrap; }
.fmt-btn { background: #2c3e50; border: 1px solid #4a6278; color: #fff;
           width: 30px; height: 26px; border-radius: 3px; cursor: pointer; font-size: 12px;
           display: inline-flex; align-items: center; justify-content: center; }
.fmt-btn:hover { background: #3d5166; }

/* Brand lock badge */
.brand-lock { background: #8e44ad; color: #fff; font-size: 11px;
              padding: 3px 8px; border-radius: 10px; display: inline-block; margin-bottom: 4px; }

/* ── Brand Standards Modal (admin) ── */
#brand-modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7);
    z-index: 500; align-items: center; justify-content: center;
}
#brand-modal-overlay.open { display: flex; }
#brand-modal {
    background: #1a252f; border-radius: 8px; padding: 24px; width: 700px; max-width: 95vw;
    max-height: 90vh; overflow-y: auto; border: 1px solid #34495e;
}
#brand-modal h2 { font-size: 16px; margin-bottom: 4px; }
#brand-modal p  { font-size: 12px; color: #bdc3c7; margin-bottom: 16px; }
.bm-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.bm-table th, .bm-table td { padding: 8px; border-bottom: 1px solid #2c3e50; }
.bm-table th { color: #bdc3c7; font-weight: 600; text-align: left; }
.bm-table input, .bm-table select {
    background: #2c3e50; border: 1px solid #34495e; color: #fff;
    padding: 5px 7px; border-radius: 3px; font-size: 12px;
}
.bm-table input[type="color"] { width: 44px; height: 28px; padding: 1px; cursor: pointer; }
.bm-table input[type="number"] { width: 64px; }

/* ── Toast ── */
#toast {
    position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
    background: #27ae60; color: #fff; padding: 10px 22px; border-radius: 4px;
    font-weight: bold; font-size: 13px; display: none; z-index: 9999;
    box-shadow: 0 4px 12px rgba(0,0,0,.3);
}
#toast.err { background: #e74c3c; }
</style>
</head>
<body>

<!-- ── Top Nav ── -->
<div id="top-nav">
    <span class="brand"><?= htmlspecialchars(SITE_NAME) ?></span>
    <a href="crud.php">Asset Library</a>
    <?php if ($isAdmin): ?>
    <a href="admin_panel.php">Admin Panel</a>
    <?php endif; ?>
    <span class="user-info">
        <?= htmlspecialchars($me['username']) ?>
        <span class="role-tag"><?= $isAdmin ? 'ADMIN' : 'USER' ?></span>
    </span>
    <a href="viewer.php" target="_blank">View Display ↗</a>
    <a href="logout.php">Sign Out</a>
</div>

<!-- ── Control bar ── -->
<div id="control-bar">
    <?php if ($isAdmin): ?>
        <button class="btn purple" onclick="createSection()">+ Section</button>
        <button class="btn"        onclick="createBlock('text','free')">+ Free Text</button>
        <button class="btn"        onclick="createBlock('video',null)">+ Video</button>
        <div class="sep"></div>
    <?php endif; ?>

    <button class="btn orange" onclick="createBlock('text','section_header')">+ Section Header</button>
    <button class="btn orange" onclick="createBlock('text','item_title')">+ Item Title</button>
    <button class="btn orange" onclick="createBlock('text','price')">+ Price</button>
    <button class="btn orange" onclick="createBlock('text','description')">+ Description</button>
    <button class="btn"        onclick="createBlock('image',null)">+ Image</button>

    <?php if ($isAdmin): ?>
    <div class="sep"></div>
    <label style="font-size:11px; color:#bdc3c7;">Background:</label>
    <select id="bg-type" onchange="toggleBgInputs()" style="padding:5px 7px; border-radius:3px; border:1px solid #34495e; background:#2c3e50; color:#fff; font-size:12px;">
        <option value="color">Color</option>
        <option value="image">Image</option>
    </select>
    <input type="color" id="bg-color" value="#1a1a2e" oninput="applyBg()"
           style="width:40px; height:30px; padding:2px; border:none; cursor:pointer; border-radius:3px;">
    <input type="file"  id="bg-file"  accept="image/*" onchange="applyBgFile()"
           style="display:none; font-size:11px; color:#aaa;">
    <div class="sep"></div>
    <button class="btn purple" onclick="openBrandModal()">Brand Standards</button>
    <?php endif; ?>

    <button class="btn green" style="margin-left:auto;" onclick="publishCanvas()">&#10003; Publish</button>
</div>

<!-- ── Align bar (shown on multi-select) ── -->
<div id="align-bar">
    <span>Align selection:</span>
    <button class="align-btn" title="Align Left"   onclick="alignBlocks('left')">⬛←</button>
    <button class="align-btn" title="Align Right"  onclick="alignBlocks('right')">→⬛</button>
    <button class="align-btn" title="Align Top"    onclick="alignBlocks('top')">⬛↑</button>
    <button class="align-btn" title="Align Bottom" onclick="alignBlocks('bottom')">↓⬛</button>
    <button class="align-btn" title="Center Horiz" onclick="alignBlocks('center-h')">⬛|⬛</button>
    <button class="align-btn" title="Center Vert"  onclick="alignBlocks('center-v')">⬛—⬛</button>
    <div class="sep"></div>
    <span id="sel-count" style="font-size:11px; color:#bdc3c7;"></span>
</div>

<!-- ── Section banner for basic users ── -->
<?php if (!$isAdmin): ?>
<div id="section-banner">
    Click on a <strong>section</strong> (purple border) to target it, then add your blocks.
</div>
<?php endif; ?>

<!-- ── Canvas ── -->
<div id="editor-frame">
    <div id="builder-canvas"></div>
</div>

<!-- ── Inspector panel ── -->
<div id="inspector">
    <h3 id="insp-title">Block</h3>

    <!-- Section controls (admin only) -->
    <div id="insp-section" class="insp-section" style="display:none;">
        <label>Section Background Image</label>
        <input type="file" id="section-bg-file" accept="image/*" onchange="uploadSectionBg(this)">
        <div id="section-bg-preview" style="margin-top:4px; font-size:11px; color:#bdc3c7;"></div>
        <button class="btn danger" style="width:100%; margin-top:6px; font-size:12px;"
                onclick="clearSectionBg()">Remove Background</button>
    </div>

    <!-- WYSIWYG (admin only, free text) -->
    <div id="insp-wysiwyg" class="insp-section" style="display:none;">
        <label>Formatting</label>
        <div id="wysiwyg-bar">
            <button class="fmt-btn" title="Bold"      onmousedown="fmtCmd(event,'bold')"><b>B</b></button>
            <button class="fmt-btn" title="Italic"    onmousedown="fmtCmd(event,'italic')"><i>I</i></button>
            <button class="fmt-btn" title="Underline" onmousedown="fmtCmd(event,'underline')"><u>U</u></button>
            <button class="fmt-btn" title="Strike"    onmousedown="fmtCmd(event,'strikeThrough')"><s>S</s></button>
            <button class="fmt-btn" title="Align Left"   onmousedown="fmtCmd(event,'justifyLeft')">&#8676;</button>
            <button class="fmt-btn" title="Center"       onmousedown="fmtCmd(event,'justifyCenter')">&#8660;</button>
            <button class="fmt-btn" title="Align Right"  onmousedown="fmtCmd(event,'justifyRight')">&#8677;</button>
        </div>
    </div>

    <!-- Font controls (admin only, free text) -->
    <div id="insp-font" class="insp-section" style="display:none;">
        <label>Font</label>
        <select id="font-family" onchange="updateStyle('fontFamily',this.value)">
            <option>Arial</option><option>Georgia</option><option>Verdana</option>
            <option>Tahoma</option><option value="'Trebuchet MS',sans-serif">Trebuchet MS</option>
            <option value="'Times New Roman',serif">Times New Roman</option>
            <option value="'Courier New',monospace">Courier New</option>
            <option>Impact</option>
        </select>
        <div class="insp-row" style="margin-top:6px;">
            <div>
                <label>Size (px)</label>
                <input type="number" id="font-size" min="8" max="300"
                       oninput="updateStyle('fontSize',this.value+'px')">
            </div>
            <div>
                <label>Line Height</label>
                <input type="number" id="line-height" min="0.8" max="4" step="0.1"
                       oninput="updateStyle('lineHeight',this.value)">
            </div>
        </div>
        <div class="insp-row" style="margin-top:6px;">
            <div>
                <label>Color</label>
                <input type="color" id="font-color" style="width:100%;"
                       oninput="updateStyle('color',this.value)">
            </div>
            <div>
                <label>Weight</label>
                <select id="font-weight" onchange="updateStyle('fontWeight',this.value)">
                    <option value="normal">Normal</option>
                    <option value="bold">Bold</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Brand lock info (typed text blocks) -->
    <div id="insp-brand-lock" class="insp-section" style="display:none;">
        <span class="brand-lock">&#128274; Brand Style Applied</span>
        <div id="insp-brand-name" style="font-size:11px; color:#bdc3c7; margin-top:4px;"></div>
    </div>

    <!-- Image upload -->
    <div id="insp-image" class="insp-section" style="display:none;">
        <label>Upload Image</label>
        <input type="file" id="img-file" accept="image/*" onchange="uploadBlockImage(this)">
    </div>

    <!-- Video upload (admin only) -->
    <div id="insp-video" class="insp-section" style="display:none;">
        <label>Upload Video</label>
        <input type="file" id="vid-file" accept="video/mp4,video/webm,video/ogg" onchange="uploadBlockVideo(this)">
        <div style="font-size:11px; color:#bdc3c7; margin-top:4px;">MP4, WebM, OGV — max 50 MB</div>
    </div>

    <!-- DB Asset link -->
    <div id="insp-asset" class="insp-section">
        <label>Link DB Asset</label>
        <select id="asset-link" onchange="linkAsset(this.value)">
            <option value="">— None (manual content) —</option>
        </select>
    </div>

    <!-- Lock toggle -->
    <div class="insp-section">
        <label>
            <input type="checkbox" id="lock-toggle" onchange="toggleLock(this.checked)">
            Lock this block (prevent accidental moves)
        </label>
    </div>

    <!-- Delete -->
    <div class="insp-section">
        <button class="btn danger" style="width:100%; font-size:12px;" onclick="deleteSelected()">
            &#128465; Delete Block
        </button>
    </div>
</div>

<!-- ── Brand Standards Modal (admin only) ── -->
<?php if ($isAdmin): ?>
<div id="brand-modal-overlay">
    <div id="brand-modal">
        <h2>Brand Standards</h2>
        <p>These styles are locked for basic users. Changes are saved to the database.</p>
        <table class="bm-table">
            <thead><tr>
                <th>Type</th><th>Font</th><th>Size</th><th>Color</th>
                <th>Weight</th><th>Style</th><th>Line H</th>
            </tr></thead>
            <tbody id="bm-tbody"></tbody>
        </table>
        <div style="margin-top:16px; display:flex; gap:10px;">
            <button class="btn green" onclick="saveBrandStandards()">Save to Database</button>
            <button class="btn gray"  onclick="closeBrandModal()">Cancel</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="toast"></div>

<script>
// ============================================================
// CONSTANTS (injected by PHP)
// ============================================================
var IS_ADMIN  = <?= $isAdmin ? 'true' : 'false' ?>;
var SITE_NAME = <?= json_encode(SITE_NAME) ?>;

// Block default sizes
var BLOCK_DEFAULTS = {
    section_header: { w:420, h:60  },
    item_title:     { w:300, h:50  },
    price:          { w:160, h:60  },
    description:    { w:360, h:90  },
    image:          { w:220, h:160 },
    video:          { w:400, h:225 },
    free:           { w:320, h:80  },
    section:        { w:600, h:380 },
};

var FONT_FAMILIES = ['Arial','Georgia','Verdana','Tahoma',
    "'Trebuchet MS',sans-serif","'Times New Roman',serif",
    "'Courier New',monospace",'Impact'];

var TYPE_LABELS = {
    section_header:'Section Header', item_title:'Item Title',
    price:'Price', description:'Description'
};

// ============================================================
// STATE
// ============================================================
var activeBlock    = null;   // single selected block
var multiSel       = [];     // multi-selection array
var targetSection  = null;   // section targeted for adding (basic users + admin)
var savedRange     = null;   // preserved text selection for WYSIWYG
var assetsCache    = [];
var blockStyles    = {};     // brand standards cache

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    Promise.all([loadAssets(), loadLayout()]).catch(function() {
        showToast('Failed to load layout.', true);
    });
    setupCanvas();
    if (!IS_ADMIN) {
        document.getElementById('section-banner').style.display = 'block';
    }
});

// ============================================================
// LOAD
// ============================================================
function loadAssets() {
    return fetch('api.php?action=get_assets')
        .then(function(r){ return r.json(); })
        .then(function(list) {
            assetsCache = list;
            var sel = document.getElementById('asset-link');
            sel.innerHTML = '<option value="">— None (manual content) —</option>';
            list.forEach(function(a) {
                sel.innerHTML += '<option value="'+a.id+'">['+a.type.toUpperCase()+'] '+escHtml(a.label||a.content.substr(0,20))+'</option>';
            });
        });
}

function loadLayout() {
    return fetch('api.php?action=get_layout')
        .then(function(r){ return r.json(); })
        .then(function(data) {
            blockStyles = data.block_styles || {};
            var canvas  = document.getElementById('builder-canvas');

            if (data.settings) {
                var s = data.settings;
                document.getElementById('bg-type') && (document.getElementById('bg-type').value = s.bg_type);
                if (s.bg_type === 'color') {
                    document.getElementById('bg-color') && (document.getElementById('bg-color').value = s.bg_val);
                    applyBg();
                } else {
                    canvas.style.backgroundImage = "url('"+s.bg_val+"')";
                }
                IS_ADMIN && toggleBgInputs();
            }

            var elements = data.elements || [];
            // Render sections first
            elements.filter(function(e){ return e.type==='section'; }).forEach(function(e){
                renderSection(e);
            });
            // Render children and root blocks
            elements.filter(function(e){ return e.type!=='section'; }).forEach(function(e){
                var parent = e.section_id
                    ? document.querySelector('.section-block[data-db-id="'+e.section_id+'"]')
                    : canvas;
                if (parent) renderBlock(e, parent);
            });

            setupInteract();
        });
}

// ============================================================
// BACKGROUND (admin)
// ============================================================
function toggleBgInputs() {
    if (!IS_ADMIN) return;
    var t = document.getElementById('bg-type').value;
    document.getElementById('bg-color').style.display = t==='color' ? 'inline-block' : 'none';
    document.getElementById('bg-file').style.display  = t==='image' ? 'inline-block' : 'none';
}
function applyBg() {
    if (!IS_ADMIN) return;
    var canvas = document.getElementById('builder-canvas');
    canvas.style.backgroundColor = document.getElementById('bg-color').value;
    canvas.style.backgroundImage = 'none';
}
function applyBgFile() {
    if (!IS_ADMIN) return;
    var f = document.getElementById('bg-file').files[0];
    if (!f) return;
    var r = new FileReader();
    r.onload = function(e){ document.getElementById('builder-canvas').style.backgroundImage = "url('"+e.target.result+"')"; };
    r.readAsDataURL(f);
}

// ============================================================
// CREATE SECTION (admin)
// ============================================================
function createSection() {
    if (!IS_ADMIN) return;
    renderSection({
        type:'section', temp_id: tmpId(), db_id: null,
        x_pos:80, y_pos:80, width:600, height:380, section_bg:null, locked:0
    });
}

function renderSection(el) {
    var s = document.createElement('div');
    s.className = 'editable-block section-block';
    if (!el.locked) s.classList.add('draggable-block');
    s.dataset.type    = 'section';
    s.dataset.tempId  = el.temp_id || tmpId();
    s.dataset.dbId    = el.id      || '';
    s.dataset.locked  = el.locked  || 0;
    s.dataset.sectionBg = el.section_bg || '';
    s.style.width     = el.width  + 'px';
    s.style.height    = el.height + 'px';
    s.style.transform = 'translate('+el.x_pos+'px,'+el.y_pos+'px)';
    s.setAttribute('data-x', el.x_pos);
    s.setAttribute('data-y', el.y_pos);
    if (el.section_bg) {
        s.style.backgroundImage = "url('"+el.section_bg+"')";
    }
    // Label
    var lbl = document.createElement('div');
    lbl.className = 'section-label';
    lbl.textContent = 'Section';
    s.appendChild(lbl);
    // Lock icon
    if (el.locked) appendLockIcon(s);

    s.addEventListener('mousedown', function(e) {
        if (e.target === s || e.target === lbl) {
            e.stopPropagation();
            if (IS_ADMIN) selectBlock(s, e);
            setTargetSection(s);
        }
    });
    document.getElementById('builder-canvas').appendChild(s);
}

// ============================================================
// CREATE BLOCK
// ============================================================
function createBlock(type, subtype) {
    // Basic users must have a section targeted
    if (!IS_ADMIN && !targetSection) {
        showToast('Please click on a section first to add content.', true);
        return;
    }

    var key  = subtype || type;
    var def  = BLOCK_DEFAULTS[key] || {w:200,h:100};
    var parent = targetSection || document.getElementById('builder-canvas');

    // For basic users: check if block fits within targeted section
    if (!IS_ADMIN && targetSection) {
        var sw = targetSection.offsetWidth;
        var sh = targetSection.offsetHeight;
        if (def.w + 20 > sw || def.h + 20 > sh) {
            showToast('Block ('+def.w+'×'+def.h+'px) is too large for this section ('+sw+'×'+sh+'px).', true);
            return;
        }
    }

    var el = {
        type: type, block_subtype: subtype || 'free',
        x_pos: 10, y_pos: 10, width: def.w, height: def.h,
        manual_content: type==='text' ? (subtype ? 'Enter text here' : 'Double-click to edit') : '',
        asset_id: null, locked: 0,
        font_family: 'Arial', font_size: 16, font_color: '#000000',
        font_weight: 'normal', font_style: 'normal', line_height: 1.4
    };
    renderBlock(el, parent);
}

function renderBlock(el, parent) {
    var block = document.createElement('div');
    block.className = 'editable-block';
    var isChildBlock = parent !== document.getElementById('builder-canvas');
    block.classList.add(isChildBlock ? 'child-block' : 'root-block');
    if (!el.locked && (IS_ADMIN || isChildBlock)) block.classList.add('draggable-block');
    if (el.locked) block.classList.add('locked-block');

    block.dataset.type    = el.type;
    block.dataset.subtype = el.block_subtype || 'free';
    block.dataset.assetId = el.asset_id   || '';
    block.dataset.sectionBg = '';
    block.dataset.locked  = el.locked     ? '1' : '0';
    block.style.width     = el.width  + 'px';
    block.style.height    = el.height + 'px';
    block.style.transform = 'translate('+el.x_pos+'px,'+el.y_pos+'px)';
    block.setAttribute('data-x', el.x_pos);
    block.setAttribute('data-y', el.y_pos);

    var content = el.asset_id ? el.db_content : el.manual_content;

    if (el.type === 'text') {
        applyTextStyles(block, el);
        var inner = document.createElement('div');
        inner.className = 'text-inner';
        // Basic users can edit content but not CSS of branded blocks
        inner.contentEditable = 'true';
        inner.innerHTML = content || (el.block_subtype !== 'free' ? 'Enter text here' : 'Double-click to edit');
        inner.addEventListener('focus', function() { if (block !== activeBlock) selectBlock(block); });
        block.appendChild(inner);
    } else if (el.type === 'image') {
        var img = document.createElement('img');
        img.src = content || svgPlaceholder(el.width, el.height, 'Image');
        img.alt = '';
        block.appendChild(img);
    } else if (el.type === 'video') {
        var vid = document.createElement('video');
        vid.autoplay = true; vid.loop = true; vid.muted = true; vid.playsInline = true;
        if (content) { var src = document.createElement('source'); src.src = content; vid.appendChild(src); }
        block.appendChild(vid);
    }

    if (el.locked) appendLockIcon(block);

    block.addEventListener('mousedown', function(e) {
        if (e.target.closest('.text-inner')) return; // let text-inner handle its own focus
        e.stopPropagation();
        if (e.shiftKey) {
            toggleMultiSel(block);
        } else {
            selectBlock(block, e);
        }
    });

    parent.appendChild(block);
}

// ============================================================
// APPLY TEXT STYLES
// ============================================================
function applyTextStyles(block, el) {
    var sub = el.block_subtype || 'free';
    if (sub !== 'free' && blockStyles[sub]) {
        var bs = blockStyles[sub];
        block.style.fontFamily  = bs.font_family;
        block.style.fontSize    = bs.font_size + 'px';
        block.style.color       = bs.font_color;
        block.style.fontWeight  = bs.font_weight;
        block.style.fontStyle   = bs.font_style;
        block.style.lineHeight  = bs.line_height;
    } else {
        block.style.fontFamily  = el.font_family  || 'Arial';
        block.style.fontSize    = (el.font_size||16) + 'px';
        block.style.color       = el.font_color   || '#000000';
        block.style.fontWeight  = el.font_weight  || 'normal';
        block.style.fontStyle   = el.font_style   || 'normal';
        block.style.lineHeight  = el.line_height  || 1.4;
    }
}

// ============================================================
// SELECTION (single)
// ============================================================
function selectBlock(block, e) {
    if (e) e.stopPropagation();
    clearMultiSel();
    deselectAll();
    activeBlock = block;
    block.classList.add('selected');
    showInspector(block);
}

function deselectAll() {
    if (activeBlock) { activeBlock.classList.remove('selected'); }
    activeBlock = null;
    document.getElementById('inspector').style.display = 'none';
}

function showInspector(block) {
    var insp = document.getElementById('inspector');
    var type    = block.dataset.type;
    var subtype = block.dataset.subtype || 'free';
    var isSection = type === 'section';

    insp.style.display = 'flex';
    document.getElementById('insp-title').textContent =
        isSection ? 'Section' :
        subtype !== 'free' ? (TYPE_LABELS[subtype]||subtype) :
        type.charAt(0).toUpperCase()+type.slice(1)+' Block';

    // Section-only controls
    document.getElementById('insp-section').style.display = (isSection && IS_ADMIN) ? 'block' : 'none';
    if (isSection && IS_ADMIN) {
        var bg = block.dataset.sectionBg || '';
        document.getElementById('section-bg-preview').textContent = bg || 'No background set';
    }

    // WYSIWYG – admin + free text only
    var showWysiwyg = IS_ADMIN && type==='text' && subtype==='free';
    document.getElementById('insp-wysiwyg').style.display = showWysiwyg ? 'block' : 'none';

    // Font controls – admin + free text only
    document.getElementById('insp-font').style.display = (IS_ADMIN && type==='text' && subtype==='free') ? 'block' : 'none';
    if (IS_ADMIN && type==='text' && subtype==='free') {
        document.getElementById('font-family').value  = block.style.fontFamily.replace(/['"]/g,'') || 'Arial';
        document.getElementById('font-size').value    = parseInt(block.style.fontSize) || 16;
        document.getElementById('font-color').value   = rgbToHex(block.style.color) || '#000000';
        document.getElementById('font-weight').value  = block.style.fontWeight || 'normal';
        document.getElementById('line-height').value  = parseFloat(block.style.lineHeight) || 1.4;
    }

    // Brand lock badge – typed text blocks
    var showBrand = type==='text' && subtype!=='free';
    document.getElementById('insp-brand-lock').style.display = showBrand ? 'block' : 'none';
    if (showBrand) document.getElementById('insp-brand-name').textContent = TYPE_LABELS[subtype] || subtype;

    // Image upload – all users, image blocks
    document.getElementById('insp-image').style.display = (type==='image' && !isSection) ? 'block' : 'none';

    // Video upload – admin only
    document.getElementById('insp-video').style.display = (IS_ADMIN && type==='video') ? 'block' : 'none';

    // Asset link – non-section
    document.getElementById('insp-asset').style.display = isSection ? 'none' : 'block';
    document.getElementById('asset-link').value = block.dataset.assetId || '';

    // Lock toggle
    document.getElementById('lock-toggle').checked = block.dataset.locked === '1';
}

// ============================================================
// MULTI-SELECT
// ============================================================
function toggleMultiSel(block) {
    if (block.dataset.type === 'section') return; // don't multi-select sections
    deselectAll();

    var idx = multiSel.indexOf(block);
    if (idx >= 0) {
        block.classList.remove('multi-sel');
        multiSel.splice(idx, 1);
    } else {
        block.classList.add('multi-sel');
        multiSel.push(block);
    }
    updateAlignBar();
}

function clearMultiSel() {
    multiSel.forEach(function(b){ b.classList.remove('multi-sel'); });
    multiSel = [];
    updateAlignBar();
}

function updateAlignBar() {
    var bar = document.getElementById('align-bar');
    if (multiSel.length > 1) {
        bar.style.display = 'flex';
        document.getElementById('sel-count').textContent = multiSel.length + ' blocks selected';
    } else {
        bar.style.display = 'none';
    }
}

// ============================================================
// ALIGNMENT
// ============================================================
function alignBlocks(direction) {
    if (multiSel.length < 2) return;

    var bounds = multiSel.map(function(b) {
        return {
            el: b,
            x: parseFloat(b.getAttribute('data-x')) || 0,
            y: parseFloat(b.getAttribute('data-y')) || 0,
            w: b.offsetWidth,
            h: b.offsetHeight
        };
    });

    var minX   = Math.min.apply(null, bounds.map(function(b){ return b.x; }));
    var minY   = Math.min.apply(null, bounds.map(function(b){ return b.y; }));
    var maxR   = Math.max.apply(null, bounds.map(function(b){ return b.x + b.w; }));
    var maxB   = Math.max.apply(null, bounds.map(function(b){ return b.y + b.h; }));
    var ctrX   = minX + (maxR - minX) / 2;
    var ctrY   = minY + (maxB - minY) / 2;

    bounds.forEach(function(b) {
        var nx = b.x, ny = b.y;
        if      (direction==='left')     nx = minX;
        else if (direction==='right')    nx = maxR - b.w;
        else if (direction==='top')      ny = minY;
        else if (direction==='bottom')   ny = maxB - b.h;
        else if (direction==='center-h') nx = ctrX - b.w / 2;
        else if (direction==='center-v') ny = ctrY - b.h / 2;
        moveBlock(b.el, nx, ny);
    });
}

function moveBlock(block, nx, ny) {
    block.style.transform = 'translate('+nx+'px,'+ny+'px)';
    block.setAttribute('data-x', nx);
    block.setAttribute('data-y', ny);
}

// ============================================================
// LOCK / UNLOCK
// ============================================================
function toggleLock(locked) {
    if (!activeBlock) return;
    activeBlock.dataset.locked = locked ? '1' : '0';
    if (locked) {
        activeBlock.classList.add('locked-block');
        activeBlock.classList.remove('draggable-block');
        appendLockIcon(activeBlock);
    } else {
        activeBlock.classList.remove('locked-block');
        var isChild = activeBlock.classList.contains('child-block');
        if (IS_ADMIN || isChild) activeBlock.classList.add('draggable-block');
        var li = activeBlock.querySelector('.lock-icon');
        if (li) li.remove();
    }
}

function appendLockIcon(el) {
    if (!el.querySelector('.lock-icon')) {
        var li = document.createElement('span');
        li.className = 'lock-icon'; li.textContent = '🔒';
        el.appendChild(li);
    }
}

// ============================================================
// SECTION TARGET (for adding children)
// ============================================================
function setTargetSection(sectionEl) {
    if (targetSection) targetSection.classList.remove('targeted');
    targetSection = sectionEl;
    if (targetSection) {
        targetSection.classList.add('targeted');
        if (!IS_ADMIN) {
            document.getElementById('section-banner').textContent =
                'Section selected — now add a block from the bar above.';
        }
    }
}

function clearTargetSection() {
    if (targetSection) targetSection.classList.remove('targeted');
    targetSection = null;
    if (!IS_ADMIN) {
        document.getElementById('section-banner').textContent =
            'Click on a section (purple border) to target it, then add your blocks.';
    }
}

// ============================================================
// CANVAS CLICK HANDLER
// ============================================================
function setupCanvas() {
    document.getElementById('editor-frame').addEventListener('mousedown', function(e) {
        if (e.target === this || e.target === document.getElementById('builder-canvas')) {
            if (!e.shiftKey) { deselectAll(); clearMultiSel(); }
            clearTargetSection();
        }
    });
    document.addEventListener('selectionchange', trackSelection);
}

// ============================================================
// WYSIWYG
// ============================================================
function trackSelection() {
    if (!activeBlock || activeBlock.dataset.type !== 'text' || activeBlock.dataset.subtype !== 'free') return;
    var sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return;
    try {
        var inner = activeBlock.querySelector('.text-inner');
        if (inner && inner.contains(sel.getRangeAt(0).commonAncestorContainer)) {
            savedRange = sel.getRangeAt(0).cloneRange();
        }
    } catch(e) {}
}

function fmtCmd(evt, cmd) {
    evt.preventDefault();
    if (savedRange) {
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(savedRange);
    }
    document.execCommand(cmd, false, null);
    if (activeBlock) activeBlock.querySelector('.text-inner').focus();
}

// ============================================================
// STYLE UPDATES
// ============================================================
function updateStyle(prop, val) {
    if (!activeBlock) return;
    activeBlock.style[prop] = val;
}

// ============================================================
// SECTION BACKGROUND
// ============================================================
function uploadSectionBg(input) {
    if (!IS_ADMIN || !activeBlock || !input.files[0]) return;
    var fd = new FormData();
    fd.append('file', input.files[0]);
    fetch('api.php?action=upload_file', {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.status==='success') {
                activeBlock.style.backgroundImage = "url('"+res.path+"')";
                activeBlock.dataset.sectionBg = res.path;
                document.getElementById('section-bg-preview').textContent = res.path;
            } else { showToast(res.message||'Upload failed.', true); }
        });
}

function clearSectionBg() {
    if (!IS_ADMIN || !activeBlock) return;
    activeBlock.style.backgroundImage = 'none';
    activeBlock.dataset.sectionBg = '';
    document.getElementById('section-bg-preview').textContent = 'No background set';
}

// ============================================================
// IMAGE / VIDEO UPLOADS
// ============================================================
function uploadBlockImage(input) {
    if (!input.files[0] || !activeBlock) return;
    var fd = new FormData();
    fd.append('file', input.files[0]);
    fetch('api.php?action=upload_file', {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.status==='success') {
                activeBlock.querySelector('img').src = res.path;
                activeBlock.dataset.manualPath = res.path;
                activeBlock.dataset.assetId    = '';
                document.getElementById('asset-link').value = '';
            } else { showToast(res.message||'Upload failed.', true); }
        });
}

function uploadBlockVideo(input) {
    if (!IS_ADMIN || !input.files[0] || !activeBlock) return;
    showToast('Uploading video…');
    var fd = new FormData();
    fd.append('file', input.files[0]);
    fetch('api.php?action=upload_video', {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.status==='success') {
                var vid = activeBlock.querySelector('video');
                vid.innerHTML = '';
                var src = document.createElement('source');
                src.src = res.path; vid.appendChild(src); vid.load();
                activeBlock.dataset.manualPath = res.path;
                activeBlock.dataset.assetId    = '';
                document.getElementById('asset-link').value = '';
                showToast('Video uploaded.');
            } else { showToast(res.message||'Upload failed.', true); }
        });
}

// ============================================================
// ASSET LINK
// ============================================================
function linkAsset(assetId) {
    if (!activeBlock) return;
    activeBlock.dataset.assetId = assetId;
    if (!assetId) return;
    var match = assetsCache.find(function(a){ return a.id == assetId; });
    if (!match) return;
    if (activeBlock.dataset.type === 'text') {
        activeBlock.querySelector('.text-inner').innerHTML = match.content;
    } else if (activeBlock.dataset.type === 'image') {
        activeBlock.querySelector('img').src = match.content;
    } else if (activeBlock.dataset.type === 'video') {
        var vid = activeBlock.querySelector('video');
        vid.innerHTML = '';
        var src = document.createElement('source');
        src.src = match.content; vid.appendChild(src); vid.load();
    }
}

// ============================================================
// DELETE
// ============================================================
function deleteSelected() {
    if (activeBlock) {
        if (activeBlock.dataset.type === 'section') {
            if (!confirm('Delete this section and ALL blocks inside it?')) return;
        }
        activeBlock.remove();
        deselectAll();
    }
}

// ============================================================
// PUBLISH
// ============================================================
function publishCanvas() {
    var canvas   = document.getElementById('builder-canvas');
    var elements = [];

    // Collect sections (admin only publishes section data)
    canvas.querySelectorAll(':scope > .section-block').forEach(function(s) {
        elements.push({
            type:       'section',
            temp_id:    s.dataset.tempId,
            x_pos:      Math.round(parseFloat(s.getAttribute('data-x'))||0),
            y_pos:      Math.round(parseFloat(s.getAttribute('data-y'))||0),
            width:      Math.round(s.offsetWidth),
            height:     Math.round(s.offsetHeight),
            section_bg: s.dataset.sectionBg || null,
            locked:     s.dataset.locked === '1' ? 1 : 0,
            sort_order: 0,
        });
    });

    // Collect all non-section blocks
    canvas.querySelectorAll('.editable-block:not(.section-block)').forEach(function(block, i) {
        var type    = block.dataset.type;
        var subtype = block.dataset.subtype || 'free';
        var assetId = block.dataset.assetId || '';
        var sectionEl = block.closest('.section-block');
        var manual  = '';
        var savePool = false;

        if (!assetId) {
            if (type === 'text') {
                manual   = block.querySelector('.text-inner').innerHTML;
                savePool = true;
            } else {
                manual   = block.dataset.manualPath || (block.querySelector('img,video source') || {}).src || '';
                savePool = !!block.dataset.manualPath;
            }
        }

        elements.push({
            type:           type,
            block_subtype:  subtype,
            parent_temp_id: sectionEl ? sectionEl.dataset.tempId : null,
            x_pos:          Math.round(parseFloat(block.getAttribute('data-x'))||0),
            y_pos:          Math.round(parseFloat(block.getAttribute('data-y'))||0),
            width:          Math.round(block.offsetWidth),
            height:         Math.round(block.offsetHeight),
            asset_id:       assetId,
            manual_content: manual,
            save_to_db_pool: savePool,
            font_family:    block.style.fontFamily  || 'Arial',
            font_size:      parseInt(block.style.fontSize) || 16,
            font_color:     rgbToHex(block.style.color) || '#000000',
            font_weight:    block.style.fontWeight  || 'normal',
            font_style:     block.style.fontStyle   || 'normal',
            line_height:    parseFloat(block.style.lineHeight) || 1.4,
            locked:         block.dataset.locked === '1' ? 1 : 0,
            sort_order:     i,
        });
    });

    var fd = new FormData();
    fd.append('layout_data', JSON.stringify(elements));

    if (IS_ADMIN) {
        fd.append('bg_type', document.getElementById('bg-type').value);
        fd.append('bg_val',  document.getElementById('bg-color').value);
        var bgFile = document.getElementById('bg-file').files[0];
        if (bgFile) fd.append('bg_file', bgFile);
    }

    fetch('api.php?action=publish', {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.status === 'success') {
                showToast('Published! Display screen will update in 30 seconds.');
                loadAssets();
            } else { showToast(res.message||'Publish failed.', true); }
        })
        .catch(function(){ showToast('Network error.', true); });
}

// ============================================================
// BRAND STANDARDS MODAL (admin)
// ============================================================
function openBrandModal() {
    if (!IS_ADMIN) return;
    var tbody = document.getElementById('bm-tbody');
    tbody.innerHTML = '';
    var types = ['section_header','item_title','price','description'];
    var labels = {section_header:'Section Header',item_title:'Item Title',price:'Price',description:'Description'};
    var fonts  = ['Arial','Georgia','Verdana','Tahoma','Times New Roman','Courier New','Impact'];
    types.forEach(function(t) {
        var s = blockStyles[t] || {};
        var row = document.createElement('tr');
        row.innerHTML =
            '<td><strong>'+escHtml(labels[t])+'</strong></td>' +
            '<td><select id="bm_'+t+'_family">' + fonts.map(function(f){
                return '<option value="'+f+'"'+(s.font_family===f?' selected':'')+'>'+f+'</option>';
            }).join('') + '</select></td>' +
            '<td><input type="number" id="bm_'+t+'_size" value="'+(s.font_size||16)+'" min="8" max="300" style="width:60px;"></td>' +
            '<td><input type="color" id="bm_'+t+'_color" value="'+(s.font_color||'#000000')+'"></td>' +
            '<td><select id="bm_'+t+'_weight"><option value="normal"'+(s.font_weight==='normal'?' selected':'')+'>Normal</option><option value="bold"'+(s.font_weight==='bold'?' selected':'')+'>Bold</option></select></td>' +
            '<td><select id="bm_'+t+'_style"><option value="normal"'+(s.font_style==='normal'?' selected':'')+'>Normal</option><option value="italic"'+(s.font_style==='italic'?' selected':'')+'>Italic</option></select></td>' +
            '<td><input type="number" id="bm_'+t+'_lh" value="'+(s.line_height||1.4)+'" min="0.8" max="4" step="0.1" style="width:60px;"></td>';
        tbody.appendChild(row);
    });
    document.getElementById('brand-modal-overlay').classList.add('open');
}

function closeBrandModal() {
    document.getElementById('brand-modal-overlay').classList.remove('open');
}

function saveBrandStandards() {
    var types = ['section_header','item_title','price','description'];
    var fd    = new FormData();
    var styles = {};
    types.forEach(function(t) {
        styles[t] = {
            font_family: document.getElementById('bm_'+t+'_family').value,
            font_size:   parseInt(document.getElementById('bm_'+t+'_size').value),
            font_color:  document.getElementById('bm_'+t+'_color').value,
            font_weight: document.getElementById('bm_'+t+'_weight').value,
            font_style:  document.getElementById('bm_'+t+'_style').value,
            line_height: parseFloat(document.getElementById('bm_'+t+'_lh').value),
        };
    });
    fd.append('styles_data', JSON.stringify(styles));

    fetch('api.php?action=save_brand_styles', {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.status === 'success') {
                blockStyles = styles;
                // Re-apply brand styles to all typed blocks on canvas
                document.querySelectorAll('.editable-block[data-subtype]').forEach(function(b) {
                    var sub = b.dataset.subtype;
                    if (sub && sub !== 'free' && styles[sub]) {
                        var bs = styles[sub];
                        b.style.fontFamily  = bs.font_family;
                        b.style.fontSize    = bs.font_size + 'px';
                        b.style.color       = bs.font_color;
                        b.style.fontWeight  = bs.font_weight;
                        b.style.fontStyle   = bs.font_style;
                        b.style.lineHeight  = bs.line_height;
                    }
                });
                showToast('Brand standards saved.');
                closeBrandModal();
            } else { showToast(res.message||'Save failed.', true); }
        });
}

// ============================================================
// INTERACT.JS – drag, resize, bounds
// ============================================================
function setupInteract() {
    var canvas = document.getElementById('builder-canvas');

    if (IS_ADMIN) {
        // Sections: drag + resize, constrained to canvas
        interact('.section-block').draggable({
            listeners: { move: handleMove },
            modifiers: [interact.modifiers.restrictRect({restriction: canvas})],
            ignoreFrom: '.editable-block',
        }).resizable({
            edges: {left:true, right:true, bottom:true, top:true},
            listeners: { move: handleResize },
            modifiers: [interact.modifiers.restrictSize({min:{width:100,height:60}})]
        });

        // Root blocks: drag + resize, constrained to canvas
        interact('.root-block').draggable({
            listeners: { move: handleMove },
            modifiers: [interact.modifiers.restrictRect({restriction: canvas})]
        }).resizable({
            edges: {left:true, right:true, bottom:true, top:true},
            listeners: { move: handleResize },
        });
    }

    // Child blocks: drag constrained to parent section; resize for admin
    var childInteract = interact('.child-block').draggable({
        listeners: {
            move: function(event) {
                if (event.target.dataset.locked === '1') return;
                handleMove(event);
            }
        },
        modifiers: [interact.modifiers.restrictRect({restriction: 'parent', endOnly: false})]
    });

    if (IS_ADMIN) {
        childInteract.resizable({
            edges: {left:true, right:true, bottom:true, top:true},
            listeners: { move: handleResize },
            modifiers: [interact.modifiers.restrictRect({restriction: 'parent'})]
        });
    } else {
        // Basic users can also resize within section bounds
        childInteract.resizable({
            edges: {left:true, right:true, bottom:true, top:true},
            listeners: { move: handleResize },
            modifiers: [interact.modifiers.restrictRect({restriction: 'parent'})]
        });
    }
}

function handleMove(event) {
    var t = event.target;
    if (t.dataset.locked === '1') return;
    var x = (parseFloat(t.getAttribute('data-x'))||0) + event.dx;
    var y = (parseFloat(t.getAttribute('data-y'))||0) + event.dy;
    t.style.transform = 'translate('+x+'px,'+y+'px)';
    t.setAttribute('data-x', x);
    t.setAttribute('data-y', y);
}

function handleResize(event) {
    var t = event.target;
    if (t.dataset.locked === '1') return;
    var x = (parseFloat(t.getAttribute('data-x'))||0) + event.deltaRect.left;
    var y = (parseFloat(t.getAttribute('data-y'))||0) + event.deltaRect.top;
    t.style.width  = event.rect.width  + 'px';
    t.style.height = event.rect.height + 'px';
    t.style.transform = 'translate('+x+'px,'+y+'px)';
    t.setAttribute('data-x', x);
    t.setAttribute('data-y', y);
}

// ============================================================
// UTILITIES
// ============================================================
function tmpId() { return 'tmp-' + Math.random().toString(36).substr(2,9); }

function rgbToHex(rgb) {
    if (!rgb || rgb.startsWith('#')) return rgb||'#000000';
    var m = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    if (!m) return '#000000';
    return '#'+[m[1],m[2],m[3]].map(function(n){return ('0'+parseInt(n,10).toString(16)).slice(-2);}).join('');
}

function escHtml(s) {
    return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function svgPlaceholder(w, h, label) {
    return 'data:image/svg+xml,'+encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="'+w+'" height="'+h+'">' +
        '<rect width="'+w+'" height="'+h+'" fill="#dde3ea"/>' +
        '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" ' +
        'font-family="Arial" font-size="14" fill="#7f8c8d">'+label+'</text></svg>'
    );
}

function showToast(msg, isErr) {
    var t = document.getElementById('toast');
    t.textContent   = msg;
    t.className     = isErr ? 'err' : '';
    t.style.display = 'block';
    clearTimeout(t._tid);
    t._tid = setTimeout(function(){ t.style.display='none'; }, 3500);
}
</script>
</body>
</html>
