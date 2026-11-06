/*
  beelogger_audio is  based on
  https://www.hackster.io/l-abeille-vie/l-abeille-vie-ed1e73

  Let explain how do we analyse sounds to deduct bees' behavior.
  Bees sounds are at very specific frequencies depending on their behavior. Bees buzzing is around 150Hz and 250 Hz. We analyse amplitude at special frequencies base on determined thresholds.
  To announce swarming, the queen sound is at 475Hz.
  Hornet's presence help us to deduct attacks : 90Hz and 180Hz.
  Queen's presence is indicated by a high frequency amplitude around 316Hz
  When bees are stressed, the sound level get louder.
  As a consequence we focused on bees buzzing specific frequencies (between 150Hz and 250Hz).
  First we take 256 sounds samples each (at 1212Hz frequency to respect Shannon theorem, leads to max 606Hz).
  With these parameters we do a 4.7Hz accuracy FFT.

  After the FFT, we have a 128 workable frequency datas (256 / 2 = 128 because of Shannon theorem).
  Then we identify the amplitude at our specific frequencies and compare them to the last amplitude previously measured.
  If there is a strong amplitude change (based on threshold), we can deduct a behaviour corresponding to the frequency.

  For example, before swarming the queen does tooting.

  For more details see "Analyse du comportement des abeilles.pdf" (it's in french).
  more information on swarmmnitor.com
*/


/**
  @brief Funktion  bee_FFT ()
  grab data from analog input, perform FFT to calculate peak
  get level value out of FFT data
  option: retry if frequency is below limit
  @param  none
  @return  values frequency and volume go into the Aux[] array
*/


#if beelogger_Audio   // Sensor Audio


#define Sound_signal_input_port  PA1  // the sound input

//--- Library ---//
#include <arduinoFFT.h>

// FFT definitions
#define SMPL_FREQ      1212    // sampling frequency, half of this is maximum detectable frequency
#define SAMPLES         256    // number of data samples for FFT

// Filter parameter
#define MEAS           3     // number (1,2,3) of data aquisitions to filter out false frequency 
#define FILTER_RANGE   5     // search freqencies inside range of 10 * Hz

#define LOW_Freq_RETRY 1     // retry if low frequency
#define LOW_FREQ      55.5   // below this frequency

#define SCALE_LVL    10.0    // scaling factor for level value


void bee_FFT() {
  //--- Variables ---//
  unsigned long microSeconds;
  unsigned long sampling_time;

  double vReal[SAMPLES];          // array FFT "real" data
  double vImag[SAMPLES];          // array FFT "imag" data
  double peak[MEAS], vol[MEAS];   // array of found peaks
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
        vReal[x] = analogRead(Sound_signal_input_port);
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
    vol[j] = peak_Vol;
    peak[j] = peak_Freq;
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
  Aux[2] = (float)peak_Vol;  //spare Temperature
  Aux[1] = (float)peak_Freq;    // rain sensor
  // Aux[0] baro pressure
  debugprintF("FFT peak freq.[Hz]: ");
  debugprintln(Aux[1]);
  debugprintF("  volume [dB?]: ");
  debugprintln(Aux[2]);
  debugflush();
}


/**
  @brief Funktion  chk_level ()
  calculate mean and variance of given data
  may be used for adjusting gain of preamp or signal amplitude
  @param  array of data captured
  @return  variance as an int 16
*/

int16_t chk_level(double *vReal) {
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
#else  // nothing to do
void bee_FFT() {}
#endif
