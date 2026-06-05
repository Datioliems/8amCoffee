// ─────────────────────────────────────────────────────────────
// Dashboard charts — Chart.js (bundle nội bộ, không cần CDN)
// Đọc dữ liệu từ thẻ <script type="application/json" id="dashboard-data">
// ─────────────────────────────────────────────────────────────
import {
    Chart,
    LineController, LineElement, PointElement,
    BarController, BarElement,
    DoughnutController, PieController, ArcElement,
    CategoryScale, LinearScale,
    Tooltip, Legend, Filler,
} from 'chart.js';

Chart.register(
    LineController, LineElement, PointElement,
    BarController, BarElement,
    DoughnutController, PieController, ArcElement,
    CategoryScale, LinearScale,
    Tooltip, Legend, Filler,
);

const PALETTE = ['#E82C2A', '#8B5A2B', '#52613B', '#CADCAC', '#80534a', '#f59e0b', '#2563eb', '#9ca3af'];
const moneyVN = (v) => new Intl.NumberFormat('vi-VN').format(Math.round(v)) + 'đ';

function readData() {
    const el = document.getElementById('dashboard-data');
    if (!el) return null;
    try { return JSON.parse(el.textContent); } catch { return null; }
}

function make(id, config) {
    const el = document.getElementById(id);
    if (!el) return;
    new Chart(el, config);
}

document.addEventListener('DOMContentLoaded', () => {
    const d = readData();
    if (!d) return;
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#522C25';

    // 1) Doanh thu 7 ngày — đường
    make('chart-revenue7', {
        type: 'line',
        data: {
            labels: d.revenue7.labels,
            datasets: [{
                label: 'Doanh thu', data: d.revenue7.values,
                borderColor: '#E82C2A', backgroundColor: 'rgba(232,44,42,.12)',
                fill: true, tension: .35, pointRadius: 3,
            }],
        },
        options: {
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => moneyVN(c.parsed.y) } } },
            scales: { y: { ticks: { callback: (v) => (v / 1000) + 'k' } } },
            maintainAspectRatio: false,
        },
    });

    // 2) Phương thức thanh toán — doughnut
    make('chart-payment', {
        type: 'doughnut',
        data: { labels: d.payment.labels, datasets: [{ data: d.payment.values, backgroundColor: PALETTE }] },
        options: {
            plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (c) => c.label + ': ' + moneyVN(c.parsed) } } },
            maintainAspectRatio: false,
        },
    });

    // 3) Đơn theo trạng thái (hôm nay) — doughnut
    make('chart-status', {
        type: 'doughnut',
        data: { labels: d.status.labels, datasets: [{ data: d.status.values, backgroundColor: PALETTE }] },
        options: { plugins: { legend: { position: 'bottom' } }, maintainAspectRatio: false },
    });

    // 4) Top món bán chạy — cột ngang
    make('chart-topmons', {
        type: 'bar',
        data: { labels: d.topMons.labels, datasets: [{ label: 'Số lượng', data: d.topMons.values, backgroundColor: '#52613B', borderRadius: 6 }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, maintainAspectRatio: false },
    });

    // 5) Doanh thu theo giờ (hôm nay) — cột
    make('chart-hourly', {
        type: 'bar',
        data: { labels: d.hourly.labels, datasets: [{ label: 'Doanh thu', data: d.hourly.values, backgroundColor: '#8B5A2B', borderRadius: 5 }] },
        options: {
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => moneyVN(c.parsed.y) } } },
            scales: { y: { ticks: { callback: (v) => (v / 1000) + 'k' } } },
            maintainAspectRatio: false,
        },
    });

    // 6) Tại chỗ vs Mang về — pie
    make('chart-channel', {
        type: 'pie',
        data: { labels: d.channel.labels, datasets: [{ data: d.channel.values, backgroundColor: ['#E82C2A', '#8B5A2B'] }] },
        options: { plugins: { legend: { position: 'bottom' } }, maintainAspectRatio: false },
    });

    // 7) Doanh thu theo danh mục — cột
    make('chart-category', {
        type: 'bar',
        data: { labels: d.category.labels, datasets: [{ label: 'Doanh thu', data: d.category.values, backgroundColor: '#E82C2A', borderRadius: 6 }] },
        options: {
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => moneyVN(c.parsed.y) } } },
            scales: { y: { ticks: { callback: (v) => (v / 1000) + 'k' } } },
            maintainAspectRatio: false,
        },
    });
});
