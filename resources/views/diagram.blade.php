<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mermaid ERD</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">

    <header class="flex items-center justify-between gap-4 border-b border-gray-200 bg-white px-6 py-3">
        <h1 class="shrink-0 text-sm font-semibold text-gray-800">ERD &mdash; {{ $connectionName }}</h1>
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <input id="erd-search" type="search" placeholder="Filter tables &amp; columns&hellip;" autocomplete="off"
                class="w-full max-w-72 rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-700 outline-none transition focus:border-gray-400" />
            <span id="erd-count" class="shrink-0 text-xs text-gray-500"></span>
        </div>
        <div class="flex shrink-0 items-center gap-2">
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
        <div id="diagram-wrapper" class="inline-block min-h-full min-w-full origin-top-left p-8"></div>
    </div>

    <script>
        const mermaidSource = {!! $diagram !!};
        const graph = {!! $graph !!};
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script>
        mermaid.initialize(Object.assign({}, {!! $mermaidConfig !!}, { startOnLoad: false }));

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
            navigator.clipboard.writeText(currentSource).then(function() {
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

        // --- Filtering ---------------------------------------------------------
        // The mermaid source is split once into entity blocks and relation lines
        // (the renderer's line format is stable); matching and neighbor expansion
        // run on the structured `graph` payload.

        const parsed = (function parseSource(src) {
            const blocks = new Map();
            const edges = [];
            let current = null;
            for (const line of src.split('\n')) {
                const start = line.match(/^ {4}(\w+)\[/);
                const edge = line.match(/^ {4}(\w+) \S+ (\w+) : /);
                if (current) {
                    blocks.get(current).push(line);
                    if (line === '    }') current = null;
                } else if (start) {
                    current = start[1];
                    blocks.set(current, [line]);
                } else if (edge) {
                    edges.push({ from: edge[1], to: edge[2], line: line });
                }
            }
            return { blocks: blocks, edges: edges };
        })(mermaidSource);

        const totalTables = Object.keys(graph.tables).length;

        function computeVisible(query) {
            const q = query.trim().toLowerCase();
            if (!q) return null;

            const matched = new Set();
            for (const [table, columns] of Object.entries(graph.tables)) {
                if (table.toLowerCase().includes(q) || columns.some(c => c.toLowerCase().includes(q))) {
                    matched.add(table);
                }
            }

            const visible = new Set(matched);
            for (const [from, to] of graph.edges) {
                if (matched.has(from)) visible.add(to);
                if (matched.has(to)) visible.add(from);
            }

            return visible;
        }

        function buildFilteredSource(visible) {
            let columns = 0;
            visible.forEach(t => columns += graph.tables[t].length);

            let out = '---\ntitle: ' + visible.size + ' of ' + totalTables + ' tables · ' + columns + ' columns\n---\nerDiagram\n';
            for (const [name, lines] of parsed.blocks) {
                if (visible.has(name)) out += lines.join('\n') + '\n';
            }
            for (const edge of parsed.edges) {
                if (visible.has(edge.from) && visible.has(edge.to)) out += edge.line + '\n';
            }
            return out;
        }

        let currentSource = mermaidSource;
        let renderSeq = 0;

        async function renderDiagram() {
            resetView();
            try {
                const { svg } = await mermaid.render('erd-render-' + (++renderSeq), currentSource);
                wrapper.innerHTML = svg;
            } catch (e) {
                wrapper.innerHTML = '<pre class="p-8 text-xs text-red-600">' + e.message + '</pre>';
            }
        }

        const searchInput = document.getElementById('erd-search');
        const countLabel = document.getElementById('erd-count');

        function applyFilter(query) {
            const visible = computeVisible(query);

            const url = new URL(location);
            if (query.trim()) url.searchParams.set('q', query.trim()); else url.searchParams.delete('q');
            history.replaceState(null, '', url);

            if (visible === null) {
                countLabel.textContent = totalTables + ' tables';
                currentSource = mermaidSource;
                renderDiagram();
                return;
            }

            countLabel.textContent = visible.size + ' / ' + totalTables + ' tables';

            if (visible.size === 0) {
                currentSource = '';
                resetView();
                wrapper.innerHTML = '<div class="p-8 text-sm text-gray-500">No tables or columns match.</div>';
                return;
            }

            currentSource = buildFilteredSource(visible);
            renderDiagram();
        }

        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => applyFilter(searchInput.value), 150);
        });

        searchInput.value = new URL(location).searchParams.get('q') || '';
        applyFilter(searchInput.value);
    </script>

    <style>
        #diagram-wrapper svg { display: block; max-width: none !important; }
    </style>
</body>
</html>
