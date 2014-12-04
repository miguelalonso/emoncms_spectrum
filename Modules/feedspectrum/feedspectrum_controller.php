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


function feedspectrum_controller()
{
    global $mysqli, $redis, $session, $route, $feedspectrum_settings;
    $result = false;


    include "Modules/feedspectrum/feedspectrum_model.php";
    $feedspectrum = new feedspectrum($mysqli,$redis,$feedspectrum_settings);



    if ($route->format == 'html')
    {

        if ($route->action == "list" && $session['write']) $result = view("Modules/feedspectrum/Views/feedspectrumlist_view.php",array());
        if ($route->action == "api" && $session['write']) $result = view("Modules/feedspectrum/Views/feedspectrumapi_view.php",array());
    }

    if ($route->format == 'json')
    {
        // Public actions available on public feedspectrums.
        if ($route->action == "list")
        {
            if (!isset($_GET['userid']) && $session['read']) $result = $feedspectrum->get_user_feedspectrums($session['userid']);
            if (isset($_GET['userid']) && $session['read'] && $_GET['userid'] == $session['userid']) $result = $feedspectrum->get_user_feedspectrums($session['userid']);
            if (isset($_GET['userid']) && $session['read'] && $_GET['userid'] != $session['userid']) $result = $feedspectrum->get_user_public_feedspectrums(get('userid'));
            if (isset($_GET['userid']) && !$session['read']) $result = $feedspectrum->get_user_public_feedspectrums(get('userid'));

        } elseif ($route->action == "getid" && $session['read']) {
            $result = $feedspectrum->get_id($session['userid'],get('name'));
        } elseif ($route->action == "create" && $session['write']) {
            $result = $feedspectrum->create($session['userid'],get('name'),get('datatype'),get('engine'),json_decode(get('options')));
        } elseif ($route->action == "updatesize" && $session['write']) {
            $result = $feedspectrum->update_user_feedspectrums_size($session['userid']);
        } elseif ($route->action == "checksize") {
            $result = $feedspectrum->check_user_feedspectrums_size($session['userid']);
        } else {
            $feedspectrumid = (int) get('id');
            // Actions that operate on a single existing feedspectrum that all use the feedspectrumid to select:
            // First we load the meta data for the feedspectrum that we want

            if ($feedspectrum->exist($feedspectrumid)) // if the feedspectrum exists
            {
                $f = $feedspectrum->get($feedspectrumid);
                // if public or belongs to user
                if ($f['public'] || ($session['userid']>0 && $f['userid']==$session['userid'] && $session['read']))
                {
                    if ($route->action == "value") $result = $feedspectrum->get_value($feedspectrumid);
                    if ($route->action == "timevalue") $result = $feedspectrum->get_timevalue_seconds($feedspectrumid);
                    if ($route->action == "get") $result = $feedspectrum->get_field($feedspectrumid,get('field')); // '/[^\w\s-]/'
                    if ($route->action == "aget") $result = $feedspectrum->get($feedspectrumid);

                    if ($route->action == 'histogram') $result = $feedspectrum->histogram_get_power_vs_kwh($feedspectrumid,get('start'),get('end'));
                    if ($route->action == 'kwhatpower') $result = $feedspectrum->histogram_get_kwhd_atpower($feedspectrumid,get('min'),get('max'));
                    if ($route->action == 'kwhatpowers') $result = $feedspectrum->histogram_get_kwhd_atpowers($feedspectrumid,get('points'));
                    if ($route->action == 'data') $result = $feedspectrum->get_data($feedspectrumid,get('start'),get('end'),get('dp'));
                    if ($route->action == 'average') $result = $feedspectrum->get_average($feedspectrumid,get('start'),get('end'),get('interval'));
                }

                // write session required
                if (isset($session['write']) && $session['write'] && $session['userid']>0 && $f['userid']==$session['userid'])
                {
                    // Storage engine agnostic
                    if ($route->action == 'set') $result = $feedspectrum->set_feedspectrum_fields($feedspectrumid,get('fields'));
                    if ($route->action == "insert") $result = $feedspectrum->insert_data($feedspectrumid,time(),get("time"),get("value"));
                    if ($route->action == "update") $result = $feedspectrum->update_data($feedspectrumid,time(),get("time"),get('value'));
                    if ($route->action == "delete") $result = $feedspectrum->delete($feedspectrumid);
                    if ($route->action == "getmeta") $result = $feedspectrum->get_meta($feedspectrumid);
                    
                    if ($route->action == "csvexport") $feedspectrum->csv_export($feedspectrumid,get('start'),get('end'),get('interval'));
                    
                    if ($f['engine']==Engine::TIMESTORE) {
                        if ($route->action == "export") $result = $feedspectrum->timestore_export($feedspectrumid,get('start'),get('layer'));
                        if ($route->action == "exportmeta") $result = $feedspectrum->timestore_export_meta($feedspectrumid);
                        if ($route->action == "scalerange") $result = $feedspectrum->timestore_scale_range($feedspectrumid,get('start'),get('end'),get('value'));
                    } elseif ($f['engine']==Engine::MYSQL) {
                        if ($route->action == "export") $result = $feedspectrum->mysqltimeseries_export($feedspectrumid,get('start'));
                        if ($route->action == "deletedatapoint") $result = $feedspectrum->mysqltimeseries_delete_data_point($feedspectrumid,get('feedspectrumtime'));
                        if ($route->action == "deletedatarange") $result = $feedspectrum->mysqltimeseries_delete_data_range($feedspectrumid,get('start'),get('end'));
                    } elseif ($f['engine']==Engine::PHPTIMESERIES) {
                        if ($route->action == "export") $result = $feedspectrum->phptimeseries_export($feedspectrumid,get('start'));
                    } elseif ($f['engine']==Engine::PHPFIWA) {
                        if ($route->action == "export") $result = $feedspectrum->phpfiwa_export($feedspectrumid,get('start'),get('layer'));
                    } elseif ($f['engine']==Engine::PHPFINA) {
                        if ($route->action == "export") $result = $feedspectrum->phpfina_export($feedspectrumid,get('start'));
                    }
                }
            }
            else
            {
                $result = array('success'=>false, 'message'=>'feedspectrum does not exist');
            }
        }
    }

    return array('content'=>$result);
}
