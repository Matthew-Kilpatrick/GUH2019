<?php
    require 'base.php';
    header('Content-type: application/json');
    $response = [];
    foreach ($_DB->get('vehicles') as $vehicle) {
        $_DB->where('vehicle_id', $vehicle['id'])->where('start_time', time(), '<=')->where('end_time', time(), '>=');
        $trips = $_DB->get('trips');
        $_DB->where('vehicle_id', $vehicle['id'])->orderBy('end_time', 'desc');
        $pastTrip = $_DB->getOne('trips');
        $chargeEnd = $pastTrip['end_time'] + (((100 - $vehicle['charge']) / 100) * $vehicle['charge_time']) * 60;
        $loc = $_DB->where('id', $vehicle['next_location_id'])->getOne('locations');
        if (sizeof($trips) > 0) {
            $status = 'In flight (to ' . $loc['name'] . ')';
        } else if ($chargeEnd >= time()) {
            $status = 'Charging (' . $loc['name'] . ')';
        } else {
            $status = 'Idle (' . $loc['name'] . ')';
        }
        $response[] = [
            'id' => $vehicle['id'],
            'charge_time' => $vehicle['charge_time'],
            'charge_fly_time' => $vehicle['charge_fly_time'],
            'speed' => $vehicle['speed'],
            'status' => $status,
            'capacity' => $vehicle['capacity'],
            'next_location' => $vehicle['next_location_id'],
            'enabled' => $vehicle['enabled']
        ];
    }
    echo json_encode($response);
?>
