<?php

require_once __DIR__ . '/config.php';

function timetable_positive_integer($value): ?int
{
    $integer = filter_var($value, FILTER_VALIDATE_INT);

    return $integer !== false && $integer > 0 ? $integer : null;
}

function timetable_read_json_payload(): ?array
{
    $payload = json_decode(file_get_contents('php://input'), true);

    return is_array($payload) ? $payload : null;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = timetable_positive_integer($_GET['user_id'] ?? null);
    if ($userId === null) {
        json_response(['error' => 'A valid user_id is required.'], 400);
    }

    try {
        $statement = $pdo->prepare(
            'SELECT id, user_id, class_name, class_name AS activity_name,
                    day_of_week, start_time, end_time, color_code
             FROM timetable_events
             WHERE user_id = :user_id
             ORDER BY FIELD(day_of_week, \'Monday\', \'Tuesday\', \'Wednesday\',
                            \'Thursday\', \'Friday\', \'Saturday\', \'Sunday\'),
                      start_time'
        );
        $statement->execute(['user_id' => $userId]);

        json_response(['events' => $statement->fetchAll()]);
    } catch (PDOException $exception) {
        json_response(['error' => 'Unable to fetch timetable events.'], 500);
    }
}

if ($method === 'POST') {
    $payload = timetable_read_json_payload();
    if ($payload === null) {
        json_response(['error' => 'A valid JSON object is required.'], 400);
    }

    $userId = timetable_positive_integer($payload['user_id'] ?? null);
    $activityName = isset($payload['activity_name']) && is_string($payload['activity_name'])
        ? trim($payload['activity_name'])
        : (isset($payload['class_name']) && is_string($payload['class_name'])
            ? trim($payload['class_name'])
            : '');
    $selectedDays = $payload['days'] ?? null;
    $startTime = $payload['start_time'] ?? null;
    $endTime = $payload['end_time'] ?? null;
    $colorCode = isset($payload['color_code']) && is_string($payload['color_code'])
        ? trim($payload['color_code'])
        : '#8e73f5';
    if ($colorCode === '') {
        $colorCode = '#8e73f5';
    }
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $timePattern = '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/';

    if (
        $activityName === ''
        || !is_array($selectedDays)
        || empty($selectedDays)
        || empty($startTime)
        || empty($endTime)
    ) {
        json_response([
            'success' => false,
            'error' => 'Invalid timetable event data.',
        ], 400);
    }

    $validSelectedDays = is_array($selectedDays) && count($selectedDays) > 0;
    $seenDays = [];
    if ($validSelectedDays) {
        foreach ($selectedDays as $selectedDay) {
            if (
                !is_string($selectedDay)
                || !in_array($selectedDay, $days, true)
                || in_array($selectedDay, $seenDays, true)
            ) {
                $validSelectedDays = false;
                break;
            }
            $seenDays[] = $selectedDay;
        }
    }

    if (
        $userId === null
        || $activityName === ''
        || preg_match('/^.{1,255}$/us', $activityName) !== 1
        || !$validSelectedDays
        || !is_string($startTime)
        || !is_string($endTime)
        || preg_match($timePattern, $startTime) !== 1
        || preg_match($timePattern, $endTime) !== 1
        || preg_match('/^#[0-9a-f]{6}$/i', $colorCode) !== 1
    ) {
        json_response([
            'success' => false,
            'error' => 'Invalid timetable event data.',
        ], 400);
    }

    $normalizedStart = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
    $normalizedEnd = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;
    if ($normalizedEnd <= $normalizedStart) {
        json_response(['error' => 'end_time must be later than start_time.'], 400);
    }

    try {
        $pdo->beginTransaction();
        $conflictStatement = $pdo->prepare(
            'SELECT id, start_time, end_time
             FROM timetable_events
             WHERE user_id = ? AND day_of_week = ?
               AND ((start_time < ? AND end_time > ?)
                 OR (start_time < ? AND end_time > ?)
                 OR (start_time >= ? AND end_time <= ?))
             LIMIT 1'
        );
        foreach ($selectedDays as $dayOfWeek) {
            $conflictStatement->execute([
                $userId,
                $dayOfWeek,
                $normalizedEnd,
                $normalizedStart,
                $normalizedStart,
                $normalizedStart,
                $normalizedStart,
                $normalizedEnd,
            ]);
            $conflict = $conflictStatement->fetch();
            if ($conflict !== false) {
                $pdo->rollBack();
                json_response([
                    'success' => false,
                    'error' => sprintf(
                        'Time slot conflict on %s with an existing class from %s to %s.',
                        $dayOfWeek,
                        substr($conflict['start_time'], 0, 5),
                        substr($conflict['end_time'], 0, 5)
                    ),
                    'conflict' => [
                        'day' => $dayOfWeek,
                        'start_time' => $conflict['start_time'],
                        'end_time' => $conflict['end_time'],
                    ],
                ], 409);
            }
        }

        $statement = $pdo->prepare(
            'INSERT INTO timetable_events
                (user_id, class_name, day_of_week, start_time, end_time, color_code)
             VALUES
                (?, ?, ?, ?, ?, ?)'
        );
        $createdIds = [];
        foreach ($selectedDays as $dayOfWeek) {
            $statement->execute([
                $userId,
                $activityName,
                $dayOfWeek,
                $normalizedStart,
                $normalizedEnd,
                $colorCode,
            ]);
            $createdIds[] = (int) $pdo->lastInsertId();
        }
        $pdo->commit();

        json_response([
            'message' => 'Timetable event created.',
            'ids' => $createdIds,
        ], 201);
    } catch (Exception $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['error' => $exception->getMessage()], 500);
    }
}

if ($method === 'DELETE') {
    $payload = timetable_read_json_payload();
    if ($payload === null) {
        json_response(['error' => 'A valid JSON object is required.'], 400);
    }

    $eventId = timetable_positive_integer($payload['id'] ?? null);
    if ($eventId === null) {
        json_response(['error' => 'A valid event id is required.'], 400);
    }

    try {
        $statement = $pdo->prepare('DELETE FROM timetable_events WHERE id = :id');
        $statement->execute(['id' => $eventId]);

        if ($statement->rowCount() === 0) {
            json_response(['error' => 'Timetable event not found.'], 404);
        }

        json_response(['message' => 'Timetable event deleted.']);
    } catch (PDOException $exception) {
        json_response(['error' => 'Unable to delete timetable event.'], 500);
    }
}

header('Allow: GET, POST, DELETE, OPTIONS');
json_response(['error' => 'Method not allowed.'], 405);
