// DateTime Class is a modified  version supporting day-of-week.
// Original DateTime Class and its utility code is by Jean-Claude Wippler at JeeLabs
// http://jeelabs.net/projects/cafe/wiki/RTClib 
// Released under MIT License http://opensource.org/licenses/mit-license.php

#include "DateTime.h"
#include "Arduino.h"

#define EPOCH_TIME_OFF 946684800  // This is 2000-jan-01 00:00:00 in epoch time
#define SECONDS_PER_DAY 86400L


////////////////////////////////////////////////////////////////////////////////
// utility code, some of this could be exposed in the DateTime API if needed

static const uint8_t daysInMonth [] PROGMEM = { 31,28,31,30,31,30,31,31,30,31,30,31 };

// number of days since 2000/01/01, valid for 2001..2099
static uint16_t date2days(uint16_t y, uint8_t m, uint8_t d) {
    if (y >= 2000)
        y -= 2000;
    uint16_t days = d;
    for (uint8_t i = 1; i < m; ++i)
        days += pgm_read_byte(daysInMonth + i - 1);
    if (m > 2 && y % 4 == 0)
        ++days;
    return days + 365 * y + (y + 3) / 4 - 1;
}

static uint32_t time2long(uint16_t days, uint8_t h, uint8_t m, uint8_t s) {
    return ((days * 24L + h) * 60 + m) * 60 + s;
}

static uint8_t conv2d(const char* p) {
    uint8_t v = 0;
    if ('0' <= *p && *p <= '9')
        v = *p - '0';
    return 10 * v + *++p - '0';
}

////////////////////////////////////////////////////////////////////////////////
// DateTime implementation - ignores time zones and DST changes
// NOTE: also ignores leap seconds, see http://en.wikipedia.org/wiki/Leap_second

DateTime::DateTime (long t) {
    ss = t % 60;
    t /= 60;
    mm = t % 60;
    t /= 60;
    hh = t % 24;
    uint16_t days = t / 24;
    uint16_t leap;
    for (yOff = 0; ; ++yOff) {
        leap = yOff % 4 == 0;
        if (days < (365 + leap))
            break;
        days -= 365 + leap;
    }
    for (m = 1; ; ++m) {
        uint8_t daysPerMonth = pgm_read_byte(daysInMonth + m - 1);
        if (leap && m == 2)
            ++daysPerMonth;
        if (days < daysPerMonth)
            break;
        days -= daysPerMonth;
    }
    d = days + 1;
    wday = 0;         // FIXME This is not properly initialized
}

DateTime::DateTime (uint16_t year, uint8_t month, uint8_t date, uint8_t hour, uint8_t min, uint8_t sec, uint8_t wd) {
    if (year >= 2000)
        year -= 2000;
    yOff = year;
    m = month;
    d = date;
    hh = hour;
    mm = min;
    ss = sec;
    wday = wd;
}

// A convenient constructor for using "the compiler's time":
//   DateTime now (__DATE__, __TIME__);
// NOTE: using PSTR would further reduce the RAM footprint
DateTime::DateTime (const char* date, const char* time) {
    // sample input: date = "Dec 26 2009", time = "12:34:56"
    yOff = conv2d(date + 9);
    // Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec 
    switch (date[0]) {
        case 'J': m = date[1] == 'a' ? 1 : date[2] == 'n' ? 6 : 7; break;
        case 'F': m = 2; break;
        case 'A': m = date[2] == 'r' ? 4 : 8; break;
        case 'M': m = date[2] == 'r' ? 3 : 5; break;
        case 'S': m = 9; break;
        case 'O': m = 10; break;
        case 'N': m = 11; break;
        case 'D': m = 12; break;
    }
    d = conv2d(date + 4);
    hh = conv2d(time);
    mm = conv2d(time + 3);
    ss = conv2d(time + 6);
    wday = 0;         // FIXME This is not properly initialized
}

uint32_t DateTime::get() const {
    uint16_t days = date2days(yOff, m, d);
    return time2long(days, hh, mm, ss);
}

uint32_t DateTime::getEpoch() const
{
    return get() + EPOCH_TIME_OFF;
}

/*
 * Format an integer as %0*d
 *
 * Arduino formatting sucks.
 */
static void add0Nd(String &str, uint16_t val, size_t width)
{
    if (width >= 5 && val < 1000) {
	str += '0';
    }
    if (width >= 4 && val < 100) {
	str += '0';
    }
    if (width >= 3 && val < 100) {
	str += '0';
    }
    if (width >= 2 && val < 10) {
	str += '0';
    }
    str += val;
}
static inline void add04d(String &str, uint16_t val) { add0Nd(str, val, 4); }
static inline void add02d(String &str, uint16_t val) { add0Nd(str, val, 2); }

void DateTime::addToString(String & str) const
{
    add04d(str, year());
    str += '-';
    add02d(str, month());
    str += '-';
    add02d(str, date());
    str += ' ';
    add02d(str, hour());
    str += ':';
    add02d(str, minute());
    str += ':';
    add02d(str, second());
}
