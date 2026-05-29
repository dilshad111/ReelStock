<template>
    <section class="qc-production-workspace">
        <div class="qc-production-header">
            <div>
                <span class="qc-eyebrow">Production Module</span>
                <h1><i class="bi bi-boxes me-2"></i> QC Production</h1>
                <p>Manufacturing specifications, job issues, corrugation tracking, inventory reports, and carton die-line tools.</p>
            </div>
            <div class="qc-header-actions">
                <button class="qc-action qc-action-primary" type="button" @click="$emit('navigate', 'job-cards')">
                    <i class="bi bi-plus-circle"></i>
                    Job Card
                </button>
                <button class="qc-action qc-action-secondary" type="button" @click="$emit('navigate', 'production-dashboard')">
                    <i class="bi bi-graph-up-arrow"></i>
                    Analytics
                </button>
            </div>
        </div>

        <div class="qc-production-grid">
            <article v-for="module in modules" :key="module.title" class="qc-module-card">
                <div class="qc-module-icon" :class="module.tone">
                    <i :class="module.icon"></i>
                </div>
                <div>
                    <h2>{{ module.title }}</h2>
                    <p>{{ module.description }}</p>
                    <div class="qc-module-meta">
                        <span v-for="item in module.items" :key="item">{{ item }}</span>
                    </div>
                </div>
            </article>
        </div>

        <div class="qc-tool-panel">
            <div class="qc-tool-copy">
                <span class="qc-eyebrow">Image Generation Engine</span>
                <h2>Carton Die-Line Preview</h2>
                <p>Start of the imported QC production die-line engine. Enter carton dimensions to generate a clean 2D FEFCO-style layout and export the SVG.</p>
            </div>

            <form class="qc-dieline-form" @submit.prevent>
                <label>
                    <span>FEFCO</span>
                    <select v-model="dieLine.fefco">
                        <option value="0201">0201 Regular Slotted Carton</option>
                        <option value="0200">0200 Slotted Carton</option>
                        <option value="0427">0427 Folder Type</option>
                    </select>
                </label>
                <label>
                    <span>Length (mm)</span>
                    <input v-model.number="dieLine.length" type="number" min="1" inputmode="decimal">
                </label>
                <label>
                    <span>Width (mm)</span>
                    <input v-model.number="dieLine.width" type="number" min="1" inputmode="decimal">
                </label>
                <label>
                    <span>Height (mm)</span>
                    <input v-model.number="dieLine.height" type="number" min="1" inputmode="decimal">
                </label>
                <button class="qc-action qc-action-primary qc-export-button" type="button" @click="downloadDieLine">
                    <i class="bi bi-download"></i>
                    Export SVG
                </button>
            </form>

            <div class="qc-dieline-preview" v-html="dieLineSvg"></div>
        </div>
    </section>
</template>

<script setup>
import { computed, reactive } from 'vue';

defineEmits(['navigate']);

const modules = [
    {
        title: 'Specifications & Job Cards',
        description: 'Customer-wise carton specification entry with layers, papers, inks, machine speeds, and revision history.',
        icon: 'bi bi-file-earmark-ruled',
        tone: 'tone-blue',
        items: ['Create', 'Revise', 'Print', 'PDF']
    },
    {
        title: 'Production Issue',
        description: 'Issue job cards to production, manage reel usage, process progress, inventory movement, and dispatch handoff.',
        icon: 'bi bi-kanban',
        tone: 'tone-emerald',
        items: ['Issue', 'Manage', 'Track', 'Dispatch']
    },
    {
        title: 'Corrugation Plant',
        description: 'Start/end production runs, downtime, wastage, operators, speed monitoring, and daily corrugation report.',
        icon: 'bi bi-cpu',
        tone: 'tone-amber',
        items: ['Run Log', 'Downtime', 'Wastage', 'Report']
    },
    {
        title: 'Inventory & Reports',
        description: 'GRN, material issue, balance report, consumption report, suppliers, raw material masters, and departments.',
        icon: 'bi bi-clipboard-data',
        tone: 'tone-violet',
        items: ['GRN', 'Issue', 'Balance', 'Consumption']
    },
    {
        title: 'Machine Masters',
        description: 'Corrugation, printing, die cutting machines, speed parameters, company setup, papers, staff, and cartons.',
        icon: 'bi bi-sliders',
        tone: 'tone-cyan',
        items: ['Machines', 'Speeds', 'Papers', 'Staff']
    },
    {
        title: 'Die-Line Engine',
        description: 'FEFCO-based technical carton sketch generation with dimension labels and downloadable engineering output.',
        icon: 'bi bi-bezier2',
        tone: 'tone-rose',
        items: ['Preview', 'SVG', 'Export', 'FEFCO']
    }
];

const dieLine = reactive({
    fefco: '0201',
    length: 300,
    width: 180,
    height: 140
});

