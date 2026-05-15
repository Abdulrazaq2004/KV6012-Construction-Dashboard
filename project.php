<?php
/*
 * project.php
 * This page shows the full details of a single construction project.
 * It pulls project info and resources from the database, shows the
 * location on a map, and displays live weather and air quality data
 * with recommendations for the site manager.
 */

require_once 'includes/db.php';
require_once 'includes/weather.php';

// Grab the project ID from the URL (e.g. project.php?id=1)
// Cast to int to prevent SQL injection
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid ID was provided, send user back to the homepage
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Look up the project in the database
$stmt = $pdo->prepare("SELECT * FROM Projects WHERE Project_id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

// If the project doesn't exist, send user back to the homepage
if (!$project) {
    header('Location: index.php');
    exit;
}

// Get all resources (equipment) assigned to this project
// by joining the Resources and Project_Resources tables
$stmt = $pdo->prepare("
    SELECT r.Resource_Type, r.Conditions_of_use 
    FROM Resources r
    JOIN Project_Resources pr ON r.Resource_id = pr.Resource_id
    WHERE pr.Project_id = ?
");
$stmt->execute([$id]);
$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

// The geolocation is stored as "lat, lng" in one field
// so we split it into two separate values for the map and API calls
$geo = explode(',', $project['Geolocation']);
$lat = trim($geo[0]);
$lng = trim($geo[1]);

// Fetch live weather data and generate site recommendations
$weather = getWeather($lat, $lng);
$recommendations = getWeatherRecommendations($weather, $resources);

// Fetch live air quality data and generate recommendation
$airData = getAirQuality($lat, $lng);
$airRecommendation = getAirQualityRecommendation($airData, $resources);
// Fetch 5-day forecast data
$forecast = getForecast($lat, $lng);
// Fetch air quality forecast for the next 5 days
$airForecast = getAirQualityForecast($lat, $lng);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['Project_Name']) ?> - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Leaflet CSS for the map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>
    <header>
        <h1>Construction Project Dashboard</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
        </nav>
    </header>
    <main>
        <a href="index.php" class="back-btn">&larr; Back to Projects</a>

        <!-- Project details section -->
        <section class="project-info">
            <h2><?= htmlspecialchars($project['Project_Name']) ?></h2>
            <p><strong>Manager:</strong> <?= htmlspecialchars($project['Manager']) ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($project['Location']) ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($project['Description']) ?></p>
        </section>

        <!-- Resources assigned to this project -->
        <section class="resources">
            <h2>Resources</h2>
            <ul>
                <?php foreach ($resources as $resource): ?>
                    <li>
                        <strong><?= htmlspecialchars($resource['Resource_Type']) ?></strong>
                        <p><?= htmlspecialchars($resource['Conditions_of_use']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- Map showing the project location using OpenStreetMap and Leaflet -->
        <section class="map-section">
            <h2>Project Location</h2>
            <div id="map" style="height: 400px; border-radius: 5px;"></div>
        </section>

        <!-- Live weather data from OpenWeather API -->
        <section class="weather-section">
            <h2>Current Weather</h2>
            <?php if ($weather): ?>
                <div class="weather-info">
                    <p><strong>Condition:</strong> <?= htmlspecialchars(ucfirst($weather['weather'][0]['description'])) ?></p>
                    <p><strong>Temperature:</strong> <?= round($weather['main']['temp']) ?>°C</p>
                    <p><strong>Wind Speed:</strong> <?= round($weather['wind']['speed'] * 2.237) ?> mph</p>
                    <p><strong>Humidity:</strong> <?= $weather['main']['humidity'] ?>%</p>
                </div>
                <div class="recommendations">
                    <h3>Site Recommendations</h3>
                    <?php foreach ($recommendations as $rec): ?>
                        <div class="alert <?= $rec['type'] === 'warning' ? 'alert-warning' : 'alert-safe' ?>">
                            <?= htmlspecialchars($rec['message']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Weather data is currently unavailable. Please try again later.</p>
            <?php endif; ?>
        </section>

        <!-- Live air quality data from OpenWeather Air Pollution API -->
        <section class="airquality-section">
            <h2>Air Quality</h2>
            <?php if ($airData): ?>
                <div class="weather-info">
                    <p><strong>Air Quality Index:</strong> <?= getAqiLabel($airData['list'][0]['main']['aqi']) ?></p>
                    <p><strong>CO:</strong> <?= $airData['list'][0]['components']['co'] ?> μg/m³</p>
                    <p><strong>NO2:</strong> <?= $airData['list'][0]['components']['no2'] ?> μg/m³</p>
                    <p><strong>PM10:</strong> <?= $airData['list'][0]['components']['pm10'] ?> μg/m³</p>
                    <p><strong>PM2.5:</strong> <?= $airData['list'][0]['components']['pm2_5'] ?> μg/m³</p>
                </div>
                <div class="recommendations">
                    <h3>Air Quality Recommendation</h3>
                    <div class="alert <?= $airRecommendation['type'] === 'warning' ? 'alert-warning' : 'alert-safe' ?>">
                        <?= htmlspecialchars($airRecommendation['message']) ?>
                    </div>
                </div>
            <?php else: ?>
                <p>Air quality data is currently unavailable. Please try again later.</p>
            <?php endif; ?>
        </section>
	
	<!-- 5-day weather forecast from OpenWeather API -->
        <section class="forecast-section">
            <h2>Weather Forecast</h2>
            <p>Select a date to view the forecast (up to 5 days ahead):</p>
            <input type="date" id="forecast-date" 
                min="<?= date('Y-m-d') ?>" 
                max="<?= date('Y-m-d', strtotime('+5 days')) ?>">
            
            <div id="forecast-results" style="margin-top:15px;">
                <p>Please select a date above.</p>
            </div>

            <?php if ($forecast): ?>
                  
                <script>
                
                // Store forecast data as a JS variable so we can filter it by date
                var forecastData = <?= json_encode($forecast['list']) ?>;
                
                document.getElementById('forecast-date').addEventListener('change', function() {
                    var selectedDate = this.value;
                    var resultsDiv = document.getElementById('forecast-results');
                    
                    // Filter forecast entries that match the selected date
                    var dayForecast = forecastData.filter(function(entry) {
                        return entry.dt_txt.startsWith(selectedDate);
                    });
                    
                    if (dayForecast.length === 0) {
                        resultsDiv.innerHTML = '<p>No forecast data available for this date. Please choose a date within the next 5 days.</p>';
                        return;
                    }
                    
                    // Build the forecast table
                    var html = '<table style="width:100%; border-collapse:collapse;">';
                    html += '<tr style="background:#1a1a2e; color:white;">';
                    html += '<th style="padding:8px;">Time</th>';
                    html += '<th style="padding:8px;">Condition</th>';
                    html += '<th style="padding:8px;">Temp (°C)</th>';
                    html += '<th style="padding:8px;">Wind (mph)</th>';
                    html += '<th style="padding:8px;">Humidity</th>';
                    html += '</tr>';
                    
                    dayForecast.forEach(function(entry, index) {
                        var bg = index % 2 === 0 ? '#f9f9f9' : '#ffffff';
                        var time = entry.dt_txt.split(' ')[1].substring(0,5);
                        var windMph = Math.round(entry.wind.speed * 2.237);
                        html += '<tr style="background:' + bg + '; text-align:center;">';
                        html += '<td style="padding:8px;">' + time + '</td>';
                        html += '<td style="padding:8px;">' + entry.weather[0].description + '</td>';
                        html += '<td style="padding:8px;">' + Math.round(entry.main.temp) + '°C</td>';
                        html += '<td style="padding:8px;">' + windMph + ' mph</td>';
                        html += '<td style="padding:8px;">' + entry.main.humidity + '%</td>';
                        html += '</tr>';
                    });
                    
                    html += '</table>';
                    resultsDiv.innerHTML = html;
                });
                </script>
            <?php else: ?>
                <p>Forecast data is currently unavailable. Please try again later.</p>
            <?php endif; ?>
        </section>
	
	<!-- Historical weather data section -->
        <!-- Note: Historical data requires a paid OpenWeather plan -->
        <!-- Error handling is in place to inform the user -->
        <section class="forecast-section">
            <h2>Historical Weather Data</h2>
            <p>Select a past date to view historical weather data:</p>
            <input type="date" id="history-date"
                max="<?= date('Y-m-d', strtotime('-1 day')) ?>">
            <div id="history-results" style="margin-top:15px;"></div>
            <script>
            document.getElementById('history-date').addEventListener('change', function() {
                var selectedDate = this.value;
                var resultsDiv = document.getElementById('history-results');
                
                // Historical data is not available on the free OpenWeather plan.
                // In a production environment, this would call the History API.
                resultsDiv.innerHTML = '<div class="alert alert-warning">Historical weather data requires a paid OpenWeather subscription. This feature is not available on the current free plan. Please refer to the 5-day forecast above for upcoming weather information.</div>';
            });
            </script>
        </section>
	
	<!-- Air quality forecast section -->
        <section class="forecast-section">
            <h2>Air Quality Forecast</h2>
            <p>Select a date to view the air quality forecast (up to 5 days ahead):</p>
            <input type="date" id="aq-forecast-date"
                min="<?= date('Y-m-d') ?>"
                max="<?= date('Y-m-d', strtotime('+5 days')) ?>">

            <div id="aq-forecast-results" style="margin-top:15px;">
                <p>Please select a date above.</p>
            </div>

            <?php if ($airForecast): ?>
                <script>

                // $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$
                // THIS SCRIPT WAS PARTLY ENHANCED USING AI BECAUSE MINE LOOK TRASH 😱😱😱😱😱😱😱😱😱
                // $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$
    
                // Store air quality forecast data as JS variable
                var airForecastData = <?= json_encode($airForecast['list']) ?>;

                document.getElementById('aq-forecast-date').addEventListener('change', function() {
                    var selectedDate = this.value;
                    var resultsDiv = document.getElementById('aq-forecast-results');

                    // Convert selected date to timestamp range for filtering
                    var startTs = new Date(selectedDate).getTime() / 1000;
                    var endTs = startTs + 86400; // 24 hours later

                    // Filter entries that fall within the selected date
                    var dayData = airForecastData.filter(function(entry) {
                        return entry.dt >= startTs && entry.dt < endTs;
                    });

                    if (dayData.length === 0) {
                        resultsDiv.innerHTML = '<p>No air quality forecast available for this date. Please choose a date within the next 5 days.</p>';
                        return;
                    }

                    var aqiLabels = {1: 'Good', 2: 'Fair', 3: 'Moderate', 4: 'Poor', 5: 'Very Poor'};

                    var html = '<table style="width:100%; border-collapse:collapse;">';
                    html += '<tr style="background:#1a1a2e; color:white;">';
                    html += '<th style="padding:8px;">Time</th>';
                    html += '<th style="padding:8px;">AQI</th>';
                    html += '<th style="padding:8px;">CO (μg/m³)</th>';
                    html += '<th style="padding:8px;">NO2 (μg/m³)</th>';
                    html += '<th style="padding:8px;">PM10 (μg/m³)</th>';
                    html += '<th style="padding:8px;">PM2.5 (μg/m³)</th>';
                    html += '</tr>';

                    dayData.forEach(function(entry, index) {
                        var bg = index % 2 === 0 ? '#f9f9f9' : '#ffffff';
                        var time = new Date(entry.dt * 1000).toUTCString().slice(17, 22);
                        var aqi = aqiLabels[entry.main.aqi] || 'Unknown';
                        html += '<tr style="background:' + bg + '; text-align:center;">';
                        html += '<td style="padding:8px;">' + time + '</td>';
                        html += '<td style="padding:8px;">' + aqi + '</td>';
                        html += '<td style="padding:8px;">' + entry.components.co + '</td>';
                        html += '<td style="padding:8px;">' + entry.components.no2 + '</td>';
                        html += '<td style="padding:8px;">' + entry.components.pm10 + '</td>';
                        html += '<td style="padding:8px;">' + entry.components.pm2_5 + '</td>';
                        html += '</tr>';
                    });

                    html += '</table>';
                    resultsDiv.innerHTML = html;
                });
                </script>
            <?php else: ?>
                <p>Air quality forecast is currently unavailable. Please try again later.</p>
            <?php endif; ?>
        </section>
	
	<!-- Historical air quality section -->
        <!-- Free plan does not support historical air quality data -->
        <!-- Error handling is in place to inform the user of this limitation -->
        <section class="forecast-section">
            <h2>Historical Air Quality Data</h2>
            <p>Select a past date to view historical air quality data:</p>
            <input type="date" id="history-aq-date"
                max="<?= date('Y-m-d', strtotime('-1 day')) ?>">
            <div id="history-aq-results" style="margin-top:15px;"></div>
            <script>
            document.getElementById('history-aq-date').addEventListener('change', function() {
                var resultsDiv = document.getElementById('history-aq-results');
                // Historical air quality data requires a paid OpenWeather plan
                // We handle this limitation gracefully by informing the user
                resultsDiv.innerHTML = '<div class="alert alert-warning">Historical air quality data requires a paid OpenWeather subscription. This feature is not available on the current free plan. Please refer to the 5-day air quality forecast above for upcoming air quality information.</div>';
            });
            </script>
        </section>
	
    </main>

    <footer>
        <p>Construction Project Dashboard &copy; 2026</p>
    </footer>

    <!-- Leaflet JS for the interactive map -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Centre the map on the project coordinates and zoom in
        var map = L.map('map').setView([<?= $lat ?>, <?= $lng ?>], 15);

        // Load map tiles from OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Drop a marker on the exact project location with a popup label
        L.marker([<?= $lat ?>, <?= $lng ?>])
            .addTo(map)
            .bindPopup('<?= htmlspecialchars($project['Project_Name']) ?>')
            .openPopup();
    </script>
</body>
</html>
