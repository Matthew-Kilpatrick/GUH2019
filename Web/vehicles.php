<?php
    require 'base.php';
    header('Content-type: application/json');
    $response = [];
    foreach ($_DB->get('vehicles') as $vehicle) {
        $response[] = [
            'id' => $vehicle['id'],
            'charge_time' => $vehicle['charge_time'],
            'capacity' => $vehicle['capacity']
        ];
    }
    echo json_encode($response);
?>
