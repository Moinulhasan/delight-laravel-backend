import Chart from 'chart.js/auto';
import { DataTable } from 'simple-datatables';

// Brand-derived and checked for colour-blind separation on the #112737 panel.
const BRASS = '#b58a3e';
const BLUE = '#4a92cc';
const CORAL = '#d4614f';
const PANEL = '#112737';
const GRID = 'rgba(244, 238, 227, 0.08)';
const BONE = '#f4eee3';
const MUTED = '#a6b6c0';

const data = JSON.parse(document.getElementById('dashboard-data').textContent);
const labels = data.types.map((type) => type.label);

Chart.defaults.color = MUTED;
Chart.defaults.font.family = "'Montserrat', system-ui, sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.maintainAspectRatio = false;
Chart.defaults.animation = matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 700 };
Chart.defaults.plugins.tooltip = {
    ...Chart.defaults.plugins.tooltip,
    backgroundColor: '#0b1a24',
    borderColor: 'rgba(244, 238, 227, 0.2)',
    borderWidth: 1,
    titleColor: BONE,
    bodyColor: BONE,
    padding: 10,
    cornerRadius: 8,
    boxPadding: 4,
};

const canvas = (id) => document.getElementById(id);
const quietAxis = { grid: { display: false }, border: { display: false } };
const gridAxis = { grid: { color: GRID }, border: { display: false } };

// Project status
new Chart(canvas('status-chart'), {
    type: 'doughnut',
    data: {
        labels: data.status.map((status) => status[0].toUpperCase() + status.slice(1)),
        datasets: [{
            data: data.statusCounts,
            backgroundColor: [BRASS, BLUE, CORAL],
            borderColor: PANEL,
            borderWidth: 2,
            borderRadius: 4,
        }],
    },
    options: { cutout: '76%', plugins: { legend: { display: false } } },
});

// Live and hidden items per content type
new Chart(canvas('visibility-chart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            { label: 'Live', data: data.types.map((type) => type.live), backgroundColor: BRASS },
            { label: 'Hidden', data: data.types.map((type) => type.hidden), backgroundColor: BLUE },
        ].map((set) => ({ ...set, borderColor: PANEL, borderWidth: 2, borderRadius: 4, maxBarThickness: 18 })),
    },
    options: {
        indexAxis: 'y',
        scales: {
            x: { ...gridAxis, stacked: true, beginAtZero: true, ticks: { precision: 0 } },
            y: { ...quietAxis, stacked: true },
        },
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8 } } },
    },
});

// Bangla coverage per content type
new Chart(canvas('bangla-chart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Bangla filled',
            data: data.types.map((type) => type.bangla),
            backgroundColor: BRASS,
            borderRadius: 4,
            maxBarThickness: 18,
        }],
    },
    options: {
        indexAxis: 'y',
        scales: {
            x: { ...gridAxis, min: 0, max: 100, ticks: { callback: (value) => `${value}%` } },
            y: quietAxis,
        },
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (item) => `${item.parsed.x}% filled` } },
        },
    },
});

// Items last edited per day
const editsCanvas = canvas('edits-chart');
const fill = editsCanvas.getContext('2d').createLinearGradient(0, 0, 0, 260);
fill.addColorStop(0, 'rgba(201, 162, 91, 0.28)');
fill.addColorStop(1, 'rgba(201, 162, 91, 0)');

new Chart(editsCanvas, {
    type: 'line',
    data: {
        labels: data.editLabels,
        datasets: [{
            label: 'Items last edited',
            data: data.editCounts,
            borderColor: '#c9a25b',
            backgroundColor: fill,
            borderWidth: 2,
            fill: true,
            cubicInterpolationMode: 'monotone',
            pointRadius: 0,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: '#c9a25b',
            pointHoverBorderColor: PANEL,
            pointHoverBorderWidth: 2,
        }],
    },
    options: {
        interaction: { mode: 'index', intersect: false },
        scales: {
            x: quietAxis,
            y: { ...gridAxis, beginAtZero: true, ticks: { precision: 0 } },
        },
        plugins: { legend: { display: false } },
    },
});

new DataTable('#inventory-table', {
    perPage: 10,
    perPageSelect: [10, 25, 50],
    searchable: true,
    columns: [{ select: 3, type: 'number' }],
    labels: {
        placeholder: 'Search content',
        searchTitle: 'Search content',
        perPage: 'rows per page',
        noRows: 'No content yet',
        noResults: 'Nothing matches that search',
        info: 'Showing {start} to {end} of {rows} items',
    },
});
