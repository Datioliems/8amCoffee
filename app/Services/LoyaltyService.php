<?php

namespace App\Services;

use App\Models\GiaoDichDiem;
use App\Models\KhachHang;
use App\Models\LichSuQuetThe;
use App\Models\TheThanhVien;
use App\Support\Pii;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * LoyaltyService — nghiệp vụ thẻ thành viên RFID + tích điểm.
 *
 *  Cơ chế:
 *   1) PHÁT THẺ khi khách đạt mốc chi tiêu tích lũy (config: issue_threshold).
 *      Điểm KHỞI TẠO tính theo tổng chi tiêu trước đó của khách — tra qua
 *      blind index sdt_hash (SĐT đang mã hóa), không cần giải mã để tìm.
 *   2) TÍCH ĐIỂM mỗi hóa đơn: floor(số_tiền / earn_per_amount).
 *   3) ĐỔI ĐIỂM lấy giảm giá: 1 điểm = point_value đồng (có ngưỡng & trần).
 *
 *  Số dư denormalize ở THE_THANH_VIEN.diem_hien_tai; nguồn sự thật là sổ cái
 *  GIAO_DICH_DIEM (số dư = SUM(so_diem)).
 */
class LoyaltyService
{
    /** Tổng chi tiêu tích lũy của một khách (đ) — theo hóa đơn đã lập. */
    public function lifetimeSpend(string $maKh): float
    {
        return (float) DB::table('HOA_DON')->where('ma_kh', $maKh)->sum('tong_tien_sau_ck');
    }

    /** Tìm khách theo SĐT qua blind index (không giải mã cột sdt). */
    public function findCustomerByPhone(string $sdt): ?KhachHang
    {
        $hash = Pii::phoneHash($sdt);
        if (! $hash) {
            return null;
        }
        return KhachHang::where('sdt_hash', $hash)->first();
    }

    /** Khách đã đủ điều kiện được phát thẻ chưa? */
    public function isEligible(string $maKh): bool
    {
        return $this->lifetimeSpend($maKh) >= (int) config('loyalty.issue_threshold');
    }

    /** Quy đổi tiền chi tiêu → số điểm theo tỉ lệ cấu hình. */
    public function pointsForAmount(float $soTien): int
    {
        $per = max(1, (int) config('loyalty.earn_per_amount', 1000));
        return (int) floor($soTien / $per);
    }

    /**
     * PHÁT THẺ cho khách (định danh qua SĐT), tính điểm khởi tạo từ chi tiêu.
     *
     * @return array{ok:bool, message:string, card?:TheThanhVien, diem?:int, spend?:float, ten_kh?:?string}
     */
    public function issueCard(string $uid, string $sdt, ?string $maChiNhanh = null, ?string $maThietBi = null): array
    {
        $uid = strtoupper(trim($uid));
        $kh = $this->findCustomerByPhone($sdt);
        if (! $kh) {
            $this->logScan($uid, 'phat_the', 'that_bai', $maThietBi, $maChiNhanh, 'Khong tim thay khach theo SDT');
            return ['ok' => false, 'message' => 'Không tìm thấy khách hàng có số điện thoại này (khách cần có lịch sử giao dịch).'];
        }
        return $this->issueForCustomer($uid, $kh, $maChiNhanh, $maThietBi);
    }

    /** Phát thẻ theo mã khách (chọn từ danh sách đủ điều kiện). */
    public function issueCardByMaKh(string $uid, string $maKh, ?string $maChiNhanh = null, ?string $maThietBi = null): array
    {
        $uid = strtoupper(trim($uid));
        $kh = KhachHang::find($maKh);
        if (! $kh) {
            $this->logScan($uid, 'phat_the', 'that_bai', $maThietBi, $maChiNhanh, 'Khong tim thay khach theo ma_kh');
            return ['ok' => false, 'message' => 'Không tìm thấy khách hàng đã chọn.'];
        }
        return $this->issueForCustomer($uid, $kh, $maChiNhanh, $maThietBi);
    }

