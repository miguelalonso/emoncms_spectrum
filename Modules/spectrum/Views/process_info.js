var process_info = {

    '1':"<p><b>Log to feedspectrum:</b> This processor logs the current selected spectrum to a timeseries feedspectrum which can then be used to explore historic data. This is recommended for logging power, temperature, humidity, voltage and current data.</p><p><b>feedspectrum engine:</b> The fixed interval with averaging (PHPFIWA) feedspectrum engine is the recommended engine to use for logging power, temperature, humidity, voltage and current data. In addition to storing the full resolution data it produces a series of downsampled averaged layers which gives a more accurate representation of the data when viewing the data over a large time range.</p><p><b>feedspectrum interval:</b> When selecting the feedspectrum interval select an interval that is the same as, or longer than the update rate that is set in your monitoring equipment. Setting the interval rate to be shorter than the update rate of the equipment causes un-needed disk space to be used up.</p>",
    
    '2':"Scale spectrum by value given. This can be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the spectrum processing list",
    
    '3':"Offset spectrum by value given. This can again be useful for calibrating a particular variable on the web rather than by reprogramming hardware. Result is passed back for further processing by the next processor in the spectrum processing list",

    '4':"Convert a power value in Watts to a cumulative and ever rising kWh timeseries plot",

    '5':"Convert a power value in Watts to a feedspectrum that contains an entry for the total energy used each day (kWh/d)",

    '6':"This multiplies the current selected spectrum with another spectrum as selected from the dropdown menu. The result is passed back for further processing by the next processor in the spectrum processing list.",
    
    '12':"This divides the current selected spectrum with another spectrum as selected from the dropdown menu. The result is passed back for further processing by the next processor in the spectrum processing list.",
    
    '11':"This adds the selected spectrum from the dropdown menu to the current spectrum. The result is passed back for further processing by the next processor in the spectrum processing list.",
    
    '22':"This subtracts the selected spectrum from the dropdown menu from the current spectrum. The result is passed back for further processing by the next processor in the spectrum processing list.",
    
    '14':"Output feedspectrum accumulates by spectrum value",

    '15':"Output feedspectrum is the difference between the current value and the last",
    
    '7':"Counts the amount of time that an spectrum is high in each day and logs the result to a feedspectrum. Created for counting the number of hours a solar hot water pump is on each day",
    
    '34':"To be used in conjunction with an emontx sending total watt hours elapsed to emoncms. This processor ensures that when the emontx is reset the watt hour count in emoncms does not reset, it also checks filter's out spikes in energy use that are larger than a max power threshold set in the processor, assuming these are error's, the max power threshold is set to 25kW.<br><b>Requires redis installed to work</b>",
    
    '21':"Convert accumulating kWh to instantaneous power"
}


