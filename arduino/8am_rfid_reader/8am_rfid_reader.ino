/* =====================================================================
 *  8AM COFFEE — Đầu đọc thẻ thành viên RFID (ESP32 + RC522)
 *  Firmware cho Arduino IDE. Gửi UID thẻ lên hệ thống Laravel để
 *  nhận diện khách / phát thẻ / tích điểm / đổi điểm.
 *
 *  PHẦN CỨNG: ESP32 DevKit + module RC522 (RFID 13.56MHz, giao tiếp SPI).
 *  (Arduino UNO KHÔNG có WiFi → dùng ESP32; xem README.md để đấu nối.)
 *
 *  THƯ VIỆN (Library Manager): "MFRC522" (GithubCommunity), "ArduinoJson" (v7).
 *  Board: "ESP32 Dev Module" (cài qua Boards Manager — xem README).
 *
 *  Thao tác:
 *   - Quẹt thẻ        → tự gọi /quet, in tên khách + số điểm ra Serial.
 *   - Gõ ở Serial Monitor (sau khi vừa quẹt 1 thẻ):
 *        P 0901234567   → phát thẻ cho SĐT này (dùng UID vừa quẹt)
 *        T 50000        → tích điểm theo số tiền 50.000đ
 *        D 200          → đổi 200 điểm lấy giảm giá
 * ===================================================================== */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <ArduinoJson.h>

// ─────────────── CẤU HÌNH (SỬA CHO ĐÚNG MÔI TRƯỜNG) ───────────────
const char* WIFI_SSID = "TEN_WIFI";
const char* WIFI_PASS = "MAT_KHAU_WIFI";

// URL gốc của API (KHÔNG có dấu / ở cuối). Trỏ tới domain Railway hoặc tunnel.
//   Production: https://<app>.up.railway.app/api/arduino
//   Local tunnel (https): https://xxxx.trycloudflare.com/api/arduino
const char* API_BASE = "https://your-app.up.railway.app/api/arduino";

// Định danh thiết bị (khớp bản ghi THIET_BI_ARDUINO trong DB).
const char* DEVICE_ID    = "ARD001";
const char* DEVICE_TOKEN = "ARD-DEMO-KEY-001"; // token thật; server giữ sha256(token)

// Chân nối RC522 (ESP32). SCK=18, MOSI=23, MISO=19 (mặc định VSPI).
#define RST_PIN 22
#define SS_PIN  21
#define LED_OK   2   // LED báo thành công (LED onboard ESP32)
#define BUZZER  -1   // -1 = không dùng; gán chân nếu có còi

MFRC522 mfrc522(SS_PIN, RST_PIN);
String lastUid = "";  // UID thẻ vừa quẹt gần nhất (dùng cho lệnh P/T/D)

// ───────────────────────── SETUP ─────────────────────────
void setup() {
  Serial.begin(115200);
  delay(300);
  pinMode(LED_OK, OUTPUT);
  if (BUZZER >= 0) pinMode(BUZZER, OUTPUT);

  SPI.begin();
  mfrc522.PCD_Init();

  Serial.println("\n8AM Coffee - Dau doc RFID");
  connectWifi();
  Serial.println("San sang. Hay quet the...");
}

// ───────────────────────── LOOP ─────────────────────────
void loop() {
  handleSerialCommand();   // xử lý lệnh P/T/D từ Serial Monitor

  // Có thẻ mới?
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    delay(50);
    return;
  }

  lastUid = uidToHex(mfrc522.uid.uidByte, mfrc522.uid.size);
  Serial.println("\n>> Quet the UID: " + lastUid);
  quetThe(lastUid);

  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
  delay(1200);   // chống quẹt lặp
}

// ─────────────────────── WIFI ───────────────────────
void connectWifi() {
  Serial.print("Ket noi WiFi");
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  while (WiFi.status() != WL_CONNECTED) {
    delay(400);
    Serial.print(".");
  }
  Serial.println(" OK. IP=" + WiFi.localIP().toString());
}

// ─────────────────── UID → chuỗi HEX (viết HOA, không phân cách) ───────────────────
String uidToHex(byte* buffer, byte size) {
  String s = "";
  for (byte i = 0; i < size; i++) {
    if (buffer[i] < 0x10) s += "0";
    s += String(buffer[i], HEX);
  }
  s.toUpperCase();
  return s;
}

