<?php

namespace App\Http\Requests;

use App\Models\Ban;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $maBan = $this->route('ma_ban');
        if (!is_string($maBan)) {
            return false;
        }

        // Bàn chỉ cần tồn tại — một bàn có thể có nhiều lượt khách/đơn,
        // không chặn khi bàn đang "có khách" để tránh lỗi truy cập khi quét lại.
        return Ban::where('ma_ban', $maBan)->exists();
    }

    protected function failedAuthorization(): never
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'Bàn này không tồn tại.'
        );
    }

    public function rules(): array
    {
        return [
            'ten_kh' => 'required|string|max:100',
            'sdt_kh' => ['nullable', 'string', 'regex:/^0[0-9]{9}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'ten_kh.required' => 'Vui lòng nhập tên khách hàng.',
            'ten_kh.max' => 'Tên không được quá 100 ký tự.',
            'sdt_kh.regex' => 'Số điện thoại phải gồm đúng 10 chữ số, bắt đầu bằng 0.',
        ];
    }
}
