<?php

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

function assignments_read_json_payload(): ?array
{
    $payload = json_decode(file_get_contents('php://input'), true);

    return is_array($payload) ? $payload : null;
}

function assignments_positive_integer($value): ?int
{
    $integer = filter_var($value, FILTER_VALIDATE_INT);

    return $integer !== false && $integer > 0 ? $integer : null;
}

function assignments_valid_date($value): bool
{
    if (!is_string($value)) {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value;
}

if ($method === 'GET') {
    $userId = assignments_positive_integer($_GET['user_id'] ?? null);
    $status = $_GET['status'] ?? null;
    if ($userId === null || ($status !== null && $status !== 'unattended')) {
        json_response(['error' => 'A valid user_id and optional status=unattended are required.'], 400);
    }

    try {
        $sql = 'SELECT id, user_id, unit_code, unit_name, task, due_date, is_completed
                FROM assignments
                WHERE user_id = :user_id';
        if ($status === 'unattended') {
            $sql .= ' AND is_completed = 0';
        }
        $sql .= ' ORDER BY due_date, id';

        $statement = $pdo->prepare($sql);
        $statement->execute(['user_id' => $userId]);

        json_response(['assignments' => $statement->fetchAll()]);
    } catch (PDOException $exception) {
        json_response(['error' => 'Unable to fetch assignments.'], 500);
    }
}

if ($method === 'POST') {
    $payload = assignments_read_json_payload();
    if ($payload === null) {
        json_response(['error' => 'A valid JSON object is required.'], 400);
    }

    $userId = assignments_positive_integer($payload['user_id'] ?? 1);
    $unitCode = isset($payload['unit_code']) && is_string($payload['unit_code'])
        ? trim($payload['unit_code'])
        : '';
    $unitName = isset($payload['unit_name']) && is_string($payload['unit_name'])
        ? trim($payload['unit_name'])
        : '';
    $task = isset($payload['task']) && is_string($payload['task'])
        ? trim($payload['task'])
        : '';
    $dueDate = $payload['due_date'] ?? null;

    if (
        $userId === null
        || $unitCode === ''
        || mb_strlen($unitCode) > 50
        || $unitName === ''
        || mb_strlen($unitName) > 255
        || $task === ''
        || mb_strlen($task) > 500
        || !assignments_valid_date($dueDate)
    ) {
        json_response(['error' => 'Invalid assignment data.'], 400);
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO assignments (user_id, unit_code, unit_name, task, due_date)
             VALUES (:user_id, :unit_code, :unit_name, :task, :due_date)'
        );
        $statement->execute([
            'user_id' => $userId,
            'unit_code' => $unitCode,
            'unit_name' => $unitName,
            'task' => $task,
            'due_date' => $dueDate,
        ]);

        json_response([
            'message' => 'Assignment created.',
            'id' => (int) $pdo->lastInsertId(),
        ], 201);
    } catch (PDOException $e) {
        error_log('Unable to create assignment: ' . $e->getMessage());
        json_response([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}

if ($method === 'PUT') {
    $payload = assignments_read_json_payload();
    if ($payload === null) {
        json_response(['error' => 'A valid JSON object is required.'], 400);
    }

    $assignmentId = assignments_positive_integer($payload['id'] ?? null);
    $isCompleted = filter_var(
        $payload['is_completed'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0, 'max_range' => 1]],
    );
    if ($assignmentId === null || $isCompleted === false) {
        json_response(['error' => 'A valid assignment id and is_completed status (0 or 1) are required.'], 400);
    }

    try {
        $statement = $pdo->prepare(
            'UPDATE assignments
             SET is_completed = :is_completed
             WHERE id = :id'
        );
        $statement->execute([
            'is_completed' => $isCompleted,
            'id' => $assignmentId,
        ]);

        if ($statement->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT id FROM assignments WHERE id = :id');
            $exists->execute(['id' => $assignmentId]);
            if ($exists->fetch() === false) {
                json_response(['error' => 'Assignment not found.'], 404);
            }
        }

        json_response(['message' => 'Assignment status updated.']);
    } catch (PDOException $exception) {
        json_response(['error' => 'Unable to update assignment status.'], 500);
    }
}

if ($method === 'DELETE') {
    $payload = assignments_read_json_payload();
    if ($payload === null) {
        json_response(['error' => 'A valid JSON object is required.'], 400);
    }

    $assignmentId = assignments_positive_integer($payload['id'] ?? null);
    if ($assignmentId === null) {
        json_response(['error' => 'A valid assignment id is required.'], 400);
    }

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'SELECT is_completed FROM assignments WHERE id = :id FOR UPDATE'
        );
        $statement->execute(['id' => $assignmentId]);
        $assignment = $statement->fetch();

        if ($assignment === false) {
            $pdo->rollBack();
            json_response(['error' => 'Assignment not found.'], 404);
        }
        if ((int) $assignment['is_completed'] !== 1) {
            $pdo->rollBack();
            json_response(['error' => 'Only completed assignments can be deleted.'], 409);
        }

        $delete = $pdo->prepare(
            'DELETE FROM assignments WHERE id = :id AND is_completed = 1'
        );
        $delete->execute(['id' => $assignmentId]);
        $pdo->commit();

        json_response(['message' => 'Assignment deleted.']);
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['error' => 'Unable to delete assignment.'], 500);
    }
}

header('Allow: GET, POST, PUT, DELETE, OPTIONS');
json_response(['error' => 'Method not allowed.'], 405);
