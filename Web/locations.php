<?php
    require 'base.php';
    header('Content-type: application/json');
    $response = [];
    if (!isset($_GET['include_disabled'])) {
        $_DB->where('enabled', True);
    }
    foreach ($_DB->get('locations') as $location) {
        $response[] = [
          'id' => $location['id'],
          'latitude' => $location['lat'],
          'longitude' => $location['lon'],
          'name' => $location['name'],
          'enabled' => $location['enabled']
        ];
    }
    echo json_encode($response);
?>
