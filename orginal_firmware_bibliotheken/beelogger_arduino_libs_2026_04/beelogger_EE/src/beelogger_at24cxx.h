/*
* beelogger_at24cxx.h
*  based on
*
* File:   at24cxx.h
* Author: RandallR
*
* Created on March 26, 2012, 06:52 PM
*
* // https://forum.arduino.cc/index.php?topic=90594.0
*
* 09.08.2019 R.Schick
*  create beelogger_at24cxx.h
*  remove static declaration of functions
*  modify constructor
*
*/

#ifndef beelogger_AT24Cxx_h
#define beelogger_AT24Cxx_h


#define AT24Cxx_CTRL_ID_def 0x57 // default I2C address


#if defined(__AVR_ATmega328PB__) 
#define BUFFER_LENGTH 32 // = TWI_BUFFER_SIZE
#endif

class AT24Cxx
{
  // user-accessible "public" interface
  public:
    AT24Cxx(int addr_i2c);
    void begin(int addr_i2c);
	void end(void);
    
	uint8_t isPresent(void);      // check if the device is present
    uint16_t ReadMem(uint16_t iAddr, char Buf[], uint16_t iCnt);
    uint8_t WriteMem(uint16_t iAddr, uint8_t iVal);
    uint8_t WriteMem(uint16_t iAddr, const char *pBuf, uint16_t iCnt);

    uint16_t ReadStr(uint16_t iAddr, char Buf[], uint16_t iBufLen);
    uint8_t WriteStr(uint16_t iAddr, const char *pBuf);

  private:
    int _i2c_addr;
};
#endif
