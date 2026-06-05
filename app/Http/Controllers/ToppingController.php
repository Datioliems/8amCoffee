<?php

namespace App\Http\Controllers;

use App\Models\Topping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ToppingController extends Controller
{
    public function index()
    {
        $toppings = Topping::orderBy('ten_topping')->get();
        return view('staff.topping-list', compact('toppings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ten_topping' => 'required|string|max:100',
            'gia_them'    => 'required|integer|min:0',
            'canh_bao'    => 'nullable|string|max:255',
        ], [], ['ten_topping' => 'tên topping', 'gia_them' => 'giá thêm']);

        Topping::create([
            'ma_topping'  => $this->nextId(),
            'ten_topping' => $data['ten_topping'],
            'gia_them'    => $data['gia_them'],
            'canh_bao'    => $data['canh_bao'] ?? null,
            'trang_thai'  => 'active',
        ]);

        return back()->with('success', 'Đã thêm topping “' . $data['ten_topping'] . '”.');
    }

    public function update(Request $request, string $maTopping)
    {
        $topping = Topping::where('ma_topping', $maTopping)->firstOrFail();

        $data = $request->validate([
            'ten_topping' => 'required|string|max:100',
            'gia_them'    => 'required|integer|min:0',
            'canh_bao'    => 'nullable|string|max:255',
            'trang_thai'  => 'required|in:active,inactive',
        ]);

        $topping->update($data);

        return back()->with('success', 'Đã cập nhật topping ' . $maTopping . '.');
    }

    public function destroy(string $maTopping)
    {
        Topping::where('ma_topping', $maTopping)->delete();
        return back()->with('success', 'Đã xóa topping ' . $maTopping . '.');
    }

    /** Sinh mã TP + 3 chữ số tăng dần. */
    private function nextId(): string
    {
        $max = DB::table('TOPPING')
            ->where('ma_topping', 'like', 'TP%')
            ->selectRaw("MAX(CAST(SUBSTRING(ma_topping, 3) AS UNSIGNED)) AS m")
            ->value('m');

        return 'TP' . str_pad(((int) $max) + 1, 3, '0', STR_PAD_LEFT);
    }
}
