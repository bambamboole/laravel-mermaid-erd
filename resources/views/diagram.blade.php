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
            <select id="erd-layout" title="Layout engine"
                class="rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-600 outline-none transition hover:border-gray-300 cursor-pointer">
                <option value="dagre">Layout: Dagre</option>
                <option value="elk">Layout: ELK</option>
            </select>
            <div class="mx-1 h-5 w-px bg-gray-200"></div>
            <button id="zoom-out-btn" title="Zoom out"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                &minus;
            </button>
            <span id="zoom-level" class="min-w-[48px] text-center text-xs text-gray-500">100%</span>
            <button id="zoom-in-btn" title="Zoom in"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                +
            </button>
            <button id="reset-view-btn" title="Reset view"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                Reset
            </button>
            <div class="mx-1 h-5 w-px bg-gray-200"></div>
            <button id="copy-btn" title="Copy Mermaid source"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                Copy Mermaid
            </button>
            <button id="download-svg-btn" title="Download SVG"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                Download SVG
            </button>
        </div>
    </header>

    <div id="diagram-container" class="fixed inset-x-0 bottom-0 top-[49px] cursor-grab overflow-hidden">
        <div id="diagram-wrapper" class="inline-block min-h-full min-w-full origin-top-left p-8"></div>
    </div>

    <div id="table-modal" class="fixed inset-0 z-10 hidden items-center justify-center bg-black/30 p-4">
        <div class="max-h-[80vh] w-full max-w-md overflow-y-auto rounded-lg bg-white p-5 shadow-xl">
            <div class="mb-3 flex items-center justify-between gap-4">
                <h2 id="table-modal-title" class="truncate text-sm font-semibold text-gray-800"></h2>
                <button id="table-modal-close" title="Close"
                    class="shrink-0 rounded-md px-2 py-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 cursor-pointer">
                    &times;
                </button>
            </div>
            <div id="table-modal-body"></div>
        </div>
    </div>

    <script>
        const mermaidSource = {!! $diagram !!};
        const graph = {!! $graph !!};
        const baseMermaidConfig = {!! $mermaidConfig !!};
    </script>
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.esm.min.mjs';
        import elkLayouts from 'https://cdn.jsdelivr.net/npm/@mermaid-js/layout-elk/dist/mermaid-layout-elk.esm.min.mjs';

        mermaid.registerLayoutLoaders(elkLayouts);

        let currentLayout = new URL(location).searchParams.get('layout') === 'elk' ? 'elk' : 'dagre';
        mermaid.initialize(Object.assign({}, baseMermaidConfig, { startOnLoad: false, layout: currentLayout }));

        let scale = 1;
        let translateX = 0;
        let translateY = 0;
        let isDragging = false;
        let dragged = false;
        let startX, startY;
        let mouseDownClientX, mouseDownClientY;

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

        // Zoom keeping the container point (px, py) fixed on screen.
        function zoomAt(px, py, newScale) {
            newScale = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, newScale));
            const ratio = newScale / scale;
            translateX = px - ratio * (px - translateX);
            translateY = py - ratio * (py - translateY);
            scale = newScale;
            applyTransform();
        }

        function zoomIn()  { zoomAt(container.clientWidth / 2, container.clientHeight / 2, scale + ZOOM_STEP); }
        function zoomOut() { zoomAt(container.clientWidth / 2, container.clientHeight / 2, scale - ZOOM_STEP); }
        function resetView() { scale = 1; translateX = 0; translateY = 0; applyTransform(); }

        container.addEventListener('wheel', function(e) {
            e.preventDefault();
            // Zoom proportionally to the wheel delta: trackpads emit many
            // small deltas (smooth glide), a mouse wheel notch (~100) matches
            // roughly the old 15% step. deltaMode 1 means line-based deltas.
            const delta = e.deltaMode === 1 ? e.deltaY * 24 : e.deltaY;
            const rect = container.getBoundingClientRect();
            zoomAt(e.clientX - rect.left, e.clientY - rect.top, scale * Math.exp(-delta * 0.002));
        }, { passive: false });

        container.addEventListener('mousedown', function(e) {
            isDragging = true;
            dragged = false;
            mouseDownClientX = e.clientX;
            mouseDownClientY = e.clientY;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
            container.classList.remove('cursor-grab');
            container.classList.add('cursor-grabbing');
        });

        window.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            // A few pixels of jitter shouldn't turn a click-to-open into a
            // suppressed drag; only real panning should block the click.
            if (Math.abs(e.clientX - mouseDownClientX) > 4 || Math.abs(e.clientY - mouseDownClientY) > 4) {
                dragged = true;
            }
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

        document.getElementById('zoom-out-btn').addEventListener('click', zoomOut);
        document.getElementById('zoom-in-btn').addEventListener('click', zoomIn);
        document.getElementById('reset-view-btn').addEventListener('click', resetView);
        document.getElementById('copy-btn').addEventListener('click', copyMermaid);
        document.getElementById('download-svg-btn').addEventListener('click', downloadSVG);

        const layoutSelect = document.getElementById('erd-layout');
        layoutSelect.value = currentLayout;
        layoutSelect.addEventListener('change', function() {
            currentLayout = layoutSelect.value;
            const url = new URL(location);
            url.searchParams.set('layout', currentLayout);
            history.replaceState(null, '', url);
            mermaid.initialize(Object.assign({}, baseMermaidConfig, { startOnLoad: false, layout: currentLayout }));
            renderDiagram();
        });

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
        const pivots = new Set(graph.pivots);

        function computeVisible(query) {
            const q = query.trim().toLowerCase();
            if (!q) return null;

            const matched = new Set();
            for (const [table, columns] of Object.entries(graph.tables)) {
                const model = (graph.models[table] || '').toLowerCase();
                if (table.toLowerCase().includes(q) || model.includes(q) || columns.some(c => c.toLowerCase().includes(q))) {
                    matched.add(table);
                }
            }

            const visible = new Set(matched);
            for (const [from, to] of graph.edges) {
                if (matched.has(from)) visible.add(to);
                if (matched.has(to)) visible.add(from);
            }

            // Pivot tables are pass-throughs: a many-to-many is one logical
            // relation, so both sides of a visible pivot stay visible.
            for (const [from, to] of graph.edges) {
                if (visible.has(from) && pivots.has(from)) visible.add(to);
                if (visible.has(to) && pivots.has(to)) visible.add(from);
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
                applyHighlights(searchInput.value);
            } catch (e) {
                wrapper.innerHTML = '<pre class="p-8 text-xs text-red-600">' + e.message + '</pre>';
            }
        }

        // Mermaid renders each field (table title, column type/name/keys/comment)
        // as its own <p> inside a foreignObject; highlighting the matches is just
        // flagging the ones whose text contains the query.
        function applyHighlights(query) {
            const q = query.trim().toLowerCase();
            if (!q) return;
            const svg = wrapper.querySelector('svg');
            if (!svg) return;
            svg.querySelectorAll('g.node .label p').forEach(function(p) {
                if (p.textContent.toLowerCase().includes(q)) {
                    p.parentElement.classList.add('erd-hl');
                }
            });
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

        // --- Table detail modal -------------------------------------------------
        // Mermaid gives each entity's rendered <g> an id of
        // "<renderId>-entity-<table>-<n>"; that's the only place the table name
        // survives into the DOM, so clicks are resolved by parsing it back out.

        const modal = document.getElementById('table-modal');
        const modalTitle = document.getElementById('table-modal-title');
        const modalBody = document.getElementById('table-modal-body');

        function escapeHtml(value) {
            return value.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        // Each column field (type/name/keys/comment) is its own <g class="label
        // ...">; reading them back out of the rendered entity — rather than
        // adding a parallel data payload — keeps the modal in lockstep with
        // whatever MermaidErdRenderer put in the box, accessors/casts included.
        function fieldTexts(nodeEl, className) {
            return Array.from(nodeEl.querySelectorAll('.label.' + className)).map(function(g) {
                const p = g.querySelector('p');
                return p ? p.textContent : '';
            });
        }

        function openTableModal(table, nodeEl) {
            const modelClass = graph.modelClasses[table];

            const types = fieldTexts(nodeEl, 'attribute-type');
            const names = fieldTexts(nodeEl, 'attribute-name');
            const keys = fieldTexts(nodeEl, 'attribute-keys');
            const comments = fieldTexts(nodeEl, 'attribute-comment');
            const columns = names.map((name, i) => ({ name: name, type: types[i], notes: [keys[i], comments[i]].filter(Boolean).join(', ') }));

            const relations = parsed.edges
                .filter(e => e.from === table || e.to === table)
                .map(e => {
                    const label = (e.line.match(/: "(.*)"$/) || [])[1] || '';
                    const other = e.from === table ? e.to : e.from;
                    return { other: other, label: label };
                });

            modalTitle.textContent = table;
            modalBody.innerHTML = `
                <dl class="grid grid-cols-[auto,1fr] gap-x-3 gap-y-1 text-xs">
                    <dt class="font-medium text-gray-500">Model</dt>
                    <dd class="text-gray-800">${modelClass ? escapeHtml(modelClass) : '—'}</dd>
                    <dt class="font-medium text-gray-500">Columns</dt>
                    <dd class="text-gray-800">${columns.length}</dd>
                    <dt class="font-medium text-gray-500">Pivot table</dt>
                    <dd class="text-gray-800">${pivots.has(table) ? 'Yes' : 'No'}</dd>
                </dl>
                <ul class="mt-3 max-h-48 divide-y divide-gray-100 overflow-y-auto text-xs">
                    ${columns.map(c => `
                        <li class="flex items-baseline justify-between gap-3 py-1">
                            <span class="text-gray-800">${escapeHtml(c.name)} <span class="text-gray-400">${escapeHtml(c.type)}</span></span>
                            <span class="shrink-0 text-right text-gray-500">${escapeHtml(c.notes)}</span>
                        </li>
                    `).join('')}
                </ul>
                ${relations.length ? `
                    <h3 class="mt-3 text-xs font-medium text-gray-500">Relations</h3>
                    <ul class="mt-1 max-h-32 divide-y divide-gray-100 overflow-y-auto text-xs">
                        ${relations.map(r => `
                            <li class="py-1 text-gray-700">
                                <span class="font-medium">${escapeHtml(r.other)}</span> — ${escapeHtml(r.label)}
                            </li>
                        `).join('')}
                    </ul>
                ` : ''}
            `;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeTableModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        wrapper.addEventListener('click', function(e) {
            if (dragged) return;
            const nodeEl = e.target.closest('g.node[id]');
            if (!nodeEl) return;
            const match = nodeEl.id.match(/-entity-(.+)-\d+$/);
            if (!match) return;
            openTableModal(match[1], nodeEl);
        });

        document.getElementById('table-modal-close').addEventListener('click', closeTableModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeTableModal();
        });
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeTableModal();
        });
    </script>

    <style>
        #diagram-wrapper svg { display: block; max-width: none !important; }
        #diagram-wrapper svg g.node { cursor: pointer; }
        #diagram-wrapper svg .erd-hl { background-color: rgba(245, 158, 11, 0.35); border-radius: 3px; }
    </style>
</body>
</html>