    /** Lõi phát thẻ cho một khách đã xác định. */
    private function issueForCustomer(string $uid, KhachHang $kh, ?string $maChiNhanh = null, ?string $maThietBi = null): array
    {
        $uid = strtoupper(trim($uid));
        if ($uid === '') {
            return ['ok' => false, 'message' => 'Thiếu UID thẻ (hãy quẹt thẻ).'];
        }

        $spend = $this->lifetimeSpend($kh->ma_kh);
        $threshold = (int) config('loyalty.issue_threshold');
        if ($spend < $threshold) {
            $this->logScan($uid, 'phat_the', 'that_bai', $maThietBi, $maChiNhanh, 'Chua dat moc chi tieu');
            return [
                'ok' => false,
                'message' => sprintf(
                    'Khách mới chi tiêu %sđ, chưa đạt mốc %sđ để được phát thẻ.',
                    number_format($spend, 0, ',', '.'),
                    number_format($threshold, 0, ',', '.')
                ),
                'spend' => $spend,
            ];
        }

        $diemKhoiTao = $this->pointsForAmount($spend);

        return DB::transaction(function () use ($uid, $kh, $spend, $diemKhoiTao, $maChiNhanh, $maThietBi) {
            // Thẻ đã tồn tại theo UID?
            $card = TheThanhVien::where('uid_rfid', $uid)->lockForUpdate()->first();

            if ($card && $card->ma_kh && $card->ma_kh !== $kh->ma_kh) {
                $this->logScan($uid, 'phat_the', 'that_bai', $maThietBi, $maChiNhanh, 'The da gan khach khac');
                return ['ok' => false, 'message' => 'Thẻ này đã được gán cho khách khác. Vui lòng dùng thẻ trắng khác.'];
            }

            if (! $card) {
                $card = new TheThanhVien();
                $card->ma_the   = $this->nextCardId();
                $card->uid_rfid = $uid;
            }

            $card->ma_kh              = $kh->ma_kh;
            $card->diem_hien_tai      = $diemKhoiTao;
            $card->tong_diem_tich_luy = $diemKhoiTao;
            $card->hang_the           = $this->tierForPoints($diemKhoiTao);
            $card->trang_thai         = 'hoat_dong';
            $card->ngay_phat          = now()->toDateString();
            $card->ma_chi_nhanh       = $maChiNhanh ?? $card->ma_chi_nhanh;
            $card->save();

            GiaoDichDiem::create([
                'ma_the'            => $card->ma_the,
                'ma_kh'             => $kh->ma_kh,
                'loai'              => 'khoi_tao',
                'so_diem'           => $diemKhoiTao,
                'so_diem_sau'       => $diemKhoiTao,
                'so_tien_lien_quan' => $spend,
                'ma_thiet_bi'       => $maThietBi,
                'mo_ta'             => 'Phat the + diem khoi tao tu tong chi tieu.',
                'thoi_gian'         => now(),
            ]);

            $this->logScan($uid, 'phat_the', 'thanh_cong', $maThietBi, $maChiNhanh, "Phat the {$card->ma_the}, +{$diemKhoiTao} diem", $card->ma_the);

            return [
                'ok'      => true,
                'message' => "Đã phát thẻ {$card->ma_the} cho khách, cộng {$diemKhoiTao} điểm khởi tạo.",
                'card'    => $card,
                'diem'    => $diemKhoiTao,
                'spend'   => $spend,
                'ten_kh'  => Pii::tryDecrypt($kh->getRawOriginal('ten_kh') ?? ''),
            ];
        });
    }

    /**
     * Danh sách khách ĐỦ ĐIỀU KIỆN phát thẻ: tổng chi tiêu ≥ mốc VÀ chưa có thẻ
     * đang hoạt động. Trả tên (đã giải mã) + SĐT che + tổng chi tiêu.
     *
     * @return array<int,array{ma_kh:string, ten_kh:string, sdt_che:string, tong:float}>
     */
    public function eligibleForCard(): array
    {
        $threshold = (int) config('loyalty.issue_threshold');

        $daCoThe = TheThanhVien::whereNotNull('ma_kh')
            ->where('trang_thai', 'hoat_dong')->pluck('ma_kh')->all();

        $rows = DB::table('HOA_DON')
            ->selectRaw('ma_kh, SUM(tong_tien_sau_ck) as tong')
            ->whereNotNull('ma_kh')
            ->groupBy('ma_kh')
            ->havingRaw('SUM(tong_tien_sau_ck) >= ?', [$threshold])
            ->get()
            ->keyBy('ma_kh');

        $maKhs = array_values(array_diff($rows->keys()->all(), $daCoThe));
        if (empty($maKhs)) {
            return [];
        }

        $out = [];
        foreach (KhachHang::whereIn('ma_kh', $maKhs)->get() as $kh) {
            $sdt = (string) $kh->sdt;                                    // cast tự giải mã
            $out[] = [
                'ma_kh'   => $kh->ma_kh,
                'ten_kh'  => (string) $kh->ten_kh,
                'sdt'     => preg_replace('/\D/', '', $sdt),             // số đầy đủ — dùng để TÌM (autocomplete)
                'sdt_che' => $this->maskPhone($sdt),                     // hiển thị (che bớt)
                'tong'    => (float) ($rows[$kh->ma_kh]->tong ?? 0),
            ];
        }
        usort($out, fn ($a, $b) => $b['tong'] <=> $a['tong']);
        return $out;
    }

