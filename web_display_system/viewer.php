<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            width: 100%;
            height: 100%;
            background: #111;
            overflow: hidden;
        }

        /* The canvas is always 1920×1080 in design pixels.
           JavaScript scales it to fill the actual screen. */
        #viewer-canvas {
            width: 1920px;
            height: 1080px;
            position: absolute;
            top: 0;
            left: 0;
            transform-origin: top left;
            overflow: hidden;
            background-color: #1a1a2e;
            background-size: cover;
            background-position: center;
        }

        .element-block {
            position: absolute;
            overflow: hidden;
            display: block;
        }

        .element-block img {
            width: 100%;
            height: 100%;
            object-fit: fill;
            display: block;
        }
    </style>
</head>
<body>
<div id="viewer-canvas"></div>
<script>
    // Scale the 1920×1080 canvas to exactly fill whatever screen is showing it
    function scaleToFit() {
        var canvas = document.getElementById('viewer-canvas');
        var scaleX = window.innerWidth  / 1920;
        var scaleY = window.innerHeight / 1080;
        var scale  = Math.min(scaleX, scaleY);
        var offsetX = (window.innerWidth  - 1920 * scale) / 2;
        var offsetY = (window.innerHeight - 1080 * scale) / 2;
        canvas.style.transform   = 'scale(' + scale + ')';
        canvas.style.marginLeft  = offsetX + 'px';
        canvas.style.marginTop   = offsetY + 'px';
    }

    window.addEventListener('resize', scaleToFit);
    scaleToFit();

    // Auto-refresh every 30 seconds so changes published from the builder
    // appear on this screen without anyone needing to reload manually.
    setInterval(function() { loadLayout(); }, 30000);

    function loadLayout() {
        fetch('api.php?action=get_layout')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var canvas = document.getElementById('viewer-canvas');
                // Clear existing elements
                canvas.innerHTML = '';

                if (data.settings) {
                    if (data.settings.bg_type === 'color') {
                        canvas.style.backgroundColor  = data.settings.bg_val;
                        canvas.style.backgroundImage  = 'none';
                    } else {
                        canvas.style.backgroundImage  = "url('" + data.settings.bg_val + "')";
                        canvas.style.backgroundColor  = '#111';
                    }
                }

                (data.elements || []).forEach(function(el) {
                    var block = document.createElement('div');
                    block.className    = 'element-block';
                    block.style.left   = el.x_pos  + 'px';
                    block.style.top    = el.y_pos   + 'px';
                    block.style.width  = el.width   + 'px';
                    block.style.height = el.height  + 'px';

                    var content = el.asset_id ? el.db_content : el.manual_content;

                    if (el.type === 'text') {
                        block.style.fontFamily = el.font_family || 'Arial';
                        block.style.fontSize   = (el.font_size || 16) + 'px';
                        block.style.color      = el.font_color || '#000000';
                        block.innerHTML        = content || '';
                    } else if (el.type === 'image') {
                        var img  = document.createElement('img');
                        img.src  = content || '';
                        img.alt  = '';
                        block.appendChild(img);
                    }

                    canvas.appendChild(block);
                });
            })
            .catch(function() {
                // Silent fail – keep showing current content if API is unreachable
            });
    }

    document.addEventListener('DOMContentLoaded', loadLayout);
</script>
</body>
</html>
