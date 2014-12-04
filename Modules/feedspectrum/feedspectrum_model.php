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
$max_file_size=10000000;

class feedspectrum
{
    private $mysqli;
    private $redis;
    public $engine;
    private $histogram;
    private $csvdownloadlimit_mb = 10;
    private $log;
    private $mqtt = false;
    
    // 5 years of daily data
    private $max_npoints_returned = 1825;

    const MAX_FILE_SIZE=10000000;

    public function __construct($mysqli,$redis,$settings)
    {        
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->log = new EmonLogger(__FILE__);
        
        // Load different storage engines
        require "Modules/feedspectrum/engine/MysqlTimeSeries.php";
        require "Modules/feedspectrum/engine/Timestore.php";
        require "Modules/feedspectrum/engine/PHPTimestore.php";
        require "Modules/feedspectrum/engine/Histogram.php";
        require "Modules/feedspectrum/engine/PHPTimeSeries.php";
        require "Modules/feedspectrum/engine/GraphiteTimeSeries.php";
        
        // Development engines
        require "Modules/feedspectrum/engine/PHPFina.php";
        require "Modules/feedspectrum/engine/PHPFiwa.php";
           
        // Backwards compatibility 
        if (!isset($settings)) $settings= array();
        if (!isset($settings['timestore'])) {
            global $timestore_adminkey; 
            $settings['timestore'] = array('adminkey'=>$timestore_adminkey);
        }
        if (!isset($settings['graphite'])) $settings['graphite'] = array('host'=>"", 'port'=>0);
        if (!isset($settings['phpfiwa'])) $settings['phpfiwa'] = array();
        if (!isset($settings['phpfina'])) $settings['phpfina'] = array();
        if (!isset($settings['phptimestore'])) $settings['phptimestore'] = array();
        if (!isset($settings['phptimeseries'])) $settings['phptimeseries'] = array();
              
        // Load engine instances to engine array to make selection below easier
        $this->engine = array();
        $this->engine[Engine::MYSQL] = new MysqlTimeSeries($mysqli);
        $this->engine[Engine::TIMESTORE] = new Timestore($settings['timestore']);
        $this->engine[Engine::PHPTIMESTORE] = new PHPTimestore($settings['phptimestore']);
        $this->engine[Engine::PHPTIMESERIES] = new PHPTimeSeries($settings['phptimeseries']);
        $this->engine[Engine::GRAPHITE] = new GraphiteTimeSeries($settings['graphite']);
        $this->engine[Engine::PHPFINA] = new PHPFina($settings['phpfina']);
        $this->engine[Engine::PHPFIWA] = new PHPFiwa($settings['phpfiwa']);
                
        $this->histogram = new Histogram($mysqli);
        
        if (isset($settings['csvdownloadlimit_mb'])) {
            $this->csvdownloadlimit_mb = $settings['csvdownloadlimit_mb']; 
        }
        
        if (isset($settings['max_npoints_returned'])) {
            $this->max_npoints_returned = $settings['max_npoints_returned'];
        }
        
        // Load MQTT if enabled
        // Publish value to MQTT topic, see: http://openenergymonitor.org/emon/node/5943
        global $mqtt_enabled;
        if (isset($mqtt_enabled) && $mqtt_enabled == true)
        {
            error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
            require('SAM/php_sam.php');
            $this->mqtt = new SAMConnection();
            $this->mqtt->connect(SAM_MQTT, array(SAM_HOST => '127.0.0.1', SAM_PORT => 1883));
        }
    }

