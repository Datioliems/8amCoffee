/* =====================================================================
 *  LCD TEST — kiểm tra RIÊNG màn LCD 1602 I2C (tách khỏi RFID).
 *  Nếu sketch này hiện chữ thì LCD + thư viện OK; lúc đó nạp lại
 *  sketch chính 8am_uno_rfid_lcd.
 *
 *  Đấu nối:  VCC→5V  GND→GND  SDA→A4  SCL→A5
 *  Thư viện: "LiquidCrystal I2C" (Frank de Brabander).
 *
 *  NẾU VẪN KHÔNG HIỆN CHỮ, theo thứ tự:
 *   1) Đổi địa chỉ 0x27 ↔ 0x3F (dùng i2c_scanner để biết đúng).
 *   2) VẶN BIẾN TRỞ XANH (chiết áp) ở lưng LCD từ từ — sai contrast là
 *      nguyên nhân phổ biến nhất khiến nền sáng mà KHÔNG thấy chữ.
 *   3) Nếu báo lỗi 'init' was not declared → đổi lcd.init() thành lcd.begin().
 * ===================================================================== */
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// Đổi sang 0x3F nếu i2c_scanner báo địa chỉ khác.
LiquidCrystal_I2C lcd(0x27, 16, 2);

void setup() {
  Wire.begin();
  lcd.init();        // vài phiên bản thư viện dùng lcd.begin();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("8AM Coffee");
  lcd.setCursor(0, 1);
  lcd.print("LCD OK!");
}

void loop() {
  // Nhấp nháy nền để xác nhận LCD còn sống.
  lcd.backlight();
  delay(1500);
  lcd.noBacklight();
  delay(400);
}
