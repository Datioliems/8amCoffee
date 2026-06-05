<?php

namespace App\Services;

use App\Models\Mon;

class MenuAvailabilityService
{
    public function unavailableIngredients(Mon $mon, ?string $maChiNhanh, int $quantity = 1): array
    {
        if (!$maChiNhanh || !$mon->relationLoaded('dinhMucs')) {
            $mon->loadMissing('dinhMucs.nguyenLieu.tonKhos');
        }

        $quantity = max(1, $quantity);

        return $mon->dinhMucs
            ->filter(function ($dinhMuc) use ($maChiNhanh, $quantity) {
                $required = (float) $dinhMuc->so_luong_dung * $quantity;
                if ($required <= 0) {
                    return false;
                }

                $stock = $dinhMuc->nguyenLieu?->tonKhos
                    ->first(fn($tonKho) => !$maChiNhanh || $tonKho->ma_chi_nhanh === $maChiNhanh);

                $available = (float) ($stock?->sl_ton_kho_he_thong ?? 0);

                return $available < ($required - 0.0001);
            })
            ->map(fn($dinhMuc) => $dinhMuc->nguyenLieu?->ten_nl ?? $dinhMuc->ma_nl)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function annotate($mons, ?string $maChiNhanh): void
    {
        $mons->each(function (Mon $mon) use ($maChiNhanh) {
            $ingredients = $this->unavailableIngredients($mon, $maChiNhanh);

            $mon->setAttribute('het_hang_theo_kho', count($ingredients) > 0);
            $mon->setAttribute('nguyen_lieu_het', $ingredients);
        });
    }
}
