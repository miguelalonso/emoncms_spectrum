<?php

/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/

// dashboardspectrum/new						New dashboardspectrum
// dashboardspectrum/delete 				POST: id=			Delete dashboardspectrum
// dashboardspectrum/clone					POST: id=			Clone dashboardspectrum
// dashboardspectrum/thumb 					List dashboardspectrums
// dashboardspectrum/list         	List mode
// dashboardspectrum/view?id=1			View and run dashboardspectrum (id)
// dashboardspectrum/edit?id=1			Edit dashboardspectrum (id) with the draw editor
// dashboardspectrum/ckeditor?id=1	Edit dashboardspectrum (id) with the CKEditor
// dashboardspectrum/set POST				Set dashboardspectrum
// dashboardspectrum/setconf POST 	Set dashboardspectrum configuration

defined('EMONCMS_EXEC') or die('Restricted access');

function dashboardspectrum_controller()
{
    global $mysqli, $path, $session, $route, $user;

    require "Modules/dashboardspectrum/dashboardspectrum_model.php";
    $dashboardspectrum = new dashboardspectrum($mysqli);

    // id, userid, content, height, name, alias, description, main, public, published, showdescription

    $result = false; $submenu = '';

    if ($route->format == 'html')
    {
        if ($route->action == "list" && $session['write'])
        {
            $result = view("Modules/dashboardspectrum/Views/dashboardspectrum_list.php",array());

            $menu = $dashboardspectrum->build_menu($session['userid'],"view");
            $submenu = view("Modules/dashboardspectrum/Views/dashboardspectrum_menu.php", array('menu'=>$menu, 'type'=>"view"));
        }

        if ($route->action == "view" && $session['read'])
        {
            if ($route->subaction) $dash = $dashboardspectrum->get_from_alias($session['userid'],$route->subaction,false,false);
            elseif (isset($_GET['id'])) $dash = $dashboardspectrum->get($session['userid'],get('id'),false,false);
            else $dash = $dashboardspectrum->get_main($session['userid']);

            if ($dash) {
              $result = view("Modules/dashboardspectrum/Views/dashboardspectrum_view.php",array('dashboardspectrum'=>$dash));
            } else {
              $result = view("Modules/dashboardspectrum/Views/dashboardspectrum_list.php",array());
            }

            $menu = $dashboardspectrum->build_menu($session['userid'],"view");
            $submenu = view("Modules/dashboardspectrum/Views/dashboardspectrum_menu.php", array('id'=>$dash['id'], 'menu'=>$menu, 'type'=>"view"));
        }

        if ($route->action == "edit" && $session['write'])
        {
            if ($route->subaction) $dash = $dashboardspectrum->get_from_alias($session['userid'],$route->subaction,false,false);
            elseif (isset($_GET['id'])) $dash = $dashboardspectrum->get($session['userid'],get('id'),false,false);

            $result = view("Modules/dashboardspectrum/Views/dashboardspectrum_edit_view.php",array('dashboardspectrum'=>$dash));

            $result .= view("Modules/dashboardspectrum/Views/dashboardspectrum_config.php", array('dashboardspectrum'=>$dash));

            $menu = $dashboardspectrum->build_menu($session['userid'],"edit");
            $submenu = view("Modules/dashboardspectrum/Views/dashboardspectrum_menu.php", array('id'=>$dash['id'], 'menu'=>$menu, 'type'=>"edit"));
        }
    }

    if ($route->format == 'json')
    {
        if ($route->action=='list' && $session['write']) $result = $dashboardspectrum->get_list($session['userid'], false, false);

        if ($route->action=='set' && $session['write']) $result = $dashboardspectrum->set($session['userid'],get('id'),get('fields'));
        if ($route->action=='setcontent' && $session['write']) $result = $dashboardspectrum->set_content($session['userid'],post('id'),post('content'),post('height'));
        if ($route->action=='delete' && $session['write']) $result = $dashboardspectrum->delete(get('id'));

        if ($route->action=='create' && $session['write']) $result = $dashboardspectrum->create($session['userid']);
        if ($route->action=='clone' && $session['write']) $result = $dashboardspectrum->dashclone($session['userid'], get('id'));
    }

    // $result = $dashboardspectrum->get_main($session['userid'])

    return array('content'=>$result, 'submenu'=>$submenu);
}