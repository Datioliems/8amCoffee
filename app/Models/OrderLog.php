<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    protected $table = 'ORDER_LOGS';
    public $timestamps = false;

    protected $fillable = [
        'ma_order',
        'hanh_dong',
        'trang_thai_cu',
        'trang_thai_moi',
        'noi_dung',
        'du_lieu',
        'ma_nv',
        'created_at',
    ];

    protected $casts = [
        'du_lieu' => 'array',
        'created_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'ma_order', 'ma_order');
    }
}
