<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mon extends Model
{
    protected $table      = 'MON';
    protected $primaryKey = 'ma_mon';
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    protected $fillable = ['ma_mon','ten_mon','don_gia','mo_ta','hinh_anh','ma_danh_muc','trang_thai'];

    public function getImageUrlAttribute(): ?string
    {
        $fallbacks = [
            'MON001'=>'espresso.jpg', 'MON002'=>'americano.jpg', 'MON003'=>'latte.jpg',
            'MON004'=>'cam_em.jpg', 'MON005'=>'salted_caramel.jpg', 'MON006'=>'ca_phe_muoi.jpg',
            'MON007'=>'ca_phe_trung.png', 'MON008'=>'lady_sweet.jpg', 'MON009'=>'ginger_latte.jpg',
            'MON010'=>'v60.jpg', 'MON011'=>'origami.jpg', 'MON012'=>'cold_brew.jpg',
            'MON013'=>'cold_brew_mo.png', 'MON014'=>'cold_brew.jpg', 'MON015'=>'tonic.jpg',
            'MON016'=>'nhiet_doi.jpg', 'MON017'=>'ca_phe_den.webp', 'MON018'=>'ca_phe_nau.jpg',
            'MON019'=>'bac_xiu.jpg', 'MON020'=>'sua_chua_ca_phe.jpg', 'MON021'=>'ca_cao.jpg',
            'MON022'=>'chanh_xi_muoi.jpg', 'MON023'=>'chanh_leo.png', 'MON024'=>'tra_oi_hong.jpg',
            'MON025'=>'tra_chanh_dao.jpg', 'MON026'=>'banh_sung_bo.jpg',
            'MON027'=>'banh_sung_bo_socola.jpg', 'MON028'=>'hat_sen_say.jpg',
        ];

        $image = $this->hinh_anh ?: ($fallbacks[$this->ma_mon] ?? null);
        if (!$image) {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '/')) {
            return $image;
        }

        return asset('images/' . ltrim($image, '/'));
    }

    /** URL model 3D của món (trên CDN/R2). Tên file phân biệt hoa/thường. Null nếu món chưa có model. */
    public function getModel3dUrlAttribute(): ?string
    {
        $map = [
            'MON002' => 'americano_da.glb',
            'MON005' => 'saltedcaramel.glb',
            'MON006' => 'Caphemuoi.glb',
            'MON007' => 'ca_phe_trung.glb',
            'MON008' => 'lady_sweet.glb',
            'MON009' => 'ginger_latte.glb',
            'MON013' => 'cold_brew_mo.glb',
            'MON014' => 'cold_brew_me.glb',
            'MON015' => 'cold_brew_tonic.glb',
            'MON016' => 'cold_brew_nhiet_doi.glb',
            'MON017' => 'den.glb',
            'MON018' => 'nau.glb',
            'MON019' => 'Bacsiu.glb',
            'MON020' => 'Suachuacaphe.glb',
            'MON021' => 'ca_cao.glb',
            'MON022' => 'chanh_xi_muoi.glb',
            'MON023' => 'mot_chanh_leo.glb',
            'MON024' => 'tra_oi_hong.glb',
            'MON025' => 'tra_chanh_dao.glb',
            'MON026' => 'banh_sung_bo.glb',
            'MON027' => 'banh_sung_scl.glb',
            'MON028' => 'hat_Sen_say.glb',
        ];
        $file = $map[$this->ma_mon] ?? null;
        return $file ? \App\Support\Cdn::url('models/' . $file) : null;
    }

    public function danhMuc()  { return $this->belongsTo(DanhMuc::class, 'ma_danh_muc', 'ma_danh_muc'); }
    public function dinhMucs() { return $this->hasMany(DinhMuc::class, 'ma_mon', 'ma_mon'); }
    public function options()  { return $this->hasMany(MonOption::class, 'ma_mon', 'ma_mon'); }
}
