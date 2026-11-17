<?php

require_once __DIR__ . '/config.php';

$userId = 1;
$method = $_SERVER['REQUEST_METHOD'];

function bible_theme_read_json_payload(): ?array
{
    $payload = json_decode(file_get_contents('php://input'), true);

    return is_array($payload) ? $payload : null;
}

$verses = [
    'faith' => [
        'reference' => 'Hebrews 11:1',
        'text' => 'Now faith is the substance of things hoped for, the evidence of things not seen.',
    ],
    'strength' => [
        'reference' => 'Philippians 4:13',
        'text' => 'I can do all things through Christ which strengtheneth me.',
    ],
    'peace' => [
        'reference' => 'John 14:27',
        'text' => 'Peace I leave with you, my peace I give unto you: not as the world giveth, give I unto you.',
    ],
    'patience' => [
        'reference' => 'Romans 12:12',
        'text' => 'Rejoicing in hope; patient in tribulation; continuing instant in prayer.',
    ],
    'love' => [
        'reference' => '1 Corinthians 13:4',
        'text' => 'Charity suffereth long, and is kind; charity envieth not; charity vaunteth not itself, is not puffed up.',
    ],
    'hope' => [
        'reference' => 'Romans 15:13',
        'text' => 'Now the God of hope fill you with all joy and peace in believing, that ye may abound in hope.',
    ],
    'wisdom' => [
        'reference' => 'James 1:5',
        'text' => 'If any of you lack wisdom, let him ask of God, that giveth to all men liberally.',
    ],
    'courage' => [
        'reference' => 'Joshua 1:9',
        'text' => 'Be strong and of a good courage; be not afraid, neither be thou dismayed.',
    ],
    'focus' => [
        'reference' => 'Colossians 3:23',
        'text' => 'And whatsoever ye do, do it heartily, as to the Lord, and not unto men.',
    ],
    'perseverance' => [
        'reference' => 'Galatians 6:9',
        'text' => 'And let us not be weary in well doing: for in due season we shall reap, if we faint not.',
    ],
    'gratitude' => [
        'reference' => '1 Thessalonians 5:18',
        'text' => 'In every thing give thanks: for this is the will of God in Christ Jesus concerning you.',
    ],
    'anxiety' => [
        'reference' => 'Philippians 4:6',
        'text' => 'Be careful for nothing; but in every thing by prayer and supplication with thanksgiving let your requests be made known unto God.',
    ],
    'rest' => [
        'reference' => 'Matthew 11:28',
        'text' => 'Come unto me, all ye that labour and are heavy laden, and I will give you rest.',
    ],
    'purpose' => [
        'reference' => 'Proverbs 16:3',
        'text' => 'Commit thy works unto the LORD, and thy thoughts shall be established.',
    ],
    'discipline' => [
        'reference' => '1 Corinthians 9:24',
        'text' => 'So run, that ye may obtain.',
    ],
    'joy' => [
        'reference' => 'Nehemiah 8:10',
        'text' => 'The joy of the LORD is your strength.',
    ],
    'forgiveness' => [
        'reference' => 'Ephesians 4:32',
        'text' => 'And be ye kind one to another, tenderhearted, forgiving one another.',
    ],
    'default' => [
        'reference' => 'Proverbs 3:5',
        'text' => 'Trust in the LORD with all thine heart; and lean not unto thine own understanding.',
    ],
];

if ($method === 'GET') {
    try {
        $statement = $pdo->prepare(
            'SELECT theme, verse_reference, verse_text, updated_at
             FROM weekly_themes
             WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
        $theme = $statement->fetch();

        json_response(['theme' => $theme === false ? null : $theme]);
    } catch (PDOException $exception) {
        error_log('Unable to fetch weekly Bible theme: ' . $exception->getMessage());
        json_response(['error' => 'Unable to fetch weekly Bible theme.'], 500);
    }
}

if ($method === 'POST') {
    $payload = bible_theme_read_json_payload();
    $theme = isset($payload['theme']) && is_string($payload['theme'])
        ? trim($payload['theme'])
        : '';
    if ($payload === null || $theme === '' || mb_strlen($theme) > 100) {
        json_response(['error' => 'A theme between 1 and 100 characters is required.'], 400);
    }

    $verse = $verses[strtolower($theme)] ?? $verses['default'];

    try {
        $statement = $pdo->prepare(
            'INSERT INTO weekly_themes (user_id, theme, verse_reference, verse_text)
             VALUES (:user_id, :theme, :verse_reference, :verse_text)
             ON DUPLICATE KEY UPDATE
               theme = VALUES(theme),
               verse_reference = VALUES(verse_reference),
               verse_text = VALUES(verse_text)'
        );
        $statement->execute([
            'user_id' => $userId,
            'theme' => $theme,
            'verse_reference' => $verse['reference'],
            'verse_text' => $verse['text'],
        ]);

        json_response([
            'theme' => $theme,
            'verse_reference' => $verse['reference'],
            'verse_text' => $verse['text'],
        ]);
    } catch (PDOException $exception) {
        error_log('Unable to save weekly Bible theme: ' . $exception->getMessage());
        json_response(['error' => 'Unable to save weekly Bible theme.'], 500);
    }
}

header('Allow: GET, POST, OPTIONS');
json_response(['error' => 'Method not allowed.'], 405);
