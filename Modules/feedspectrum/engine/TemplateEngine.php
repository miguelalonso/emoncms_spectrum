<?php

class TemplateEngine
{
    private $dir = "";

    /**
     * Constructor.
     *
     * @api
    */
    public function __construct($options)
    {

    }

    /**
     * Create feedspectrum
     *
     * @param integer $feedspectrumid The id of the feedspectrum to be created
    */
    public function create($feedspectrumid,$options)
    {
    
        return true; // if successful 
    }


    /**
     * Adds a data point to the feedspectrum
     *
     * @param integer $feedspectrumid The id of the feedspectrum to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function post($feedspectrumid,$time,$value)
    {
    
    }
    
    /**
     * Updates a data point in the feedspectrum
     *
     * @param integer $feedspectrumid The id of the feedspectrum to add to
     * @param integer $time The unix timestamp of the data point, in seconds
     * @param float $value The value of the data point
    */
    public function update($feedspectrumid,$time,$value)
    {
    
    }

    /**
     * Return the data for the given timerange
     *
     * @param integer $feedspectrumid The id of the feedspectrum to fetch from
     * @param integer $start The unix timestamp in ms of the start of the data range
     * @param integer $end The unix timestamp in ms of the end of the data range
     * @param integer $dp The number of data points to return (used by some engines)
    */
    public function get_data($feedspectrumid,$start,$end,$dp)
    {
        $data = array();

        // example of datapoint format
        $time = time() * 1000; // time in milliseconds
        $value = 123.4; 
        $data[] = array($time,$value);

        return $data;
    }

    /**
     * Get the last value from a feedspectrum
     *
     * @param integer $feedspectrumid The id of the feedspectrum
    */
    public function lastvalue($feedspectrumid)
    {
        // time returned as date (to be changed to unixtimestamp in future)
        return array('time'=>date("Y-n-j H:i:s",0), 'value'=>0);
    }
    
    public function export($feedspectrumid,$start)
    {
    
    }
    
    public function delete($feedspectrumid)
    {
    
    }
    
    public function get_feedspectrum_size($feedspectrumid)
    {
    
    }
    
    public function get_meta($feedspectrumid)
    {
    
    }
    
    public function csv_export($feedspectrumid,$start,$end,$outinterval)
    {
    
    }

}
