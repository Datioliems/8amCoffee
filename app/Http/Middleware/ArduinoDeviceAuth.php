<?php

namespace App\Http\Middleware;

use App\Models\ThietBiArduino;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Xác thực đầu đọc Arduino qua header thiết bị.
 *   X-Device-Id    : ma_thiet_bi (vd ARD001)
 *   X-Device-Token : token bí mật; so khớp sha256(token) === api_key_hash
 *
 * Thiết bị hợp lệ được gắn vào request: $request->attributes->get('device').
 */
class ArduinoDeviceAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $id    = (string) $request->header('X-Device-Id');
        $token = (string) $request->header('X-Device-Token');

        if ($id === '' || $token === '') {
            return response()->json(['ok' => false, 'message' => 'Thiếu thông tin xác thực thiết bị.'], 401);
        }

        $device = ThietBiArduino::where('ma_thiet_bi', $id)->first();

        if (! $device
            || $device->trang_thai !== 'hoat_dong'
            || ! $device->api_key_hash
            || ! hash_equals($device->api_key_hash, ThietBiArduino::hashKey($token))) {
            return response()->json(['ok' => false, 'message' => 'Thiết bị không hợp lệ hoặc bị khóa.'], 401);
        }

        // Cập nhật "lần kết nối cuối" (nhẹ — chỉ ghi cột, không đụng nghiệp vụ).
        $device->forceFill([
            'lan_ket_noi_cuoi' => now(),
            'ip_cuoi'          => $request->ip(),
        ])->save();

        $request->attributes->set('device', $device);

        return $next($request);
    }
}