    public function create($userid,$name,$datatype,$engine,$options_in)
    {
        $userid = (int) $userid;
        $name = preg_replace('/[^\w\s-:]/','',$name);
        $datatype = (int) $datatype;
        $engine = (int) $engine;
        
        // Histogram engine requires MYSQL
        if ($datatype==DataType::HISTOGRAM && $engine!=Engine::MYSQL) $engine = Engine::MYSQL;
        
        // If feedspectrum of given name by the user already exists
        $feedspectrumid = $this->get_id($userid,$name);
        if ($feedspectrumid!=0) return array('success'=>false, 'message'=>'feedspectrum already exists');

        $result = $this->mysqli->query("INSERT INTO feedspectrums (userid,name,datatype,public,engine) VALUES ('$userid','$name','$datatype',false,'$engine')");
        $feedspectrumid = $this->mysqli->insert_id;

        if ($feedspectrumid>0)
        {
            // Add the feedspectrum to redis
            if ($this->redis) {
                $this->redis->sAdd("user:feedspectrums:$userid", $feedspectrumid);
                $this->redis->hMSet("feedspectrum:$feedspectrumid",array(
                    'id'=>$feedspectrumid,
                    'userid'=>$userid,
                    'name'=>$name,
                    'datatype'=>$datatype,
                    'tag'=>'',
                    'public'=>false,
                    'size'=>0,
                    'engine'=>$engine
                ));
            }
            
            $options = array();
            if ($engine==Engine::TIMESTORE) $options['interval'] = (int) $options_in->interval;
            if ($engine==Engine::PHPTIMESTORE) $options['interval'] = (int) $options_in->interval;
            if ($engine==Engine::PHPFINA) $options['interval'] = (int) $options_in->interval;
            if ($engine==Engine::PHPFIWA) $options['interval'] = (int) $options_in->interval;
            
            $engineresult = false;
            if ($datatype==DataType::HISTOGRAM) {
                $engineresult = $this->histogram->create($feedspectrumid,$options);
            } else {
                $engineresult = $this->engine[$engine]->create($feedspectrumid,$options);
            }

            if ($engineresult == false)
            {
                $this->log->warn("feedspectrum model: failed to create feedspectrum model feedspectrumid=$feedspectrumid");
                // feedspectrum engine creation failed so we need to delete the meta entry for the feedspectrum
                
                $this->mysqli->query("DELETE FROM feedspectrums WHERE `id` = '$feedspectrumid'");

                if ($this->redis) {
                    $userid = $this->redis->hget("feedspectrum:$feedspectrumid",'userid');
                    $this->redis->del("feedspectrum:$feedspectrumid");
                    $this->redis->srem("user:feedspectrums:$userid",$feedspectrumid);
                }

                return array('success'=>false, 'message'=>"");
            }

            return array('success'=>true, 'feedspectrumid'=>$feedspectrumid, 'result'=>$engineresult);
        } else return array('success'=>false);
    }

    public function exist($id)
    {
        $feedspectrumexist = false;
        if ($this->redis)
        {
            
            if (!$this->redis->exists("feedspectrum:$id")) {
                if ($this->load_feedspectrum_to_redis($id))
                {
                    $feedspectrumexist = true;
                }
            } else {
                $feedspectrumexist = true;
            }
        }
        else 
        {
            $id = intval($id);
            $result = $this->mysqli->query("SELECT id FROM feedspectrums WHERE id = '$id'");
            if ($result->num_rows>0) $feedspectrumexist = true;
        }
        return $feedspectrumexist;
    }

    public function get_id($userid,$name)
    {
        $userid = intval($userid);
        $name = preg_replace('/[^\w\s-:]/','',$name);
        $result = $this->mysqli->query("SELECT id FROM feedspectrums WHERE userid = '$userid' AND name = '$name'");
        if ($result->num_rows>0) { $row = $result->fetch_array(); return $row['id']; } else return false;
    }
    /*

    User feedspectrum lists

    Returns a specified user's feedspectrumlist in different forms:
    get_user_feedspectrums: 	all the feedspectrums table data
    get_user_feedspectrum_ids: 	only the id's
    get_user_feedspectrum_names: 	id's and names

    */

    public function get_user_feedspectrums($userid)
    {
        $userid = (int) $userid;
        
        if ($this->redis) {
            $feedspectrums = $this->redis_get_user_feedspectrums($userid);
        } else {
            $feedspectrums = $this->mysql_get_user_feedspectrums($userid);
        }
        //print_r ($feedspectrums) ;
        return $feedspectrums;
    }
    
