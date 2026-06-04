/* =====================================================================
 *  8AM COFFEE — ĐỌC THẺ RFID (chỉ RC522, KHÔNG LCD) cho Arduino UNO
 *
 *  Mục tiêu: xác nhận đầu đọc + thẻ hoạt động → in UID ra Serial Monitor.
 *  Không dùng LCD (bỏ qua lỗi màn hình). Có TỰ KIỂM TRA kết nối RC522.
 *
 *  ĐẤU NỐI RC522 ↔ UNO:
 *    RST→D9   SDA(SS)→D10   MOSI→D11   MISO→D12   SCK→D13   VCC→3.3V   GND→GND
 *
 *  THƯ VIỆN: "MFRC522" (GithubCommunity).
 *  Mở Serial Monitor ở 9600 baud, rồi quẹt thẻ.
 * ===================================================================== */

#include <SPI.h>
#include <MFRC522.h>

#define RST_PIN 9
#define SS_PIN  10

MFRC522 mfrc522(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(9600);
  while (!Serial) {}
  SPI.begin();
  mfrc522.PCD_Init();
  delay(50);

  Serial.println(F("READY"));

  // Tự kiểm tra: đọc thanh ghi phiên bản của RC522.
  byte v = mfrc522.PCD_ReadRegister(MFRC522::VersionReg);
  Serial.print(F("RC522 VersionReg = 0x"));
  Serial.println(v, HEX);
  if (v == 0x00 || v == 0xFF) {
    Serial.println(F("!! KHONG giao tiep duoc voi RC522."));
    Serial.println(F("   Kiem tra: SS->D10, RST->D9, MOSI->D11, MISO->D12, SCK->D13, VCC->3.3V (KHONG 5V), GND->GND."));
  } else {
    Serial.println(F("RC522 OK. Hay quet the..."));
  }
}

void loop() {
  // Chờ thẻ mới
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    delay(50);
    return;
  }

  // Ghép UID thành chuỗi hex viết HOA (khớp định dạng hệ thống dùng)
  String uid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(mfrc522.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  Serial.print(F("UID:"));
  Serial.println(uid);                 // <-- dòng này dùng cho bridge & để bạn copy vào web

  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
  delay(1200);                         // chống quẹt lặp
}
