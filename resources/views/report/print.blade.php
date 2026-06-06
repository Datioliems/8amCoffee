<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo doanh thu {{ $tuNgay }} – {{ $denNgay }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a1a; margin: 0; padding: 28px 32px; background: #fff; }
        .head { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid #1a1a1a; padding-bottom: 14px; }
        .head img { width: 54px; height: 54px; border-radius: 10px; object-fit: cover; }
        .head h1 { margin: 0; font-size: 20px; }
        .head .sub { margin: 2px 0 0; font-size: 12px; color: #666; letter-spacing: .04em; text-transform: uppercase; }
        .meta { margin: 16px 0 20px; font-size: 13px; color: #333; line-height: 1.7; }
        .meta b { color: #000; }
        .cards { display: flex; gap: 14px; margin-bottom: 22px; }
        .card { flex: 1; border: 1px solid #e5e5e5; border-radius: 10px; padding: 14px 16px; }
        .card .k { font-size: 11px; color: #888; margin: 0 0 6px; text-transform: uppercase; letter-spacing: .05em; }
        .card .v { font-size: 22px; font-weight: 700; margin: 0; }
        .card.rev .v { color: #b45309; }
        .card.cnt .v { color: #1d4ed8; }
        h2 { font-size: 14px; margin: 22px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; font-size: 12.5px; margin-bottom: 8px; }
        th, td { padding: 7px 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f6f6f6; font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: .03em; }
        td.r, th.r { text-align: right; }
        tfoot td { font-weight: 700; border-top: 2px solid #1a1a1a; }
        .grid2 { display: flex; gap: 22px; }
        .grid2 > div { flex: 1; }
        .empty { color: #999; text-align: center; padding: 18px; }
        .toolbar { margin-bottom: 18px; }
        .btn { display: inline-block; background: #1a1a1a; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; font-size: 13px; cursor: pointer; }
        .foot { margin-top: 28px; font-size: 11px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .grid2 { gap: 16px; }
            @page { margin: 14mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button class="btn" onclick="window.print()">🖨️ In / Lưu PDF</button>
    </div>

    <div class="head">
        <img src="{{ \App\Support\Cdn::url('images/logo8am.jpg') }}" alt="8AM Coffee">
        <div>
            <h1>8AM Coffee — Báo cáo doanh thu</h1>
            <p class="sub">Hệ thống quản trị 8am.coffee</p>
        </div>
    </div>

    <div class="meta">
        <div><b>Kỳ báo cáo:</b> {{ $kyLabel }} — {{ \Carbon\Carbon::parse($tuNgay)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($denNgay)->format('d/m/Y') }}</div>
        <div><b>Phạm vi:</b> {{ $branchLabel }}</div>
        <div><b>Xuất lúc:</b> {{ $inLuc }}</div>
    </div>

    <div class="cards">
        <div class="card rev">
            <p class="k">Tổng doanh thu</p>
            <p class="v">{{ number_format($tongKet['tong_doanh_thu'], 0, ',', '.') }}đ</p>
        </div>
        <div class="card cnt">
            <p class="k">Số hóa đơn</p>
            <p class="v">{{ $tongKet['tong_hoa_don'] }}</p>
        </div>
    </div>

    @if($doanhThuTheoChiNhanh->isNotEmpty())
    <h2>Doanh thu theo chi nhánh</h2>
    <table>
        <thead><tr><th>Chi nhánh</th><th class="r">Số HD</th><th class="r">Doanh thu</th></tr></thead>
        <tbody>
            @foreach($doanhThuTheoChiNhanh as $row)
            <tr>
                <td>{{ $row->ten_chi_nhanh ?? $row->ma_chi_nhanh }}</td>
                <td class="r">{{ $row->so_hoa_don }}</td>
                <td class="r">{{ number_format($row->tong_doanh_thu, 0, ',', '.') }}đ</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="grid2">
        <div>
            <h2>Doanh thu theo ngày</h2>
            <table>
                <thead><tr><th>Ngày</th><th class="r">Số HD</th><th class="r">Doanh thu</th></tr></thead>
                <tbody>
                    @forelse($doanhThuTheoNgay as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->ngay)->format('d/m/Y') }}</td>
                        <td class="r">{{ $row->so_hoa_don }}</td>
                        <td class="r">{{ number_format($row->tong_doanh_thu, 0, ',', '.') }}đ</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty">Không có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
                @if($doanhThuTheoNgay->isNotEmpty())
                <tfoot>
                    <tr>
                        <td>Tổng</td>
                        <td class="r">{{ $tongKet['tong_hoa_don'] }}</td>
                        <td class="r">{{ number_format($tongKet['tong_doanh_thu'], 0, ',', '.') }}đ</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <div>
            <h2>Top 5 món bán chạy</h2>
            <table>
                <thead><tr><th>Hạng</th><th>Tên món</th><th class="r">SL</th></tr></thead>
                <tbody>
                    @forelse($topMon as $idx => $mon)
                    <tr>
                        <td>#{{ $idx + 1 }}</td>
                        <td>{{ $mon->ten_mon }}</td>
                        <td class="r">{{ $mon->tong_sl }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty">Không có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="foot">8am.coffee · Báo cáo tạo tự động — số liệu mang tính nội bộ.</div>

    <script>
        // Tự mở hộp thoại in khi tải xong (cho phép trình duyệt vẽ xong trước).
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });
    </script>
</body>
</html>
