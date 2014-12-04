<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Process
{
    private $mysqli;
    private $spectrum;
    private $feedspectrum;
    private $log;
    
    private $timezoneoffset = 0;

    public function __construct($mysqli,$spectrum,$feedspectrum)
    {
            $this->mysqli = $mysqli;
            $this->spectrum = $spectrum;
            $this->feedspectrum = $feedspectrum;
            $this->log = new EmonLogger(__FILE__);
    }
    
    public function set_timezone_offset($timezoneoffset)
    {
        $this->timezoneoffset = $timezoneoffset;
    }

    public function get_process_list()
    {
        
        $list = array();
        
        // Note on engine selection
        
        // The engines listed against each process are the recommended engines for each process - and is only used in the spectrum and node config GUI dropdown selectors
        // By using the create feedspectrum api and add spectrum process its possible to create any feedspectrum type and add any process to it - this needs to be improved so that only
        // feedspectrums capable of using a particular processor can be used.

        // description | Arg type | function | No. of datafields if creating feedspectrum | Datatype | Engine

        $list[1] = array(_("Log to feedspectrum"),ProcessArg::FEEDSPECTRUMID,"log_to_feedspectrum",1,DataType::REALTIME,"Main",array(Engine::PHPFIWA,Engine::PHPFINA,Engine::PHPTIMESERIES));
        $list[2] = array(_("x"),ProcessArg::VALUE,"scale",0,DataType::UNDEFINED,"Calibration");                           
        $list[3] = array(_("+"),ProcessArg::VALUE,"offset",0,DataType::UNDEFINED,"Calibration");                          
        $list[4] = array(_("Power to kWh"),ProcessArg::FEEDSPECTRUMID,"power_to_kwh",1,DataType::REALTIME,"Power",array(Engine::PHPFINA,Engine::PHPTIMESERIES));
        $list[5] = array(_("Power to kWh/d"),ProcessArg::FEEDSPECTRUMID,"power_to_kwhd",1,DataType::DAILY,"Power",array(Engine::PHPTIMESERIES));
        $list[6] = array(_("x spectrum"),ProcessArg::SPECTRUMID,"times_spectrum",0,DataType::UNDEFINED,"spectrum");
        $list[7] = array(_("spectrum on-time"),ProcessArg::FEEDSPECTRUMID,"spectrum_ontime",1,DataType::DAILY,"spectrum",array(Engine::PHPTIMESERIES));
        $list[8] = array(_("Wh increments to kWh/d"),ProcessArg::FEEDSPECTRUMID,"kwhinc_to_kwhd",1,DataType::DAILY,"Power",array(Engine::PHPTIMESERIES));
        $list[9] = array(_("kWh to kWh/d (OLD)"),ProcessArg::FEEDSPECTRUMID,"kwh_to_kwhd_old",1,DataType::DAILY,"Deleted",array(Engine::PHPTIMESERIES));       // need to remove
        $list[10] = array(_("update feedspectrum @time"),ProcessArg::FEEDSPECTRUMID,"update_feedspectrum_data",1,DataType::DAILY,"spectrum",array(Engine::MYSQL));
        $list[11] = array(_("+ spectrum"),ProcessArg::SPECTRUMID,"add_spectrum",0,DataType::UNDEFINED,"spectrum");
        $list[12] = array(_("/ spectrum"),ProcessArg::SPECTRUMID,"divide_spectrum",0,DataType::UNDEFINED,"spectrum");
        $list[13] = array(_("Phaseshift"),ProcessArg::VALUE,"phaseshift",0,DataType::UNDEFINED,"Deleted");                             // need to remove
        $list[14] = array(_("Accumulator"),ProcessArg::FEEDSPECTRUMID,"accumulator",1,DataType::REALTIME,"Misc",array(Engine::PHPFINA,Engine::PHPTIMESERIES));
        $list[15] = array(_("Rate of change"),ProcessArg::FEEDSPECTRUMID,"ratechange",1,DataType::REALTIME,"Misc",array(Engine::PHPFIWA,Engine::PHPFINA,Engine::PHPTIMESERIES));
        $list[16] = array(_("Histogram"),ProcessArg::FEEDSPECTRUMID,"histogram",2,DataType::HISTOGRAM,"Power",array(Engine::MYSQL));
        $list[17] = array(_("Daily Average"),ProcessArg::FEEDSPECTRUMID,"average",2,DataType::HISTOGRAM,"Deleted",array(Engine::PHPTIMESERIES));               // need to remove
        
        // to be reintroduced in post-processing
        $list[18] = array(_("Heat flux"),ProcessArg::FEEDSPECTRUMID,"heat_flux",1,DataType::REALTIME,"Deleted",array(Engine::PHPFIWA,Engine::PHPFINA,Engine::PHPTIMESERIES));
        
        // need to remove - result can be achieved with allow_positive & power_to_kwhd
        $list[19] = array(_("Power gained to kWh/d"),ProcessArg::FEEDSPECTRUMID,"power_acc_to_kwhd",1,DataType::DAILY,"Deleted",array(Engine::PHPTIMESERIES));
        
        // - look into implementation that doesnt need to store the ref feedspectrum
        $list[20] = array(_("Total pulse count to pulse increment"),ProcessArg::FEEDSPECTRUMID,"pulse_diff",1,DataType::REALTIME,"Pulse",array(Engine::PHPFINA,Engine::PHPTIMESERIES));
        
        // fixed works now with redis - look into state implementation without feedspectrum
        $list[21] = array(_("kWh to Power"),ProcessArg::FEEDSPECTRUMID,"kwh_to_power",1,DataType::REALTIME,"Power",array(Engine::PHPFIWA,Engine::PHPFINA,Engine::PHPTIMESERIES));
        
        $list[22] = array(_("- spectrum"),ProcessArg::SPECTRUMID,"subtract_spectrum",0,DataType::UNDEFINED,"spectrum");
        $list[23] = array(_("kWh to kWh/d"),ProcessArg::FEEDSPECTRUMID,"kwh_to_kwhd",2,DataType::DAILY,"Power",array(Engine::PHPTIMESERIES));                  // fixed works now with redis
        $list[24] = array(_("Allow positive"),ProcessArg::NONE,"allowpositive",0,DataType::UNDEFINED,"Limits");           
        $list[25] = array(_("Allow negative"),ProcessArg::NONE,"allownegative",0,DataType::UNDEFINED,"Limits");           
        $list[26] = array(_("Signed to unsigned"),ProcessArg::NONE,"signed2unsigned",0,DataType::UNDEFINED,"Misc");       
        $list[27] = array(_("Max value"),ProcessArg::FEEDSPECTRUMID,"max_value",1,DataType::DAILY,"Misc",array(Engine::PHPTIMESERIES));
        $list[28] = array(_("Min value"),ProcessArg::FEEDSPECTRUMID,"min_value",1,DataType::DAILY,"Misc",array(Engine::PHPTIMESERIES));
                              
        $list[29] = array(_(" + feedspectrum"),ProcessArg::FEEDSPECTRUMID,"add_feedspectrum",0,DataType::UNDEFINED,"feedspectrum");        // Klaus 24.2.2014
        $list[30] = array(_(" - feedspectrum"),ProcessArg::FEEDSPECTRUMID,"sub_feedspectrum",0,DataType::UNDEFINED,"feedspectrum");        // Klaus 24.2.
        $list[31] = array(_(" * feedspectrum"),ProcessArg::FEEDSPECTRUMID,"multiply_by_feedspectrum",0,DataType::UNDEFINED,"feedspectrum");
        $list[32] = array(_(" / feedspectrum"),ProcessArg::FEEDSPECTRUMID,"divide_by_feedspectrum",0,DataType::UNDEFINED,"feedspectrum");
        $list[33] = array(_("Reset to ZERO"),ProcessArg::NONE,"reset2zero",0,DataType::UNDEFINED,"Misc");
        
        $list[34] = array(_("Wh Accumulator"),ProcessArg::FEEDSPECTRUMID,"wh_accumulator",1,DataType::REALTIME,"Main",array(Engine::PHPFINA,Engine::PHPTIMESERIES));
        
        // $list[29] = array(_("save to spectrum"),ProcessArg::spectrumID,"save_to_spectrum",1,DataType::UNDEFINED);

        return $list;
    }

    public function spectrum($time, $value, $processList)
    {
        $this->log->info("spectrum() received time=$time, value=$value");
           
        $process_list = $this->get_process_list();
        $pairs = explode(",",$processList);
        foreach ($pairs as $pair)
        {
            $spectrumprocess = explode(":", $pair);                                // Divide into process id and arg
            $processid = (int) $spectrumprocess[0];                                    // Process id

            $arg = 0;
            if (isset($spectrumprocess[1]))
                $arg = $spectrumprocess[1];               // Can be value or feedspectrum id

            $process_public = $process_list[$processid][2];             // get process public function name
            //echo "valor spectrum:$$process_public:$arg";
            $value = $this->$process_public($arg,$time,$value);           // execute process public function
            //echo "valor spectrum:$$process_public:$arg";
            //$process_public es el nombre de la función, p.e. log_to_feedspectrum(($arg,$time,$value);
        }
        return $value;
    }

    public function get_process($id)
    {
        $list = $this->get_process_list();
        if ($id>0 && $id<count($list)+1) return $list[$id];
    }

    public function scale($arg, $time, $value)
    {
        return $value * $arg;
    }

    public function divide($arg, $time, $value)
    {
        return $value / $arg;
    }

    public function offset($arg, $time, $value)
    {
        return $value + $arg;
    }

    public function allowpositive($arg, $time, $value)
    {
        if ($value<0) $value = 0;
        return $value;
    }

    public function allownegative($arg, $time, $value)
    {
        if ($value>0) $value = 0;
        return $value;
    }

    public function reset2zero($arg, $time, $value)
     {
         $value = 0;
         return $value;
     }

    public function signed2unsigned($arg, $time, $value)
    {
        if($value < 0) $value = $value + 65536;
        return $value;
    }

    public function log_to_feedspectrum($id, $time, $value)
    {
        $this->feedspectrum->insert_data($id, $time, $time, $value);

        return $value;
    }

    //---------------------------------------------------------------------------------------
    // Times value by current value of another spectrum
    //---------------------------------------------------------------------------------------
    public function times_spectrum($id, $time, $value)
    {
        return $value * $this->spectrum->get_last_value($id);
    }

    public function divide_spectrum($id, $time, $value)
    {
        $lastval = $this->spectrum->get_last_value($id);
        if($lastval > 0){
            return $value / $lastval;
        } else {
            return null; // should this be null for a divide by zero?
        }
    }
    
	public function update_feedspectrum_data($id, $time, $value)
	{
		$time = mktime(0, 0, 0, date("m",$time), date("d",$time), date("Y",$time));

		$feedspectrumname = "feedspectrum_".trim($id)."";
		$result = $this->mysqli->query("SELECT * FROM $feedspectrumname WHERE `time` = '$time'");
		$row = $result->fetch_array();

		if (!$row)
		{
			$this->mysqli->query("INSERT INTO $feedspectrumname (time,data) VALUES ('$time','$value')");
		}
		else
		{
			$this->mysqli->query("UPDATE $feedspectrumname SET data = '$value' WHERE `time` = '$time'");
		}
		return $value;
	} 

    public function add_spectrum($id, $time, $value)
    {
        return $value + $this->spectrum->get_last_value($id);
    }

    public function subtract_spectrum($id, $time, $value)
    {
        return $value - $this->spectrum->get_last_value($id);
    }

    //---------------------------------------------------------------------------------------
    // Power to kwh
    //---------------------------------------------------------------------------------------
    public function power_to_kwh($feedspectrumid, $time_now, $value)
    {
        $new_kwh = 0;

        // Get last value
        $last = $this->feedspectrum->get_timevalue($feedspectrumid);

        $last['time'] = strtotime($last['time']);
        if (!isset($last['value'])) $last['value'] = 0;
        $last_kwh = $last['value']*1;
        $last_time = $last['time']*1;

        // only update if last datapoint was less than 2 hour old
        // this is to reduce the effect of monitor down time on creating
        // often large kwh readings.
        if ($last_time && (time()-$last_time)<7200)
        {
            // kWh calculation
            $time_elapsed = ($time_now - $last_time);
            $kwh_inc = ($time_elapsed * $value) / 3600000.0;
            $new_kwh = $last_kwh + $kwh_inc;
        } else {
            // in the event that redis is flushed the last time will
            // likely be > 7200s ago and so kwh inc is not calculated
            // rather than enter 0 we enter the last value
            $new_kwh = $last_kwh;
        }

        $this->feedspectrum->insert_data($feedspectrumid, $time_now, $time_now, $new_kwh);

        return $value;
    }

    public function power_to_kwhd($feedspectrumid, $time_now, $value)
    {
        $new_kwh = 0;

        // Get last value
        $last = $this->feedspectrum->get_timevalue($feedspectrumid);

        $last['time'] = strtotime($last['time']);
        if (!isset($last['value'])) $last['value'] = 0;
        $last_kwh = $last['value']*1;
        $last_time = $last['time']*1;
        
        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);    

        if ($last_time && ((time()-$last_time)<7200)) {
            // kWh calculation
            $time_elapsed = ($time_now - $last_time);
            $kwh_inc = ($time_elapsed * $value) / 3600000.0;
        } else {
            // in the event that redis is flushed the last time will
            // likely be > 7200s ago and so kwh inc is not calculated
            // rather than enter 0 we dont increase it
            $kwh_inc = 0;
        }
        
        if($last_slot == $current_slot) {
            $new_kwh = $last_kwh + $kwh_inc;
        } else {
            # We are working in a new slot (new day) so don't increment it with the data from yesterday
            $new_kwh = $kwh_inc;
        }
        
        $this->feedspectrum->update_data($feedspectrumid, $time_now, $current_slot, $new_kwh);

        return $value;
    }


    public function kwh_to_kwhd($feedspectrumid, $time_now, $value)
    {
        global $redis;
        if (!$redis) return $value; // return if redis is not available
        
        $currentkwhd = $this->feedspectrum->get_timevalue($feedspectrumid);
        $last_time = strtotime($currentkwhd['time']);
        
        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);

        if ($redis->exists("process:kwhtokwhd:$feedspectrumid")) {
            $lastkwhvalue = $redis->hmget("process:kwhtokwhd:$feedspectrumid",array('time','value'));
            $kwhinc = $value - $lastkwhvalue['value'];

            // kwh values should always be increasing so ignore ones that are less
            // assume they are errors
            if ($kwhinc<0) { $kwhinc = 0; $value = $lastkwhvalue['value']; }
            
            if($last_slot == $current_slot) {
                $new_kwh = $currentkwhd['value'] + $kwhinc;
            } else {
                $new_kwh = $kwhinc;
            }

            $this->feedspectrum->update_data($feedspectrumid, $time_now, $current_slot, $new_kwh);
        }
        
        $redis->hMset("process:kwhtokwhd:$feedspectrumid", array('time' => $time_now, 'value' => $value));

        return $value;
    }

    //---------------------------------------------------------------------------------------
    // spectrum on-time counter
    //---------------------------------------------------------------------------------------
    public function spectrum_ontime($feedspectrumid, $time_now, $value)
    {
        // Get last value
        $last = $this->feedspectrum->get_timevalue($feedspectrumid);
        $last_time = strtotime($last['time']);
        
        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);
        
        if (!isset($last['value'])) $last['value'] = 0;
        $ontime = $last['value'];
        $time_elapsed = 0;
        
        if ($value > 0 && (($time_now-$last_time)<7200))
        {
            $time_elapsed = $time_now - $last_time;
            $ontime += $time_elapsed;
        }
        
        if($last_slot != $current_slot) $ontime = $time_elapsed;

        $this->feedspectrum->update_data($feedspectrumid, $time_now, $current_slot, $ontime);

        return $value;
    }

    //--------------------------------------------------------------------------------
    // Display the rate of change for the current and last entry
    //--------------------------------------------------------------------------------
    public function ratechange($feedspectrumid, $time, $value)
    {
        global $redis;
        if (!$redis) return $value; // return if redis is not available
        
        if ($redis->exists("process:ratechange:$feedspectrumid")) {
            $lastvalue = $redis->hmget("process:ratechange:$feedspectrumid",array('time','value'));
            $ratechange = $value - $lastvalue['value'];
            $this->feedspectrum->insert_data($feedspectrumid, $time, $time, $ratechange);
        }
        $redis->hMset("process:ratechange:$feedspectrumid", array('time' => $time, 'value' => $value));

        // return $ratechange;
    }

    public function save_to_spectrum($spectrumid, $time, $value)
    {
        $this->spectrum->set_timevalue($spectrumid, $time, $value);
        return $value;
    }

    public function kwhinc_to_kwhd($feedspectrumid, $time_now, $value)
    {
        $last = $this->feedspectrum->get_timevalue($feedspectrumid);
        $last_time = strtotime($last['time']);
        
        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);
               
        $new_kwh = $last['value'] + ($value / 1000.0);
        if ($last_slot != $current_slot) $new_kwh = ($value / 1000.0);
        
        $this->feedspectrum->update_data($feedspectrumid, $time_now, $current_slot, $new_kwh);

        return $value;
    }

    public function accumulator($feedspectrumid, $time, $value)
    {
        $last = $this->feedspectrum->get_timevalue($feedspectrumid);
        $value = $last['value'] + $value;
        $this->feedspectrum->insert_data($feedspectrumid, $time, $time, $value);
        return $value;
    }
    /*
    public function accumulator_daily($feedspectrumid, $time_now, $value)
    {
        $last = $this->feedspectrum->get_timevalue($feedspectrumid);
        $value = $last['value'] + $value;
        $feedspectrumtime = $this->getstartday($time_now);
        $this->feedspectrum->update_data($feedspectrumid, $time_now, $feedspectrumtime, $value);
        return $value;
    }*/

    //---------------------------------------------------------------------------------
    // This method converts power to energy vs power (Histogram)
    //---------------------------------------------------------------------------------
    public function histogram($feedspectrumid, $time_now, $value)
    {

        ///return $value;

        $feedspectrumname = "feedspectrum_" . trim($feedspectrumid) . "";
        $new_kwh = 0;
        // Allocate power values into pots of varying sizes
        if ($value < 500) {
            $pot = 50;

        } elseif ($value < 2000) {
            $pot = 100;

        } else {
            $pot = 500;
        }

        $new_value = round($value / $pot, 0, PHP_ROUND_HALF_UP) * $pot;

        $time = $this->getstartday($time_now);

        // Get the last time
        $lastvalue = $this->feedspectrum->get_timevalue($feedspectrumid);
        $last_time = strtotime($lastvalue['time']);

        // kWh calculation
        if ((time()-$last_time)<7200) {
            $time_elapsed = ($time_now - $last_time);
            $kwh_inc = ($time_elapsed * $value) / 3600000;
        } else {
            $kwh_inc = 0;
        }

        // Get last value
        $result = $this->mysqli->query("SELECT * FROM $feedspectrumname WHERE time = '$time' AND data2 = '$new_value'");

        if (!$result) return $value;

        $last_row = $result->fetch_array();

        if (!$last_row)
        {
            $result = $this->mysqli->query("INSERT INTO $feedspectrumname (time,data,data2) VALUES ('$time','0.0','$new_value')");

            $this->feedspectrum->set_timevalue($feedspectrumid, $new_value, $time_now);
            $new_kwh = $kwh_inc;
        }
        else
        {
            $last_kwh = $last_row['data'];
            $new_kwh = $last_kwh + $kwh_inc;
        }

        // update kwhd feedspectrum
        $this->mysqli->query("UPDATE $feedspectrumname SET data = '$new_kwh' WHERE time = '$time' AND data2 = '$new_value'");

        $this->feedspectrum->set_timevalue($feedspectrumid, $new_value, $time_now);
        return $value;
    }

    public function pulse_diff($feedspectrumid,$time_now,$value)
    {
        $value = $this->signed2unsigned(false,false, $value);

        if($value>0)
        {
            $pulse_diff = 0;
            $last = $this->feedspectrum->get_timevalue($feedspectrumid);
            $last['time'] = strtotime($last['time']);
            if ($last['time']) {
                // Need to handle resets of the pulse value (and negative 2**15?)
                if ($value >= $last['value']) {
                    $pulse_diff = $value - $last['value'];
                } else {
                    $pulse_diff = $value;
                }
            }

            // Save to allow next difference calc.
            $this->feedspectrum->insert_data($feedspectrumid,$time_now,$time_now,$value);

            return $pulse_diff;
        }
    }

    public function kwh_to_power($feedspectrumid,$time,$value)
    {
        global $redis;
        if (!$redis) return $value; // return if redis is not available
        
        if ($redis->exists("process:kwhtopower:$feedspectrumid")) {
            $lastvalue = $redis->hmget("process:kwhtopower:$feedspectrumid",array('time','value'));
            $kwhinc = $value - $lastvalue['value'];
            $joules = $kwhinc * 3600000.0;
            $timeelapsed = ($time - $lastvalue['time']);
            $power = $joules / $timeelapsed;
            $this->feedspectrum->insert_data($feedspectrumid, $time, $time, $power);
        }
        $redis->hMset("process:kwhtopower:$feedspectrumid", array('time' => $time, 'value' => $value));

        return $power;
    }

    public function max_value($feedspectrumid, $time_now, $value)
    {
        // Get last values
        $last = $this->feedspectrum->get_timevalue($feedspectrumid);
        $last_val = $last['value'];
        $last_time = strtotime($last['time']);
        $feedspectrumtime = $this->getstartday($time_now);
        $time_check = $this->getstartday($last_time);

        // Runs on setup and midnight to reset current value - (otherwise db sets 0 as new max)
        if ($time_check != $feedspectrumtime) {
            $this->feedspectrum->insert_data($feedspectrumid, $time_now, $feedspectrumtime, $value);
        } else {
            if ($value > $last_val) $this->feedspectrum->update_data($feedspectrumid, $time_now, $feedspectrumtime, $value);
        }
        return $value;
    }

    public function min_value($feedspectrumid, $time_now, $value)
    {
        // Get last values
        $last = $this->feedspectrum->get_timevalue($feedspectrumid);
        $last_val = $last['value'];
        $last_time = strtotime($last['time']);
        $feedspectrumtime = $this->getstartday($time_now);
        $time_check = $this->getstartday($last_time);

        // Runs on setup and midnight to reset current value - (otherwise db sets 0 as new min)
        if ($time_check != $feedspectrumtime) {
            $this->feedspectrum->insert_data($feedspectrumid, $time_now, $feedspectrumtime, $value);
        } else {
            if ($value < $last_val) $this->feedspectrum->update_data($feedspectrumid, $time_now, $feedspectrumtime, $value);
        }
        return $value;

    }
    
    public function add_feedspectrum($feedspectrumid, $time, $value)
    {
        $last = $this->feedspectrum->get_timevalue($feedspectrumid);
        $value = $last['value'] + $value;
        return $value;
    }

    public function sub_feedspectrum($feedspectrumid, $time, $value)
    {
        $last  = $this->feedspectrum->get_timevalue($feedspectrumid);
        $myvar = $last['value'] *1;
        return $value - $myvar;
    }
    
    public function multiply_by_feedspectrum($feedspectrumid, $time, $value)
    {
        $last = $this->feedspectrum->get_timevalue($feedspectrumid);
        $value = $last['value'] * $value;
        return $value;
    }

   public function divide_by_feedspectrum($feedspectrumid, $time, $value)
    {
        $last  = $this->feedspectrum->get_timevalue($feedspectrumid);
        $myvar = $last['value'] *1;
        
        if ($myvar!=0) {
            return $value / $myvar;
        } else {
            return 0;
        }
    }
    
    public function wh_accumulator($feedspectrumid, $time, $value)
    {
        $max_power = 25000;
        $totalwh = $value;
        
        global $redis;
        if (!$redis) return $value; // return if redis is not available

        if ($redis->exists("process:whaccumulator:$feedspectrumid")) {
            $last_spectrum = $redis->hmget("process:whaccumulator:$feedspectrumid",array('time','value'));
    
            $last_feedspectrum  = $this->feedspectrum->get_timevalue($feedspectrumid);
            $totalwh = $last_feedspectrum['value'];
            
            $time_diff = $time - $last_feedspectrum['time'];
            $val_diff = $value - $last_spectrum['value'];
            
            $power = ($val_diff * 3600) / $time_diff;
            
            if ($val_diff>0 && $power<$max_power) $totalwh += $val_diff;
            
            $this->feedspectrum->insert_data($feedspectrumid, $time, $time, $totalwh);
            
        }
        $redis->hMset("process:whaccumulator:$feedspectrumid", array('time' => $time, 'value' => $value));

        return $totalwh;
    }

    // No longer used
    public function average($feedspectrumid, $time_now, $value) { return $value; } // needs re-implementing
    public function phaseshift($id, $time, $value) { return $value; }
    public function kwh_to_kwhd_old($feedspectrumid, $time_now, $value) { return $value; }
    public function power_acc_to_kwhd($feedspectrumid,$time_now,$value) { return $value; } // Process can now be achieved with allow positive process before power to kwhd

    //------------------------------------------------------------------------------------------------------
    // Calculate the energy used to heat up water based on the rate of change for the current and a previous temperature reading
    // See http://harizanov.com/2012/05/measuring-the-solar-yield/ for more info on how to use it
    //------------------------------------------------------------------------------------------------------
    public function heat_flux($feedspectrumid,$time_now,$value) { return $value; } // Removed to be reintroduced as a post-processing based visualisation calculated on the fly.
    
    // Get the start of the day
    private function getstartday($time_now)
    {
        // $midnight  = mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now)) - ($this->timezoneoffset * 3600);
        // $this->log->warn($midnight." ".date("Y-n-j H:i:s",$midnight)." [".$this->timezoneoffset."]");
        return mktime(0, 0, 0, date("m",$time_now), date("d",$time_now), date("Y",$time_now)) - ($this->timezoneoffset * 3600);
    }

}
