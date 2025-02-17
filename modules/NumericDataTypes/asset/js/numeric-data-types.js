var NumericDataTypes = {
    /**
     * Get an ISO 8601 datetime string given the datetime form elements.
     *
     * @param y Year input
     * @param m Month select
     * @param d Day input
     * @param h Hour input
     * @param mi Minute input
     * @param s Second input
     * @param o Offset input
     */
    getDateTime : function(y, m, d, h, mi, s, o) {
        var yearMatches = /^(-?)(\d+)$/.exec(y.val());
        var yearSign = yearMatches ? yearMatches[1] : null;
        var year = yearMatches ? yearMatches[2] : null;
        var month = m.val();
        var day = d.val();
        var hour = h.val();
        var minute = mi.val();
        var second = s.val();
        var offset = o.val();
        if (year && month && day && hour && minute && second && offset) {
            return `${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute.padStart(2, '0')}:${second.padStart(2, '0')}${offset}`;
        } else if (year && month && day && hour && minute && offset) {
            return `${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute.padStart(2, '0')}${offset}`;
        } else if (year && month && day && hour && offset) {
            return `${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}${offset}`;
        } else if (year && month && day && hour && minute && second) {
            return `${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute.padStart(2, '0')}:${second.padStart(2, '0')}`;
        } else if (year && month && day && hour && minute) {
            return `${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute.padStart(2, '0')}`;
        } else if (year && month && day && hour) {
            return `${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}`;
        } else if (year && month && day) {
            return `${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
        } else if (year && month) {
            return `${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}`;
        } else if (year) {
            return `${yearSign}${year.padStart(4, '0')}`;
        } else {
            return null; // must have year
        }
    },
    /**
     * Get the client's timezone offset.
     *
     * @return string
     */
    getLocalOffset : function() {
        var offset = new Date().getTimezoneOffset();
        var offsetSign = (offset <= 0) ? '+' : '-';
        var offsetHour = Math.abs(Math.floor(offset / 60));
        var offsetMinute = offset % 60;
        return `${offsetSign}${offsetHour.toString().padStart(2, '0')}:${offsetMinute.toString().padStart(2, '0')}`;
    },
    /**
     * Set a timestamp to a value.
     *
     * @param v Value input
     * @param y Year input
     * @param m Month select
     * @param d Day input
     * @param h Hour input
     * @param mi Minute input
     * @param s Second input
     * @param o Offset input
     */
    setTimestampValue : function(v, y, m, d, h, mi, s, o) {
        v.val(this.getDateTime(y, m, d, h, mi, s, o));
    },
    /**
     * Set an interval to a value.
     *
     * @param v Value input
     * @param yStart Year start input
     * @param mStart Month start select
     * @param dStart Day start input
     * @param hStart Hour start input
     * @param miStart Minute start input
     * @param sStart Second start input
     * @param oStart Offset start input
     * @param yEnd Year end input
     * @param mEnd Month end select
     * @param dEnd Day end input
     * @param hEnd Hour end input
     * @param miEnd Minute end input
     * @param sEnd Second end input
     * @param oEnd Offset end input
     */
    setIntervalValue : function(v, yStart, mStart, dStart, hStart, miStart, sStart, oStart, yEnd, mEnd, dEnd, hEnd, miEnd, sEnd, oEnd) {
        var start = this.getDateTime(yStart, mStart, dStart, hStart, miStart, sStart, oStart);
        var end = this.getDateTime(yEnd, mEnd, dEnd, hEnd, miEnd, sEnd, oEnd);
        if (start && end) {
            v.val(`${start}/${end}`);
        } else {
            v.val(null);
        }
    },
    /**
     * Set a duration to a value.
     *
     * @param v Value input
     * @param y Years input
     * @param m Months input
     * @param w Weeks input
     * @param d Days input
     * @param mi Minutes input
     * @param s Seconds input
     */
    setDurationValue : function(v, y, m, d, h, i, s) {
        var years = y.val();
        var months = m.val();
        var days = d.val();
        var hours = h.val();
        var minutes = i.val();
        var seconds = s.val();
        var value = '';
        if (years) {
            value = `${value}${years}Y`;
        }
        if (months) {
            value = `${value}${months}M`;
        }
        if (days) {
            value = `${value}${days}D`;
        }
        if (hours || minutes || seconds) {
            value = `${value}T`;
        }
        if (hours) {
            value = `${value}${hours}H`;
        }
        if (minutes) {
            value = `${value}${minutes}M`;
        }
        if (seconds) {
            value = `${value}${seconds}S`;
        }
        if (value) {
            value = `P${value}`;
        }
        v.val(value);
    },
    /**
     * Enable the timestamp controls.
     *
     * @param container
     */
    enableTimestamp : function(container) {
        var v = container.find('.numeric-datetime-value');
        var numericContainer = v.closest('.numeric-timestamp');
        if (numericContainer.hasClass('numeric-enabled')) {
            return; // Enable only once.
        }
        numericContainer.addClass('numeric-enabled')
        var y = container.find('.numeric-datetime-year');
        var m = container.find('.numeric-datetime-month');
        var d = container.find('.numeric-datetime-day');
        var h = container.find('.numeric-datetime-hour');
        var mi = container.find('.numeric-datetime-minute');
        var s = container.find('.numeric-datetime-second');
        var o = container.find('.numeric-datetime-offset');
        y.add(m).add(d).add(h).add(mi).add(s).add(o).on('input', function(e) {
            y[0].setCustomValidity('');
            NumericDataTypes.setTimestampValue(v, y, m, d, h, mi, s, o);
        });
        // Match against ISO 8601, allowing for reduced accuracy.
        var matches = /^(-?\d{4,})(-(\d{2}))?(-(\d{2}))?(T(\d{2}))?(:(\d{2}))?(:(\d{2}))?([+-]\d{2}:\d{2})?$/.exec(v.val());
        if (matches) {
            // Set existing date/time during initial load.
            y.val(parseInt(matches[1]));
            m.val(matches[3] ? parseInt(matches[3]) : null);
            d.val(matches[5] ? parseInt(matches[5]) : null);
            h.val(matches[7] ? parseInt(matches[7]) : null);
            mi.val(matches[9] ? parseInt(matches[9]) : null);
            s.val(matches[11] ? parseInt(matches[11]) : null);
            o.val(matches[12] ? matches[12] : null);
        } else if ('' !== v.val()) {
            var invalidValue = numericContainer.find('.invalid-value');
            y[0].setCustomValidity(invalidValue.data('customValidity'));
            invalidValue.text(invalidValue.data('invalidMessage').replace('%s', v.val()));
        }
        // By default, show time inputs only if there's an hour.
        var timeInputs = h.closest('.numeric-time-inputs');
        h.val() ? timeInputs.show() : timeInputs.hide();
    },
    /**
     * Enable the interval controls.
     *
     * @param container
     */
    enableInterval : function(container) {
        var v = container.find('.numeric-datetime-value');
        var numericContainer = v.closest('.numeric-interval');
        if (numericContainer.hasClass('numeric-enabled')) {
            return; // Enable only once.
        }
        numericContainer.addClass('numeric-enabled')
        var yStart = container.find('.numeric-interval-start .numeric-datetime-year');
        var mStart = container.find('.numeric-interval-start .numeric-datetime-month');
        var dStart = container.find('.numeric-interval-start .numeric-datetime-day');
        var hStart = container.find('.numeric-interval-start .numeric-datetime-hour');
        var miStart = container.find('.numeric-interval-start .numeric-datetime-minute');
        var sStart = container.find('.numeric-interval-start .numeric-datetime-second');
        var oStart = container.find('.numeric-interval-start .numeric-datetime-offset');
        var yEnd = container.find('.numeric-interval-end .numeric-datetime-year');
        var mEnd = container.find('.numeric-interval-end .numeric-datetime-month');
        var dEnd = container.find('.numeric-interval-end .numeric-datetime-day');
        var hEnd = container.find('.numeric-interval-end .numeric-datetime-hour');
        var miEnd = container.find('.numeric-interval-end .numeric-datetime-minute');
        var sEnd = container.find('.numeric-interval-end .numeric-datetime-second');
        var oEnd = container.find('.numeric-interval-end .numeric-datetime-offset');
        yStart.add(mStart).add(dStart).add(hStart).add(miStart).add(sStart).add(oStart).add(yEnd).add(mEnd).add(dEnd).add(hEnd).add(miEnd).add(sEnd).add(oEnd).on('input', function(e) {
            yStart[0].setCustomValidity('');
            NumericDataTypes.setIntervalValue(v, yStart, mStart, dStart, hStart, miStart, sStart, oStart, yEnd, mEnd, dEnd, hEnd, miEnd, sEnd, oEnd);
        });
        // Match against ISO 8601, allowing for reduced accuracy.
        var matches = /^(-?\d{4,})(-(\d{2}))?(-(\d{2}))?(T(\d{2}))?(:(\d{2}))?(:(\d{2}))?([+-]\d{2}:\d{2})?\/(-?\d{4,})(-(\d{2}))?(-(\d{2}))?(T(\d{2}))?(:(\d{2}))?(:(\d{2}))?([+-]\d{2}:\d{2})?$/.exec(v.val());
        if (matches) {
            // Set existing date/time during initial load.
            yStart.val(parseInt(matches[1]));
            mStart.val(matches[3] ? parseInt(matches[3]) : null);
            dStart.val(matches[5] ? parseInt(matches[5]) : null);
            hStart.val(matches[7] ? parseInt(matches[7]) : null);
            miStart.val(matches[9] ? parseInt(matches[9]) : null);
            sStart.val(matches[11] ? parseInt(matches[11]) : null);
            oStart.val(matches[12] ? matches[12] : null);
            yEnd.val(parseInt(matches[13]));
            mEnd.val(matches[15] ? parseInt(matches[15]) : null);
            dEnd.val(matches[17] ? parseInt(matches[17]) : null);
            hEnd.val(matches[19] ? parseInt(matches[19]) : null);
            miEnd.val(matches[21] ? parseInt(matches[21]) : null);
            sEnd.val(matches[23] ? parseInt(matches[23]) : null);
            oEnd.val(matches[24] ? matches[24] : null);
        } else if ('' !== v.val()) {
            var invalidValue = numericContainer.find('.invalid-value');
            yStart[0].setCustomValidity(invalidValue.data('customValidity'));
            invalidValue.text(invalidValue.data('invalidMessage').replace('%s', v.val()));
        }
        // By default, show time inputs only if there's an hour.
        var timeInputsStart = hStart.closest('.numeric-time-inputs');
        hStart.val() ? timeInputsStart.show() : timeInputsStart.hide();
        var timeInputsEnd = hEnd.closest('.numeric-time-inputs');
        hEnd.val() ? timeInputsEnd.show() : timeInputsEnd.hide();
    },
    /**
     * Enable the duration controls.
     *
     * @param container
     */
    enableDuration : function(container) {
        var v = container.find('.numeric-duration-value');
        var numericContainer = v.closest('.numeric-duration');
        if (numericContainer.hasClass('numeric-enabled')) {
            return; // Enable only once.
        }
        numericContainer.addClass('numeric-enabled')
        var y = container.find('.numeric-duration-years');
        var m = container.find('.numeric-duration-months');
        var d = container.find('.numeric-duration-days');
        var h = container.find('.numeric-duration-hours');
        var i = container.find('.numeric-duration-minutes');
        var s = container.find('.numeric-duration-seconds');
        y.add(m).add(d).add(h).add(i).add(s).on('input', function(e) {
            y[0].setCustomValidity('');
            NumericDataTypes.setDurationValue(v, y, m, d, h, i, s);
        });
        // Match against ISO 8601, allowing for reduced precision.
        var matches = /^P(\d+Y)?(\d+M)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$/.exec(v.val());
        if (matches) {
            // Set existing values during initial load.
            y.val(matches[1] ? parseInt(matches[1].slice(0, -1)) : null);
            m.val(matches[2] ? parseInt(matches[2].slice(0, -1)) : null);
            d.val(matches[3] ? parseInt(matches[3].slice(0, -1)) : null);
            h.val(matches[5] ? parseInt(matches[5].slice(0, -1)) : null);
            i.val(matches[6] ? parseInt(matches[6].slice(0, -1)) : null);
            s.val(matches[7] ? parseInt(matches[7].slice(0, -1)) : null);
        } else if ('' !== v.val()) {
            var invalidValue = numericContainer.find('.invalid-value');
            y[0].setCustomValidity(invalidValue.data('customValidity'));
            invalidValue.text(invalidValue.data('invalidMessage').replace('%s', v.val()));
        }
        // By default, show time inputs only if there's an hour.
        var timeInputs = h.closest('.numeric-time-inputs');
        h.val() ? timeInputs.show() : timeInputs.hide();
    },
    /**
     * Enable the integer control.
     *
     * @param container
     */
    enableInteger : function(container) {
        var v = container.find('.numeric-integer-value');
        var numericContainer = v.closest('.numeric-integer');
        if (numericContainer.hasClass('numeric-enabled')) {
            return; // Enable only once.
        }
        numericContainer.addClass('numeric-enabled');
        var int = container.find('.numeric-integer-integer');
        int.on('input', function(e) {
            int[0].setCustomValidity('');
            v.val(int.val());
        });
        if ($.isNumeric(v.val())) {
            int.val(v.val());
        } else if ('' !== v.val()) {
            var invalidValue = numericContainer.find('.invalid-value');
            int[0].setCustomValidity(invalidValue.data('customValidity'));
            invalidValue.text(invalidValue.data('invalidMessage').replace('%s', v.val()));
        }
    }
};

// Enable numeric controls when preparing values on the resource form.
$(document).on('o:prepare-value o:prepare-value-annotation', function(e, type, value) {
    if ('numeric:timestamp' === type) {
        NumericDataTypes.enableTimestamp(value);
    }
    if ('numeric:interval' === type) {
        NumericDataTypes.enableInterval(value);
    }
    if ('numeric:duration' === type) {
        NumericDataTypes.enableDuration(value);
    }
    if ('numeric:integer' === type) {
        NumericDataTypes.enableInteger(value);
    }
});

$(function() {
     // Automatically enable numeric controls that exist on the page.
    $(document).find('.numeric-timestamp:visible').each(function() {
        NumericDataTypes.enableTimestamp($(this));
    });
    $(document).find('.numeric-interval:visible').each(function() {
        NumericDataTypes.enableInterval($(this));
    });
    $(document).find('.numeric-duration:visible').each(function() {
        NumericDataTypes.enableDuration($(this));
    });
    $(document).find('.numeric-integer:visible').each(function() {
        NumericDataTypes.enableInteger($(this));
    });
    // Toggle visibility of time inputs.
    $(document).on('change', '.numeric-toggle-time input[type="checkbox"]', function() {
        $(this).closest('.numeric-datetime-inputs').find('.numeric-time-inputs').toggle();
    });
});
