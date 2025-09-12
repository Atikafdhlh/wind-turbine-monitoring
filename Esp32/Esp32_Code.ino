#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>

// Konfigurasi WiFi
const char* ssid = "TA-BAS1"; // Ganti dengan SSID WiFi Anda
const char* password = "1234567890"; // Ganti dengan password WiFi Anda

// Alamat PHP server
String serverName = "https://ta-bas1.site/kirim_data.php";

// Pakai UART1 untuk komunikasi ke Arduino
HardwareSerial Tegangan(1); // UART1

void setup() {
  Serial.begin(9600); 
  Tegangan.begin(9600, SERIAL_8N1, 35, -1); 

  // Koneksi ke WiFi
  WiFi.begin(ssid, password);
  Serial.print("Menghubungkan ke WiFi...");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nTerkoneksi ke WiFi! IP Address: " + WiFi.localIP().toString());
}

void loop() {
  // Baca data dari UART
  String data_terima = "";
  if (Tegangan.available()) {
    data_terima = Tegangan.readStringUntil('\n');
    data_terima.trim();
  }

  if (data_terima != "") {
    Serial.print("Data diterima: ");
    Serial.println(data_terima); // Debugging: Tampilkan data mentah

    int c1 = data_terima.indexOf(',');
    int c2 = data_terima.indexOf(',', c1 + 1);
    int c3 = data_terima.indexOf(',', c2 + 1);
    int c4 = data_terima.length(); // Akhir string (setelah koma terakhir)

    if (c1 > 0 && c2 > c1 && c3 > c2) {
      String V1 = data_terima.substring(0, c1);
      String V2 = data_terima.substring(c1 + 1, c2);
      String daya = data_terima.substring(c2 + 1, c3);
      String arus_dc = data_terima.substring(c3 + 1, c4);

      // Debugging: Tampilkan nilai yang diparse
      Serial.print("Parsed - V1: "); Serial.print(V1);
      Serial.print(", V2: "); Serial.print(V2);
      Serial.print(", Daya: "); Serial.print(daya);
      Serial.print(", Arus DC: "); Serial.println(arus_dc);

      // Validasi data numerik
      if (V1.toFloat() >= 0 && V2.toFloat() >= 0 && daya.toFloat() >= 0 && arus_dc.toFloat() >= 0) {
        Serial.print("V1: "); Serial.print(V1);
        Serial.print(" V, V2: "); Serial.print(V2);
        Serial.print(" V, Daya: "); Serial.print(daya);
        Serial.print(" W, Arus DC: "); Serial.print(arus_dc);
        Serial.println(" A");

        if (WiFi.status() == WL_CONNECTED) {
          WiFiClientSecure client;
          client.setInsecure(); // Hanya untuk pengujian, tidak verifikasi sertifikat SSL
          HTTPClient http;

          // Mulai koneksi ke server
          Serial.println("Menghubungkan ke: " + serverName);
          http.begin(client, serverName);
          http.addHeader("Content-Type", "application/x-www-form-urlencoded");

          // Siapkan data untuk dikirim
          String httpRequestData = "tegangan_1=" + V1 + "&tegangan_2=" + V2 + "&daya=" + daya + "&arus=" + arus_dc;
          Serial.println("Mengirim data: " + httpRequestData);

          // Kirim POST request
          int httpResponseCode = http.POST(httpRequestData);

          if (httpResponseCode > 0) {
            String response = http.getString();
            Serial.println("Respon Server: " + response);
          } else {
            Serial.print("Error Kirim Data. Kode: ");
            Serial.println(httpResponseCode);
            Serial.println("Coba lagi dalam 5 detik...");
            delay(5000); // Retry setelah 5 detik jika gagal
            return; // Keluar dari loop untuk mencoba lagi
          }

          http.end();
        } else {
          Serial.println("WiFi tidak terkoneksi! Mencoba reconnect...");
          WiFi.reconnect();
          delay(5000); // Tunggu sebelum mencoba lagi
        }
      } else {
        Serial.println("Data tidak valid (nilai negatif atau bukan numerik)!");
      }
    } else {
      Serial.println("Format data salah! Harus ada 4 nilai: V1,V2,Daya,Arus DC");
    }
  } else {
    Serial.println("Tidak ada data diterima dari UART!");
  }

  delay(100); // Delay untuk stabilitas
}