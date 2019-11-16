<?php
    require 'base.php';
    $startLocation = $_POST['from_location_id'];
    $endLocation = $_POST['to_location_id'];

    $locations = $_DB->get('locations');
    $vehicles = $_DB->get('vehicle');
    $vehicleTrips = array();
    foreach ($vehicles as $vehicle) {
        $_DB->where('vehicle_id', $vehicle['id'])->where('start_time', time(), '<=')
        ->where('end_time', time, '>=');
        $vehicleTrips[$vehicle['id']] = $_DB->get('trips')[0]; // ASSUMES ONLY ONE TRIP AT ONCE
        
    }
?>