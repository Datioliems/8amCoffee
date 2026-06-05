<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn {{ $hoaDon->ma_hoa_don }}</title>
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
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .grand { font-size: 18px; font-weight: 700; color: #8B5A2B; border-top: 2px solid #8B5A2B; margin-top: 6px; padding-top: 8px; }
        .meta { display: flex; flex-wrap: wrap; gap: 24px; margin-top: 10px; font-size: 13px; }
        .actions { max-width: 720px; margin: 12px auto; text-align: right; }
        .btn { background: #1a1a1a; color: #fff; border: 0; padding: 8px 18px; border-radius: 8px; cursor: pointer; font-size: 14px; }
        @media print { .actions { display: none; } body { background: #fff; } .sheet { box-shadow: none; margin: 0; } }
        @php
            $method = [
                'tien_mat'=>'Tiền mặt','chuyen_khoan'=>'Chuyển khoản','the'=>'Thẻ',
                'vi_dien_tu'=>'Ví điện tử','momo'=>'MoMo','vnpay'=>'VNPay',
            ][$hoaDon->phuong_thuc_tt] ?? $hoaDon->phuong_thuc_tt;
        @endphp
    </style>
</head>
<body>
    <div class="actions"><button class="btn" onclick="window.print()">In hóa đơn</button></div>
    <div class="sheet">
        <div class="brand">8AM Coffee</div>
        <h1>HÓA ĐƠN BÁN HÀNG</h1>
        <p class="muted">{{ $hoaDon->order->chiNhanh->ten_chi_nhanh ?? '8AM Coffee' }}
            @if($hoaDon->order->chiNhanh?->dia_chi) · {{ $hoaDon->order->chiNhanh->dia_chi }} @endif
        </p>

        <div class="meta">
            <div><strong>Số HĐ:</strong> {{ $hoaDon->ma_hoa_don }}</div>
            <div><strong>Mã đơn:</strong> {{ $hoaDon->ma_order }}</div>
            <div><strong>Thời gian:</strong> {{ \Carbon\Carbon::parse($hoaDon->thoi_gian_lap)->format('d/m/Y H:i') }}</div>
            <div><strong>{{ $hoaDon->order->ban ? 'Bàn '.$hoaDon->order->ban->so_ban : 'Mang về' }}</strong></div>
            <div><strong>Khách:</strong> {{ $hoaDon->khachHang->ten_kh ?? $hoaDon->order->ten_khach ?? 'Khách lẻ' }}</div>
        </div>

        <table>
            <thead>
                <tr><th>Món</th><th class="r">SL</th><th class="r">Đơn giá</th><th class="r">Thành tiền</th></tr>
            </thead>
            <tbody>
                @foreach($hoaDon->order->chiTietOrders as $item)
                @php $donGia = $item->don_gia_tai_thoi_diem + $item->options->sum('gia_them'); @endphp
                <tr>
                    <td>
                        {{ $item->mon->ten_mon ?? $item->ma_mon }}
                        @if($item->options->count())<br><span class="muted">{{ $item->options->pluck('ten_lua_chon')->join(', ') }}</span>@endif
                    </td>
                    <td class="r">{{ $item->so_luong }}</td>
                    <td class="r">{{ number_format($donGia, 0, ',', '.') }}đ</td>
                    <td class="r">{{ number_format($donGia * $item->so_luong, 0, ',', '.') }}đ</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><span>Tạm tính</span><span>{{ number_format($hoaDon->tong_tien_truoc_ck, 0, ',', '.') }}đ</span></div>
            <div><span>Chiết khấu</span><span>{{ (float) $hoaDon->chiet_khau }}%</span></div>
            <div class="grand"><span>Tổng thu</span><span>{{ number_format($hoaDon->tong_tien_sau_ck, 0, ',', '.') }}đ</span></div>
            <div style="margin-top:8px"><span>Thanh toán</span><span>{{ $method }}</span></div>
        </div>

        <p class="muted" style="margin-top:28px;text-align:center">Cảm ơn quý khách đã ghé 8AM Coffee. Hẹn gặp lại!</p>
    </div>
</body>
</html>
