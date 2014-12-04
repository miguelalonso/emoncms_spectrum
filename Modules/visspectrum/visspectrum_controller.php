<?php
  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visspectrumualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

  */

  // no direct access
  defined('EMONCMS_EXEC') or die(_('Restricted access'));

  function visspectrum_controller()
  {
    global $mysqli, $redis, $session, $route, $user, $feedspectrum_settings;

    $result = false;

    require "Modules/feedspectrum/feedspectrum_model.php";
    $feedspectrum = new feedspectrum($mysqli,$redis, $feedspectrum_settings);


    $visspectrumdir = "visspectrum/visualisations/";

    require "Modules/visspectrum/visspectrum_object.php";

    $write_apikey = ""; $read_apikey = "";
    if ($session['read']) $read_apikey = $user->get_apikey_read($session['userid']);
    if ($session['write']) $write_apikey = $user->get_apikey_write($session['userid']);

    if ($route->format =='html')
    {
        if ($route->action == 'list' && $session['write'])
        {

            $feedspectrumlist = $feedspectrum->get_user_feedspectrums($session['userid']);
            //print_r($feedspectrumlist) ;
            $result = view("Modules/visspectrum/visspectrum_main_view.php", array('user' => $user->get($session['userid']), 'feedspectrumlist'=>$feedspectrumlist, 'apikey'=>$read_apikey, 'visualisations'=>$visualisations));
        }

        // Auto - automatically selects visspectrumualisation based on datatype
        // and is used primarily for quick checking feedspectrums from the feedspectrums page.
        if ($route->action == "auto")
        {
            $feedspectrumid = intval(get('feedspectrumid'));
            $datatype = $feedspectrum->get_field($feedspectrumid,'datatype');
            if ($datatype == 0) $result = "feedspectrum type or authentication not valid";
            if ($datatype == 1) $route->action = 'rawdata';
        }

        while ($visspectrum = current($visualisations))
        {
            $visspectrumkey = key($visualisations);

            // If the visspectrumualisation has a set property called action
            // then override the visspectrumualisation key and use the set action instead
            if (isset($visspectrum['action'])) $visspectrumkey = $visspectrum['action'];

            if ($route->action == $visspectrumkey)
            {
                $array = array();
                $array['valid'] = true;

                if (isset($visspectrum['options']))
                {
                    foreach ($visspectrum['options'] as $option)
                    {
                        $key = $option[0]; $type = $option[1];
                        if (isset($option[2])) $default = $option[2]; else $default = "";

                        if ($type==0 || $type==1 || $type==2 || $type==3)
                        {
                            $feedspectrumid = (int) get($key);
                            if ($feedspectrumid) {
                              $f = $feedspectrum->get($feedspectrumid);
                              $array[$key] = $feedspectrumid;
                              $array[$key.'name'] = $f['name'];

                              if ($f['userid']!=$session['userid']) $array['valid'] = false;
                              if ($f['public']) $array['valid'] = true;
                            } else {
                              $array['valid'] = false;
                            }

                        }

                        // Boolean not used at the moment
                            if ($type==4)
                                if (get($key)==true || get($key)==false)
                                    $array[$key] = get($key); else $array[$key] = $default;
                            if ($type==5)
                                $array[$key] = preg_replace('/[^\w\s£$€¥]/','',get($key))?get($key):$default;
                            if ($type==6)
                                $array[$key] = str_replace(',', '.', floatval((get($key)?get($key):$default)));
                            if ($type==7)
                                $array[$key] = intval((get($key)?get($key):$default));

                            # we need to either urlescape the colour, or just scrub out invalid chars. I'm doing the second, since
                            # we can be fairly confident that colours are eiter a hex or a simple word (e.g. "blue" or such)
                            if ($key == "colour")
                                $array[$key] = preg_replace('/[^\dA-Za-z]/','',$array[$key]);
                    }
                }

                $array['apikey'] = $read_apikey;
                $array['write_apikey'] = $write_apikey;

                $result = view("Modules/".$visspectrumdir.$visspectrumkey.".php", $array);

                if ($array['valid'] == false) $result .= "<div style='position:absolute; top:0px; left:0px; background-color:rgba(240,240,240,0.5); width:100%; height:100%; text-align:center; padding-top:100px;'><h3>Authentication not valid</h3></div>";

            }
            next($visualisations);
        }
    }





    return array('content'=>$result);
  }