    /** Che bớt SĐT để hiển thị: 0901234567 → 090***567. */
    private function maskPhone(string $sdt): string
    {
        $d = preg_replace('/\D/', '', $sdt);
        if (strlen((string) $d) < 7) {
            return $sdt;
        }
        return substr($d, 0, 3) . '***' . substr($d, -3);
    }

    /**
     * Danh sách thẻ ĐANG HOẠT ĐỘNG (cho autocomplete ở POS — tra theo tên/SĐT/UID).
     *
     * @return array<int,array{ma_the:string, uid:string, ten_kh:string, sdt:string, sdt_che:string}>
     */
    public function activeCardHolders(int $limit = 300): array
    {
        $out = [];
        $cards = TheThanhVien::with('khachHang')
            ->where('trang_thai', 'hoat_dong')
            ->orderByDesc('tao_luc')
            ->limit($limit)
            ->get();

        foreach ($cards as $c) {
            $sdt = (string) ($c->khachHang->sdt ?? '');   // cast tự giải mã
            $out[] = [
                'ma_the'  => $c->ma_the,
                'uid'     => $c->uid_rfid,
                'ten_kh'  => (string) ($c->khachHang->ten_kh ?? $c->ma_kh ?? $c->ma_the),
                'sdt'     => preg_replace('/\D/', '', $sdt),  // để TÌM
                'sdt_che' => $this->maskPhone($sdt),          // để hiển thị
            ];
        }
        return $out;
    }

    /**
     * TÍCH ĐIỂM cho một hóa đơn vừa thanh toán (gọi từ PaymentService).
     * Tìm thẻ đang hoạt động của khách; nếu không có thẻ → bỏ qua (trả null).
     */
    public function earnForCustomer(?string $maKh, float $soTien, ?string $maOrder = null, ?string $maHoaDon = null, ?string $maThietBi = null): ?int
    {
        if (! $maKh) {
            return null;
        }
        $card = TheThanhVien::where('ma_kh', $maKh)->where('trang_thai', 'hoat_dong')->first();
        if (! $card) {
            return null;
        }
        return $this->earn($card, $soTien, $maOrder, $maHoaDon, $maThietBi);
    }

    /** TÍCH ĐIỂM trực tiếp lên một thẻ (đã nhân HỆ SỐ theo hạng). Trả về số điểm cộng. */
    public function earn(TheThanhVien $card, float $soTien, ?string $maOrder = null, ?string $maHoaDon = null, ?string $maThietBi = null): int
    {
        $base = $this->pointsForAmount($soTien);
        if ($base <= 0) {
            return 0;
        }
        $heSo = $this->earnMultiplier($card->hang_the);
        $diem = (int) round($base * $heSo);
        if ($diem <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($card, $diem, $heSo, $soTien, $maOrder, $maHoaDon, $maThietBi) {
            $card = TheThanhVien::whereKey($card->ma_the)->lockForUpdate()->first();
            $card->diem_hien_tai      += $diem;
            $card->tong_diem_tich_luy += $diem;
            $card->hang_the            = $this->tierForPoints($card->tong_diem_tich_luy);
            $card->save();

            GiaoDichDiem::create([
                'ma_the'            => $card->ma_the,
                'ma_kh'             => $card->ma_kh,
                'loai'              => 'tich_diem',
                'so_diem'           => $diem,
                'so_diem_sau'       => $card->diem_hien_tai,
                'so_tien_lien_quan' => $soTien,
                'ma_order'          => $maOrder,
                'ma_hoa_don'        => $maHoaDon,
                'ma_thiet_bi'       => $maThietBi,
                'mo_ta'             => $heSo > 1 ? ("Tich diem x{$heSo} theo hang.") : 'Tich diem theo hoa don.',
                'thoi_gian'         => now(),
            ]);

            return $diem;
        });
    }

