/**
  @brief Funktion display_mem_info()
  report freee memory 
   
  @param  none
  @return none
*/


#if (DBG_MEM_INFO == 0)               // Memory usage info
void display_mem_info(void) {}
#else

#define NUM_BLOCKS 100
#define BLOCK_SIZE 4
#include <malloc.h>

extern "C" char *sbrk(int i);
/* Use linker definition */
extern char _end;
extern char _sdata;
extern char _estack;
extern char _Min_Stack_Size;

static char *ramstart = &_sdata;
static char *ramend = &_estack;
static char *minSP = (char*)(ramend - &_Min_Stack_Size);

void display_mem_info(void) {
  char *heapend = (char*)sbrk(0);
  char * stack_ptr = (char*)__get_MSP();
  struct mallinfo mi = mallinfo();

  /*
    debugprint("Total non-mmapped bytes (arena):       ");  debugprintln(mi.arena);
    debugprint("# of free chunks (ordblks):            ");  debugprintln(mi.ordblks);
    debugprint("# of free fastbin blocks (smblks):     ");  debugprintln(mi.smblks);
    debugprint("# of mapped regions (hblks):           ");  debugprintln(mi.hblks);
    debugprint("Bytes in mapped regions (hblkhd):      ");  debugprintln(mi.hblkhd);
    debugprint("Max. total allocated space (usmblks):  ");  debugprintln(mi.usmblks);
    debugprint("Free bytes held in fastbins (fsmblks): ");  debugprintln(mi.fsmblks);
    debugprint("Total allocated space (uordblks):      ");  debugprintln(mi.uordblks);
    debugprint("Total free space (fordblks):           ");  debugprintln(mi.fordblks);
    debugprint("Topmost releasable block (keepcost):   ");  debugprintln(mi.keepcost);
  */
  //MONITOR_RX_TX.print("RAM Start at:       0x");  MONITOR_RX_TX.println((unsigned long)ramstart, HEX);
  //MONITOR_RX_TX.print("Data/Bss end at:    0x");  MONITOR_RX_TX.println((unsigned long)&_end, HEX);
  //MONITOR_RX_TX.print("Heap end at:        0x");  MONITOR_RX_TX.println((unsigned long)heapend, HEX);
  //MONITOR_RX_TX.print("Stack Ptr end at:   0x");  MONITOR_RX_TX.println((unsigned long)stack_ptr, HEX);
  //MONITOR_RX_TX.print("RAM End at:         0x");  MONITOR_RX_TX.println((unsigned long)ramend, HEX);

  MONITOR_RX_TX.print("Heap RAM Used:      ");  MONITOR_RX_TX.println(mi.uordblks);
  MONITOR_RX_TX.print("Program RAM Used:   ");  MONITOR_RX_TX.println(&_end - ramstart);
  MONITOR_RX_TX.print("Stack RAM Used:     ");  MONITOR_RX_TX.println(ramend - stack_ptr);
  MONITOR_RX_TX.print("Estimated Free RAM: ");  MONITOR_RX_TX.println(((stack_ptr < minSP) ? stack_ptr : minSP) - heapend + mi.fordblks);
}
#endif
