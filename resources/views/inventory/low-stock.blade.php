@extends('layouts.app')

@section('title', 'Cảnh báo tồn kho thấp')
@section('page-title', 'Cảnh báo tồn kho thấp')

@section('content')
<div class="max-w-4xl">
    @if($alerts->isEmpty())
    <div class="bg-green-50 border border-green-200 rounded-xl p-8 text-center">
        <p class="text-green-600 font-medium">Tất cả nguyên liệu đều ở mức an toàn.</p>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-red-50 text-red-700 text-xs uppercase">
                <tr>
                    <th class="px-5 py-3 text-left">Nguyên liệu</th>
                    <th class="px-5 py-3 text-right">Tồn kho</th>
                    <th class="px-5 py-3 text-right">Ngưỡng cảnh báo</th>
                    <th class="px-5 py-3 text-right">Còn thiếu</th>
                    <th class="px-5 py-3 text-left">Đơn vị</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($alerts as $alert)
                @php $thieu = max(0, $alert->nguong_canh_bao - $alert->sl_ton_kho_he_thong); @endphp
                <tr class="{{ $alert->sl_ton_kho_he_thong == 0 ? 'bg-red-50' : '' }}">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $alert->ten_nl }}</td>
                    <td class="px-5 py-3 text-right font-bold {{ $alert->sl_ton_kho_he_thong == 0 ? 'text-red-600' : 'text-amber-600' }}">
                        {{ $alert->sl_ton_kho_he_thong }}
                    </td>
                    <td class="px-5 py-3 text-right text-gray-500">{{ $alert->nguong_canh_bao }}</td>
                    <td class="px-5 py-3 text-right text-red-500 font-medium">{{ $thieu > 0 ? '+'.$thieu : '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $alert->don_vi }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="{{ route('inventory.import.create') }}"
           class="inline-block bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
            Tạo phiếu nhập kho
        </a>
    </div>
    @endif
</div>
@endsection
