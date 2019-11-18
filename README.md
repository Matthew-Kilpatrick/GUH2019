## Great Uni Hack 2019 Submission
This is the repository for a submission for the [2019 Great Uni Hack](http://greatunihack.com/), a "24-hour student-oriented hackathon" in Manchester.

This submission was for Airbus' challenge - to build a system for a ride hailing platform making use of their [CityAirbus](https://www.airbus.com/innovation/urban-air-mobility/vehicle-demonstrators/cityairbus.html) autonomous flying vehicles. The submission received 2nd place for this challenge.

## What it does
The system is mostly focused on a basic administrative interface for managing a vehicle fleet. A live map view shows the location of all takeoff points (blue), vehicle paths (black lines), and flying vehicle locations (green). New journeys can be scheduled. Vehicle status (charging / idle / flying) and details (speed, capacity, charge time, battery fly time) can be viewed for existing vehicles, and set for new vehicles. Vehicles can be enabled and disabled - while disabled, they will be excluded from being scheduled for future trips. Locations can be disabled, which will prevent future vehicles taking off and landing from that location (and hide from the location select on scheduling interfaces).

There is an additional basic customer interface, which allows customers to locate their nearest takeoff point, and will show the location of only their vehicle as it travels to their chosen take off point, and also as it travels from their desired start location to the end location.

For trips scheduled from either the customer or management side, the system will locate the vehicle which is able to reach the customer soonest, and send it to the customer location (if not already present). This takes into account time for the current journey to end, charge time after a journey, time to reach the customer, and time to charge when at the customer location.

Allows booking of a taxi from a take off point to a landing point, and system will find the taxi able to reach the customer soonest. Allows creation of new vehicles, disabling vehicles (for maintenance) so they can't be scheduled, disabling locations to prevent any new take off or landings at that location in case of an incident.

## Technologies used
- leaflet.js for displaying map tiles, and for showing points / lines on the map
- MapBox for map tiles
- PHP for assigning most suitable vehicle, and handling returning data in database to jQuery on webpages
- JavaScript (and quite a lot of jQuery) for real time updates to map / data on page
- MySQL database for trips, vehicles, and locations
- Bootstrap for form styling
- Composer for dependancy (not many) installation

## Demo
- Customer: https://guh2019.matthewkilpatrick.uk/User/
- Fleet management: https://guh2019.matthewkilpatrick.uk/map.html
