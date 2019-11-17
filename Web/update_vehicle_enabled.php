<?php
    require 'base.php';
    if ($_POST['enabled'] == 'enabled') {
        $enabled = True;
        echo 't';
    } else {
        $enabled = False;
        echo 'f';
    }
    $_DB->where('id', $_POST['vehicle_id'])->update('vehicles', ['enabled' => $enabled]);
?>