<?php
    require 'base.php';
    header('Content-type: application/json');
    $locations = $_DB->get('locations');
    $response = [];
    if (isset($_GET['ids'])) {
        $ids = explode(',', $_GET['ids']);
        if (sizeof($ids) == 1) {
            $_DB->where('start_time <= ? AND end_time >= ? AND (id=?)', [time(), time(), $ids[0]]);
        } else {
            // is 2
            $_DB->where('start_time <= ? AND end_time >= ? AND (id=? OR id=?)', [time(), time(), $ids['ids'][0], $ids['ids'][1]]);
        }
    }else {
        $_DB->where('start_time', time(), '<=')->where('end_time', time(), '>=');
    }
    foreach ($_DB->get('trips') as $trip) {
        foreach ($locations as $loc) {
            if ($loc['id'] == $trip['start_location_id']) {
                $startLoc = $loc;
            } else if ($loc['id'] == $trip['end_location_id']) {
                $endLoc = $loc;
            }
        }
        $response[] = [
            'id' => $trip['id'],
            'start_time' => $trip['start_time'],
            'end_time' => $trip['end_time'],
            'vehicle_id' => $trip['vehicle_id'],
            'start_location' => [
                'id' => $startLoc['id'],
                'latitude' => $startLoc['lat'],
                'longitude' => $startLoc['lon'],
                'name' => $startLoc['name']
            ],
            'end_location' => [
                'id' => $endLoc['id'],
                'latitude' => $endLoc['lat'],
                'longitude' => $endLoc['lon'],
                'name' => $endLoc['name']
            ]
        ];
    }
    echo json_encode($response);
?>
