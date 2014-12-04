<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/dashboardspectrum/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_left[] = array('name'=> dgettext($domain, "dashboardspectrum"), 'path'=>"dashboardspectrum/view" , 'session'=>"write", 'order' => 8 );
