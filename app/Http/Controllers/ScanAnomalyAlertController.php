<?php

namespace App\Http\Controllers;

use App\Models\ScanAnomalyAlert;
use App\Models\ScanAnomalyFeedback;
use Illuminate\Http\Request;

class ScanAnomalyAlertController extends Controller
{
    /**
     * Danh sách cảnh báo.
     * Mặc định chỉ hiện is_alert = true; ?show_all=1 để xem cả mức đang theo dõi.
     */
    public function index(Request $request)
    {
        $maChiNhanh = (string) session('ma_chi_nhanh', '');
        $isSuper    = session('chuc_vu') === 'superadmin';
        $showAll    = $request->boolean('show_all');

        $riskLevel = $request->input('risk_level');
        $method    = $request->input('method');

        $base = ScanAnomalyAlert::query()
            ->when(! $isSuper, fn ($q) => $q->where('ma_chi_nhanh', $maChiNhanh));

        $stats = [
            'total'    => (clone $base)->where('is_alert', true)->count(),
            'critical' => (clone $base)->where('risk_level', 'critical')->where('is_alert', true)->count(),
            'high'     => (clone $base)->where('risk_level', 'high')->where('is_alert', true)->count(),
            'pending'  => (clone $base)->where('is_alert', true)->doesntHave('feedbacks')->count(),
        ];

        $alerts = (clone $base)
            ->with('feedbacks')
            ->when(! $showAll,  fn ($q) => $q->where('is_alert', true))
            ->when($riskLevel,  fn ($q) => $q->where('risk_level', $riskLevel))
            ->when($method,     fn ($q) => $q->where('detection_method', $method))
            ->orderByDesc('detected_at')
            ->paginate(20)
            ->withQueryString();

        return view('staff.scan-anomaly-alert', compact('alerts', 'isSuper', 'showAll', 'stats', 'riskLevel', 'method'));
    }

    /**
     * Quản lý đánh dấu phản hồi (true positive / false positive).
     * Dữ liệu này tích lũy để sau dùng supervised learning.
     */
    public function feedback(Request $request, int $id)
    {
        $request->validate([
            'is_true_positive' => 'required|boolean',
            'note'             => 'nullable|string|max:500',
        ]);

        $alert = ScanAnomalyAlert::findOrFail($id);

        ScanAnomalyFeedback::updateOrCreate(
            ['alert_id' => $alert->id],
            [
                'is_true_positive' => $request->boolean('is_true_positive'),
                'reviewed_by'      => session('ten_nv', 'admin'),
                'note'             => $request->input('note'),
            ]
        );

        return back()->with('success', 'Đã lưu phản hồi cho cảnh báo #' . $id);
    }
}
