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



function spectrum_controller()
{
    //return array('content'=>"ok");

    global $mysqli, $redis, $user, $session, $route, $max_node_id_limit, $feedspectrum_settings;

    // There are no actions in the spectrum module that can be performed with less than write privileges
    if (!$session['write']) return array('content'=>false);

    global $feedspectrum, $timestore_adminkey;
    $result = false;

    include "Modules/feedspectrum/feedspectrum_model.php";
    $feedspectrum = new feedspectrum($mysqli,$redis, $feedspectrum_settings);

    require "Modules/spectrum/spectrum_model.php"; // 295
    $spectrum = new spectrum($mysqli,$redis, $feedspectrum);

    require "Modules/spectrum/process_model.php"; // 886
    $process = new Process($mysqli,$spectrum,$feedspectrum);
    
    $process->set_timezone_offset($user->get_timezone($session['userid']));

    if ($route->format == 'html')
    {
        if ($route->action == 'api') $result = view("Modules/spectrum/Views/spectrum_api.php", array());
        if ($route->action == 'view') $result =  view("Modules/spectrum/Views/spectrum_view.php", array());
    }

    if ($route->format == 'json')
    {
        /*
        
        spectrum/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]

        The first number of each node is the time offset (see below).

        The second number is the node id, this is the unique identifer for the wireless node.

        All the numbers after the first two are data values. The first node here (node 16) has only one data value: 1137.

        Optional offset and time parameters allow the sender to set the time
        reference for the packets.
        If none is specified, it is assumed that the last packet just arrived.
        The time for the other packets is then calculated accordingly.

        offset=-10 means the time of each packet is relative to [now -10 s].
        time=1387730127 means the time of each packet is relative to 1387730127
        (number of seconds since 1970-01-01 00:00:00 UTC)

        Examples:
        
        // legacy mode: 4 is 0, 2 is -2 and 0 is -4 seconds to now.
          spectrum/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]
        // offset mode: -6 is -16 seconds to now.
          spectrum/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&offset=-10
        // time mode: -6 is 1387730121
          spectrum/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&time=1387730127
        // sentat (sent at) mode:
          spectrum/bulk.json?data=[[520,16,1137],[530,17,1437,3164],[535,19,1412,3077]]&offset=543

        See pull request for full discussion:
        https://github.com/emoncms/emoncms/pull/118
        */

        if ($route->action == 'bulk')
        {
            $valid = true;
            
            if (!isset($_GET['data']) && isset($_POST['data']))
            {
                $data = json_decode(post('data'));
            }
            else 
            {
                $data = json_decode(get('data'));
            }
            
            $userid = $session['userid'];
            $dbspectrums = $spectrum->get_spectrums($userid);

            $len = count($data);
            if ($len>0)
            {
                if (isset($data[$len-1][0]))
                {
                    // Sent at mode: spectrum/bulk.json?data=[[45,16,1137],[50,17,1437,3164],[55,19,1412,3077]]&sentat=60
                    if (isset($_GET['sentat'])) {
                        $time_ref = time() - (int) $_GET['sentat'];
                    }  elseif (isset($_POST['sentat'])) {
                        $time_ref = time() - (int) $_POST['sentat'];
                    } 
                    // Offset mode: spectrum/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&offset=-10
                    elseif (isset($_GET['offset'])) {
                        $time_ref = time() - (int) $_GET['offset'];
                    } elseif (isset($_POST['offset'])) {
                        $time_ref = time() - (int) $_POST['offset'];
                    }
                    // Time mode: spectrum/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&time=1387729425
                    elseif (isset($_GET['time'])) {
                        $time_ref = (int) $_GET['time'];
                    } elseif (isset($_POST['time'])) {
                        $time_ref = (int) $_POST['time'];
                    } 
                    // Legacy mode: spectrum/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]
                    else {
                        $time_ref = time() - (int) $data[$len-1][0];
                    }

                    foreach ($data as $item)
                    {
                        if (count($item)>2)
                        {
                            // check for correct time format
                            $itemtime = (int) $item[0];

                            $time = $time_ref + (int) $itemtime;
                            $nodeid = $item[1];

                            $spectrums = array();
                            $name = 1;
                            for ($i=2; $i<count($item); $i++)
                            {
                                $value = (float) $item[$i];
                                $spectrums[$name] = $value;
                                $name ++;
                            }

                            $tmp = array();
                            foreach ($spectrums as $name => $value)
                            {
                                if ($spectrum->check_node_id_valid($nodeid))
                                {
                                    if (!isset($dbspectrums[$nodeid][$name]))
                                    {
                                        $spectrumid = $spectrum->create_spectrum($userid, $nodeid, $name);
                                        $dbspectrums[$nodeid][$name] = true;
                                        $dbspectrums[$nodeid][$name] = array('id'=>$spectrumid, 'processList'=>'');
                                        $spectrum->set_timevalue($dbspectrums[$nodeid][$name]['id'],$time,$value);
                                    }
                                    else
                                    {
                                        $spectrumid = $dbspectrums[$nodeid][$name]['id'];
                                        $spectrum->set_timevalue($dbspectrums[$nodeid][$name]['id'],$time,$value);

                                        if ($dbspectrums[$nodeid][$name]['processList']) $tmp[] = array('value'=>$value,'processList'=>$dbspectrums[$nodeid][$name]['processList']);
                                    }
                                }
                                else
                                {

                                    $valid = false;
                                    $error = "NodeID must be a positive integer between 0 and ".$max_node_id_limit.", nodeid given was out of range";
                                }
                            }

                            foreach ($tmp as $i) $process->spectrum($time,$i['value'],$i['processList']);

                        }
                        else
                        {
                            $valid = false;
                            $error = "Format error, bulk item needs at least 3 values";
                        }
                    }
                }
                else
                {
                    $valid = false;
                    $error = "Format error, last item in bulk data does not contain any data";
                }
            }
            else
            {
                $valid = false;
                $error = "Format error, json string supplied is not valid";
            }

            if ($valid)
            {
                $result = 'ok';
            }
            else
            {
                $result = "Error: $error\n";
            }
        }

        // spectrum/post.json?node=10&json={power1:100,power2:200,power3:300}
        // spectrum/post.json?node=10&csv=100,200,300
//http://emoncms.sytes.net/emoncms_spectrum/spectrum/post.json?json={power:[numero%20de%20serie,%20tab%20por%20ejemplo][1.1,2.2,3.3,4.4,5.5][6.6,7.7,8.8,9.9,10.1]}
        if ($route->action == 'post')
        {
            $valid = true; $error = "";

            $nodeid = (int) get('node');

            $error = " old".$max_node_id_limit;

            if (!isset($max_node_id_limit))
            {
                $max_node_id_limit = 132;
            }

            $error .= " new".$max_node_id_limit;

            if (!$spectrum->check_node_id_valid($nodeid))
            {

                $valid = false;
                $error = "NodeID must be a positive integer between 0 and ".$max_node_id_limit.", nodeid given was out of range";
            }
            if (!$valid)
            {
                return array('content'=>"$error");
            }

            $nodeid = (int) $nodeid;

            if (isset($_GET['time'])) $time = (int) $_GET['time']; else $time = time();

            $data = array();

            $datain = false;
            // code below processes spectrum regardless of json or csv type
            if (isset($_GET['json'])) $datain = get('json');
            if (isset($_GET['csv'])) $datain = get('csv');
            if (isset($_GET['data'])) $datain = get('data');
            if (isset($_POST['data'])) $datain = post('data');

            if ($datain!="")
            {
                $json = preg_replace('/[^\w\s-.\[\]:,]/','',$datain);
                //$json = preg_replace('/[^\w\s-.:,]/','',$datain);

//echo "datain: $datain\r\n";
//echo "json: $json\r\n";
                //$csvi = 0;
                //for ($i=0; $i<count($datapairs); $i++)
                //{
                  //  $keyvalue = explode(':', $datapairs[$i]);
                $keyvalue = explode(':', $json);
//echo "keyvalue: $keyvalue[0]\r\n";

                    if (isset($keyvalue[1])) {
                        if ($keyvalue[0]=='') {$valid = false; $error = "Format error, json key missing or invalid character"; }
                        //if (!is_numeric($keyvalue[1])) {$valid = false; $error = "Format error, json value is not numeric"; }
                        $data[$keyvalue[0]] = $keyvalue[1];
                    } else {
                        //if (!is_numeric($keyvalue[0])) {$valid = false; $error = "Format error: csv value is not numeric"; }
                        //$data[$csvi+1] = (float) $keyvalue[0];
                        $data[1] = $keyvalue[0];
                        //$csvi ++;
                    }
                $data[$keyvalue[0]] = $keyvalue[1];
               // $data[1] = $keyvalue[0];
  //     print_r($data);

                $userid = $session['userid'];
                $dbspectrums = $spectrum->get_spectrums($userid);

                $tmp = array();
                foreach ($data as $name => $value) {
//echo "value : $value\r\n";
                    if (!isset($dbspectrums[$nodeid][$name])) {
                        $spectrumid = $spectrum->create_spectrum($userid, $nodeid, $name);
                        $dbspectrums[$nodeid][$name] = true;
                        $dbspectrums[$nodeid][$name] = array('id' => $spectrumid);
                        $spectrum->set_timevalue($dbspectrums[$nodeid][$name]['id'], $time, $value);
                    } else {
                        $spectrumid = $dbspectrums[$nodeid][$name]['id'];
                        $spectrum->set_timevalue($dbspectrums[$nodeid][$name]['id'], $time, $value);

                        if ($dbspectrums[$nodeid][$name]['processList']) $tmp[] = array('value' => $value, 'processList' => $dbspectrums[$nodeid][$name]['processList']);
                    }
                }

  //              echo "temp \r\n";
  //              print_r($tmp);
                foreach ($tmp as $i) $process->spectrum($time,$i['value'],$i['processList']);
            }
            else
            {
                $valid = false; $error = "Request contains no data via csv, json or data tag";
            }

            if ($valid)
                $result = "ok post\r\n";
            else
                $result = "Error: $error\n";
        }

        if ($route->action == "clean") $result = $spectrum->clean($session['userid']);
        if ($route->action == "list") $result = $spectrum->getlist($session['userid']);
        if ($route->action == "getspectrums") $result = $spectrum->get_spectrums($session['userid']);
        if ($route->action == "getallprocesses") $result = $process->get_process_list();

        if (isset($_GET['spectrumid']) && $spectrum->belongs_to_user($session['userid'],get("spectrumid")))
        {
            if ($route->action == "delete") $result = $spectrum->delete($session['userid'],get("spectrumid"));

            if ($route->action == 'set') $result = $spectrum->set_fields(get('spectrumid'),get('fields'));

            if ($route->action == "process")
            {
                if ($route->subaction == "add") $result = $spectrum->add_process($process,$session['userid'], get('spectrumid'), get('processid'), get('arg'), get('newfeedspectrumname'), get('newfeedspectruminterval'),get('engine'));
                if ($route->subaction == "list") $result = $spectrum->get_processlist(get("spectrumid"));
                if ($route->subaction == "delete") $result = $spectrum->delete_process(get("spectrumid"),get('processid'));
                if ($route->subaction == "move") $result = $spectrum->move_process(get("spectrumid"),get('processid'),get('moveby'));
                if ($route->subaction == "reset") $result = $spectrum->reset_process(get("spectrumid"));
            }           
        }
    }

    return array('content'=>$result);
}
