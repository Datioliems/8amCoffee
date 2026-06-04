/* =====================================================================
 *  I2C SCANNER — tìm địa chỉ I2C của LCD (và mọi thiết bị I2C).
 *  Nạp sketch này TRƯỚC để biết LCD ở 0x27 hay 0x3F, rồi điền lại
 *  vào sketch chính (8am_uno_rfid_lcd) hoặc lcd_test.
 *
 *  Đấu nối LCD I2C ↔ UNO:  VCC→5V  GND→GND  SDA→A4  SCL→A5
 *  Mở Serial Monitor ở 9600 baud để xem kết quả.
 * ===================================================================== */
#include <Wire.h>

void setup() {
  Wire.begin();
  Serial.begin(9600);
  while (!Serial) {}
  Serial.println(F("\n=== I2C Scanner ==="));
}

void loop() {
  byte count = 0;
  Serial.println(F("Dang quet I2C..."));
  for (byte addr = 1; addr < 127; addr++) {
    Wire.beginTransmission(addr);
    if (Wire.endTransmission() == 0) {
      Serial.print(F("  -> Tim thay thiet bi tai 0x"));
      if (addr < 16) Serial.print('0');
      Serial.println(addr, HEX);
      count++;
    }
  }
  if (count == 0) {
    Serial.println(F("  KHONG thay thiet bi I2C nao."));
    Serial.println(F("  Kiem tra: SDA->A4, SCL->A5, VCC->5V, GND->GND, moi noi han chac."));
  } else {
    Serial.print(F("Xong. Tong: "));
    Serial.println(count);
    Serial.println(F("Dia chi cua LCD thuong la 0x27 hoac 0x3F -> dien vao sketch chinh."));
  }
  Serial.println();
  delay(3000);
}
