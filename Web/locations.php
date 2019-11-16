<?php
    require 'base.php';
    header('Content-type: application/json');
    $response = [];
    foreach ($_DB->get('locations') as $location) {
        $response[] = [
          'id' => $location['id'],
          'latitude' => $location['lat'],
          'longitude' => $location['lon'],
          'name' => $location['name']
        ];
    }
    echo json_encode($response);
?>
