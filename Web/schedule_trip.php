<?php
    require 'base.php';
    header('Content-type: application/json');
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

    function getVehicle($id) {
        global $vehicles;
        foreach ($vehicles as $vehicle) {
            if ($vehicle['id'] == $id) {
                return $vehicle;
            }
        }
    }

    $startLocation = $_POST['from_location_id'];
    $endLocation = $_POST['to_location_id'];

    $locations = $_DB->get('locations');
    $vehicles = $_DB->get('vehicles');
    $vehicleTrips = array();
    $vehicleTimes = array();
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
                // Set times in time array
                $vehicleTimes[$vehicle['id']] = [
                    'time_to_trip_end' => 0,
                    'time_to_charge_1' => 0,
                    'time_to_customer' => $trips[0]['end_time'] - time(),
                    'time_to_charge_2' => $vehicle['charge_time'] * ((100 - $vehicle['charge']) / 100)
                ];
            } else {
                // vehicle ends elsewhere
                // Time to end current trip
                $timeToAvailable = $trips[0]['end_time'] - time();
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
                // Set val in times array
                $vehicleTimes[$vehicle['id']] = [
                    'time_to_trip_end' => $trips[0]['end_time'] - time(),
                    'time_to_charge_1' => $vehicle['charge_time'] * ((100 - $vehicle['charge']) / 100),
                    'time_to_customer' => $timeOnJourney,
                    'time_to_charge_2' => $vehicle['charge_time'] * $chargeLostOnJourney
                ];
            }
        } else if ($vehicle['next_location_id'] == $startLocation) {
            // Vehicle at current location
            $vehiclesAtLocation[] = $vehicle['id'];
            $timeToAvailable = 0;
            if ($vehicle['charge'] != 100) {
                // Charges from last active time
                $timeToCharge = $vehicle['charge_time'] * ((100 - $vehicle['charge']) / 100) * 60;
                $_DB->where('vehicle_id', $vehicle['id'])->orderBy('end_time', 'desc');
                $lastTrip = $_DB->getOne('trips');
                $chargeDoneAt = $lastTrip['end_time'] + $timeToCharge;
                if ($chargeDoneAt < time()) {
                    // charge done
                    $vehicleTimes[$vehicle['id']] = [
                        'time_to_trip_end' => 0,
                        'time_to_charge_1' => 0,
                        'time_to_customer' => 0,
                        'time_to_charge_2' => 0
                    ];
                } else {
                    // charge incomplete
                    $timeToAvailable = $vehicle['charge_time'] * ((100 - $vehicle['charge']) / 100);
                    $vehicleTimes[$vehicle['id']] = [
                        'time_to_trip_end' => 0,
                        'time_to_charge_1' => 0,
                        'time_to_customer' => 0,
                        'time_to_charge_2' => $timeToCharge
                    ];
                }
            } else {
                $timeToAvailable = 0;
                $vehicleTimes[$vehicle['id']] = [
                    'time_to_trip_end' => 0,
                    'time_to_charge_1' => 0,
                    'time_to_customer' => 0,
                    'time_to_charge_2' => 0
                ];
            }
        } else {
            // Vehicle is idle at another location
            // Charge time - account for whether charged since landing
            $timeToCharge = $vehicle['charge_time'] * ((100 - $vehicle['charge']) / 100) * 60;
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
                // Populate array
                $vehicleTimes[$vehicle['id']] = [
                    'time_to_trip_end' => 0,
                    'time_to_charge_1' => 0,
                    'time_to_customer' => $timeOnJourney,
                    'time_to_charge_2' => $vehicle['charge_time'] * $chargeLostOnJourney
                ];
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
                // Populate array
                $vehicleTimes[$vehicle['id']] = [
                    'time_to_trip_end' => 0,
                    'time_to_charge_1' => $timeToChargedAt,
                    'time_to_customer' => $timeOnJourney,
                    'time_to_charge_2' => $vehicle['charge_time'] * $chargeLostOnJourney
                ];
            }
        }
        $vehicleAvailabilityTimes[$vehicle['id']] = $timeToAvailable;
        $startLoc = getLocation($startLocation);
        $endLoc = getLocation($endLocation);
        $distToDest = getDistance($startLoc['lat'], $startLoc['lon'], $endLoc['lat'], $endLoc['lon']);
        $vehicleTimes[$vehicle['id']]['time_to_destination'] = 60 * ($distToDest / $vehicle['speed']); // 60 * dist(km)/speed(km/h) = min
    }
    // Check if vehicle idle at location

    // VEHICLE SELECTED BY THIS STAGE
    // Update vehicle battery to what it will be after trip
    // Create trip(s) in database (one to get vehicle to user, one to get user to destination)
    $minTime = -1;
    $minVehicle = Null;
    foreach ($vehicleAvailabilityTimes as $vID=>$vTime) {
        if ($minTime == -1 || $vTime < $minTime) {
            $minTime = $vTime;
            $minVehicle = $vID;
        }
    }
    $vehicleData = $vehicleTimes[$minVehicle];
    $tripIDs = [];
    if ($vehicleData['time_to_trip_end'] > 0) {
        // Trip needed to reach customer
        $data = [
            'start_location_id' => $startLocation,
            'end_location_id' => $endLocation,
            'vehicle_id' => $minVehicle
        ];
        $data['start_time'] = time() + ($vehicleData['time_to_trip_end'] + $vehicleData['time_to_charge_1']) * 60;
        $data['end_time'] = $data['start_time'] + ($vehicleData['time_to_customer'] * 60);
        $tripIDs[] = $_DB->insert('trips', $data);
    }
    // Data for trip with customer
    $data = [
        'start_location_id' => $startLocation,
        'end_location_id' => $endLocation,
        'vehicle_id' => $minVehicle
    ];
    $data['start_time'] = time() + ($vehicleData['time_to_trip_end'] + $vehicleData['time_to_charge_1']
        + $vehicleData['time_to_customer'] + $vehicleData['time_to_charge_2']) * 60;
    $data['end_time'] = $data['start_time'] + ($vehicleData['time_to_destination'] * 60);
    $tripIDs[] = $_DB->insert('trips', $data);
    // TODO: UPDATE CHARGE & NEXT LOCATION OF VEHICLE
    $chargeAfterJourney = round(100 * (1 - $vehicleData['time_to_destination'] / $vehicle['charge_fly_time']));
    $_DB->where('id', $minVehicle)->update('vehicles', [
        'next_location_id' => $endLocation,
        'charge' => $chargeAfterJourney
    ]);
    echo json_encode(['message'=> 'The vehicle will arrive in ' . round($minTime) . ' minutes', 'ids' => implode($tripIDs, ','), 'd' => $vehicleAvailabilityTimes]);
?>