    public function get_user_public_feedspectrums($userid)
    {
        $feedspectrums = $this->get_user_feedspectrums($userid);
        $publicfeedspectrums = array();
        foreach ($feedspectrums as $feedspectrum) { if ($feedspectrum['public']) $publicfeedspectrums[] = $feedspectrum; }
        return $publicfeedspectrums;
    }
    
    public function redis_get_user_feedspectrums($userid)
    {
        $userid = (int) $userid;
        if (!$this->redis->exists("user:feedspectrums:$userid")) $this->load_to_redis($userid);
      
        $feedspectrums = array();
        $feedspectrumids = $this->redis->sMembers("user:feedspectrums:$userid");
        foreach ($feedspectrumids as $id)
        {
            $row = $this->redis->hGetAll("feedspectrum:$id");

            $lastvalue = $this->get_timevalue($id);
            $row['time'] = strtotime($lastvalue['time']);
            $row['value'] = $lastvalue['value'];
            $feedspectrums[] = $row;
        }
        
        return $feedspectrums;
    }
    
    public function mysql_get_user_feedspectrums($userid)
    {
        $userid = (int) $userid;
        $feedspectrums = array();
        $result = $this->mysqli->query("SELECT * FROM feedspectrums WHERE `userid` = '$userid'");
        while ($row = (array)$result->fetch_object())
        {
            $row['time'] = strtotime($row['time']);
            $feedspectrums[] = $row;
        }
        return $feedspectrums;
    }
    
    public function get_user_feedspectrum_ids($userid)
    {
        $userid = (int) $userid;
        if ($this->redis) {
            if (!$this->redis->exists("user:feedspectrums:$userid")) $this->load_to_redis($userid);
            $feedspectrumids = $this->redis->sMembers("user:feedspectrums:$userid");
        } else {
            $result = $this->mysqli->query("SELECT id FROM feedspectrums WHERE `userid` = '$userid'");
            $feedspectrumids = array();
            while ($row = $result->fetch_array()) $feedspectrumids[] = $row['id'];
        }
        return $feedspectrumids;
    }

    /*

    feedspectrums table GET public functions

    */

