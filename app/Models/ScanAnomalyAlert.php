<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanAnomalyAlert extends Model
{
    protected $table = 'SCAN_ANOMALY_ALERT';

    protected $fillable = [
        'ip_hash', 'ip_masked', 'ma_ban', 'ma_chi_nhanh',
        'scan_count', 'distinct_tables', 'distinct_branches', 'unique_user_agents',
        'created_orders', 'conversion_rate',
        'anomaly_score', 'risk_score', 'risk_level', 'is_alert',
        'detection_method', 'reason', 'suggested_action', 'raw_summary',
        'detected_at',
    ];

    protected $casts = [
        'raw_summary'     => 'array',
        'detected_at'     => 'datetime',
        'conversion_rate' => 'float',
        'anomaly_score'   => 'float',
        'is_alert'        => 'boolean',
    ];

    public function feedbacks(): HasMany
    {
        return $this->hasMany(ScanAnomalyFeedback::class, 'alert_id');
    }

    /** Badge CSS class theo risk_level (dùng trong view). */
    public function getRiskBadgeClassAttribute(): string
    {
        return match ($this->risk_level) {
            'critical' => 'bg-red-100 text-red-700 ring-red-200',
            'high'     => 'bg-orange-100 text-orange-700 ring-orange-200',
            'medium'   => 'bg-yellow-100 text-yellow-700 ring-yellow-200',
            default    => 'bg-gray-100 text-gray-500 ring-gray-200',
        };
    }
}
