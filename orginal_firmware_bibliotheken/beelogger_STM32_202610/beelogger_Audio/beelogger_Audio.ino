/*

   (C) 2022 R.Schick - beelogger.de

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.


  with STM32Fxyz mocrocontroller:

    http://github.com/stm32duino/wiki/wiki/Libraries
    STM32 Core Version 2.1.0
    https://stm32-base.org/boards/
    Bluepill STM32F103C8  64k / 128k
             STM32F103CB 128k
    Blackpill STM32F411CE  512k  (WeAct Blackpill V 2.0)
              STM32F401CC 256k

  Arduino IDE Config:
    Voreinstellungen zusätzliche Board verwalter URL:
    https://github.com/stm32duino/BoardManagerFiles/raw/main/package_stmicroelectronics_index.json


  this sketch also runs on standard Arduino ATmega328 16MHz


  thanks to:
  https://www.hackster.io/l-abeille-vie/l-abeille-vie-ed1e73

  For more details see "Analyse du comportement des abeilles.pdf"
  and   http://swarmonitor.com/

  use Serial-Monitor or Serial-Plotter to show results.
  an OnLine Tone Generator will do as a sound signal source,
  noisy enivornment or taping on the micro will also give data

*/




#ifdef ARDUINO_ARCH_AVR
#define Analog_input_port A3   // the sound input  A3 for Ardunio NANO 
#define Power_Pin 4

#else
#define Analog_input_port PA1  // the sound input  PA1 for STM32Fxyz
#define Power_Pin PB5
#endif



//--- Library ---//
#include <arduinoFFT.h>

// FFT definitions
#define SMPL_FREQ      1212  // sampling frequency, half of this is maximum detectable frequency
#define SAMPLES         128  // number of data samples for FFT

// Filter parameter
#define MEAS           3     // number of data aquisitions to filter out low frequency 
#define FILTER_RANGE   5     // filter measurements outside range of 10 * Hz

#define LOW_Freq_RETRY 1     // try to filter low frequency
#define LOW_FREQ      55.5   // below this frequency

#define SCALE_LVL    10.0    // scaling factor for peak level value




void setup() {
  digitalWrite(Power_Pin, HIGH);
  pinMode(Power_Pin, OUTPUT);
  digitalWrite(Power_Pin, HIGH);

  Serial.begin(9600);
  while (!Serial) {} // wait until serial ready
  Serial.println(" Vol. Freq. beelogger_Audio");
}

float Aux[3];
int p = 0;
void loop() {
  Aux[2] = (float) bee_FFT();
  if (Aux[2] > LOW_FREQ) { // ignore low frequencies in graph
    if (Aux[1] > SCALE_LVL) {
      Serial.print(Aux[1]);
      Serial.print(" ");
      Serial.println(Aux[2]);
    }
  }
  else {
#if 0
    if (p) {
      Serial.println("Vol. Freq. +"); p = 0;
    }
    else {
      Serial.println("Vol. Freq. #"); p = 1;
    }
#endif
  }
}


/**
  @brief Funktion  bee_FFT ()
  grab data from analog input, perform FFT to calculate peak
  get level value out of FFT data
  option: retry if frequency is below limit
  @param  none
  @return  frequency with highest level
*/


double bee_FFT() {
  //--- Variables ---//
  unsigned long microSeconds;
  unsigned long sampling_time;

  double vReal[SAMPLES];          // array FFT "real" data
  double vImag[SAMPLES];          // array FFT "imag" data
  double peak[MEAS], vol[MEAS];  // array of found peaks and level
  double peak_Freq, peak_Vol;
  int i_pk[MEAS];                 // integer of found peaks
  int i, j;

  arduinoFFT FFT = arduinoFFT(vReal, vImag, SAMPLES, SMPL_FREQ);

  sampling_time = round(1000000 * (1.0 / SMPL_FREQ));

  for (j = 0; j < MEAS; j++) { // three measurements to elimate spurious
    peak_Freq = 0.0;
#if LOW_Freq_RETRY
    i = 0;
    do {
#endif
      for (int x = 0; x < SAMPLES; x++) {
        microSeconds = micros();
        vReal[x] = analogRead(Analog_input_port);
        vImag[x] = 0;
        while (micros() < (microSeconds + sampling_time)) {}
      }
      FFT.Windowing(FFT_WIN_TYP_HAMMING, FFT_FORWARD);    // FFT.Windowing(FFT_WIN_TYP_HANN, FFT_FORWARD);
      FFT.Compute(FFT_FORWARD);
      FFT.DCRemoval();
      FFT.ComplexToMagnitude();
      FFT.MajorPeak(&peak_Freq, &peak_Vol);
#if LOW_Freq_RETRY
      i++;
    } while ((i < 2) && (peak_Freq < LOW_FREQ)); // retry twice
#endif
    peak[j] = peak_Freq;
    vol[j] = peak_Vol;
    i_pk[j] = (int) (peak_Freq / 10.0);
  }
  // find identical frequencies, i.e. peaks, eliminate spurious
  for (j = 0; j < (MEAS - 1); j++) {
    peak_Freq = 0.0;
    for (i = (j + 1); i < MEAS; i++) {
      if ( abs(i_pk[j] - i_pk[i]) < FILTER_RANGE)  {
        peak_Freq = peak[j];
        peak_Vol  = vol[j];
        j = MEAS;  // stop for "j"
        break;    // stop for "i"
      }
    }
  }
#if LOW_Freq_RETRY
  if (peak_Freq < LOW_FREQ) { //  check for other frequency in array
    for (j = 0; j < (MEAS - 1); j++) {
      if (peak[j] > LOW_FREQ) {
        peak_Freq = peak[j];
        peak_Vol  = vol[j];
        break;
      }
    }
  }
#endif
  peak_Vol = peak_Vol / SCALE_LVL;  // scale value;
  Aux[1] = (float)peak_Vol;
  Aux[2] = (float)peak_Freq;
  return (peak_Freq);
}


/**
  @brief Funktion  chk_level ()
  calculate mean and variance of given data
  may be used for adjusting gain of preamp or signal amplitude
  @param  array of data captured
  @return  variance as an int 16
*/

int16_t chk_level(double * vReal) {
  // calculate variance of data
  double mean_val = 0.0;
  double var = 0.0;
  for (int x = 0; x < SAMPLES; x++) {  // mean
    mean_val = mean_val + vReal[x];
  }
  mean_val = mean_val / SAMPLES;
  //Serial.print(mean); Serial.print(" ");
  for (int y = 0; y < SAMPLES; y++) {  // mean
    double diff = (vReal[y] - mean_val);
    var =  var + (diff * diff);
  }
  var = var / (SAMPLES - 1);
  //Serial.print(var); Serial.println(" ");
  return ((int16_t)(var));
}
