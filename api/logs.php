<?php

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST, OPTIONS');
    json_response(['error' => 'Method not allowed.'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    json_response(['error' => 'A valid JSON object is required.'], 400);
}

$habitId = filter_var($payload['habit_id'] ?? null, FILTER_VALIDATE_INT);
$dateLogged = $payload['date_logged'] ?? null;
$status = $payload['status'] ?? null;

if (
    $habitId === false
    || $habitId === null
    || $habitId < 1
    || !is_string($dateLogged)
    || !is_string($status)
    || !in_array($status, ['completed', 'partial', 'grace_day'], true)
) {
    json_response(['error' => 'Invalid habit log data.'], 400);
}

$date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateLogged);
if ($date === false || $date->format('Y-m-d') !== $dateLogged) {
    json_response(['error' => 'date_logged must be a valid date in YYYY-MM-DD format.'], 400);
}

try {
    $statement = $pdo->prepare(
        'INSERT INTO habit_logs (habit_id, date_logged, status)
         VALUES (:habit_id, :date_logged, :status)
         ON DUPLICATE KEY UPDATE status = VALUES(status)'
    );
    $statement->execute([
        'habit_id' => $habitId,
        'date_logged' => $dateLogged,
        'status' => $status,
    ]);

    json_response(['message' => 'Habit log saved.']);
} catch (PDOException $exception) {
    json_response(['error' => 'Unable to save habit log.'], 500);
}
