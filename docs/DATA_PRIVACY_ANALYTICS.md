# Báo cáo: Bảo mật & Phân tích dữ liệu khách hàng — 8AM Coffee

> Tổng hợp từ deep-research (kiểm chứng đối kháng 24/24 luận điểm). Trích dẫn nguồn ở cuối.

## 0. Khung pháp lý

| Văn bản | Vai trò | Điểm cốt lõi |
|---|---|---|
| **Luật BVDLCN số 91/2025/QH15** (thông qua 26/6/2025, hiệu lực **01/01/2026**) | Luật — hiệu lực cao nhất | Mã hóa (Đ.12), phi định danh (Đ.2.11, Đ.14.6) |
| **NĐ 356/2025/NĐ-CP** | Hướng dẫn thi hành Luật 91/2025 | Chuẩn kỹ thuật chi tiết (cần đối chiếu) |
| **NĐ 13/2023/NĐ-CP** | Khung chi tiết trước Luật | Phân loại dữ liệu, DPIA (Đ.24), chuyển dữ liệu ra nước ngoài (Đ.25) |
| GDPR Art.4(5)/32, NIST IR 8053 | Tham chiếu best-practice (KHÔNG ràng buộc tại VN) | Pseudonymisation, encryption |

**3 nguyên tắc xương sống:**
1. **Mã hóa KHÔNG xóa nghĩa vụ pháp lý** — Đ.12: "dữ liệu cá nhân sau khi mã hóa vẫn là dữ liệu cá nhân" → tách khóa (KMS) + vẫn kiểm soát truy cập + DPIA.
2. **Cấm tái định danh** — Đ.14(6): không ghép ngược dữ liệu đã phi định danh.
3. **Phi định danh = tạo dữ liệu mới không xác định được cá nhân** (Đ.2.11); pseudonymization (NIST) giữ liên kết chéo qua bí danh → cơ sở dùng `customer_hash`.

**Áp cho 8AM:** `ten_kh/sdt/email/địa chỉ` = dữ liệu cá nhân cơ bản (NĐ13 Đ.2.3); quán = Bên Kiểm soát dữ liệu (Đ.2.9); phải lập DPIA (Đ.24).

## 1. Pipeline & mã hóa

| Trường | Phân loại | Kỹ thuật |
|---|---|---|
| `sdt`, `sdt_khach` | Định danh trực tiếp | Tokenize (HMAC blind index) + mã hóa bản gốc |
| `email`, `ten_kh`, `ten_khach`, `cccd`, `dia_chi` | Định danh trực tiếp | Mã hóa AES-256 (Laravel Crypt) |
| Ngày sinh / địa chỉ chi tiết | Tựa định danh | Generalize → nhóm tuổi / tỉnh thành |
| `ma_kh` | Khóa nối | Thay bằng `customer_hash` (pseudonym) |
| Món/số lượng/giá/thời gian/danh mục/hình thức | Hành vi (KHÔNG PII) | Giữ nguyên — nhiên liệu cho ML |

- **App-level (Laravel Crypt AES-256-GCM)** mã hóa từng cột → chống cả DBA tò mò; kết hợp **TDE** chống mất ổ đĩa.
- **KMS**: không để `APP_KEY`/pepper cạnh DB.
- **k-anonymity ≥ 5 + l-diversity** sau generalize trước khi mở phân tích.

## 2. RBAC cho Data Analyst / BI

- **MySQL Community KHÔNG có Dynamic Data Masking gốc** (chỉ Enterprise/HeatWave 9.7+) → dùng **view-based masking**:
  - View chỉ lộ trường ẩn danh (`SQL SECURITY DEFINER`).
  - `GRANT SELECT ON db.view` cho analyst; **KHÔNG** cấp quyền trên bảng PII gốc.
- Cloud: Snowflake/BigQuery có masking gắn role/IAM theo cột (nếu sau này migrate).

## 3. ML Best-seller

- **Học từ dữ liệu ẩn danh KHÔNG giảm độ chính xác** (chỉ bỏ PII, giữ hành vi + `customer_hash` ổn định).
- Thuật toán theo quy mô: **Apriori/FP-Growth** (giỏ hàng, đã có), **CF + RFM** (cá nhân hóa), **Prophet/LightGBM** (chuỗi thời gian khi dữ liệu lớn).
- ⚠️ Quan niệm "ARIMA chính xác hơn Prophet/LightGBM" đã **bị bác** — chỉ đúng với tập dữ liệu cụ thể.
- **Features quan trọng**: RFM, khoảng cách giữa các lần mua, danh mục, giờ/thứ trong tuần, mùa vụ, thời tiết, kênh (tại bàn/mang về).

## 4. Cảnh báo
- Luật 91/2025 mới — đối chiếu bản tiếng Việt chính thức + NĐ 356/2025 trước khi ký pháp lý.
- GDPR/NIST chỉ tham chiếu, không ràng buộc tại VN.
- Kết luận ML đặc thù theo dữ liệu nghiên cứu — cần thử nghiệm trên dữ liệu thật.

## Nguồn
- Luật 91/2025/QH15: congbao.chinhphu.vn · english.luatvietnam.vn
- NĐ 13/2023: thuvienphapluat.vn
- NIST IR 8053: nvlpubs.nist.gov · ICO encryption: ico.org.uk
- MySQL DDM: blogs.oracle.com · View masking: aws.amazon.com · Snowflake/BigQuery docs
- ML: arXiv:2203.06848 · IEEE 8290000
