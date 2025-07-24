<?php
// test_workers.php - Simple script to verify DB connection and list all workers.
require_once 'db.php';

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$result = $conn->query("SELECT id, name, pin, rate_per_hr, skill_type FROM workers ORDER BY id ASC");
if (!$result) {
    echo "<h3>Database query failed:</h3><pre>" . $conn->error . "</pre>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Workers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2>Worker List (Test)</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>PIN</th>
                <th>Rate/Hour</th>
                <th>Skill</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['pin']) ?></td>
                <td><?= htmlspecialchars($row['rate_per_hr']) ?></td>
                <td><?= htmlspecialchars($row['skill_type']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
