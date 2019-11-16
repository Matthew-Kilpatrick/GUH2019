<?php
    require 'base.php';
    function getDistance($latitude1, $longitude1, $latitude2, $longitude2) {
        $earth_radius = 6371;
        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;
        return $d;
    }

    function getLocation($id) {
        global $locations;
        foreach ($locations as $loc) {
            if ($loc['id'] == $id) {
                return $loc;
            }
        }
    }

    $startLocation = $_POST['from_location_id'];
    $endLocation = $_POST['to_location_id'];

    $locations = $_DB->get('locations');
    $vehicles = $_DB->get('vehicles');
    $vehicleTrips = array();
    $vehiclesAtLocation = array();
    $vehicleAvailabilityTimes = array();
    // Get current active trips
    foreach ($vehicles as $vehicle) {
        $_DB->where('vehicle_id', $vehicle['id'])->where('start_time', time(), '<=')
        ->where('end_time', time(), '>=');
        $trips = $_DB->get('trips');
        if (sizeof($trips) > 0) {
            if ($trips[0]['end_location_id'] == $startLocation) {
                // vehicle ends at this location
                $timeToAvailable = $trips[0]['end_time'] - time();
                // and needs charging...
                $timeToAvailable += $vehicle['charge_time'] * ((100 - $vehicle['charge']) / 100);
            } else {
                // vehicle ends elsewhere
                // Time to end current trip
                $timeToAvailable = $trips[0]['end_time'] - time();
                $timeToAvailable = time() - $trips[0]['end_time'];
                // Time to charge
                $timeToAvailable += $vehicle['charge_time'] * ((100 - $vehicle['charge']) / 100);
                // Time to travel to customer
                $customerLocation = getLocation($startLocation);
                $vehicleNextLocation = getLocation($vehicle['next_location_id']);
                $distance = getDistance(
                    $customerLocation['lat'], $customerLocation['lon'],
                    $vehicleNextLocation['lat'], $vehicleNextLocation['lon']
                );
                $timeOnJourney = 60 * ($distance / $vehicle['speed']); // km / kmph = hr * 60 = min
                $timeToAvailable += $timeOnJourney;
                // Time to charge at customer
                $chargeLostOnJourney = $timeOnJourney / $vehicle['charge_fly_time'];
                $timeToAvailable += $vehicle['charge_time'] * $chargeLostOnJourney;
            }
        } else if ($vehicle['next_location_id'] == $startLocation) {
            // Vehicle at current location
            $vehiclesAtLocation[] = $vehicle['id'];
            $timeToAvailable = 0;
            if ($vehicle['charge'] != 100) {
                $timeToAvailable = $vehicle['charge_time'] * ((100 - $vehicle['charge']) / 100);
            } else {
                $timeToAvailable = 0;
            }
        } else {
            // Vehicle is idle at another location
            // Charge time - account for whether charged since landing
            $timeToCharge = $vehicle['charge_time'] * ((100 - $vehicle['charge']) / 100);
            $_DB->where('vehicle_id', $vehicle['id'])->orderBy('end_time', 'desc');
            $lastTrip = $_DB->getOne('trips');
            if ($lastTrip['end_time'] + $timeToCharge < time()) {
                // Fully charged
                // Travel to customer
                $customerLocation = getLocation($startLocation);
                $vehicleNextLocation = getLocation($vehicle['next_location_id']);
                $distance = getDistance(
                    $customerLocation['lat'], $customerLocation['lon'],
                    $vehicleNextLocation['lat'], $vehicleNextLocation['lon']
                );
                $timeOnJourney = 60 * ($distance / $vehicle['speed']); // km / kmph = hr * 60 = min
                $timeToAvailable = $timeOnJourney;
                // Time to charge at customer
                $chargeLostOnJourney = $timeOnJourney / $vehicle['charge_fly_time'];
                $timeToAvailable += $vehicle['charge_time'] * $chargeLostOnJourney;
            } else {
                // Needs more charge
                // Remaining time to charge at current location
                $chargedAt = $lastTrip['end_time'] + $timeToCharge;
                $timeToChargedAt = $chargedAt - time();
                $timeToAvailable = $timeToChargedAt;
                // Travel to customer
                $customerLocation = getLocation($startLocation);
                $vehicleNextLocation = getLocation($vehicle['next_location_id']);
                $distance = getDistance(
                    $customerLocation['lat'], $customerLocation['lon'],
                    $vehicleNextLocation['lat'], $vehicleNextLocation['lon']
                );
                $timeOnJourney = 60 * ($distance / $vehicle['speed']); // km / kmph = hr * 60 = min
                $timeToAvailable += $timeOnJourney;
                // Charge at customer
                $chargeLostOnJourney = $timeOnJourney / $vehicle['charge_fly_time'];
                $timeToAvailable += $vehicle['charge_time'] * $chargeLostOnJourney;
            }
        }
        $vehicleAvailabilityTimes[$vehicle['id']] = $timeToAvailable;
    }
    echo json_encode($vehicleAvailabilityTimes);
    // Check if vehicle idle at location

    // VEHICLE SELECTED BY THIS STAGE
    // Update vehicle battery to what it will be after trip
    // Create trip(s) in database (one to get vehicle to user, one to get user to destination)
?>