    public function get($id)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'feedspectrum does not exist');

        if ($this->redis) {
            // Get from redis cache
            $row = $this->redis->hGetAll("feedspectrum:$id");
            $lastvalue = $this->redis->hmget("feedspectrum:lastvalue:$id",array('time','value'));
            $row['time'] = $lastvalue['time'];
            $row['value'] = $lastvalue['value'];
        } else {
            // Get from mysql db
            $result = $this->mysqli->query("SELECT * FROM feedspectrums WHERE `id` = '$id'");
            $row = (array) $result->fetch_object();
            $row['time'] = strtotime($row['time']);
        }

        return $row;
    }

    public function get_field($id,$field)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'feedspectrum does not exist');

        if ($field!=NULL) // if the feedspectrum exists
        {
            $field = preg_replace('/[^\w\s-]/','',$field);
            
            if ($this->redis) {
                $val = $this->redis->hget("feedspectrum:$id",$field);
            } else {
                $result = $this->mysqli->query("SELECT `$field` FROM feedspectrums WHERE `id` = '$id'");
                $row = $result->fetch_array();
                $val = $row[0];
            }
            
            if ($val) return $val; else return 0;
        }
        else return array('success'=>false, 'message'=>'Missing field parameter');
    }

    public function get_timevalue($id)
    {
        $id = (int) $id;

        // Get the timevalue from redis if it exists
        if ($this->redis) 
        {
            if ($this->redis->exists("feedspectrum:lastvalue:$id"))
            {
                $lastvalue = $this->redis->hmget("feedspectrum:lastvalue:$id",array('time','value'));
            }
            else
            {
                // if it does not load it in to redis from the actual feedspectrum data.
                $lastvalue = $this->get_timevalue_from_data($id);
                $this->redis->hMset("feedspectrum:lastvalue:$id", array('value' => $lastvalue['value'], 'time' => $lastvalue['time']));
            }
        }
        else 
        {
            $result = $this->mysqli->query("SELECT time,value FROM feedspectrums WHERE `id` = '$id'");
            $row = $result->fetch_array();
            $lastvalue = array('time'=>$row['time'], 'value'=>$row['value']);
        }

        return $lastvalue;
    }

    public function get_timevalue_seconds($id)
    {
        $lastvalue = $this->get_timevalue($id);
        $lastvalue['time'] = strtotime($lastvalue['time']);
        return $lastvalue;
    }

    public function get_value($id)
    {
        $lastvalue = $this->get_timevalue($id);
        return $lastvalue['value'];
    }

    public function get_timevalue_from_data($feedspectrumid)
    {
        $feedspectrumid = (int) $feedspectrumid;
        if (!$this->exist($feedspectrumid)) return array('success'=>false, 'message'=>'feedspectrum does not exist');

        $engine = $this->get_engine($feedspectrumid);
        
        // Call to engine lastvalue method
        return $this->engine[$engine]->lastvalue($feedspectrumid);
    }

    /*

    feedspectrums table SET public functions

    */

    public function set_feedspectrum_fields($id,$fields)
    {
        $id = (int) $id;
        if (!$this->exist($id)) return array('success'=>false, 'message'=>'feedspectrum does not exist');

        $fields = json_decode(stripslashes($fields));

        $array = array();

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\w\s-:]/','',$fields->name)."'";
        if (isset($fields->tag)) $array[] = "`tag` = '".preg_replace('/[^\w\s-:]/','',$fields->tag)."'";
        if (isset($fields->public)) $array[] = "`public` = '".intval($fields->public)."'";

        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",",$array);
        $this->mysqli->query("UPDATE feedspectrums SET ".$fieldstr." WHERE `id` = '$id'");

        // Update redis
        if ($this->redis && isset($fields->name)) $this->redis->hset("feedspectrum:$id",'name',$fields->name);
        if ($this->redis && isset($fields->tag)) $this->redis->hset("feedspectrum:$id",'tag',$fields->tag);
        if ($this->redis && isset($fields->public)) $this->redis->hset("feedspectrum:$id",'public',$fields->public);

        if ($this->mysqli->affected_rows>0){
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }

    /*

    feedspectrum data public functions

    insert, update, get and specialist histogram public functions

    */

    public function insert_data($feedspectrumid,$updatetime,$feedspectrumtime,$value)
    {
        $feedspectrumid = (int) $feedspectrumid;
        if (!$this->exist($feedspectrumid)) return array('success'=>false, 'message'=>'feedspectrum does not exist');

        if ($feedspectrumtime == null) $feedspectrumtime = time();
        $updatetime = intval($updatetime);
        $feedspectrumtime = intval($feedspectrumtime);
  //ya es un array de datos no un float     // $value = floatval($value);

        $engine = $this->get_engine($feedspectrumid);
        
        // Call to engine post method
        $this->engine[$engine]->post($feedspectrumid,$feedspectrumtime,$value);


        $this->set_timevalue($feedspectrumid, $value, $updatetime);

        //Check feedspectrum event if event module is installed
        if (is_dir(realpath(dirname(__FILE__)).'/../event/')) {
            require_once(realpath(dirname(__FILE__)).'/../event/event_model.php');
            $event = new Event($this->mysqli,$this->redis);
            $event->check_feedspectrum_event($feedspectrumid,$updatetime,$feedspectrumtime,$value);
        }

        return $value;
    }

    public function update_data($feedspectrumid,$updatetime,$feedspectrumtime,$value)
    {
        $feedspectrumid = (int) $feedspectrumid;
        if (!$this->exist($feedspectrumid)) return array('success'=>false, 'message'=>'feedspectrum does not exist');

        if ($feedspectrumtime == null) $feedspectrumtime = time();
        $updatetime = intval($updatetime);
        $feedspectrumtime = intval($feedspectrumtime);
        $value = floatval($value);

        $engine = $this->get_engine($feedspectrumid);
        
        // Call to engine update method
        $value = $this->engine[$engine]->update($feedspectrumid,$feedspectrumtime,$value);
       
        // need to find a way to not update if value being updated is older than the last value
        // in the database, redis lastvalue is last update time rather than last datapoint time.
        // So maybe we need to store both in redis.

        $this->set_timevalue($feedspectrumid, $value, $updatetime);

        //Check feedspectrum event if event module is installed
        if (is_dir(realpath(dirname(__FILE__)).'/../event/')) {
            require_once(realpath(dirname(__FILE__)).'/../event/event_model.php');
            $event = new Event($this->mysqli,$this->redis);
            $event->check_feedspectrum_event($feedspectrumid,$updatetime,$feedspectrumtime,$value);
        }

        return $value;
    }

    public function get_data($feedspectrumid,$start,$end,$dp)
    {
        $feedspectrumid = (int) $feedspectrumid;
        if ($end == 0) $end = time()*1000;
                
        if (!$this->exist($feedspectrumid)) return array('success'=>false, 'message'=>'feedspectrum does not exist');
  
        $engine = $this->get_engine($feedspectrumid);
        
        // Call to engine get_data method
        $range = ($end - $start) * 0.001;
        if ($dp>$this->max_npoints_returned) $dp = $this->max_npoints_returned;
        if ($dp<1) $dp = 1;
        $outinterval = round($range / $dp);
        return $this->engine[$engine]->get_data($feedspectrumid,$start,$end,$outinterval);

    }

    public function get_average($feedspectrumid,$start,$end,$outinterval)
    {
        $feedspectrumid = (int) $feedspectrumid;
        if ($end == 0) $end = time()*1000;

        if (!$this->exist($feedspectrumid)) return array('success'=>false, 'message'=>'feedspectrum does not exist');

        $engine = $this->get_engine($feedspectrumid);

        // Call to engine get_average method
        if ($outinterval<1) $outinterval = 1;
        $range = ($end - $start) * 0.001;
        $npoints = ($range / $outinterval);
        //if ($npoints>$this->max_npoints_returned) $outinterval = round($range / $this->max_npoints_returned);
       // echo ("feedspectrum_model funcion get_average:\r\n");
        //echo ("feedspectrumid: $feedspectrumid\r\n");
        //echo ("start: $start\r\n");
        //echo ("end: $end\r\n");
        //echo ("outinterval: $outinterval\r\n");

        return $this->engine[$engine]->get_data($feedspectrumid,$start,$end,$outinterval);

    }
    
    public function csv_export($feedspectrumid,$start,$end,$outinterval)
    {
        $feedspectrumid = (int) $feedspectrumid;
        if (!$this->exist($feedspectrumid)) return array('success'=>false, 'message'=>'feedspectrum does not exist');

        $engine = $this->get_engine($feedspectrumid);
        
        // Download limit
        if($outinterval<1)$outinterval=1;
        $downloadsize = (($end - $start) / $outinterval) * 17; // 17 bytes per dp
        if ($downloadsize>($this->csvdownloadlimit_mb*1048576)) {
            $this->log->warn("feedspectrum model: csv download limit exeeded downloadsize=$downloadsize feedspectrumid=$feedspectrumid");
            return false;
        }

        // Call to engine get_average method
        return $this->engine[$engine]->csv_export($feedspectrumid,$start,$end,$outinterval);
    }


    public function delete($feedspectrumid)
    {
        $feedspectrumid = (int) $feedspectrumid;
        if (!$this->exist($feedspectrumid)) return array('success'=>false, 'message'=>'feedspectrum does not exist');

        $engine = $this->get_engine($feedspectrumid);
        
        // Call to engine delete method
        $this->engine[$engine]->delete($feedspectrumid);

        $this->mysqli->query("DELETE FROM feedspectrums WHERE `id` = '$feedspectrumid'");

        if ($this->redis) {
            $userid = $this->redis->hget("feedspectrum:$feedspectrumid",'userid');
            $this->redis->del("feedspectrum:$feedspectrumid");
            $this->redis->srem("user:feedspectrums:$userid",$feedspectrumid);
        }
    }

    public function update_user_feedspectrums_size($userid)
    {

        $userid = (int) $userid;
        $total = 0;
        $result = $this->mysqli->query("SELECT id,engine FROM feedspectrums WHERE `userid` = '$userid'");
        while ($row = $result->fetch_array())
        {
            $size = 0;
            $feedspectrumid = $row['id'];
            $engine = $row['engine'];
            
            // Call to engine get_feedspectrum_size method
            $size = $this->engine[$engine]->get_feedspectrum_size($feedspectrumid);
            
            $this->mysqli->query("UPDATE feedspectrums SET `size` = '$size' WHERE `id`= '$feedspectrumid'");
            if ($this->redis) $this->redis->hset("feedspectrum:$feedspectrumid",'size',$size);
            $total += $size;
        }
        return $total;
    }
    public function check_user_feedspectrums_size($userid)
    {
        $userid = (int) $userid;
        $total = 0;
        $result = $this->mysqli->query("SELECT id,engine,name,datatype,tag FROM feedspectrums WHERE `userid` = '$userid'");
        while ($row = $result->fetch_array())
        {
            $size = 0;
            $feedspectrumid = $row['id'];
            $engine = $row['engine'];
            $name = $row['name'];
            $datatype = $row['datatype'];
            $tag = $row['tag'];
            // Call to engine get_feedspectrum_size method
            $size = $this->engine[$engine]->get_feedspectrum_size($feedspectrumid);
            $this->mysqli->query("UPDATE feedspectrums SET `size` = '$size' WHERE `id`= '$feedspectrumid'");
            if ($this->redis) $this->redis->hset("feedspectrum:$feedspectrumid",'size',$size);
            $total += $size;
            $ppp=self::MAX_FILE_SIZE;
//echo("\r\n max: $ppp\r\n");
            if ($size>$ppp){
                //if ($size>14000){
                $fecha = '_'.date('Y_m_d_H_i_s');
                $name=$name.$fecha;
                $options_in='';
                echo($tag);
                $resultado=$this->create($userid,$name,$datatype,$engine,$options_in);
                $id=$resultado['feedspectrumid'];
                $this->mysqli->query("UPDATE feedspectrums SET `tag`='$tag' WHERE `id` = '$id'");
                $engine = $this->get_engine($feedspectrumid);
                // Call to engine check_size method
                if ($engine==2) $this->engine[$engine]->check_size($feedspectrumid,$id);
            }
        }
        return $total;
    }
    public function get_meta($feedspectrumid) {
        $feedspectrumid = (int) $feedspectrumid;
        $engine = $this->get_engine($feedspectrumid);
        return $this->engine[$engine]->get_meta($feedspectrumid);
    }
    
    // MysqlTimeSeries specific functions that we need to make available to the controller

    public function mysqltimeseries_export($feedspectrumid,$start) {
        return $this->engine[Engine::MYSQL]->export($feedspectrumid,$start);
    }

    public function mysqltimeseries_delete_data_point($feedspectrumid,$time) {
        return $this->engine[Engine::MYSQL]->delete_data_point($feedspectrumid,$time);
    }

    public function mysqltimeseries_delete_data_range($feedspectrumid,$start,$end) {
        return $this->engine[Engine::MYSQL]->delete_data_range($feedspectrumid,$start,$end);
    }

    // Timestore specific functions that we need to make available to the controller

    public function timestore_export($feedspectrumid,$start,$layer) {
        return $this->engine[Engine::TIMESTORE]->export($feedspectrumid,$start,$layer);
    }

    public function timestore_export_meta($feedspectrumid) {
        return $this->engine[Engine::TIMESTORE]->export_meta($feedspectrumid);
    }

    public function timestore_scale_range($feedspectrumid,$start,$end,$value) {
        return $this->engine[Engine::TIMESTORE]->scale_range($feedspectrumid,$start,$end,$value);
    }

    // Histogram specific functions that we need to make available to the controller

    public function histogram_get_power_vs_kwh($feedspectrumid,$start,$end) {
        return $this->histogram->get_power_vs_kwh($feedspectrumid,$start,$end);
    }

    public function histogram_get_kwhd_atpower($feedspectrumid, $min, $max) {
        return $this->histogram->get_kwhd_atpower($feedspectrumid, $min, $max);
    }

    public function histogram_get_kwhd_atpowers($feedspectrumid, $points) {
        return $this->histogram->get_kwhd_atpowers($feedspectrumid, $points);
    }

    // PHPTimeSeries specific functions that we need to make available to the controller

    public function phptimeseries_export($feedspectrumid,$start) {
        return $this->engine[Engine::PHPTIMESERIES]->export($feedspectrumid,$start);
    }
    
    public function phpfiwa_export($feedspectrumid,$start,$layer) {
        return $this->engine[Engine::PHPFIWA]->export($feedspectrumid,$start,$layer);
    }
    
    public function phpfina_export($feedspectrumid,$start) {
        return $this->engine[Engine::PHPFINA]->export($feedspectrumid,$start);
    }



    public function set_timevalue($feedspectrumid, $value, $time)
    {
        $updatetime = date("Y-n-j H:i:s", $time);
        if ($this->redis) {
            $this->redis->hMset("feedspectrum:lastvalue:$feedspectrumid", array('value' => $value, 'time' => $updatetime));
        } else {
            $this->mysqli->query("UPDATE feedspectrums SET `time` = '$updatetime', `value` = '$value' WHERE `id`= '$feedspectrumid'");
        }
        
        // Publish value to MQTT topic, see: http://openenergymonitor.org/emon/node/5943
        if ($this->mqtt) {
            $msg = new SAMMessage($value);
            $this->mqtt->send("topic://emoncms/feedspectrum/$feedspectrumid", $msg);
        }
    }
    
    private function get_engine($feedspectrumid)
    {
        if ($this->redis) {
            return $this->redis->hget("feedspectrum:$feedspectrumid",'engine');
        } else {
            $result = $this->mysqli->query("SELECT engine FROM feedspectrums WHERE `id` = '$feedspectrumid'");
            $row = $result->fetch_object();
            return $row->engine;
        }
    }

    public function load_to_redis($userid)
    {
        $result = $this->mysqli->query("SELECT id,userid,name,datatype,tag,public,size,engine FROM feedspectrums WHERE `userid` = '$userid'");
        while ($row = $result->fetch_object())
        {
            $this->redis->sAdd("user:feedspectrums:$userid", $row->id);
            $this->redis->hMSet("feedspectrum:$row->id",array(
            'id'=>$row->id,
            'userid'=>$row->userid,
            'name'=>$row->name,
            'datatype'=>$row->datatype,
            'tag'=>$row->tag,
            'public'=>$row->public,
            'size'=>$row->size,
            'engine'=>$row->engine
            ));
        }
    }

    public function load_feedspectrum_to_redis($id)
    {
        $result = $this->mysqli->query("SELECT id,userid,name,datatype,tag,public,size,engine FROM feedspectrums WHERE `id` = '$id'");
        $row = $result->fetch_object();

        if (!$row) {
            $this->log->warn("feedspectrum model: Requested feedspectrum does not exist feedspectrumid=$id");
            return false;
        }

        $this->redis->hMSet("feedspectrum:$row->id",array(
            'id'=>$row->id,
            'userid'=>$row->userid,
            'name'=>$row->name,
            'datatype'=>$row->datatype,
            'tag'=>$row->tag,
            'public'=>$row->public,
            'size'=>$row->size,
            'engine'=>$row->engine
        ));

        return true;
    }
}

