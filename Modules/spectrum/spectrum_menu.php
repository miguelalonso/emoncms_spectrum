<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/spectrum/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_left[] = array('name'=> dgettext($domain, "Spectrum"), 'path'=>"spectrum/view" , 'session'=>"write", 'order' => 5 );
