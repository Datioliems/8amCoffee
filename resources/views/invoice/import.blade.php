<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phiếu nhập {{ $import->ma_pnk }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a1a; margin: 0; background: #f3f3f3; }
        .sheet { max-width: 720px; margin: 16px auto; background: #fff; padding: 28px 32px 32px; box-shadow: 0 6px 30px rgba(0,0,0,.08); }
        .brand { font-size: 26px; font-weight: 800; color: #8B5A2B; letter-spacing: .5px; }
        h1 { font-size: 20px; margin: 14px 0 4px; }
        .muted { color: #666; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; font-size: 14px; }
        th, td { padding: 9px 8px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #faf7f2; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #8B5A2B; }
        td.r, th.r { text-align: right; }
        .totals { margin-top: 16px; margin-left: auto; width: 280px; font-size: 14px; }
        .grand { font-size: 18px; font-weight: 700; color: #8B5A2B; border-top: 2px solid #8B5A2B; margin-top: 6px; padding-top: 8px; display:flex; justify-content:space-between; }
        .meta { display: flex; flex-wrap: wrap; gap: 24px; margin-top: 10px; font-size: 13px; }
        .actions { max-width: 720px; margin: 12px auto; text-align: right; }
        .btn { background: #1a1a1a; color: #fff; border: 0; padding: 8px 18px; border-radius: 8px; cursor: pointer; font-size: 14px; }
        @media print { .actions { display: none; } body { background: #fff; } .sheet { box-shadow: none; margin: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button class="btn" onclick="window.print()">In phiếu nhập</button></div>
    <div class="sheet">
        <div class="brand">8AM Coffee</div>
        <h1>HÓA ĐƠN NHẬP NGUYÊN LIỆU</h1>
        <p class="muted">{{ $chiNhanh->ten_chi_nhanh ?? '8AM Coffee' }}
            @if($chiNhanh?->dia_chi) · {{ $chiNhanh->dia_chi }} @endif
        </p>

        <div class="meta">
            <div><strong>Số phiếu:</strong> {{ $import->ma_pnk }}</div>
            <div><strong>Ngày nhập:</strong> {{ \Carbon\Carbon::parse($import->ngay_nk)->format('d/m/Y') }}</div>
            <div><strong>Nhà cung cấp:</strong> {{ $import->nhaCungCap->ten_ncc ?? '—' }}</div>
            <div><strong>Người nhập:</strong> {{ $import->nhanVien->ten_nv ?? $import->ma_nv }}</div>
        </div>

        <table>
            <thead>
                <tr><th>Nguyên liệu</th><th class="r">Số lượng</th><th class="r">Đơn giá</th><th class="r">Thành tiền</th></tr>
            </thead>
            <tbody>
                @php $tong = 0; @endphp
                @foreach($import->chiTietNhapKhos as $ct)
                @php $thanh = $ct->so_luong * $ct->don_gia; $tong += $thanh; @endphp
                <tr>
                    <td>{{ $ct->nguyenLieu->ten_nl ?? $ct->ma_nl }}</td>
                    <td class="r">{{ rtrim(rtrim(number_format($ct->so_luong, 2, ',', '.'), '0'), ',') }} {{ $ct->nguyenLieu->don_vi ?? '' }}</td>
                    <td class="r">{{ number_format($ct->don_gia, 0, ',', '.') }}đ</td>
                    <td class="r">{{ number_format($thanh, 0, ',', '.') }}đ</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="grand"><span>Tổng giá trị</span><span>{{ number_format($import->tong_gia_tri ?: $tong, 0, ',', '.') }}đ</span></div>
        </div>

        @if($import->ghi_chu)<p class="muted" style="margin-top:18px"><strong>Ghi chú:</strong> {{ $import->ghi_chu }}</p>@endif
    </div>
</body>
</html>