    /**
     * ĐỔI ĐIỂM lấy giảm giá. Trả về SỐ TIỀN giảm (đ).
     * $tongHoaDon (nếu truyền) dùng để áp trần max_redeem_pct.
     *
     * @throws ValidationException khi không hợp lệ (đủ điểm/ngưỡng…)
     */
    public function redeem(TheThanhVien $card, int $soDiem, ?float $tongHoaDon = null, ?string $maOrder = null, ?string $maThietBi = null): int
    {
        $quote = $this->quoteRedemption($card, $soDiem, $tongHoaDon);
        return $this->applyRedemption($card, $quote['so_diem'], $quote['tien_giam'], $maOrder, null, $maThietBi);
    }

    /**
     * Kiểm tra & tính toán đổi điểm (KHÔNG ghi DB). Tự áp trần max_redeem_pct
     * nếu biết tổng hóa đơn.
     *
     * @return array{so_diem:int, tien_giam:int}
     * @throws ValidationException
     */
    public function quoteRedemption(TheThanhVien $card, int $soDiem, ?float $tongHoaDon = null): array
    {
        $min = (int) config('loyalty.min_redeem', 100);
        $value = (int) config('loyalty.point_value', 50);

        if (! $card->dangHoatDong()) {
            throw ValidationException::withMessages(['the' => 'Thẻ không ở trạng thái hoạt động.']);
        }
        if ($soDiem < $min) {
            throw ValidationException::withMessages(['so_diem' => "Đổi tối thiểu {$min} điểm mỗi lần."]);
        }
        if ($soDiem > $card->diem_hien_tai) {
            throw ValidationException::withMessages(['so_diem' => 'Số điểm vượt quá số dư của thẻ.']);
        }

        // Áp trần % giá trị hóa đơn (nếu biết tổng hóa đơn).
        if ($tongHoaDon !== null) {
            $maxPct = (int) config('loyalty.max_redeem_pct', 50);
            $tienGiamToiDa = (int) floor($tongHoaDon * $maxPct / 100);
            $diemToiDa = $value > 0 ? (int) floor($tienGiamToiDa / $value) : 0;
            if ($soDiem > $diemToiDa) {
                $soDiem = $diemToiDa;
            }
            if ($soDiem < $min) {
                throw ValidationException::withMessages(['so_diem' => "Hóa đơn quá nhỏ: chỉ được giảm tối đa {$maxPct}% giá trị."]);
            }
        }

        return ['so_diem' => $soDiem, 'tien_giam' => $soDiem * $value];
    }

    /** Ghi sổ đổi điểm + trừ số dư (số liệu đã qua quoteRedemption). Trả tiền giảm. */
    public function applyRedemption(TheThanhVien $card, int $soDiem, int $tienGiam, ?string $maOrder = null, ?string $maHoaDon = null, ?string $maThietBi = null): int
    {
        return DB::transaction(function () use ($card, $soDiem, $tienGiam, $maOrder, $maHoaDon, $maThietBi) {
            $card = TheThanhVien::whereKey($card->ma_the)->lockForUpdate()->first();
            $card->diem_hien_tai -= $soDiem;
            $card->save();

            GiaoDichDiem::create([
                'ma_the'            => $card->ma_the,
                'ma_kh'             => $card->ma_kh,
                'loai'              => 'doi_diem',
                'so_diem'           => -$soDiem,
                'so_diem_sau'       => $card->diem_hien_tai,
                'so_tien_lien_quan' => $tienGiam,
                'ma_order'          => $maOrder,
                'ma_hoa_don'        => $maHoaDon,
                'ma_thiet_bi'       => $maThietBi,
                'mo_ta'             => "Doi {$soDiem} diem lay {$tienGiam}d giam gia.",
                'thoi_gian'         => now(),
            ]);

            return $tienGiam;
        });
    }

    /** Điều chỉnh điểm thủ công (admin). $soDiem CÓ DẤU (+ cộng / - trừ). */
    public function adjust(TheThanhVien $card, int $soDiem, string $lyDo = ''): GiaoDichDiem
    {
        return DB::transaction(function () use ($card, $soDiem, $lyDo) {
            $card = TheThanhVien::whereKey($card->ma_the)->lockForUpdate()->first();
            $card->diem_hien_tai = max(0, $card->diem_hien_tai + $soDiem);
            if ($soDiem > 0) {
                $card->tong_diem_tich_luy += $soDiem;
            }
            $card->hang_the = $this->tierForPoints($card->tong_diem_tich_luy);
            $card->save();

            return GiaoDichDiem::create([
                'ma_the'      => $card->ma_the,
                'ma_kh'       => $card->ma_kh,
                'loai'        => 'dieu_chinh',
                'so_diem'     => $soDiem,
                'so_diem_sau' => $card->diem_hien_tai,
                'mo_ta'       => $lyDo !== '' ? $lyDo : 'Dieu chinh thu cong',
                'thoi_gian'   => now(),
            ]);
        });
    }

