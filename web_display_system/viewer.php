<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Display</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { width: 100%; height: 100%; background: #111; overflow: hidden; }

    /* 1920×1080 design canvas – scaled to fill any screen via JS */
    #viewer-canvas {
        width: 1920px; height: 1080px;
        position: absolute; top: 0; left: 0;
        transform-origin: top left;
        overflow: hidden;
        background-color: #1a1a2e;
        background-size: cover;
        background-position: center;
    }

    /* Sections */
    .section-block {
        position: absolute;
        overflow: hidden;
        background-size: cover;
        background-position: center;
    }

    /* All content blocks */
    .element-block {
        position: absolute;
        overflow: hidden;
    }
    .element-block img, .element-block video {
        width: 100%; height: 100%;
        object-fit: fill; display: block;
    }
    .element-block video { object-fit: cover; }
</style>
</head>
<body>
<div id="viewer-canvas"></div>
<script>
    // Scale 1920×1080 canvas to fill the actual screen
    function scaleToFit() {
        var c  = document.getElementById('viewer-canvas');
        var sx = window.innerWidth  / 1920;
        var sy = window.innerHeight / 1080;
        var s  = Math.min(sx, sy);
        c.style.transform  = 'scale(' + s + ')';
        c.style.marginLeft = ((window.innerWidth  - 1920 * s) / 2) + 'px';
        c.style.marginTop  = ((window.innerHeight - 1080 * s) / 2) + 'px';
    }
    window.addEventListener('resize', scaleToFit);
    scaleToFit();

    // Auto-refresh every 30s so published changes appear without manual reload
    setInterval(loadLayout, 30000);

    var _layoutHash = '';

    function loadLayout() {
        fetch('api.php?action=get_layout')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var hash = JSON.stringify(data);
                if (hash === _layoutHash) return; // nothing changed — leave videos running
                _layoutHash = hash;

                var canvas = document.getElementById('viewer-canvas');
                canvas.innerHTML = '';

                var settings    = data.settings    || {};
                var elements    = data.elements    || [];
                var blockStyles = data.block_styles || {};

                // Background
                if (settings.bg_type === 'color') {
                    canvas.style.backgroundColor = settings.bg_val || '#1a1a2e';
                    canvas.style.backgroundImage = 'none';
                } else {
                    canvas.style.backgroundImage = "url('" + settings.bg_val + "')";
                    canvas.style.backgroundColor = '#111';
                }

                // Render sections first, build id→element map
                var sectionMap = {};
                elements.filter(function(e) { return e.type === 'section'; }).forEach(function(el) {
                    var s = document.createElement('div');
                    s.className    = 'section-block';
                    s.style.left   = el.x_pos  + 'px';
                    s.style.top    = el.y_pos   + 'px';
                    s.style.width  = el.width   + 'px';
                    s.style.height = el.height  + 'px';
                    if (el.section_bg) {
                        s.style.backgroundImage = "url('" + el.section_bg + "')";
                    }
                    canvas.appendChild(s);
                    sectionMap[el.id] = s;
                });

                // Render non-section elements
                elements.filter(function(e) { return e.type !== 'section'; }).forEach(function(el) {
                    var parent = el.section_id ? sectionMap[el.section_id] : canvas;
                    if (!parent) return;

                    var block = document.createElement('div');
                    block.className    = 'element-block';
                    block.style.left   = el.x_pos  + 'px';
                    block.style.top    = el.y_pos   + 'px';
                    block.style.width  = el.width   + 'px';
                    block.style.height = el.height  + 'px';

                    var content = el.asset_id ? el.db_content : el.manual_content;
                    var subtype = el.block_subtype || 'free';

                    if (el.type === 'text') {
                        // Apply brand styles for typed blocks; inline styles for free text
                        if (subtype !== 'free' && blockStyles[subtype]) {
                            var bs = blockStyles[subtype];
                            block.style.fontFamily  = bs.font_family;
                            block.style.fontSize    = bs.font_size + 'px';
                            block.style.color       = bs.font_color;
                            block.style.fontWeight  = bs.font_weight;
                            block.style.fontStyle   = bs.font_style;
                            block.style.lineHeight  = bs.line_height;
                        } else {
                            block.style.fontFamily  = el.font_family || 'Arial';
                            block.style.fontSize    = (el.font_size || 16) + 'px';
                            block.style.color       = el.font_color || '#000000';
                            block.style.fontWeight  = el.font_weight || 'normal';
                            block.style.fontStyle   = el.font_style  || 'normal';
                            block.style.lineHeight  = el.line_height || 1.4;
                        }
                        block.innerHTML = content || '';

                    } else if (el.type === 'image') {
                        var img = document.createElement('img');
                        img.src = content || '';
                        img.alt = '';
                        block.appendChild(img);

                    } else if (el.type === 'video') {
                        var vid = document.createElement('video');
                        vid.autoplay   = true;
                        vid.loop       = true;
                        vid.muted      = true;
                        vid.playsInline = true;
                        if (content) {
                            var src = document.createElement('source');
                            src.src  = content;
                            var _ext = content.split('.').pop().toLowerCase();
                            var _mime = {mp4:'video/mp4',webm:'video/webm',ogv:'video/ogg',ogg:'video/ogg'};
                            if (_mime[_ext]) src.type = _mime[_ext];
                            vid.appendChild(src);
                        }
                        block.appendChild(vid);
                    }

                    parent.appendChild(block);
                });
            })
            .catch(function() {
                // Silent fail – keep displaying current content
            });
    }

    document.addEventListener('DOMContentLoaded', loadLayout);
</script>
</body>
</html>