// ─────────────────── GỌI API (POST JSON) ───────────────────
// Trả về mã HTTP; nội dung trả về ghi vào `out`.
int apiPost(const String& path, const String& body, String& out) {
  if (WiFi.status() != WL_CONNECTED) connectWifi();

  WiFiClientSecure client;
  client.setInsecure();   // DEMO: bỏ qua kiểm tra chứng chỉ. Production nên pin CA.

  HTTPClient http;
  String url = String(API_BASE) + path;
  if (!http.begin(client, url)) {
    out = "begin_failed";
    return -1;
  }
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-Device-Id", DEVICE_ID);
  http.addHeader("X-Device-Token", DEVICE_TOKEN);

  int code = http.POST(body);
  out = http.getString();
  http.end();
  return code;
}

// ─────────────────── HÀNH ĐỘNG NGHIỆP VỤ ───────────────────
void quetThe(const String& uid) {
  String resp;
  int code = apiPost("/quet", "{\"uid\":\"" + uid + "\"}", resp);
  if (code != 200) { Serial.printf("Loi /quet (HTTP %d): %s\n", code, resp.c_str()); beep(false); return; }

  JsonDocument doc;
  if (deserializeJson(doc, resp)) { Serial.println("JSON loi: " + resp); return; }

  if (!doc["found"]) {
    Serial.println("=> The TRANG (chua dang ky). Go: P <sdt> de phat the cho khach.");
    beep(false);
    return;
  }
  Serial.printf("=> Khach: %s | Diem: %d (~%dd) | Hang: %s\n",
                (const char*)(doc["ten_kh"] | "?"),
                (int)doc["diem"], (int)doc["gia_tri"],
                (const char*)(doc["hang_the"] | "thuong"));
  beep(true);
}

void phatThe(const String& uid, const String& sdt) {
  String resp;
  String body = "{\"uid\":\"" + uid + "\",\"sdt\":\"" + sdt + "\"}";
  int code = apiPost("/phat-the", body, resp);
  Serial.printf("[Phat the] HTTP %d: %s\n", code, resp.c_str());
  beep(code == 200);
}

void tichDiem(const String& uid, const String& soTien) {
  String resp;
  String body = "{\"uid\":\"" + uid + "\",\"so_tien\":" + soTien + "}";
  int code = apiPost("/tich-diem", body, resp);
  Serial.printf("[Tich diem] HTTP %d: %s\n", code, resp.c_str());
  beep(code == 200);
}

void doiDiem(const String& uid, const String& soDiem) {
  String resp;
  String body = "{\"uid\":\"" + uid + "\",\"so_diem\":" + soDiem + "}";
  int code = apiPost("/doi-diem", body, resp);
  Serial.printf("[Doi diem] HTTP %d: %s\n", code, resp.c_str());
  beep(code == 200);
}

// ─────────────── LỆNH TỪ SERIAL MONITOR (P/T/D <tham số>) ───────────────
void handleSerialCommand() {
  if (!Serial.available()) return;
  String line = Serial.readStringUntil('\n');
  line.trim();
  if (line.length() < 2) return;

  char cmd = toupper(line.charAt(0));
  String arg = line.substring(1); arg.trim();

  if (lastUid == "") { Serial.println("Hay quet 1 the truoc da."); return; }

  switch (cmd) {
    case 'P': phatThe(lastUid, arg); break;   // P 0901234567
    case 'T': tichDiem(lastUid, arg); break;  // T 50000
    case 'D': doiDiem(lastUid, arg); break;   // D 200
    default:  Serial.println("Lenh khong hieu. Dung: P <sdt> | T <so_tien> | D <so_diem>");
  }
}

// ─────────────── PHẢN HỒI (LED + còi) ───────────────
void beep(bool ok) {
  digitalWrite(LED_OK, ok ? HIGH : LOW);
  if (BUZZER >= 0) { digitalWrite(BUZZER, HIGH); delay(ok ? 80 : 250); digitalWrite(BUZZER, LOW); }
  if (ok) { delay(150); digitalWrite(LED_OK, LOW); }
}
