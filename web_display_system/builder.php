<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Layout Builder</title>
    <!-- Pinned interact.js version for stable drag & resize behavior -->
    <script src="https://cdn.jsdelivr.net/npm/interactjs@1.10.27/dist/interact.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }

        body { background: #2c3e50; display: flex; flex-direction: column; height: 100vh; overflow: hidden; color: #fff; }

        /* ---- Top control bar ---- */
        #control-bar {
            background: #1a252f;
            padding: 10px 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            z-index: 10;
            border-bottom: 2px solid #34495e;
            flex-wrap: wrap;
        }

        .btn {
            background: #3498db; border: none; color: white;
            padding: 8px 14px; border-radius: 4px; cursor: pointer;
            font-weight: bold; font-size: 13px; white-space: nowrap;
        }
        .btn:hover { background: #2980b9; }
        .btn.success { background: #2ecc71; }
        .btn.success:hover { background: #27ae60; }
        .btn.danger  { background: #e74c3c; }
        .btn.danger:hover  { background: #c0392b; }

        /* ---- Scrollable area around the canvas ---- */
        #editor-frame {
            flex: 1;
            overflow: auto;
            padding: 40px;
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
        }

        /* ---- The 1920×1080 design canvas ---- */
        #builder-canvas {
            width: 1920px;
            height: 1080px;
            background: #fff;
            position: relative;
            flex-shrink: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            background-size: cover;
            background-position: center;
        }

        /* ---- Draggable / resizable blocks ---- */
        .editable-block {
            position: absolute;
            border: 1px dashed #3498db;
            min-width: 50px;
            min-height: 30px;
            cursor: move;
            touch-action: none;
            color: #000;
            user-select: none;
        }
        .editable-block.selected {
            border: 2px solid #e74c3c;
            box-shadow: 0 0 10px rgba(231,76,60,0.5);
        }
        .editable-block img {
            width: 100%; height: 100%;
            object-fit: fill;
            pointer-events: none;
            display: block;
        }
        .text-inner {
            width: 100%; height: 100%;
            padding: 4px;
            outline: none;
            word-break: break-word;
            overflow: hidden;
            cursor: text;
            user-select: text;
        }

        /* ---- Inspector / properties panel ---- */
        #inspector-panel {
            position: fixed;
            right: 20px;
            top: 80px;
            width: 300px;
            background: #1a252f;
            border: 1px solid #34495e;
            border-radius: 6px;
            padding: 14px;
            display: none;
            flex-direction: column;
            gap: 10px;
            z-index: 200;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
        #inspector-panel label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #bdc3c7;
            display: block;
            margin-bottom: 3px;
        }
        #inspector-panel input,
        #inspector-panel select {
            width: 100%;
            padding: 7px;
            border-radius: 4px;
            border: 1px solid #34495e;
            background: #2c3e50;
            color: white;
            font-size: 13px;
        }
        .inspector-section {
            border-top: 1px solid #34495e;
            padding-top: 10px;
        }

        /* ---- WYSIWYG formatting toolbar ---- */
        #wysiwyg-bar {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            margin-top: 4px;
        }
        .fmt-btn {
            background: #2c3e50;
            border: 1px solid #4a6278;
            color: #fff;
            width: 32px; height: 28px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            display: flex; align-items: center; justify-content: center;
        }
        .fmt-btn:hover { background: #3d5166; }
        .fmt-btn.active { background: #3498db; border-color: #2980b9; }

        /* ---- Toast notification ---- */
        #toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #2ecc71;
            color: #fff;
            padding: 10px 24px;
            border-radius: 4px;
            font-weight: bold;
            display: none;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        #toast.error { background: #e74c3c; }
    </style>
</head>
<body>

<div id="control-bar">
    <button class="btn" onclick="createNewBlock('text')">+ Text Block</button>
    <button class="btn" onclick="createNewBlock('image')">+ Image Block</button>

    <div style="border-left:2px solid #34495e; height:24px; margin:0 4px;"></div>

    <label style="color:#bdc3c7; font-size:12px; margin:0; white-space:nowrap;">Background:</label>
    <select id="canvas-bg-type" onchange="toggleBgInputs()" style="width:130px; padding:7px; border-radius:4px; border:1px solid #34495e; background:#2c3e50; color:#fff;">
        <option value="color">Solid Color</option>
        <option value="image">Image File</option>
    </select>
    <input type="color" id="canvas-bg-color" value="#1a1a2e" oninput="applyBg()"
           style="width:46px; height:34px; padding:2px; border:none; cursor:pointer; border-radius:4px;">
    <input type="file" id="canvas-bg-file" accept="image/jpeg,image/png,image/gif,image/webp"
           onchange="applyBgFile()" style="display:none; color:#fff; font-size:12px;">

    <button class="btn success" style="margin-left:auto;" onclick="publishCanvas()">
        &#10003; Publish to Display Screen
    </button>
</div>

<div id="editor-frame">
    <div id="builder-canvas"></div>
</div>

<!-- Inspector panel (shown when a block is selected) -->
<div id="inspector-panel">
    <h3 style="font-size:13px; border-bottom:1px solid #34495e; padding-bottom:8px;">Selected Block</h3>

    <!-- Text-only controls -->
    <div id="text-controls" style="display:none;">
        <div class="inspector-section">
            <label>WYSIWYG Formatting</label>
            <div id="wysiwyg-bar">
                <button class="fmt-btn" title="Bold"      onmousedown="fmtCmd(event,'bold')"><b>B</b></button>
                <button class="fmt-btn" title="Italic"    onmousedown="fmtCmd(event,'italic')"><i>I</i></button>
                <button class="fmt-btn" title="Underline" onmousedown="fmtCmd(event,'underline')"><u>U</u></button>
                <button class="fmt-btn" title="Strikethrough" onmousedown="fmtCmd(event,'strikeThrough')"><s>S</s></button>
                <button class="fmt-btn" title="Align Left"   onmousedown="fmtCmd(event,'justifyLeft')">&#8676;</button>
                <button class="fmt-btn" title="Center"       onmousedown="fmtCmd(event,'justifyCenter')">&#8660;</button>
                <button class="fmt-btn" title="Align Right"  onmousedown="fmtCmd(event,'justifyRight')">&#8677;</button>
            </div>
        </div>
        <div class="inspector-section">
            <label>Font Family</label>
            <select id="font-family" onchange="updateBlockStyle('fontFamily', this.value)">
                <option value="Arial">Arial</option>
                <option value="'Times New Roman', serif">Times New Roman</option>
                <option value="'Courier New', monospace">Courier New</option>
                <option value="Georgia, serif">Georgia</option>
                <option value="Verdana, sans-serif">Verdana</option>
                <option value="Impact, sans-serif">Impact</option>
                <option value="'Trebuchet MS', sans-serif">Trebuchet MS</option>
            </select>
        </div>
        <div class="inspector-section" style="display:flex; gap:8px;">
            <div style="flex:1;">
                <label>Size (px)</label>
                <input type="number" id="font-size" min="8" max="300" placeholder="16"
                       oninput="updateBlockStyle('fontSize', this.value + 'px')">
            </div>
            <div style="flex:1;">
                <label>Color</label>
                <input type="color" id="font-color" style="height:35px; padding:2px; cursor:pointer;"
                       oninput="updateBlockStyle('color', this.value)">
            </div>
        </div>
    </div>

    <!-- Image-only controls -->
    <div id="image-controls" style="display:none;">
        <div class="inspector-section">
            <label>Upload Image Directly</label>
            <input type="file" id="direct-image-file" accept="image/jpeg,image/png,image/gif,image/webp"
                   onchange="uploadDirectImage(this)" style="color:#fff; font-size:12px;">
        </div>
    </div>

    <!-- Shared controls -->
    <div class="inspector-section">
        <label>Link to Database Asset</label>
        <select id="asset-db-link" onchange="linkBlockToAsset(this.value)">
            <option value="">-- None (use manual content) --</option>
        </select>
    </div>

    <div class="inspector-section">
        <button class="btn danger" style="width:100%;" onclick="deleteSelectedBlock()">
            &#128465; Delete This Block
        </button>
    </div>
</div>

<div id="toast"></div>

<script>
    var activeBlock    = null;
    var assetsCache    = [];
    var savedRange     = null;

    // ============================================================
    // Initialisation
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        loadAssets().then(loadCanvasState);
        toggleBgInputs();

        // Deselect when clicking the empty canvas or frame
        document.getElementById('editor-frame').addEventListener('click', function(e) {
            if (e.target === this || e.target === document.getElementById('builder-canvas')) {
                deselectAll();
            }
        });

        // Track text selection so WYSIWYG buttons can restore it
        document.addEventListener('selectionchange', trackSelection);

        setupInteract();
    });

    // ============================================================
    // WYSIWYG selection tracking & commands
    // ============================================================
    function trackSelection() {
        var sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return;
        if (!activeBlock || activeBlock.dataset.type !== 'text') return;
        var inner = activeBlock.querySelector('.text-inner');
        if (!inner) return;
        try {
            if (inner.contains(sel.getRangeAt(0).commonAncestorContainer)) {
                savedRange = sel.getRangeAt(0).cloneRange();
            }
        } catch(e) {}
    }

    function restoreSelection() {
        if (!savedRange) return;
        try {
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedRange);
        } catch(e) {}
    }

    // onmousedown so we act before the button click steals focus
    function fmtCmd(evt, command) {
        evt.preventDefault(); // prevent focus loss
        restoreSelection();
        document.execCommand(command, false, null);
        if (activeBlock) {
            activeBlock.querySelector('.text-inner').focus();
        }
    }

    // ============================================================
    // Asset list & canvas state
    // ============================================================
    function loadAssets() {
        return fetch('api.php?action=get_assets')
            .then(function(r) { return r.json(); })
            .then(function(list) {
                assetsCache = list;
                var sel = document.getElementById('asset-db-link');
                sel.innerHTML = '<option value="">-- None (use manual content) --</option>';
                list.forEach(function(a) {
                    var preview = a.label || (a.content.substring(0, 24) + '…');
                    sel.innerHTML += '<option value="' + a.id + '">[' + a.type.toUpperCase() + '] ' + escHtml(preview) + '</option>';
                });
            });
    }

    function loadCanvasState() {
        fetch('api.php?action=get_layout')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var canvas = document.getElementById('builder-canvas');
                if (data.settings) {
                    var bgType = data.settings.bg_type;
                    document.getElementById('canvas-bg-type').value = bgType;
                    if (bgType === 'color') {
                        document.getElementById('canvas-bg-color').value = data.settings.bg_val || '#1a1a2e';
                        applyBg();
                    } else {
                        canvas.style.backgroundImage = "url('" + data.settings.bg_val + "')";
                    }
                    toggleBgInputs();
                }
                (data.elements || []).forEach(function(el) { renderBlock(el); });
            });
    }

    // ============================================================
    // Background controls
    // ============================================================
    function toggleBgInputs() {
        var type = document.getElementById('canvas-bg-type').value;
        document.getElementById('canvas-bg-color').style.display = type === 'color'  ? 'inline-block' : 'none';
        document.getElementById('canvas-bg-file').style.display  = type === 'image'  ? 'inline-block' : 'none';
    }

    function applyBg() {
        var canvas = document.getElementById('builder-canvas');
        canvas.style.backgroundColor = document.getElementById('canvas-bg-color').value;
        canvas.style.backgroundImage = 'none';
    }

    function applyBgFile() {
        var file = document.getElementById('canvas-bg-file').files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('builder-canvas').style.backgroundImage = "url('" + e.target.result + "')";
        };
        reader.readAsDataURL(file);
    }

    // ============================================================
    // Block creation & rendering
    // ============================================================
    function createNewBlock(type) {
        renderBlock({
            type: type,
            x_pos: 60, y_pos: 60,
            width:  type === 'image' ? 300 : 360,
            height: type === 'image' ? 200 : 90,
            manual_content: type === 'text' ? 'Double-click to edit text' : '',
            asset_id: null,
            font_family: 'Arial',
            font_size: 24,
            font_color: '#000000'
        });
    }

    function renderBlock(el) {
        var block = document.createElement('div');
        block.className      = 'editable-block';
        block.dataset.type   = el.type;
        block.dataset.assetId = el.asset_id || '';
        block.style.width    = el.width  + 'px';
        block.style.height   = el.height + 'px';
        block.style.transform = 'translate(' + el.x_pos + 'px,' + el.y_pos + 'px)';
        block.setAttribute('data-x', el.x_pos);
        block.setAttribute('data-y', el.y_pos);

        var content = el.asset_id ? el.db_content : el.manual_content;

        if (el.type === 'text') {
            block.style.fontFamily = el.font_family || 'Arial';
            block.style.fontSize   = (el.font_size  || 24) + 'px';
            block.style.color      = el.font_color  || '#000000';

            var inner = document.createElement('div');
            inner.className      = 'text-inner';
            inner.contentEditable = 'true';
            inner.innerHTML      = content || 'Double-click to edit text';

            inner.addEventListener('focus', function() {
                if (block !== activeBlock) selectBlock(block);
            });
            block.appendChild(inner);
        } else {
            var img      = document.createElement('img');
            img.src      = content || '';
            img.alt      = '';
            if (!content) {
                // Inline SVG placeholder – no external dependency
                img.src = 'data:image/svg+xml,' + encodeURIComponent(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200">' +
                    '<rect width="300" height="200" fill="#dde3ea"/>' +
                    '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" ' +
                    'font-family="Arial" font-size="16" fill="#7f8c8d">Image Placeholder</text></svg>'
                );
            }
            block.appendChild(img);
        }

        block.addEventListener('mousedown', function(e) {
            // Don't steal click from contentEditable inner div
            if (e.target !== block) return;
            selectBlock(block);
        });

        document.getElementById('builder-canvas').appendChild(block);
    }

    // ============================================================
    // Block selection
    // ============================================================
    function selectBlock(block) {
        deselectAll();
        activeBlock = block;
        block.classList.add('selected');

        var panel = document.getElementById('inspector-panel');
        panel.style.display = 'flex';

        var isText  = block.dataset.type === 'text';
        var isImage = block.dataset.type === 'image';
        document.getElementById('text-controls').style.display  = isText  ? 'block' : 'none';
        document.getElementById('image-controls').style.display = isImage ? 'block' : 'none';

        document.getElementById('asset-db-link').value = block.dataset.assetId || '';

        if (isText) {
            document.getElementById('font-family').value = block.style.fontFamily.replace(/['"]/g, '') || 'Arial';
            document.getElementById('font-size').value   = parseInt(block.style.fontSize) || 24;
            document.getElementById('font-color').value  = rgbToHex(block.style.color) || '#000000';
        }
    }

    function deselectAll() {
        document.querySelectorAll('.editable-block').forEach(function(b) { b.classList.remove('selected'); });
        document.getElementById('inspector-panel').style.display = 'none';
        activeBlock  = null;
        savedRange   = null;
    }

    // ============================================================
    // Inspector actions
    // ============================================================
    function updateBlockStyle(prop, val) {
        if (!activeBlock) return;
        activeBlock.style[prop] = val;
    }

    function linkBlockToAsset(assetId) {
        if (!activeBlock) return;
        activeBlock.dataset.assetId = assetId;
        if (!assetId) return;
        var match = assetsCache.find(function(a) { return a.id == assetId; });
        if (!match) return;
        if (activeBlock.dataset.type === 'text') {
            activeBlock.querySelector('.text-inner').innerHTML = match.content;
        } else {
            activeBlock.querySelector('img').src = match.content;
        }
    }

    function uploadDirectImage(input) {
        if (!input.files[0] || !activeBlock) return;
        var fd = new FormData();
        fd.append('file', input.files[0]);
        fetch('api.php?action=upload_file', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'success') {
                    activeBlock.querySelector('img').src = res.path;
                    activeBlock.dataset.manualUploadedPath = res.path;
                    activeBlock.dataset.assetId = '';
                    document.getElementById('asset-db-link').value = '';
                } else {
                    showToast(res.message || 'Upload failed.', true);
                }
            });
    }

    function deleteSelectedBlock() {
        if (activeBlock) { activeBlock.remove(); deselectAll(); }
    }

    // ============================================================
    // Publish
    // ============================================================
    function publishCanvas() {
        var elements = [];
        document.querySelectorAll('.editable-block').forEach(function(block) {
            var type     = block.dataset.type;
            var assetId  = block.dataset.assetId || '';
            var x        = parseFloat(block.getAttribute('data-x')) || 0;
            var y        = parseFloat(block.getAttribute('data-y')) || 0;
            var manual   = '';
            var savePool = false;

            if (!assetId) {
                if (type === 'text') {
                    manual   = block.querySelector('.text-inner').innerHTML;
                    savePool = true;
                } else {
                    manual   = block.dataset.manualUploadedPath || '';
                    savePool = !!block.dataset.manualUploadedPath;
                }
            }

            elements.push({
                type:          type,
                x_pos:         Math.round(x),
                y_pos:         Math.round(y),
                width:         Math.round(block.offsetWidth),
                height:        Math.round(block.offsetHeight),
                asset_id:      assetId,
                manual_content: manual,
                save_to_db_pool: savePool,
                font_family:   block.style.fontFamily || 'Arial',
                font_size:     parseInt(block.style.fontSize) || 24,
                font_color:    rgbToHex(block.style.color) || '#000000'
            });
        });

        var fd = new FormData();
        fd.append('layout_data', JSON.stringify(elements));
        fd.append('bg_type', document.getElementById('canvas-bg-type').value);
        fd.append('bg_val',  document.getElementById('canvas-bg-color').value);
        var bgFile = document.getElementById('canvas-bg-file').files[0];
        if (bgFile) fd.append('bg_file', bgFile);

        fetch('api.php?action=publish', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'success') {
                    showToast('Published! The display screen will update shortly.');
                    loadAssets();
                } else {
                    showToast(res.message || 'Publish failed.', true);
                }
            })
            .catch(function() { showToast('Network error. Could not publish.', true); });
    }

    // ============================================================
    // interact.js – drag and resize
    // ============================================================
    function setupInteract() {
        interact('.editable-block')
            .draggable({
                listeners: {
                    move: function(event) {
                        var t = event.target;
                        var x = (parseFloat(t.getAttribute('data-x')) || 0) + event.dx;
                        var y = (parseFloat(t.getAttribute('data-y')) || 0) + event.dy;
                        t.style.transform = 'translate(' + x + 'px,' + y + 'px)';
                        t.setAttribute('data-x', x);
                        t.setAttribute('data-y', y);
                    }
                },
                modifiers: [
                    interact.modifiers.restrictRect({
                        restriction: '#builder-canvas',
                        endOnly: false
                    })
                ]
            })
            .resizable({
                edges: { left: true, right: true, bottom: true, top: true },
                listeners: {
                    move: function(event) {
                        var t = event.target;
                        var x = (parseFloat(t.getAttribute('data-x')) || 0) + event.deltaRect.left;
                        var y = (parseFloat(t.getAttribute('data-y')) || 0) + event.deltaRect.top;
                        t.style.width  = event.rect.width  + 'px';
                        t.style.height = event.rect.height + 'px';
                        t.style.transform = 'translate(' + x + 'px,' + y + 'px)';
                        t.setAttribute('data-x', x);
                        t.setAttribute('data-y', y);
                    }
                },
                modifiers: [
                    interact.modifiers.restrictSize({ min: { width: 40, height: 24 } })
                ]
            });
    }

    // ============================================================
    // Utilities
    // ============================================================
    function rgbToHex(rgb) {
        if (!rgb || rgb.startsWith('#')) return rgb || '#000000';
        var m = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        if (!m) return '#000000';
        return '#' + [m[1], m[2], m[3]].map(function(n) {
            return ('0' + parseInt(n, 10).toString(16)).slice(-2);
        }).join('');
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showToast(msg, isError) {
        var toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.className   = isError ? 'error' : '';
        toast.style.display = 'block';
        setTimeout(function() { toast.style.display = 'none'; }, 3500);
    }
</script>
</body>
</html>
