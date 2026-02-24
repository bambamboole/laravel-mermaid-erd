<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mermaid ERD</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">

    <header class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-3">
        <h1 class="text-sm font-semibold text-gray-800">ERD &mdash; {{ $connectionName }}</h1>
        <div class="flex items-center gap-2">
            <button onclick="zoomOut()" title="Zoom out"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                &minus;
            </button>
            <span id="zoom-level" class="min-w-[48px] text-center text-xs text-gray-500">100%</span>
            <button onclick="zoomIn()" title="Zoom in"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                +
            </button>
            <button onclick="resetView()" title="Reset view"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                Reset
            </button>
            <div class="mx-1 h-5 w-px bg-gray-200"></div>
            <button onclick="copyMermaid()" id="copy-btn" title="Copy Mermaid source"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                Copy Mermaid
            </button>
            <button onclick="downloadSVG()" title="Download SVG"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                Download SVG
            </button>
        </div>
    </header>

    <div id="diagram-container" class="fixed inset-x-0 bottom-0 top-[49px] cursor-grab overflow-hidden">
        <div id="diagram-wrapper" class="inline-block min-h-full min-w-full origin-top-left p-8">
            <pre class="mermaid bg-transparent">{!! $diagram !!}</pre>
        </div>
    </div>

    <script>
        const mermaidSource = {!! json_encode($diagram) !!};
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script>
        mermaid.initialize({!! $mermaidConfig !!});

        let scale = 1;
        let translateX = 0;
        let translateY = 0;
        let isDragging = false;
        let startX, startY;

        const container = document.getElementById('diagram-container');
        const wrapper = document.getElementById('diagram-wrapper');
        const zoomLabel = document.getElementById('zoom-level');
        const ZOOM_STEP = 0.15;
        const MIN_ZOOM = 0.1;
        const MAX_ZOOM = 5;

        function applyTransform() {
            wrapper.style.transform = 'translate(' + translateX + 'px, ' + translateY + 'px) scale(' + scale + ')';
            zoomLabel.textContent = Math.round(scale * 100) + '%';
        }

        function zoomIn()  { scale = Math.min(MAX_ZOOM, scale + ZOOM_STEP); applyTransform(); }
        function zoomOut() { scale = Math.max(MIN_ZOOM, scale - ZOOM_STEP); applyTransform(); }
        function resetView() { scale = 1; translateX = 0; translateY = 0; applyTransform(); }

        container.addEventListener('wheel', function(e) {
            e.preventDefault();
            if (e.deltaY < 0) zoomIn(); else zoomOut();
        }, { passive: false });

        container.addEventListener('mousedown', function(e) {
            isDragging = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
            container.classList.remove('cursor-grab');
            container.classList.add('cursor-grabbing');
        });

        window.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            applyTransform();
        });

        window.addEventListener('mouseup', function() {
            isDragging = false;
            container.classList.remove('cursor-grabbing');
            container.classList.add('cursor-grab');
        });

        function copyMermaid() {
            navigator.clipboard.writeText(mermaidSource).then(function() {
                var btn = document.getElementById('copy-btn');
                var original = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(function() { btn.textContent = original; }, 1500);
            });
        }

        function downloadSVG() {
            var svg = document.querySelector('#diagram-wrapper svg');
            if (!svg) return;
            var serializer = new XMLSerializer();
            var source = serializer.serializeToString(svg);
            var blob = new Blob([source], { type: 'image/svg+xml;charset=utf-8' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'erd.svg';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>

    <style>
        pre.mermaid svg { display: block; max-width: none !important; }
    </style>
</body>
</html>
