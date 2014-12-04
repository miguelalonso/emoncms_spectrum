<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/feedspectrum/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_left[] = array('name'=> dgettext($domain, "feedspectrums"), 'path'=>"feedspectrum/list" , 'session'=>"write", 'order' => 5 );