<?php

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

function read_json_payload(): ?array
{
    $payload = json_decode(file_get_contents('php://input'), true);

    return is_array($payload) ? $payload : null;
}

function positive_integer($value): ?int
{
    $integer = filter_var($value, FILTER_VALIDATE_INT);

    return $integer !== false && $integer > 0 ? $integer : null;
}

if ($method === 'GET') {
    $userId = positive_integer($_GET['user_id'] ?? null);
    $dateLogged = $_GET['date_logged'] ?? date('Y-m-d');
    $date = is_string($dateLogged)
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $dateLogged)
        : false;
    if (
        $userId === null
        || $date === false
        || $date->format('Y-m-d') !== $dateLogged
    ) {
        json_response(['error' => 'A valid user_id and date_logged (YYYY-MM-DD) are required.'], 400);
    }

    try {
        $statement = $pdo->prepare(
            'SELECT habits.id, habits.user_id, habits.title, habits.frequency,
                    habits.category, habits.created_at, today_log.status AS today_status
             FROM habits
             LEFT JOIN habit_logs AS today_log
               ON today_log.habit_id = habits.id
              AND today_log.date_logged = :date_logged
             WHERE habits.user_id = :user_id
             ORDER BY habits.id'
        );
        $statement->execute([
            'date_logged' => $dateLogged,
            'user_id' => $userId,
        ]);

        json_response(['habits' => $statement->fetchAll()]);
    } catch (PDOException $exception) {
        json_response(['error' => 'Unable to fetch habits.'], 500);
    }
}

if ($method === 'POST') {
    $payload = read_json_payload();
    if ($payload === null) {
        json_response(['error' => 'A valid JSON object is required.'], 400);
    }

    $userId = positive_integer($payload['user_id'] ?? null);
    $title = isset($payload['title']) && is_string($payload['title'])
        ? trim($payload['title'])
        : '';
    $frequency = $payload['frequency'] ?? null;
    $category = $payload['category'] ?? null;

    if (
        $userId === null
        || $title === ''
        || preg_match('/^.{1,255}$/us', $title) !== 1
        || !in_array($frequency, ['daily', 'weekly'], true)
        || !in_array($category, ['academic', 'health', 'admin', 'social'], true)
    ) {
        json_response(['error' => 'Invalid habit data.'], 400);
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO habits (user_id, title, frequency, category)
             VALUES (:user_id, :title, :frequency, :category)'
        );
        $statement->execute([
            'user_id' => $userId,
            'title' => $title,
            'frequency' => $frequency,
            'category' => $category,
        ]);

        json_response([
            'message' => 'Habit created.',
            'id' => (int) $pdo->lastInsertId(),
        ], 201);
    } catch (PDOException $exception) {
        json_response(['error' => 'Unable to create habit.'], 500);
    }
}

if ($method === 'DELETE') {
    $payload = read_json_payload();
    if ($payload === null) {
        json_response(['error' => 'A valid JSON object is required.'], 400);
    }

    $habitId = positive_integer($payload['id'] ?? null);
    if ($habitId === null) {
        json_response(['error' => 'A valid habit id is required.'], 400);
    }

    try {
        $statement = $pdo->prepare('DELETE FROM habits WHERE id = :id');
        $statement->execute(['id' => $habitId]);

        if ($statement->rowCount() === 0) {
            json_response(['error' => 'Habit not found.'], 404);
        }

        json_response(['message' => 'Habit deleted.']);
    } catch (PDOException $exception) {
        json_response(['error' => 'Unable to delete habit.'], 500);
    }
}

header('Allow: GET, POST, DELETE, OPTIONS');
json_response(['error' => 'Method not allowed.'], 405);
