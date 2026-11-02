import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    ScatterController,
    Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Filler,
    LinearScale, LineController, LineElement, PointElement, ScatterController, Tooltip,
);

const data = JSON.parse(document.getElementById('dashboard-data').textContent);

const TEAL = '#1d9e92';
const SKY = '#7fb4f0';
const GRID = 'rgba(148, 163, 184, 0.22)';

Chart.defaults.font.family = '"Plus Jakarta Sans Variable", ui-sans-serif, system-ui, sans-serif';
Chart.defaults.font.size = 11;
Chart.defaults.color = '#6a7d8f';

const juta = (value) => `Rp ${Number(value).toLocaleString('id-ID', { maximumFractionDigits: 1 })} Jt`;

const axisValue = (value) => (value >= 1000 ? `${(value / 1000).toLocaleString('id-ID')} M` : `${value} Jt`);

const tooltip = {
    backgroundColor: '#ffffff',
    titleColor: '#0f2437',
    bodyColor: '#334a5e',
    borderColor: '#e2e8f0',
    borderWidth: 1,
    padding: 10,
    cornerRadius: 12,
    displayColors: false,
    titleFont: { weight: '700' },
};

const baseScales = {
    x: { grid: { display: false }, border: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 14 } },
    y: {
        beginAtZero: true,
        grid: { color: GRID },
        border: { display: false },
        ticks: { callback: axisValue, maxTicksLimit: 5 },
    },
};

// Garis bantu vertikal saat kursor berada di atas grafik area.
const crosshair = {
    id: 'crosshair',
    beforeDatasetsDraw(chart) {
        const [active] = chart.getActiveElements();
        if (!active) return;

        const { ctx, chartArea } = chart;
        ctx.save();
        ctx.strokeStyle = 'rgba(29, 158, 146, 0.4)';
        ctx.setLineDash([4, 4]);
        ctx.beginPath();
        ctx.moveTo(active.element.x, chartArea.top);
        ctx.lineTo(active.element.x, chartArea.bottom);
        ctx.stroke();
        ctx.restore();
    },
};

const canvas = (id) => document.getElementById(id);

function nteAreaChart() {
    const el = canvas('chart-nte');
    if (!el) return;

    new Chart(el, {
        type: 'line',
        plugins: [crosshair],
        data: {
            labels: data.nte.labels,
            datasets: [{
                data: data.nte.values,
                borderColor: TEAL,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#ffffff',
                pointHoverBorderColor: TEAL,
                pointHoverBorderWidth: 2,
                backgroundColor: ({ chart }) => {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return 'transparent';
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(29, 158, 146, 0.30)');
                    gradient.addColorStop(1, 'rgba(29, 158, 146, 0)');
                    return gradient;
                },
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: baseScales,
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltip,
                    callbacks: {
                        label: (c) => ` Akumulasi: ${juta(c.parsed.y)}`,
                        afterLabel: (c) => ` NTE kawasan: ${juta(data.nte.each[c.dataIndex])}`,
                    },
                },
            },
        },
    });
}

function usahaDonut() {
    const el = canvas('chart-usaha');
    if (!el) return;

    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: data.usaha.labels,
            datasets: [{ data: data.usaha.values, backgroundColor: data.usaha.colors, borderWidth: 0, borderRadius: 6, spacing: 3 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '74%',
            plugins: {
                legend: { display: false },
                tooltip: { ...tooltip, callbacks: { label: (c) => ` Rp ${Number(c.parsed).toLocaleString('id-ID')}` } },
            },
        },
    });
}

function nteSplitBars() {
    const el = canvas('chart-split');
    if (!el) return;

    const bar = (label, values, color) => ({
        label, data: values, backgroundColor: color, borderRadius: 4, borderSkipped: false,
        barPercentage: 0.85, categoryPercentage: 0.7,
    });

    new Chart(el, {
        type: 'bar',
        data: {
            labels: data.split.labels,
            datasets: [bar('NTE langsung', data.split.langsung, TEAL), bar('NTE tidak langsung', data.split.tidakLangsung, SKY)],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                ...baseScales,
                x: { ...baseScales.x, ticks: { maxRotation: 50, minRotation: 50, autoSkip: false, font: { size: 10 } } },
            },
            plugins: {
                legend: { display: false },
                tooltip: { ...tooltip, displayColors: true, callbacks: { label: (c) => ` ${c.dataset.label}: ${juta(c.parsed.y)}` } },
            },
        },
    });
}

function clusterScatter() {
    const el = canvas('chart-cluster');
    if (!el) return;

    new Chart(el, {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Pemetaan Kawasan Konservasi',
                data: data.cluster,
                backgroundColor: 'rgba(29, 158, 146, 0.75)',
                borderColor: '#ffffff',
                borderWidth: 2,
                pointRadius: 8,
                pointHoverRadius: 11,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'linear',
                    grid: { color: GRID },
                    border: { display: false },
                    title: { display: true, text: 'Total NTE (Juta Rp)', color: '#334a5e', font: { weight: '600' } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: GRID },
                    border: { display: false },
                    title: { display: true, text: 'Jumlah Desa Penyangga', color: '#334a5e', font: { weight: '600' } },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltip,
                    callbacks: {
                        title: (items) => items[0].raw.label,
                        label: (c) => [` Total NTE : ${juta(c.raw.x)}`, ` Desa Penyangga : ${c.raw.y} Desa`],
                    },
                },
            },
        },
    });
}

// Pencarian langsung pada tabel kelompok binaan.
function tableSearch() {
    const input = document.getElementById('searchTable');
    if (!input) return;

    input.addEventListener('input', () => {
        const keyword = input.value.toLowerCase();
        document.querySelectorAll('#kelompokTable tbody tr').forEach((row) => {
            row.hidden = !row.textContent.toLowerCase().includes(keyword);
        });
    });
}

[nteAreaChart, usahaDonut, nteSplitBars, clusterScatter, tableSearch].forEach((init) => init());
