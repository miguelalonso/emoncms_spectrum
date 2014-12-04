<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/visspectrum/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_left[] = array('name'=> dgettext($domain, "visspectrum"), 'path'=>"visspectrum/list" , 'session'=>"write", 'order' => 8 );