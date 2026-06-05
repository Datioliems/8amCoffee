// ─────────────────────────────────────────────────────────────
// Trang Phân tích AI — biểu đồ dự báo doanh thu (hồi quy tuyến tính).
// Lịch sử (nét liền) + dự báo (nét đứt) trên cùng một trục thời gian.
// ─────────────────────────────────────────────────────────────
import {
    Chart,
    LineController, LineElement, PointElement,
    CategoryScale, LinearScale,
    Tooltip, Legend, Filler,
} from 'chart.js';

Chart.register(
    LineController, LineElement, PointElement,
    CategoryScale, LinearScale,
    Tooltip, Legend, Filler,
);

const moneyVN = (v) => new Intl.NumberFormat('vi-VN').format(Math.round(v)) + 'đ';

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('forecast-data');
    const canvas = document.getElementById('chart-forecast');
    if (!el || !canvas) return;

    let d;
    try { d = JSON.parse(el.textContent); } catch { return; }

    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#522C25';

    const hist = d.history || [];
    const fc = d.forecast || [];

    const labels = [...hist.map((p) => p.label), ...fc.map((p) => p.label)];

    // Dataset lịch sử: giá trị thật, null cho phần tương lai.
    const histData = [...hist.map((p) => p.value), ...fc.map(() => null)];

    // Dataset dự báo: null cho quá khứ, nối liền từ điểm cuối lịch sử.
    const lastHist = hist.length ? hist[hist.length - 1].value : null;
    const fcData = [
        ...hist.map((_, i) => (i === hist.length - 1 ? lastHist : null)),
        ...fc.map((p) => p.value),
    ];

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Doanh thu thực tế',
                    data: histData,
                    borderColor: '#E82C2A',
                    backgroundColor: 'rgba(232,44,42,.10)',
                    fill: true, tension: .3, pointRadius: 2, spanGaps: false,
                },
                {
                    label: 'Dự báo (AI)',
                    data: fcData,
                    borderColor: '#2563eb',
                    borderDash: [6, 5],
                    backgroundColor: 'rgba(37,99,235,.08)',
                    fill: false, tension: .3, pointRadius: 3, spanGaps: true,
                },
            ],
        },
        options: {
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + moneyVN(c.parsed.y) } },
            },
            scales: { y: { beginAtZero: true, ticks: { callback: (v) => (v / 1000) + 'k' } } },
            maintainAspectRatio: false,
        },
    });
});
