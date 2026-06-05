<?php

namespace App\Http\Controllers;

use App\Models\Ban;
use App\Models\DanhMuc;
use App\Models\DinhMuc;
use App\Models\Mon;
use App\Models\MonOption;
use App\Models\NguyenLieu;
use App\Models\Topping;
use App\Services\MenuAvailabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function __construct(private MenuAvailabilityService $availabilityService) {}

    public function customerMenu(string $maBan)
    {
        $ban = Ban::findOrFail($maBan);
        $maChiNhanh = $ban->ma_chi_nhanh;

        $danhMucs = DanhMuc::with(['mons' => function ($query) {
            $query->with([
                'danhMuc',
                'dinhMucs.nguyenLieu.tonKhos',
                'options' => fn($query) => $query
                    ->whereIn('loai_option', ['temperature', 'sweetness', 'topping'])
                    ->where('trang_thai', 'active')
                    ->orderBy('loai_option')
                    ->orderBy('thu_tu'),
            ])->where('trang_thai', 'active')->orderBy('ten_mon');
        }])->orderBy('ten_danh_muc')->get();

        $danhMucs->each(fn($danhMuc) => $this->availabilityService->annotate($danhMuc->mons, $maChiNhanh));

        $maOrder = $this->resolveMaOrder($maBan);
        $model3d = DB::table('CHI_NHANH')->where('ma_chi_nhanh', $ban->ma_chi_nhanh)->value('model_3d') ?: 'cafe_opt.glb';

        return view('customer.menu', compact('ban', 'danhMucs', 'maOrder', 'model3d'));
    }

    /** Lấy ma_order: ưu tiên query, nếu thiếu thì tìm đơn đang chọn (dang_chon) của bàn. */
    private function resolveMaOrder(string $maBan): ?string
    {
        return request('ma_order') ?: DB::table('ORDERS')
            ->where('ma_ban', $maBan)
            ->where('trang_thai', 'dang_chon')
            ->orderByDesc('ma_order')
            ->value('ma_order');
    }

    /** (Giữ lại) dữ liệu dùng chung cho trang menu khách */
    private function buildMenuData(string $maBan): array
    {
        $ban = Ban::findOrFail($maBan);
        $maChiNhanh = $ban->ma_chi_nhanh;

        $danhMucs = DanhMuc::with(['mons' => function ($query) {
            $query->with([
                'danhMuc',
                'dinhMucs.nguyenLieu.tonKhos',
                'options' => fn($query) => $query
                    ->whereIn('loai_option', ['temperature', 'sweetness', 'topping'])
                    ->where('trang_thai', 'active')
                    ->orderBy('loai_option')
                    ->orderBy('thu_tu'),
            ])->where('trang_thai', 'active')->orderBy('ten_mon');
        }])->orderBy('ten_danh_muc')->get();

        $danhMucs->each(fn($danhMuc) => $this->availabilityService->annotate($danhMuc->mons, $maChiNhanh));

        return [
            'ban' => $ban,
            'danhMucs' => $danhMucs,
            'maOrder' => $this->resolveMaOrder($maBan),
        ];
    }

    public function index()
    {
        $query = Mon::with(['danhMuc', 'dinhMucs.nguyenLieu.tonKhos']);

        if (request('category')) {
            $query->where('ma_danh_muc', request('category'));
        }
        if (request('q')) {
            $keyword = trim((string) request('q'));
            $query->where(function ($query) use ($keyword) {
                $query->where('ten_mon', 'like', "%{$keyword}%")
                    ->orWhere('ma_mon', 'like', "%{$keyword}%")
                    ->orWhere('mo_ta', 'like', "%{$keyword}%");
            });
        }

        $mons = $query->orderBy('ma_danh_muc')
            ->orderBy('ten_mon')
            ->paginate(20)
            ->withQueryString();

        $this->availabilityService->annotate($mons->getCollection(), session('ma_chi_nhanh'));

        $stockWarningMons = Mon::with(['danhMuc', 'dinhMucs.nguyenLieu.tonKhos'])
            ->where('trang_thai', 'active')
            ->orderBy('ten_mon')
            ->get();
        $this->availabilityService->annotate($stockWarningMons, session('ma_chi_nhanh'));
        $stockWarnings = $stockWarningMons
            ->filter(fn($mon) => (bool) ($mon->het_hang_theo_kho ?? false))
            ->values();

        $danhMucs = DanhMuc::orderBy('ten_danh_muc')->get();

        return view('staff.menu-list', compact('mons', 'danhMucs', 'stockWarnings'));
    }

    public function outOfStock()
    {
        $mons = Mon::with(['danhMuc', 'dinhMucs.nguyenLieu.tonKhos'])
            ->where('trang_thai', 'active')
            ->orderBy('ten_mon')
            ->get();

        $this->availabilityService->annotate($mons, session('ma_chi_nhanh'));

        $mons = $mons
            ->filter(fn($mon) => (bool) ($mon->het_hang_theo_kho ?? false))
            ->values();

        return view('staff.menu-out-of-stock', compact('mons'));
    }

    public function create()
    {
        $danhMucs        = DanhMuc::orderBy('ten_danh_muc')->get();
        $nguyenLieus     = $this->getNguyenLieusWithStock();
        $toppings        = Topping::where('trang_thai', 'active')->orderBy('ten_topping')->get();
        $temperatureOptions = $this->temperatureOptions();
        $sweetnessOptions   = $this->sweetnessOptions();
        $selectedSweetness  = old('sweetness_options', ['Ngọt nhiều', 'Ngọt vừa', 'Ít ngọt', 'Không ngọt']);
        $dinhMucRows = old('dinh_muc', [['ma_nl' => '', 'so_luong_dung' => '', 'mo_ta' => '']]);

        return view('staff.menu-form', compact(
            'danhMucs', 'nguyenLieus', 'dinhMucRows', 'toppings',
            'temperatureOptions', 'sweetnessOptions', 'selectedSweetness',
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_mon' => 'required|string|max:100',
            'don_gia' => 'required|integer|min:1000',
            'mo_ta' => 'nullable|string|max:500',
            'hinh_anh' => 'nullable|string|max:255',
            'hinh_anh_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'ma_danh_muc' => 'required|exists:DANH_MUC,ma_danh_muc',
            'trang_thai' => 'required|in:active,het_hang,an',
            'dinh_muc' => 'nullable|array|max:50',
            'dinh_muc.*.ma_nl' => 'nullable|exists:NGUYEN_LIEU,ma_nl',
            'dinh_muc.*.so_luong_dung' => 'nullable|numeric|min:0.01|max:999999',
            'dinh_muc.*.mo_ta' => 'nullable|string|max:200',
            'temperature_options'   => 'nullable|array',
            'temperature_options.*' => 'string|max:100',
            'sweetness_options'     => 'nullable|array',
            'sweetness_options.*'   => 'string|max:100',
            'topping_options'       => 'nullable|array',
            'topping_options.*'     => 'string|exists:TOPPING,ma_topping',
        ]);

        unset($validated['hinh_anh_file']);
        $dinhMucs          = $validated['dinh_muc'] ?? [];
        $temperatureOptions = $validated['temperature_options'] ?? [];
        $sweetnessOptions   = $validated['sweetness_options'] ?? [];
        $toppingOptions     = $validated['topping_options'] ?? [];
        unset($validated['dinh_muc'], $validated['temperature_options'], $validated['sweetness_options'], $validated['topping_options']);

        $mon = DB::transaction(function () use ($request, $validated, $dinhMucs, $temperatureOptions, $sweetnessOptions, $toppingOptions) {
            $maMon = $this->generateMonCode();
            $validated['ma_mon'] = $maMon;

            if ($request->hasFile('hinh_anh_file')) {
                $validated['hinh_anh'] = $this->storeMenuImage($request, $maMon);
            }

            $mon = Mon::create($validated);
            $this->syncDinhMucs($maMon, $dinhMucs);
            $this->syncDisplayOptions($maMon, $temperatureOptions, $sweetnessOptions, $toppingOptions);

            return $mon;
        });

        return redirect()->route('menu.index')
            ->with('success', 'Đã thêm món: '.$mon->ten_mon);
    }

    private function generateMonCode(): string
    {
        $max = DB::table('MON')
            ->lockForUpdate()
            ->selectRaw('MAX(CAST(SUBSTRING(ma_mon, 4) AS UNSIGNED)) as max_code')
            ->value('max_code') ?? 0;

        return 'MON' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    public function edit(string $maMon)
    {
        $mon = Mon::with(['dinhMucs.nguyenLieu', 'options'])->findOrFail($maMon);
        $danhMucs = DanhMuc::orderBy('ten_danh_muc')->get();
        $nguyenLieus = $this->getNguyenLieusWithStock();
        $toppings = Topping::where('trang_thai', 'active')->orderBy('ten_topping')->get();
        $temperatureOptions = $this->temperatureOptions();
        $sweetnessOptions   = $this->sweetnessOptions();
        $selectedSweetness  = old('sweetness_options',
            $mon->options->where('loai_option', 'sweetness')->where('trang_thai', 'active')->pluck('ten_option')->all()
        );
        $dinhMucRows = old('dinh_muc', $mon->dinhMucs->map(fn($dinhMuc) => [
            'ma_nl' => $dinhMuc->ma_nl,
            'so_luong_dung' => $dinhMuc->so_luong_dung,
            'mo_ta' => $dinhMuc->mo_ta,
        ])->values()->all());

        if (empty($dinhMucRows)) {
            $dinhMucRows = [['ma_nl' => '', 'so_luong_dung' => '', 'mo_ta' => '']];
        }

        return view('staff.menu-form', compact(
            'mon', 'danhMucs', 'nguyenLieus', 'dinhMucRows', 'toppings',
            'temperatureOptions', 'sweetnessOptions', 'selectedSweetness',
        ));
    }

    public function update(Request $request, string $maMon)
    {
        $mon = Mon::findOrFail($maMon);

        $validated = $request->validate([
            'ten_mon' => 'required|string|max:100',
            'don_gia' => 'required|integer|min:1000',
            'mo_ta' => 'nullable|string|max:500',
            'hinh_anh' => 'nullable|string|max:255',
            'hinh_anh_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'ma_danh_muc' => 'required|exists:DANH_MUC,ma_danh_muc',
            'trang_thai' => 'required|in:active,het_hang,an',
            'dinh_muc' => 'nullable|array|max:50',
            'dinh_muc.*.ma_nl' => 'nullable|exists:NGUYEN_LIEU,ma_nl',
            'dinh_muc.*.so_luong_dung' => 'nullable|numeric|min:0.01|max:999999',
            'dinh_muc.*.mo_ta' => 'nullable|string|max:200',
            'temperature_options'   => 'nullable|array',
            'temperature_options.*' => 'string|max:100',
            'sweetness_options'     => 'nullable|array',
            'sweetness_options.*'   => 'string|max:100',
            'topping_options'       => 'nullable|array',
            'topping_options.*'     => 'string|exists:TOPPING,ma_topping',
        ]);

        unset($validated['hinh_anh_file']);
        $dinhMucs           = $validated['dinh_muc'] ?? [];
        $temperatureOptions = $validated['temperature_options'] ?? [];
        $sweetnessOptions   = $validated['sweetness_options'] ?? [];
        $toppingOptions     = $validated['topping_options'] ?? [];
        unset($validated['dinh_muc'], $validated['temperature_options'], $validated['sweetness_options'], $validated['topping_options']);
        if ($request->hasFile('hinh_anh_file')) {
            $validated['hinh_anh'] = $this->storeMenuImage($request, $mon->ma_mon);
        }

        DB::transaction(function () use ($mon, $validated, $dinhMucs, $temperatureOptions, $sweetnessOptions, $toppingOptions) {
            $mon->update($validated);
            $this->syncDinhMucs($mon->ma_mon, $dinhMucs);
            $this->syncDisplayOptions($mon->ma_mon, $temperatureOptions, $sweetnessOptions, $toppingOptions);
        });

        return redirect()->route('menu.index')
            ->with('success', 'Đã cập nhật món: '.$mon->ten_mon);
    }

    private function storeMenuImage(Request $request, string $maMon): string
    {
        $file = $request->file('hinh_anh_file');
        $extension = $file->getClientOriginalExtension();
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: Str::lower($maMon);
        $fileName = $maMon . '-' . $baseName . '-' . now()->format('YmdHis') . '.' . $extension;

        File::ensureDirectoryExists(public_path('images/menu'));
        $file->move(public_path('images/menu'), $fileName);

        return 'menu/' . $fileName;
    }

    private function getNguyenLieusWithStock()
    {
        $maChiNhanh = session('ma_chi_nhanh');

        return NguyenLieu::with(['tonKhos' => function ($query) use ($maChiNhanh) {
            if ($maChiNhanh) {
                $query->where('ma_chi_nhanh', $maChiNhanh);
            }
        }])->orderBy('ten_nl')->get();
    }

    private function syncDinhMucs(string $maMon, array $rows): void
    {
        $cleanRows = collect($rows)
            ->filter(fn($row) => !empty($row['ma_nl']) && isset($row['so_luong_dung']) && (float) $row['so_luong_dung'] > 0)
            ->groupBy('ma_nl')
            ->map(function ($items, $maNl) {
                $first = $items->first();
                return [
                    'ma_nl' => $maNl,
                    'so_luong_dung' => $items->sum(fn($item) => (float) $item['so_luong_dung']),
                    'mo_ta' => $first['mo_ta'] ?? null,
                ];
            })
            ->values();

        DinhMuc::where('ma_mon', $maMon)->delete();

        foreach ($cleanRows as $row) {
            DinhMuc::create([
                'ma_mon' => $maMon,
                'ma_nl' => $row['ma_nl'],
                'so_luong_dung' => $row['so_luong_dung'],
                'mo_ta' => $row['mo_ta'],
            ]);
        }
    }

    private function temperatureOptions(): array
    {
        return ['Đá', 'Nóng', 'Ít đá', 'Không đá', 'Thường', 'Làm nóng'];
    }

    private function sweetnessOptions(): array
    {
        return ['Ngọt nhiều', 'Ngọt vừa', 'Ít ngọt', 'Không ngọt'];
    }

    private function syncDisplayOptions(string $maMon, array $temperatures, array $sweetnesses, array $toppingIds): void
    {
        // Nhiệt độ
        $selectedTemperatures = collect($temperatures)->intersect($this->temperatureOptions())->values();
        foreach (collect($this->temperatureOptions()) as $index => $name) {
            $option = MonOption::firstOrNew(['ma_mon' => $maMon, 'loai_option' => 'temperature', 'ten_option' => $name]);
            if (!$option->exists) {
                $option->ma_option = $this->optionId('temperature');
            }
            $option->fill([
                'gia_them' => 0,
                'bat_buoc' => $selectedTemperatures->contains($name) && $index === 0,
                'thu_tu'   => $index,
                'trang_thai' => $selectedTemperatures->contains($name) ? 'active' : 'an',
            ])->save();
        }

        // Độ ngọt
        $selectedSweetness = collect($sweetnesses)->intersect($this->sweetnessOptions())->values();
        foreach (collect($this->sweetnessOptions()) as $index => $name) {
            $option = MonOption::firstOrNew(['ma_mon' => $maMon, 'loai_option' => 'sweetness', 'ten_option' => $name]);
            if (!$option->exists) {
                $option->ma_option = $this->optionId('sweetness');
            }
            $option->fill([
                'gia_them'   => 0,
                'bat_buoc'   => false,
                'thu_tu'     => $index,
                'trang_thai' => $selectedSweetness->contains($name) ? 'active' : 'an',
            ])->save();
        }

        // Topping
        MonOption::where('ma_mon', $maMon)->where('loai_option', 'topping')->update(['trang_thai' => 'an']);

        Topping::whereIn('ma_topping', $toppingIds)->get()->values()->each(function (Topping $topping, int $index) use ($maMon) {
            $option = MonOption::firstOrNew(['ma_mon' => $maMon, 'loai_option' => 'topping', 'ten_option' => $topping->ten_topping]);
            if (!$option->exists) {
                $option->ma_option = $this->optionId('topping');
            }
            $option->fill([
                'gia_them'   => $topping->gia_them,
                'bat_buoc'   => false,
                'thu_tu'     => $index,
                'trang_thai' => 'active',
            ])->save();
        });
    }

    private function optionId(string $type): string
    {
        $prefix = match($type) {
            'topping'   => 'TP',
            'sweetness' => 'SW',
            default     => 'TM',
        };
        do {
            $id = $prefix . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (MonOption::whereKey($id)->exists());

        return $id;
    }

    public function destroy(string $maMon)
    {
        $mon = Mon::findOrFail($maMon);
        $mon->update(['trang_thai' => 'an']);

        return redirect()->back()
            ->with('success', 'Đã ẩn món: '.$mon->ten_mon);
    }

    public function restore(string $maMon)
    {
        $mon = Mon::findOrFail($maMon);
        $mon->update(['trang_thai' => 'active']);

        return redirect()->back()
            ->with('success', 'Đã bỏ ẩn món: '.$mon->ten_mon);
    }
}
