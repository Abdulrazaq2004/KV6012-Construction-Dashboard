<?php
/**
 * weather.php
 * Handles all OpenWeather API calls and weather-based recommendations
 * for the construction project dashboard.
 */

/**
 * Fetches current weather data for a given location using the OpenWeather API.
 * Returns the decoded JSON response as an associative array.
 */
function getWeather($lat, $lng) {
    $apiKey = '7250dbafecc8320f7cc55ce1b8977b2e';
    $url = "http://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lng&appid=$apiKey&units=metric";
    $response = file_get_contents($url);
    
    if (!$response) {
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Checks current weather conditions against the project resources
 * and returns a list of recommendations for the site manager.
 * 
 * Rules from the brief:
 * - Wind > 20mph + crane on site = do not use crane
 * - Heavy/very heavy/extreme rain + diggers or dumper trucks = works may be delayed
 * -$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$
 * - THIS FUNCTION WAS ENHANCED USING AI BECAUSE MINE LOOK TRASH 😱😱😱😱😱😱😱😱😱
 * -$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$
 */ 
function getWeatherRecommendations($weather, $resources) {
    $recommendations = [];
    
    // Convert wind speed from metres per second to miles per hour
    $windSpeedMph = $weather['wind']['speed'] * 2.237;
    
    // Get the weather condition ID from the API response
    // Full list of IDs: https://openweathermap.org/weather-conditions
    $weatherId = $weather['weather'][0]['id'];
    
    // Get list of resource types on this project
    $resourceTypes = array_column($resources, 'Resource_Type');
    
    // Rule 1: High wind warning for crane operations
    $hasCrane = in_array('Crane', $resourceTypes);
    if ($hasCrane && $windSpeedMph > 20) {
        $recommendations[] = [
            'type' => 'warning',
            'message' => 'Wind speed is ' . round($windSpeedMph) . 'mph — crane operations should not be carried out today.'
        ];
    }
    
    // Rule 2: Heavy rain warning for earth-moving equipment
    // IDs 502-504 = heavy/very heavy/extreme rain, 522/531 = heavy shower rain
    $isHeavyRain = in_array($weatherId, [502, 503, 504, 522, 531]);
    $hasEarthMoving = !empty(array_filter($resources, function($r) {
        return in_array($r['Resource_Type'], ['Digger', 'Dumper Truck']);
    }));
    
    if ($isHeavyRain && $hasEarthMoving) {
        $recommendations[] = [
            'type' => 'warning',
            'message' => 'Heavy rain detected — works involving diggers and dumper trucks may be delayed.'
        ];
    }
    
    // If no warnings, all conditions are fine
    if (empty($recommendations)) {
        $recommendations[] = [
            'type' => 'safe',
            'message' => 'Weather conditions are suitable for all planned works today.'
        ];
    }
    
    return $recommendations;
}
/**
 * Fetches current air quality data for a given location.
 * Uses the OpenWeather Air Pollution API.
 */
function getAirQuality($lat, $lng) {
    $apiKey = '7250dbafecc8320f7cc55ce1b8977b2e';
    $url = "http://api.openweathermap.org/data/2.5/air_pollution?lat=$lat&lon=$lng&appid=$apiKey";
    $response = file_get_contents($url);
    
    if (!$response) {
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Converts the AQI number (1-5) into a human readable label.
 * Scale from OpenWeather: 1=Good, 2=Fair, 3=Moderate, 4=Poor, 5=Very Poor
 */
function getAqiLabel($aqi) {
    $labels = [
        1 => 'Good',
        2 => 'Fair',
        3 => 'Moderate',
        4 => 'Poor',
        5 => 'Very Poor'
    ];
    return $labels[$aqi] ?? 'Unknown';
}

/**
 * Checks air quality index and recommends whether earth-moving
 * equipment (diggers, dumper trucks) should be used on site.
 * Rule: AQI 1-2 = safe, AQI 3-5 = do not use earth-moving equipment
 */
function getAirQualityRecommendation($airData, $resources) {
    $aqi = $airData['list'][0]['main']['aqi'];
    $aqiLabel = getAqiLabel($aqi);
    
    // Check if project has earth-moving equipment
    $resourceTypes = array_column($resources, 'Resource_Type');
    $hasEarthMoving = !empty(array_intersect(['Digger', 'Dumper Truck'], $resourceTypes));
    
    if ($aqi <= 2) {
        return [
            'type' => 'safe',
            'aqi' => $aqiLabel,
            'message' => "Air quality is $aqiLabel — earth-moving equipment can be used on site today."
        ];
    } else {
        return [
            'type' => 'warning',
            'aqi' => $aqiLabel,
            'message' => "Air quality is $aqiLabel — do not use earth-moving equipment as it may worsen air quality on site."
        ];
    }


}

/**
 * Fetches a 5-day weather forecast for a given location.
 * Returns 3-hourly forecast data from the OpenWeather API.
 * Note: Free plan supports 5-day forecast only, not 16-day.
 */
function getForecast($lat, $lng) {
    $apiKey = '7250dbafecc8320f7cc55ce1b8977b2e';
    $url = "http://api.openweathermap.org/data/2.5/forecast?lat=$lat&lon=$lng&appid=$apiKey&units=metric";
    $response = file_get_contents($url);
    
    if (!$response) {
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Fetches air quality forecast for the next 5 days.
 * Uses the OpenWeather Air Pollution Forecast API.
 * This is available on the free plan unlike weather history.
 */
function getAirQualityForecast($lat, $lng) {
    $apiKey = '7250dbafecc8320f7cc55ce1b8977b2e';
    $url = "http://api.openweathermap.org/data/2.5/air_pollution/forecast?lat=$lat&lon=$lng&appid=$apiKey";
    $response = file_get_contents($url);
    
    if (!$response) {
        return null;
    }
    
    return json_decode($response, true);
}

?>
