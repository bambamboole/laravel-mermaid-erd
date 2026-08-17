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
            <button id="erd-focus" title="Clear focus"
                class="hidden shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-800 transition hover:bg-amber-200 cursor-pointer"></button>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <select id="erd-layout" title="Layout engine"
                class="rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-600 outline-none transition hover:border-gray-300 cursor-pointer">
                <option value="elk">Layout: ELK</option>
                <option value="dagre">Layout: Dagre</option>
            </select>
            <button id="health-btn" title="Schema health"
                class="flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                Health
                <span id="health-count" class="hidden rounded-full bg-amber-100 px-1.5 text-[10px] font-medium text-amber-800"></span>
            </button>
            <button id="legend-btn" title="Legend"
                class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                ?
            </button>
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

    <aside id="table-sidebar"
        class="fixed bottom-0 right-0 top-[49px] z-10 hidden w-96 max-w-full overflow-y-auto border-l border-gray-200 bg-white shadow-lg">
        <div class="sticky top-0 flex items-start justify-between gap-3 border-b border-gray-100 bg-white px-4 py-3">
            <div class="min-w-0">
                <h2 id="sidebar-title" class="truncate text-sm font-semibold text-gray-800"></h2>
                <div id="sidebar-badges" class="mt-1 flex flex-wrap gap-1"></div>
            </div>
            <div class="flex shrink-0 items-center gap-1">
                <button id="sidebar-focus" title="Focus on this table and its neighbors"
                    class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 cursor-pointer">
                    Focus
                </button>
                <button id="sidebar-close" title="Close (Esc)"
                    class="rounded-md px-2 py-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 cursor-pointer">
                    &times;
                </button>
            </div>
        </div>
        <div id="sidebar-body" class="px-4 py-3"></div>
    </aside>

    <div id="erd-legend"
        class="fixed bottom-4 left-4 z-10 hidden w-80 rounded-lg border border-gray-200 bg-white p-4 text-xs shadow-lg">
        <h3 class="mb-2 font-semibold text-gray-800">Legend</h3>
        <h4 class="mt-2 font-medium text-gray-500">Column markers</h4>
        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-gray-600">
            <span><span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-medium text-indigo-700">PK</span> primary key</span>
            <span><span class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-700">FK</span> foreign key</span>
            <span><span class="rounded bg-teal-100 px-1.5 py-0.5 text-[10px] font-medium text-teal-700">UK</span> unique</span>
        </div>
        <h4 class="mt-3 font-medium text-gray-500">Relation types</h4>
        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-gray-600">
            <span><span class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-700">FK</span> database constraint</span>
            <span><span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">Eloquent</span> declared on a model</span>
            <span><span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600">Guessed</span> by column convention</span>
            <span><span class="rounded bg-purple-100 px-1.5 py-0.5 text-[10px] font-medium text-purple-700">Morph</span> polymorphic</span>
        </div>
        <h4 class="mt-3 font-medium text-gray-500">Cardinality</h4>
        <dl class="mt-1 grid grid-cols-[auto,1fr] gap-x-3 gap-y-0.5 text-gray-600">
            <dt class="font-mono">||--o{</dt><dd>one to many</dd>
            <dt class="font-mono">|o--o{</dt><dd>optional parent, many children</dd>
            <dt class="font-mono">||--||</dt><dd>one to one</dd>
        </dl>
        <p class="mt-3 text-gray-600">
            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">no index</span>
            referencing columns lack a supporting index
        </p>
    </div>

    <script>
        const mermaidSource = {!! $diagram !!};
        const graph = {!! $graph !!};
        const baseMermaidConfig = {!! $mermaidConfig !!};
    </script>
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs';
        import elkLayouts from 'https://cdn.jsdelivr.net/npm/@mermaid-js/layout-elk@0/dist/mermaid-layout-elk.esm.min.mjs';

        mermaid.registerLayoutLoaders(elkLayouts);

        const defaultLayout = @js($defaultLayout);
        const layoutParam = new URL(location).searchParams.get('layout');
        let currentLayout = (layoutParam === 'elk' || layoutParam === 'dagre') ? layoutParam : defaultLayout;
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

        function expandNeighbors(matched) {
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

            return expandNeighbors(matched);
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
                applySelection();
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
        const focusChip = document.getElementById('erd-focus');

        function applyFilter(query) {
            const visible = computeVisible(query);

            const url = new URL(location);
            if (query.trim()) url.searchParams.set('q', query.trim()); else url.searchParams.delete('q');
            history.replaceState(null, '', url);

            if (visible === null) {
                countLabel.textContent = totalTables + ' tables · ' + graph.relations.length + ' relations';
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

        // --- Focus mode ----------------------------------------------------------
        // Focus narrows the diagram to one table plus its direct neighbors.
        // Search and focus are mutually exclusive: starting one clears the other.

        let focusTable = null;

        function applyFocus(table) {
            focusTable = table;
            searchInput.value = '';

            const url = new URL(location);
            url.searchParams.delete('q');
            history.replaceState(null, '', url);

            const visible = expandNeighbors(new Set([table]));
            focusChip.textContent = 'Focused: ' + table + ' ✕';
            focusChip.classList.remove('hidden');
            countLabel.textContent = visible.size + ' / ' + totalTables + ' tables';
            currentSource = buildFilteredSource(visible);
            renderDiagram();
        }

        function clearFocus() {
            if (focusTable === null) return;
            focusTable = null;
            focusChip.classList.add('hidden');
            applyFilter(searchInput.value);
        }

        focusChip.addEventListener('click', clearFocus);

        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                focusTable = null;
                focusChip.classList.add('hidden');
                applyFilter(searchInput.value);
            }, 150);
        });

        searchInput.value = new URL(location).searchParams.get('q') || '';
        applyFilter(searchInput.value);

        // --- Table detail sidebar ------------------------------------------------
        // Mermaid gives each entity's rendered <g> an id of
        // "<renderId>-entity-<table>-<n>"; that's the only place the table name
        // survives into the DOM, so clicks are resolved by parsing it back out.
        // The sidebar content itself comes from the structured graph payload
        // (graph.details / graph.relations), not from the rendered SVG.

        const sidebar = document.getElementById('table-sidebar');
        const sidebarTitle = document.getElementById('sidebar-title');
        const sidebarBadges = document.getElementById('sidebar-badges');
        const sidebarBody = document.getElementById('sidebar-body');

        let selectedTable = null;

        function escapeHtml(value) {
            return value.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        function chip(text, classes) {
            return '<span class="rounded px-1.5 py-0.5 text-[10px] font-medium ' + classes + '">' + escapeHtml(text) + '</span>';
        }

        const TYPE_BADGES = {
            fk: ['FK', 'bg-blue-100 text-blue-700'],
            eloquent: ['Eloquent', 'bg-emerald-100 text-emerald-700'],
            guessed: ['Guessed', 'bg-gray-100 text-gray-600'],
            morph: ['Morph', 'bg-purple-100 text-purple-700'],
        };

        const TABLE_LINK = 'cursor-pointer font-medium text-gray-800 underline decoration-gray-300 underline-offset-2 hover:decoration-gray-500';

        function applySelection() {
            const svg = wrapper.querySelector('svg');
            if (!svg) return;
            svg.querySelectorAll('g.node.erd-selected').forEach(n => n.classList.remove('erd-selected'));
            if (!selectedTable) return;
            svg.querySelectorAll('g.node[id]').forEach(function(node) {
                const match = node.id.match(/-entity-(.+)-\d+$/);
                if (match && match[1] === selectedTable) node.classList.add('erd-selected');
            });
        }

        function columnRow(c) {
            const chips = [];
            if (c.pk) chips.push(chip('PK', 'bg-indigo-100 text-indigo-700'));
            if (c.fk) chips.push(chip('FK', 'bg-blue-100 text-blue-700'));
            if (c.uk && !c.pk) chips.push(chip('UK', 'bg-teal-100 text-teal-700'));
            if (c.nullable) chips.push(chip('nullable', 'bg-gray-100 text-gray-500'));
            if (c.softDelete) chips.push(chip('soft-delete', 'bg-orange-100 text-orange-700'));
            if (c.poly) chips.push(chip('polymorphic', 'bg-purple-100 text-purple-700'));
            if (c.accessor) chips.push(chip('accessor', 'bg-gray-100 text-gray-500'));
            if (c.mutator) chips.push(chip('mutator', 'bg-gray-100 text-gray-500'));

            const extras = [];
            if (c.cast) extras.push('cast: ' + (c.cast.includes('\\') ? c.cast.split('\\').pop() : c.cast));
            if (c.default !== undefined) extras.push('default: ' + c.default);

            return `
                <li class="py-1.5">
                    <div class="flex items-baseline gap-2">
                        <span class="min-w-0 truncate font-mono text-gray-800">${escapeHtml(c.name)}</span>
                        <span class="ml-auto shrink-0 text-gray-400">${escapeHtml(c.type)}</span>
                    </div>
                    ${chips.length || extras.length ? `
                        <div class="mt-0.5 flex flex-wrap items-center gap-1">
                            ${chips.join('')}
                            ${extras.map(e => `<span class="text-[10px] text-gray-500">${escapeHtml(e)}</span>`).join('')}
                        </div>
                    ` : ''}
                </li>`;
        }

        function relationRow(r, other) {
            const badge = TYPE_BADGES[r.type] || [r.type, 'bg-gray-100 text-gray-600'];
            const cardinality = r.type === 'morph' ? 'morph(' + r.morphName + ')' : (r.oneToOne ? '1:1' : '1:N');

            const meta = [];
            if (r.columns && r.columns.length) meta.push('via ' + r.columns.join(', '));
            if (r.declaredAs) meta.push(r.declaredAs);
            if (r.onDelete) meta.push('on delete ' + r.onDelete);

            return `
                <li class="flex flex-wrap items-center gap-1.5 py-1.5">
                    <button data-table="${escapeHtml(other)}" class="${TABLE_LINK}">${escapeHtml(other)}</button>
                    ${chip(badge[0], badge[1])}
                    <span class="text-[10px] text-gray-500">${escapeHtml(cardinality)}</span>
                    ${meta.length ? `<span class="text-[10px] text-gray-400">${escapeHtml(meta.join(' · '))}</span>` : ''}
                    ${r.unindexed ? chip('no index', 'bg-amber-100 text-amber-700') : ''}
                </li>`;
        }

        function relationSection(title, rows) {
            if (!rows.length) return '';
            return `
                <h3 class="mt-4 text-xs font-medium text-gray-500">${title}</h3>
                <ul class="mt-1 divide-y divide-gray-100 text-xs">${rows.join('')}</ul>`;
        }

        function openSidebar(table) {
            const details = graph.details[table];
            if (!details) return;
            selectedTable = table;

            sidebarTitle.textContent = table;

            const badges = [];
            if (details.pivot) badges.push(chip('pivot', 'bg-amber-100 text-amber-700'));
            (details.morphs || []).forEach(function(m) {
                const unindexed = (details.unindexedMorphs || []).includes(m);
                badges.push(chip('morph: ' + m + (unindexed ? ' · no index' : ''),
                    unindexed ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700'));
            });
            if (graph.models[table]) badges.push(chip(graph.models[table], 'bg-gray-100 text-gray-600'));
            sidebarBadges.innerHTML = badges.join('');

            const belongsTo = graph.relations.filter(r => r.to === table);
            const referencedBy = graph.relations.filter(r => r.from === table);
            const modelClass = graph.modelClasses[table];

            sidebarBody.innerHTML = `
                <dl class="grid grid-cols-[auto,1fr] gap-x-3 gap-y-1 text-xs">
                    <dt class="font-medium text-gray-500">Model</dt>
                    <dd class="break-all text-gray-800">${modelClass ? escapeHtml(modelClass) : '—'}</dd>
                    <dt class="font-medium text-gray-500">Columns</dt>
                    <dd class="text-gray-800">${details.columns.length}</dd>
                    <dt class="font-medium text-gray-500">Relations</dt>
                    <dd class="text-gray-800">${belongsTo.length + referencedBy.length}</dd>
                </dl>
                <h3 class="mt-4 text-xs font-medium text-gray-500">Columns</h3>
                <ul class="mt-1 divide-y divide-gray-100 text-xs">
                    ${details.columns.map(columnRow).join('')}
                </ul>
                ${relationSection('Belongs to', belongsTo.map(r => relationRow(r, r.from)))}
                ${relationSection('Referenced by', referencedBy.map(r => relationRow(r, r.to)))}
            `;
            sidebar.classList.remove('hidden');
            applySelection();
        }

        function closeSidebar() {
            sidebar.classList.add('hidden');
            selectedTable = null;
            applySelection();
        }

        wrapper.addEventListener('click', function(e) {
            if (dragged) return;
            const nodeEl = e.target.closest('g.node[id]');
            if (!nodeEl) return;
            const match = nodeEl.id.match(/-entity-(.+)-\d+$/);
            if (!match) return;
            openSidebar(match[1]);
        });

        sidebarBody.addEventListener('click', function(e) {
            const target = e.target.closest('[data-table]');
            if (target) openSidebar(target.dataset.table);
        });

        document.getElementById('sidebar-close').addEventListener('click', closeSidebar);
        document.getElementById('sidebar-focus').addEventListener('click', function() {
            if (selectedTable) applyFocus(selectedTable);
        });

        // --- Schema health ---------------------------------------------------------

        const healthIssues = {
            unindexed: graph.relations.filter(r => r.unindexed),
            unmappedMorphs: graph.unmappedMorphs,
        };
        const healthCount = document.getElementById('health-count');
        const totalIssues = healthIssues.unindexed.length + healthIssues.unmappedMorphs.length;
        if (totalIssues > 0) {
            healthCount.textContent = totalIssues;
            healthCount.classList.remove('hidden');
        }

        function openHealthPanel() {
            selectedTable = null;
            applySelection();

            sidebarTitle.textContent = 'Schema health';
            sidebarBadges.innerHTML = '';

            const unindexedRows = healthIssues.unindexed.map(function(r) {
                const via = (r.columns && r.columns.length) ? r.columns.join(', ') : (r.morphName ? r.morphName + '_id' : '');
                return `
                    <li class="flex flex-wrap items-center gap-1.5 py-1.5">
                        <button data-table="${escapeHtml(r.to)}" class="${TABLE_LINK}">${escapeHtml(r.to)}</button>
                        <span class="text-[10px] text-gray-400">${escapeHtml(via)} →</span>
                        <button data-table="${escapeHtml(r.from)}" class="${TABLE_LINK}">${escapeHtml(r.from)}</button>
                    </li>`;
            });

            sidebarBody.innerHTML = `
                ${unindexedRows.length ? `
                    <h3 class="text-xs font-medium text-gray-500">Relations without a supporting index</h3>
                    <ul class="mt-1 divide-y divide-gray-100 text-xs">${unindexedRows.join('')}</ul>
                ` : ''}
                ${healthIssues.unmappedMorphs.length ? `
                    <h3 class="mt-4 text-xs font-medium text-gray-500">Unmapped polymorphic relations</h3>
                    <p class="mt-1 text-[10px] text-gray-400">Add them to the 'mermaid-erd.polymorphic_relationships' config.</p>
                    <ul class="mt-1 divide-y divide-gray-100 text-xs">
                        ${healthIssues.unmappedMorphs.map(m => `<li class="py-1.5 font-mono text-gray-700">${escapeHtml(m)}</li>`).join('')}
                    </ul>
                ` : ''}
                ${totalIssues === 0 ? '<p class="text-xs text-gray-500">No issues found.</p>' : ''}
            `;
            sidebar.classList.remove('hidden');
        }

        document.getElementById('health-btn').addEventListener('click', openHealthPanel);

        // --- Legend ------------------------------------------------------------------

        const legend = document.getElementById('erd-legend');
        document.getElementById('legend-btn').addEventListener('click', function() {
            legend.classList.toggle('hidden');
        });

        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSidebar();
                legend.classList.add('hidden');
            }
        });
    </script>

    <style>
        #diagram-wrapper svg { display: block; max-width: none !important; }
        #diagram-wrapper svg g.node { cursor: pointer; }
        #diagram-wrapper svg .erd-hl { background-color: rgba(245, 158, 11, 0.35); border-radius: 3px; }
        #diagram-wrapper svg g.node.erd-selected { filter: drop-shadow(0 0 3px rgb(245 158 11)); }
    </style>
</body>
</html>