    /** Số dư đối soát từ sổ cái (kiểm tra khớp với diem_hien_tai). */
    public function ledgerBalance(string $maThe): int
    {
        return (int) GiaoDichDiem::where('ma_the', $maThe)->sum('so_diem');
    }

    /**
     * Nguồn cấu hình HẠNG: ưu tiên bảng CAU_HINH_HANG (sửa qua web),
     * fallback config('loyalty.tiers') nếu bảng trống/chưa migrate.
     *
     * @return array<string,array{nhan:string,nguong:int,he_so:float,giam_loai:string,giam_gia_tri:float}>
     */
    public function tiers(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $rows = \App\Models\CauHinhHang::orderBy('nguong')->get();
            if ($rows->isNotEmpty()) {
                return $cache = $rows->mapWithKeys(fn ($r) => [$r->ma_hang => [
                    'nhan'         => $r->nhan,
                    'nguong'       => (int) $r->nguong,
                    'he_so'        => (float) $r->he_so,
                    'giam_loai'    => $r->giam_loai,
                    'giam_gia_tri' => (float) $r->giam_gia_tri,
                ]])->all();
            }
        } catch (\Throwable $e) {
            // bảng chưa tồn tại → dùng config
        }
        return $cache = (array) config('loyalty.tiers', []);
    }

    /** Xếp hạng thẻ theo tổng điểm tích lũy (hạng có ngưỡng cao nhất mà ≤ tổng điểm). */
    public function tierForPoints(int $tongDiem): string
    {
        $best = 'thuong';
        $bestNguong = -1;
        foreach ($this->tiers() as $ma => $cfg) {
            $nguong = (int) ($cfg['nguong'] ?? 0);
            if ($tongDiem >= $nguong && $nguong >= $bestNguong) {
                $best = $ma;
                $bestNguong = $nguong;
            }
        }
        return $best;
    }

    /** Cấu hình một hạng (fallback hạng 'thuong'). */
    public function tierConfig(string $tier): array
    {
        $tiers = $this->tiers();
        return $tiers[$tier]
            ?? $tiers['thuong']
            ?? ['nhan' => 'Thường', 'nguong' => 0, 'he_so' => 1.0, 'giam_loai' => 'phan_tram', 'giam_gia_tri' => 0];
    }

    /** Nhãn hiển thị của hạng. */
    public function tierLabel(string $tier): string
    {
        return (string) ($this->tierConfig($tier)['nhan'] ?? $tier);
    }

    /** Hệ số tích điểm của hạng (vd 1.5). */
    public function earnMultiplier(string $tier): float
    {
        return (float) ($this->tierConfig($tier)['he_so'] ?? 1.0);
    }

    /** Ưu đãi giảm giá của hạng: ['loai' => 'phan_tram'|'tien', 'gia_tri' => number]. */
    public function tierDiscount(string $tier): array
    {
        $c = $this->tierConfig($tier);
        return [
            'loai'    => (string) ($c['giam_loai'] ?? 'phan_tram'),
            'gia_tri' => (float) ($c['giam_gia_tri'] ?? 0),
        ];
    }

    /** Sinh mã thẻ kế tiếp dạng TV###### (an toàn trùng khóa). */
    public function nextCardId(): string
    {
        $max = (int) DB::table('THE_THANH_VIEN')
            ->selectRaw('MAX(CAST(SUBSTRING(ma_the, 3) AS UNSIGNED)) AS m')
            ->value('m');
        return 'TV' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    private function logScan(string $uid, string $hanhDong, string $ketQua, ?string $maThietBi, ?string $maChiNhanh, ?string $chiTiet = null, ?string $maThe = null): void
    {
        LichSuQuetThe::ghi([
            'uid_rfid'     => $uid,
            'ma_the'       => $maThe,
            'ma_thiet_bi'  => $maThietBi,
            'ma_chi_nhanh' => $maChiNhanh,
            'hanh_dong'    => $hanhDong,
            'ket_qua'      => $ketQua,
            'chi_tiet'     => $chiTiet,
        ]);
    }
}
