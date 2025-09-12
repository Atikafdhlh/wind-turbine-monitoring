#include <SoftwareSerial.h>

// Pin definitions
#define CURRENT_SENSOR_PIN A0  // Pin untuk ACS712 (sensor arus)
#define VOLTAGE_SENSOR_PIN_1 A1  // Pin untuk sensor tegangan 1
#define VOLTAGE_SENSOR_PIN_2 A2  // Pin untuk sensor tegangan 2

// Deklarasi SoftwareSerial untuk komunikasi dengan ESP32 (RX, TX)
SoftwareSerial KomunikasiESP(2, 3);

// Floats untuk sensor tegangan 1
float adc_voltage_1 = 0.0;
float in_voltage_1 = 0.0;

// Floats untuk sensor tegangan 2
float adc_voltage_2 = 0.0;
float in_voltage_2 = 0.0;

// Nilai resistor pada voltage divider (dalam ohm)
float R1 = 30000.0;  // 30k ohm
float R2 = 7500.0;   // 7.5k ohm

// Tegangan referensi Arduino (5V)
float ref_voltage = 5.0;

// Integer untuk nilai ADC
int adc_value_1 = 0;
int adc_value_2 = 0;

// Konstanta untuk ACS712 30A
const float offsetVoltage = 2.505; // Nilai offset dari kalibrasi
const float sensitivity = 0.066; // Sensitivitas ACS712 30A (66 mV/A)
const int sampleCount = 100; // Jumlah sampel untuk rata-rata

void setup() {
  // Inisialisasi Serial Monitor
  Serial.begin(9600);
  // Inisialisasi komunikasi dengan ESP32
  KomunikasiESP.begin(9600);
  
  Serial.println("DC Power Measurement");
  Serial.println("-------------------");
}

void loop() {
  // 1. Ukur arus menggunakan ACS712 30A
  long sum = 0;
  for (int i = 0; i < sampleCount; i++) {
    sum += analogRead(CURRENT_SENSOR_PIN);
    delay(2); // Jeda kecil antar pembacaan
  }
  float averageAnalog = sum / (float)sampleCount;

  // Konversi ke tegangan
  float voltage = (averageAnalog * 5.0) / 1023.0; // VCC = 5V
  // Hitung arus (A)
  float average_current = (voltage - offsetVoltage) / sensitivity;
  // Pastikan arus tidak negatif
  average_current = max(0, average_current);

  // 2. Ukur tegangan 1 menggunakan sensor tegangan 1
  adc_value_1 = analogRead(VOLTAGE_SENSOR_PIN_1);
  adc_voltage_1 = (adc_value_1 * ref_voltage) / 1024.0;
  in_voltage_1 = adc_voltage_1 / (R2 / (R1 + R2));

  // 3. Ukur tegangan 2 menggunakan sensor tegangan 2
  adc_value_2 = analogRead(VOLTAGE_SENSOR_PIN_2);
  adc_voltage_2 = (adc_value_2 * ref_voltage) / 1024.0;
  in_voltage_2 = adc_voltage_2 / (R2 / (R1 + R2));

  // 4. Hitung daya (P = V2 × I)
  float power = in_voltage_2 * average_current;

  // 5. Tampilkan hasil di Serial Monitor
  Serial.print("Voltage 1: ");
  Serial.print(in_voltage_1, 2);
  Serial.print(" V, Voltage 2: ");
  Serial.print(in_voltage_2, 2);
  Serial.print(" V, Current: ");
  Serial.print(average_current, 2);
  Serial.print(" A, Power: ");
  Serial.print(power, 2);
  Serial.println(" W");

  // 6. Kirim data ke ESP32 (format: V1,V2,Daya,Arus DC)
  KomunikasiESP.print(in_voltage_1, 2);
  KomunikasiESP.print(",");
  KomunikasiESP.print(in_voltage_2, 2);
  KomunikasiESP.print(",");
  KomunikasiESP.print(power, 2);
  KomunikasiESP.print(",");
  KomunikasiESP.println(average_current, 2);

  // Delay singkat
  delay(100);
}