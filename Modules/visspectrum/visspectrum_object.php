<?php

    /*
      0 - realtime or daily
      1 - realtime
      2 - daily
      3 - histogram
      4 - boolean (not used uncomment line 122)
      5 - text
      6 - float value
      7 - int value
    */

    $visualisations = array(
    

        // Hex colour EDC240 is the default color for flot. since we want existing setups to not change, we set the default value to it manually now,
        'rawdata'=> array('options'=>array(
            array('feedspectrumid',1),
            array('fill',7,0),
            array('colour',5,'EDC240'),
            array('units',5,'W'),
            array('dp',7,'1'),
            array('scale',6,'1'))
        ),
        'highcharts'=> array('options'=>array(
            array('feedspectrumid',1),
            array('fill',7,0),
            array('colour',5,'EDC240'),
            array('units',5,'W'),
            array('dp',7,'1'),
            array('scale',6,'1'))
        )
        

    );
