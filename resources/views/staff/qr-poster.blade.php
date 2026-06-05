<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Bàn {{ $ban->so_ban }} - 8AM Coffee</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Chivo:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #EDE7E3; min-height: 100vh;
               display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; color: #2A1A16; }
        .toolbar { margin-bottom: 16px; display: flex; gap: 10px; }
        .btn { border: 0; cursor: pointer; border-radius: 999px; padding: 10px 22px; font-size: 14px; font-weight: 700; }
        .btn-print { background: #1A1A1A; color: #fff; }
        .btn-ghost { background: #fff; color: #522C25; box-shadow: inset 0 0 0 1px rgba(82,44,37,.15); }

        .poster { width: 420px; max-width: 100%; background: #fff; border-radius: 34px; overflow: hidden;
                  box-shadow: 0 30px 70px rgba(82,44,37,.22); }
        .top { background: linear-gradient(160deg, #E82C2A 0%, #BB0011 100%); color: #fff; padding: 30px 28px 26px; text-align: center; position: relative; }
        .top::after { content: ''; position: absolute; left: 0; right: 0; bottom: -1px; height: 26px; background: #fff;
                      border-top-left-radius: 26px; border-top-right-radius: 26px; }
        .logo { width: 76px; height: 76px; border-radius: 999px; background: #fff; color: #E82C2A; margin: 0 auto 12px;
                display: flex; align-items: center; justify-content: center; font-family: 'Chivo'; font-weight: 800; font-size: 22px;
                box-shadow: 0 8px 20px rgba(0,0,0,.18); }
        .brand { font-family: 'Chivo'; font-weight: 800; font-size: 24px; letter-spacing: .5px; }
        .brand small { display: block; font-family: 'Inter'; font-weight: 500; font-size: 11px; letter-spacing: .28em;
                       text-transform: uppercase; opacity: .85; margin-top: 4px; }

        .body { padding: 8px 28px 28px; text-align: center; }
        .table-no { font-family: 'Chivo'; font-weight: 800; font-size: 46px; line-height: 1; color: #1A1A1A; margin-top: 6px; }
        .floor { font-size: 13px; color: #8B5A2B; font-weight: 600; margin-top: 6px; }

        .qr-wrap { margin: 20px auto 8px; width: 252px; height: 252px; padding: 16px; background: #fff; border-radius: 24px;
                   box-shadow: 0 0 0 2px #F1E7E2, 0 16px 30px rgba(82,44,37,.12); display: flex; align-items: center; justify-content: center; }
        .qr-wrap svg { width: 100% !important; height: 100% !important; display: block; }

        .cta { font-family: 'Chivo'; font-weight: 700; font-size: 22px; color: #E82C2A; margin-top: 12px; }
        .cta span { color: #1A1A1A; }
        .steps { display: flex; justify-content: center; gap: 14px; margin: 16px 0 4px; }
        .step { flex: 1; max-width: 110px; }
        .step .n { width: 30px; height: 30px; border-radius: 999px; background: #FFF1ED; color: #E82C2A; font-weight: 800;
                   display: flex; align-items: center; justify-content: center; margin: 0 auto 6px; }
        .step p { font-size: 11px; color: #522C25; line-height: 1.4; }

        .foot { margin-top: 18px; border-top: 1px dashed #E2D5CF; padding-top: 14px; }
        .foot .name { font-weight: 700; font-size: 14px; }
        .foot .addr { font-size: 12px; color: #8B5A2B; margin-top: 3px; }

        @media print {
            .toolbar { display: none; }
            body { background: #fff; padding: 0; }
            .poster { box-shadow: none; border: 1px solid #eee; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-print" onclick="window.print()">In poster</button>
        <a class="btn btn-ghost" href="{{ url()->previous() }}">← Quay lại</a>
    </div>

    <div class="poster">
        <div class="top">
            <div class="logo">:am</div>
            <div class="brand">8AM Coffee<small>Quét · Gọi món · Thưởng thức</small></div>
        </div>

        <div class="body">
            <p style="font-size:12px;letter-spacing:.28em;text-transform:uppercase;color:#522C25;opacity:.6;margin-top:6px">Bàn của bạn</p>
            <div class="table-no">BÀN {{ str_pad($ban->so_ban, 2, '0', STR_PAD_LEFT) }}</div>
            @if($ban->vi_tri)<div class="floor">{{ $ban->vi_tri }}</div>@endif

            <div class="qr-wrap">
                {!! QrCode::format('svg')->size(260)->margin(0)->errorCorrection('M')->generate($url) !!}
            </div>

            <div class="cta">Quét mã <span>để gọi món</span></div>

            <div class="steps">
                <div class="step"><div class="n">1</div><p>Mở camera điện thoại</p></div>
                <div class="step"><div class="n">2</div><p>Quét mã QR phía trên</p></div>
                <div class="step"><div class="n">3</div><p>Chọn món & gọi ngay</p></div>
            </div>

            <div class="foot">
                <div class="name">{{ $ban->chiNhanh->ten_chi_nhanh ?? '8AM Coffee' }}</div>
                @if($ban->chiNhanh?->dia_chi)<div class="addr">{{ $ban->chiNhanh->dia_chi }}</div>@endif
            </div>
        </div>
    </div>
</body>
</html>
