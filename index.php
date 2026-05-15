<?php
require_once 'includes/db.php';

// Fetch all projects
$stmt = $pdo->query("SELECT * FROM Projects");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Construction Project Dashboard</title>
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
        <section id="projects-list">
            <h2>Select a Project</h2>
            <ul>
                <?php foreach ($projects as $project): ?>
                    <li>
                        <a href="project.php?id=<?= $project['Project_id'] ?>">
                            <?= htmlspecialchars($project['Project_Name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </main>

    <footer>
        <p>Construction Project Dashboard &copy; 2026</p>
    </footer>
</body>
</html>
