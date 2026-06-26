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

    /* ── Carousel ── */
    .carousel-wrap {
        position: relative;
        width: 100%; height: 100%;
        overflow: hidden;
        background: #000;
    }
    .carousel-slide {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        opacity: 0;
        transition: opacity 0.8s ease-in-out;
        overflow: hidden;
    }
    .carousel-slide.active { opacity: 1; }
    .carousel-slide img {
        width: 100%; height: 100%;
        object-fit: cover; display: block;
        flex-shrink: 0;
    }
    .carousel-info {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.82));
        padding: 14px 18px 12px;
    }
    .carousel-title {
        font-family: Arial, sans-serif;
        font-weight: bold;
        color: #f0f0f0;
        font-size: 1.4em;
        line-height: 1.2;
        margin-bottom: 2px;
    }
    .carousel-price {
        font-family: Arial, sans-serif;
        font-weight: bold;
        color: #f39c12;
        font-size: 1.6em;
        line-height: 1.2;
        margin-bottom: 3px;
    }
    .carousel-desc {
        font-family: Arial, sans-serif;
        color: #ccc;
        font-size: 0.88em;
        line-height: 1.4;
    }
    /* Slide counter dot */
    .carousel-dots {
        position: absolute;
        top: 8px; right: 10px;
        display: flex; gap: 5px;
    }
    .carousel-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: rgba(255,255,255,0.35);
        transition: background 0.3s;
    }
    .carousel-dot.active { background: #fff; }

    /* ── Marquee ── */
    .marquee-wrap {
        width: 100%; height: 100%;
        overflow: hidden;
        display: flex;
        align-items: center;
    }
    .marquee-text {
        white-space: nowrap;
        will-change: transform;
        display: inline-block;
        padding-left: 100%;
        font-family: Arial, sans-serif;
    }
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
    var _carouselTimers = [];
    var _marqueeRAFs   = [];

    function stopAnimations() {
        _carouselTimers.forEach(function(t) { clearInterval(t); });
        _carouselTimers = [];
        _marqueeRAFs.forEach(function(id) { cancelAnimationFrame(id); });
        _marqueeRAFs = [];
    }

    function loadLayout() {
        fetch('api.php?action=get_layout')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var hash = JSON.stringify(data);
                if (hash === _layoutHash) return; // nothing changed — leave videos running
                _layoutHash = hash;

                stopAnimations();

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

                    } else if (el.type === 'carousel') {
                        renderCarousel(block, content);

                    } else if (el.type === 'marquee') {
                        renderMarquee(block, content);
                    }

                    parent.appendChild(block);
                });
            })
            .catch(function() {
                // Silent fail – keep displaying current content
            });
    }

    // ── Carousel ────────────────────────────────────────────────
    function renderCarousel(block, content) {
        var data = {};
        try { data = JSON.parse(content || '{}'); } catch(e) {}
        var slides   = data.slides   || [];
        var interval = data.interval || 5000;

        var wrap = document.createElement('div');
        wrap.className = 'carousel-wrap';

        if (slides.length === 0) {
            wrap.style.cssText = 'display:flex;align-items:center;justify-content:center;color:#666;font-family:Arial;font-size:18px;';
            wrap.textContent = 'Carousel — no slides added yet';
            block.appendChild(wrap);
            return;
        }

        var slideEls = [];
        slides.forEach(function(s) {
            var slide = document.createElement('div');
            slide.className = 'carousel-slide';

            if (s.image) {
                var img = document.createElement('img');
                img.src = s.image;
                img.alt = s.title || '';
                slide.appendChild(img);
            } else {
                slide.style.background = '#1a1a2e';
            }

            var hasInfo = s.title || s.price || s.description;
            if (hasInfo) {
                var info = document.createElement('div');
                info.className = 'carousel-info';
                if (s.title) {
                    var t = document.createElement('div');
                    t.className   = 'carousel-title';
                    t.textContent = s.title;
                    info.appendChild(t);
                }
                if (s.price) {
                    var p = document.createElement('div');
                    p.className   = 'carousel-price';
                    p.textContent = s.price;
                    info.appendChild(p);
                }
                if (s.description) {
                    var d = document.createElement('div');
                    d.className   = 'carousel-desc';
                    d.textContent = s.description;
                    info.appendChild(d);
                }
                slide.appendChild(info);
            }

            wrap.appendChild(slide);
            slideEls.push(slide);
        });

        // Dot indicators
        if (slides.length > 1) {
            var dots = document.createElement('div');
            dots.className = 'carousel-dots';
            slideEls.forEach(function(_, i) {
                var dot = document.createElement('div');
                dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
                dots.appendChild(dot);
            });
            wrap.appendChild(dots);
        }

        block.appendChild(wrap);

        // Activate first slide immediately
        if (slideEls.length > 0) slideEls[0].classList.add('active');
        if (slideEls.length < 2) return;

        var current = 0;
        var dotEls  = wrap.querySelectorAll('.carousel-dot');

        var timer = setInterval(function() {
            slideEls[current].classList.remove('active');
            if (dotEls[current]) dotEls[current].classList.remove('active');
            current = (current + 1) % slideEls.length;
            slideEls[current].classList.add('active');
            if (dotEls[current]) dotEls[current].classList.add('active');
        }, interval);

        _carouselTimers.push(timer);
    }

    // ── Marquee ─────────────────────────────────────────────────
    function renderMarquee(block, content) {
        var data = {};
        try { data = JSON.parse(content || '{}'); } catch(e) {}

        var text   = data.text   || '';
        var speed  = data.speed  || 80;  // px/sec
        var color  = data.color  || '#ffffff';
        var size   = data.size   || 28;
        var weight = data.weight || 'bold';
        var bg     = data.bg     || '#c0392b';

        block.style.background = bg;

        var wrap = document.createElement('div');
        wrap.className = 'marquee-wrap';

        var span = document.createElement('span');
        span.className       = 'marquee-text';
        span.textContent     = text || '';
        span.style.color     = color;
        span.style.fontSize  = size + 'px';
        span.style.fontWeight = weight;
        span.style.paddingLeft = '100%';

        wrap.appendChild(span);
        block.appendChild(wrap);

        if (!text) return;

        var pos      = 0;
        var lastTime = null;

        function step(ts) {
            if (!document.body.contains(span)) return; // element was removed (layout reload)
            if (lastTime === null) lastTime = ts;
            var dt = (ts - lastTime) / 1000; // seconds
            lastTime = ts;
            pos -= speed * dt;
            // Reset when text has fully scrolled off the left edge
            var textW = span.offsetWidth;
            if (pos < -textW) pos = 0;
            span.style.transform = 'translateX(' + pos + 'px)';
            var raf = requestAnimationFrame(step);
            _marqueeRAFs.push(raf);
        }

        var raf = requestAnimationFrame(step);
        _marqueeRAFs.push(raf);
    }

    document.addEventListener('DOMContentLoaded', loadLayout);
</script>
</body>
</html>
