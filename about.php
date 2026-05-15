<?php
/*
 * about.php
 * Lists all third-party libraries, APIs and resources used in this project.
 * Required by the assessment brief for academic referencing purposes.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Construction Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
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
        
        <section class="project-info">
            <h2>Third-Party Libraries & APIs</h2>
            <ul>
                <li>
                    <strong>Leaflet.js v1.9.4</strong><br>
                    Open-source JavaScript library for interactive maps.<br>
                    <a href="https://leafletjs.com" target="_blank">https://leafletjs.com</a>
                </li>
                <li style="margin-top:15px;">
                    <strong>OpenStreetMap</strong><br>
                    Map tile data used by Leaflet for rendering the project location map.<br>
                    <a href="https://www.openstreetmap.org" target="_blank">https://www.openstreetmap.org</a>
                </li>
                <li style="margin-top:15px;">
                    <strong>OpenWeather API</strong><br>
                    Used to retrieve current weather, air quality and forecast data for each project location.<br>
                    <a href="https://openweathermap.org/api" target="_blank">https://openweathermap.org/api</a>
                </li>
            </ul>
        </section>

        <section class="project-info">
            <h2>Development Tools</h2>
            <ul>
                <li><strong>Microsoft Azure</strong> : Cloud hosting platform</li>
                <li style="margin-top:10px;"><strong>Ubuntu 24.04 LTS</strong> : Server operating system</li>
                <li style="margin-top:10px;"><strong>Apache 2.4</strong> : Web server</li>
                <li style="margin-top:10px;"><strong>PHP 8.3</strong> : Server-side scripting language</li>
                <li style="margin-top:10px;"><strong>MySQL 8.0</strong> : Database management system</li>
		<li style="margin-top:10px;"><strong>Claude</strong> : debugign & enhancment (functions were enhanced with AI will be diclared in the comments)</li>
            </ul>
        </section>

	<section class="project-info">
    <h2>Meet the Team</h2>
    <div class="team-section">
        <div class="team-member">
            <img src="images/me1.jpeg" alt="Developer">
            <p class="name">Abdulrazaq</p>
            <p class="role">Developer</p>
        </div>
        <div class="team-member">
            <img src="images/me2.jpeg" alt="Designer">
            <p class="name">AbDuLrAzAq</p>
            <p class="role">Designer</p>
        </div>
        <div class="team-member">
            <img src="images/me3.jpeg" alt="Security Tester">
            <p class="name">aBdUlRaZaQ</p>
            <p class="role">Security Tester</p>
        </div>
    </div>
</section>

    </main>

    <footer>
        <p>Construction Project Dashboard &copy; 2026</p>
    </footer>
</body>
</html>
