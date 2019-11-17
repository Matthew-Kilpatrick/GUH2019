<?php
    require 'base.php';
    $nextLocationID = $_POST['next_location_id'];
    $capacity = $_POST['capacity'];
    $chargeTime = $_POST['charge_time'];
    $speed = $_POST['speed'];
    $chargeFlyTime = $_POST['charge_fly_time'];
    $_DB->insert('vehicles', [
        'next_location_id' => $nextLocationID,
        'capacity' => $capacity,
        'charge_time' => $chargeTime,
        'speed' => $speed,
        'charge_fly_time' => $chargeFlyTime,
        'enabled' => True
    ]);
?>