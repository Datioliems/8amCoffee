<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanAnomalyFeedback extends Model
{
    protected $table = 'SCAN_ANOMALY_FEEDBACK';

    protected $fillable = [
        'alert_id', 'is_true_positive', 'reviewed_by', 'note',
    ];

    protected $casts = [
        'is_true_positive' => 'boolean',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(ScanAnomalyAlert::class, 'alert_id');
    }
}