const dieLineSvg = computed(() => {
    const length = Math.max(Number(dieLine.length) || 1, 1);
    const width = Math.max(Number(dieLine.width) || 1, 1);
    const height = Math.max(Number(dieLine.height) || 1, 1);
    const glue = 35;
    const flap = width / 2;
    const totalWidth = glue + (2 * length) + (2 * width);
    const totalHeight = (2 * flap) + height;
    const scale = Math.min(760 / totalWidth, 320 / totalHeight);
    const pad = 48;
    const title = 48;
    const x = pad;
    const y = pad + title + flap * scale;
    const bodyHeight = height * scale;
    const flapHeight = flap * scale;
    const panels = [
        { width: glue, label: 'Glue 35' },
        { width: length, label: `L ${length}` },
        { width, label: `W ${width}` },
        { width: length, label: `L ${length}` },
        { width, label: `W ${width}` }
    ];

    let cursor = x;
    const panelMarkup = panels.map((panel, index) => {
        const panelWidth = panel.width * scale;
        const markup = `
            <rect x="${cursor}" y="${y}" width="${panelWidth}" height="${bodyHeight}" class="qc-svg-panel ${index === 0 ? 'glue' : ''}" />
            <text x="${cursor + panelWidth / 2}" y="${y + bodyHeight / 2}" text-anchor="middle" class="qc-svg-label">${panel.label}</text>
            ${index > 0 ? `<rect x="${cursor}" y="${y - flapHeight}" width="${panelWidth}" height="${flapHeight}" class="qc-svg-flap" />
            <rect x="${cursor}" y="${y + bodyHeight}" width="${panelWidth}" height="${flapHeight}" class="qc-svg-flap" />` : ''}
        `;
        cursor += panelWidth;
        return markup;
    }).join('');

    const viewWidth = totalWidth * scale + pad * 2;
    const viewHeight = totalHeight * scale + pad * 2 + title;
    const lengthStart = x + glue * scale;
    const lengthEnd = lengthStart + length * scale;
    const heightX = x + totalWidth * scale + 24;

    return `
        <svg viewBox="0 0 ${viewWidth} ${viewHeight}" role="img" aria-label="Carton die-line preview">
            <style>
                .qc-svg-title{font:700 18px Inter,Arial,sans-serif;fill:#0f172a}
                .qc-svg-subtitle{font:600 11px Inter,Arial,sans-serif;fill:#64748b}
                .qc-svg-panel{fill:#f8fafc;stroke:#0f172a;stroke-width:1.6}
                .qc-svg-panel.glue{fill:#eff6ff}
                .qc-svg-flap{fill:#ffffff;stroke:#0f172a;stroke-width:1.2;stroke-dasharray:4 4}
                .qc-svg-label{font:700 12px Inter,Arial,sans-serif;fill:#0f172a}
                .qc-svg-dimension{stroke:#2563eb;stroke-width:1.4;marker-start:url(#arrow);marker-end:url(#arrow)}
                .qc-svg-dimension-text{font:700 11px Inter,Arial,sans-serif;fill:#1d4ed8}
            </style>
            <defs>
                <marker id="arrow" markerWidth="8" markerHeight="8" refX="4" refY="4" orient="auto">
                    <path d="M0,0 L8,4 L0,8" fill="#2563eb" />
                </marker>
            </defs>
            <text x="${pad}" y="${pad}" class="qc-svg-title">FEFCO ${dieLine.fefco} Die-Line</text>
            <text x="${pad}" y="${pad + 20}" class="qc-svg-subtitle">${length}L x ${width}W x ${height}H mm</text>
            ${panelMarkup}
            <line x1="${lengthStart}" y1="${y - flapHeight - 18}" x2="${lengthEnd}" y2="${y - flapHeight - 18}" class="qc-svg-dimension" />
            <text x="${(lengthStart + lengthEnd) / 2}" y="${y - flapHeight - 26}" text-anchor="middle" class="qc-svg-dimension-text">L = ${length} mm</text>
            <line x1="${heightX}" y1="${y}" x2="${heightX}" y2="${y + bodyHeight}" class="qc-svg-dimension" />
            <text x="${heightX + 16}" y="${y + bodyHeight / 2}" class="qc-svg-dimension-text" transform="rotate(90 ${heightX + 16} ${y + bodyHeight / 2})">H = ${height} mm</text>
        </svg>
    `;
});

