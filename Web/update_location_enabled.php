<?php

    require 'base.php';
    if ($_POST['enabled'] == 'enabled') {
        $enabled = True;
    } else {
        $enabled = False;
    }
    $_DB->where('id', $_POST['location_id'])->update('locations', ['enabled' => $enabled]);
