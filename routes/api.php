<?php

use App\Http\Controllers\Api\ArduinoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API thiết bị Arduino (RFID + tích điểm)
|--------------------------------------------------------------------------
| Nhóm route stateless (không CSRF, không session) cho đầu đọc ESP32.
| Xác thực bằng middleware 'arduino.device' qua 2 header:
|   X-Device-Id    : mã thiết bị (vd ARD001)
|   X-Device-Token : token bí mật của thiết bị (so khớp sha256 với api_key_hash)
| Tiền tố /api được thêm tự động → đường dẫn thực: /api/arduino/...
*/
Route::middleware('arduino.device')->prefix('arduino')->name('api.arduino.')->group(function () {
    Route::post('/quet',      [ArduinoController::class, 'quet'])->name('quet');           // nhận diện thẻ
    Route::post('/phat-the',  [ArduinoController::class, 'phatThe'])->name('phat-the');    // phát thẻ + điểm khởi tạo
    Route::post('/tich-diem', [ArduinoController::class, 'tichDiem'])->name('tich-diem');  // tích điểm thủ công
    Route::post('/doi-diem',  [ArduinoController::class, 'doiDiem'])->name('doi-diem');    // đổi điểm lấy giảm giá
    Route::post('/heartbeat', [ArduinoController::class, 'heartbeat'])->name('heartbeat'); // báo sống
});