const downloadDieLine = () => {
    const blob = new Blob([dieLineSvg.value], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `qc-dieline-${dieLine.fefco}-${Date.now()}.svg`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
};
</script>

<style scoped>
.qc-production-workspace {
    --qc-bg: var(--bs-body-bg, #f8fafc);
    --qc-surface: var(--bs-body-bg, #ffffff);
    --qc-panel: var(--bs-tertiary-bg, #f1f5f9);
    --qc-border: var(--bs-border-color, #dbe3ef);
    --qc-text: var(--bs-body-color, #0f172a);
    --qc-muted: var(--bs-secondary-color, #64748b);
    --qc-primary: #2563eb;
    --qc-primary-strong: #1d4ed8;
    color: var(--qc-text);
}

.qc-production-header,
.qc-tool-panel,
.qc-module-card {
    border: 1px solid var(--qc-border);
    background: var(--qc-surface);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

.qc-production-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    padding: 24px;
    border-radius: 10px;
    margin-bottom: 22px;
}

.qc-eyebrow {
    display: inline-flex;
    margin-bottom: 6px;
    color: var(--qc-primary);
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.qc-production-header h1,
.qc-tool-copy h2,
.qc-module-card h2 {
    color: var(--qc-text);
    margin: 0;
    font-weight: 800;
}

.qc-production-header h1 {
    font-size: clamp(1.7rem, 3vw, 2.45rem);
}

.qc-production-header p,
.qc-tool-copy p,
.qc-module-card p {
    color: var(--qc-muted);
    margin: 6px 0 0;
}

.qc-header-actions,
.qc-dieline-form {
    display: flex;
    align-items: end;
    gap: 12px;
    flex-wrap: wrap;
}

.qc-action {
    border: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 16px;
    font-weight: 800;
    color: #ffffff;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.qc-action:hover {
    transform: translateY(-1px);
}

.qc-action-primary {
    background: linear-gradient(135deg, var(--qc-primary), #3b82f6);
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.28);
}

.qc-action-secondary {
    background: linear-gradient(135deg, #0f766e, #14b8a6);
    box-shadow: 0 10px 22px rgba(15, 118, 110, 0.22);
}

.qc-production-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.qc-module-card {
    display: grid;
    grid-template-columns: 46px 1fr;
    gap: 14px;
    border-radius: 8px;
    padding: 18px;
}

.qc-module-card h2,
.qc-tool-copy h2 {
    font-size: 1.1rem;
}

.qc-module-icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    font-size: 1.3rem;
}

.tone-blue { color: #1d4ed8; background: rgba(37, 99, 235, 0.14); }
.tone-emerald { color: #047857; background: rgba(16, 185, 129, 0.14); }
.tone-amber { color: #b45309; background: rgba(245, 158, 11, 0.16); }
.tone-violet { color: #7c3aed; background: rgba(124, 58, 237, 0.14); }
.tone-cyan { color: #0e7490; background: rgba(6, 182, 212, 0.14); }
.tone-rose { color: #be123c; background: rgba(244, 63, 94, 0.14); }

.qc-module-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 14px;
}

.qc-module-meta span {
    border: 1px solid var(--qc-border);
    border-radius: 999px;
    color: var(--qc-muted);
    background: var(--qc-panel);
    font-size: 0.74rem;
    font-weight: 800;
    padding: 4px 9px;
}

.qc-tool-panel {
    border-radius: 10px;
    padding: 22px;
}

.qc-dieline-form {
    margin: 18px 0;
}

.qc-dieline-form label {
    display: grid;
    gap: 6px;
    color: var(--qc-muted);
    font-size: 0.82rem;
    font-weight: 800;
}

.qc-dieline-form input,
.qc-dieline-form select {
    width: 180px;
    min-height: 42px;
    border: 1px solid var(--qc-border);
    border-radius: 8px;
    background: var(--qc-surface);
    color: var(--qc-text);
    padding: 0 12px;
    font-weight: 700;
    outline: none;
}

.qc-dieline-form select {
    width: 260px;
}

.qc-dieline-form input:focus,
.qc-dieline-form select:focus {
    border-color: var(--qc-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
}

.qc-export-button {
    align-self: end;
}

.qc-dieline-preview {
    border: 1px dashed var(--qc-border);
    border-radius: 8px;
    background: #ffffff;
    padding: 16px;
    overflow: auto;
}

.qc-dieline-preview :deep(svg) {
    display: block;
    max-width: 100%;
    min-width: 620px;
}

:global([data-theme="dark"]) .qc-production-workspace {
    --qc-surface: #111827;
    --qc-panel: #1f2937;
    --qc-border: #334155;
    --qc-text: #f8fafc;
    --qc-muted: #9fb0c8;
}

:global([data-theme="dark"]) .qc-production-header,
:global([data-theme="dark"]) .qc-tool-panel,
:global([data-theme="dark"]) .qc-module-card {
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.24);
}

@media (max-width: 1180px) {
    .qc-production-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .qc-production-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .qc-production-grid {
        grid-template-columns: 1fr;
    }

    .qc-dieline-form,
    .qc-dieline-form label,
    .qc-dieline-form input,
    .qc-dieline-form select,
    .qc-export-button {
        width: 100%;
    }
}
</style>
