/* =====================================================================
 *  8AM COFFEE — Đầu đọc thẻ RFID cho ARDUINO UNO + LCD 1602 (I2C)
 *
 *  UNO KHÔNG có WiFi → không tự gọi API được. Sketch này:
 *    - Đọc UID thẻ RFID (RC522) → hiển thị LCD → in ra Serial: "UID:xxxx"
 *    - Nhận lệnh từ máy PC (qua USB Serial) để hiện chữ lên LCD:
 *          L1:<dòng 1>      L2:<dòng 2>
 *  Một "cầu nối" (bridge) chạy trên PC sẽ đọc UID, gọi API Laravel
 *  (/api/arduino/...) rồi gửi kết quả về LCD. Xem arduino/bridge/.
 *
 *  ĐẤU NỐI (đúng theo phần cứng đang dùng):
 *    RC522 (SPI):  RST→D9  SDA(SS)→D10  MOSI→D11  MISO→D12  SCK→D13  VCC→3.3V  GND→GND
 *    LCD 1602 I2C: VCC→5V  GND→GND  SDA→A4  SCL→A5
 *
 *  THƯ VIỆN (Library Manager): "MFRC522" (GithubCommunity),
 *    "LiquidCrystal I2C" (Frank de Brabander).
 *
 *  LƯU Ý: chân SPI của UNO ở mức 5V còn RC522 danh định 3.3V — thường vẫn chạy,
 *  nhưng dùng module hạ áp (level shifter) sẽ bền hơn. Cấp nguồn RC522 = 3.3V.
 * ===================================================================== */

#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

#define RST_PIN 9
#define SS_PIN  10

MFRC522 mfrc522(SS_PIN, RST_PIN);
// Địa chỉ I2C thường là 0x27; nếu LCD không hiện chữ, đổi sang 0x3F.
LiquidCrystal_I2C lcd(0x27, 16, 2);

String lastUid = "";

void setup() {
  Serial.begin(9600);
  SPI.begin();
  mfrc522.PCD_Init();

  lcd.init();
  lcd.backlight();
  lcdShow("8AM Coffee", "Quet the...");

  Serial.println(F("READY"));
}

void loop() {
  // 1) Nhận lệnh hiển thị từ PC (bridge)
  readSerialCommands();

  // 2) Có thẻ mới?
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    delay(50);
    return;
  }

  lastUid = uidToHex(mfrc522.uid.uidByte, mfrc522.uid.size);
  Serial.print(F("UID:"));
  Serial.println(lastUid);              // PC bridge sẽ bắt dòng này

  lcdShow("The: " + lastUid, "Dang tra cuu...");

  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
  delay(1500);                          // chống quẹt lặp
}

// ─────────── Đọc lệnh L1:/L2: từ PC để hiện lên LCD ───────────
void readSerialCommands() {
  while (Serial.available()) {
    String line = Serial.readStringUntil('\n');
    line.trim();
    if (line.startsWith("L1:"))      lcdLine(0, line.substring(3));
    else if (line.startsWith("L2:")) lcdLine(1, line.substring(3));
  }
}

// ─────────── UID → HEX viết HOA, không phân cách ───────────
String uidToHex(byte* buffer, byte size) {
  String s = "";
  for (byte i = 0; i < size; i++) {
    if (buffer[i] < 0x10) s += "0";
    s += String(buffer[i], HEX);
  }
  s.toUpperCase();
  return s;
}

// ─────────── Ghi 1 dòng LCD (đệm đủ 16 ký tự để xóa chữ cũ) ───────────
void lcdLine(uint8_t row, String s) {
  if (s.length() > 16) s = s.substring(0, 16);
  while (s.length() < 16) s += ' ';
  lcd.setCursor(0, row);
  lcd.print(s);
}

void lcdShow(String a, String b) {
  lcdLine(0, a);
  lcdLine(1, b);
}
