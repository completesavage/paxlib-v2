<?php
/**
 * Movie AI endpoints
 *
 * GET  ?action=overview&barcode=X&title=Y  - Cached AI overview (generated once)
 * POST { barcode, title, question }          - Ask AI about a movie
 */

if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/movie_helpers.php';
require_once __DIR__ . '/omdb.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dataDir = __DIR__ . '/../data';
$overviewCacheFile = "$dataDir/ai_overview_cache.json";
$qaCacheFile = "$dataDir/ai_qa_cache.json";

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

function loadJsonFile($path) {
    if (!file_exists($path)) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveJsonFile($path, array $data) {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function aiProviderConfigured() {
    if (defined('GROQ_API_KEY') && trim(GROQ_API_KEY) !== '') {
        return true;
    }
    if (defined('OPENAI_API_KEY') && trim(OPENAI_API_KEY) !== '') {
        return true;
    }
    return false;
}

function aiChatCompletion($systemPrompt, $userPrompt) {
    $groqKey = defined('GROQ_API_KEY') ? trim(GROQ_API_KEY) : '';
    $openaiKey = defined('OPENAI_API_KEY') ? trim(OPENAI_API_KEY) : '';

    if ($groqKey !== '') {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $apiKey = $groqKey;
        $model = defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.1-8b-instant';
    } elseif ($openaiKey !== '') {
        $url = 'https://api.openai.com/v1/chat/completions';
        $apiKey = $openaiKey;
        $model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini';
    } else {
        return ['ok' => false, 'error' => 'No AI provider configured (set GROQ_API_KEY or OPENAI_API_KEY in config.php)'];
    }

    $payload = json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0.4,
        'max_tokens' => 500,
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
            'content' => $payload,
            'timeout' => 30,
            'ignore_errors' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return ['ok' => false, 'error' => 'AI request failed'];
    }

    $json = json_decode($raw, true);
    $text = trim($json['choices'][0]['message']['content'] ?? '');
    if ($text === '') {
        return ['ok' => false, 'error' => 'Empty AI response'];
    }

    return ['ok' => true, 'text' => $text];
}

function buildFallbackOverview(array $omdb, $title) {
    $plot = trim($omdb['Plot'] ?? '');
    if ($plot !== '' && strcasecmp($plot, 'N/A') !== 0) {
        return $plot;
    }

    $name = trim($omdb['Title'] ?? $title);
    $year = trim($omdb['Year'] ?? '');
    $genre = trim($omdb['Genre'] ?? '');
    $director = trim($omdb['Director'] ?? '');
    $actors = trim($omdb['Actors'] ?? '');

    $bits = [];
    if ($name !== '') {
        $line = $name;
        if ($year !== '' && strcasecmp($year, 'N/A') !== 0) {
            $line .= " ($year)";
        }
        $bits[] = $line . ' is a film';
        if ($genre !== '' && strcasecmp($genre, 'N/A') !== 0) {
            $bits[0] .= " in the $genre genre";
        }
        $bits[0] .= '.';
    }
    if ($director !== '' && strcasecmp($director, 'N/A') !== 0) {
        $bits[] = "Directed by $director.";
    }
    if ($actors !== '' && strcasecmp($actors, 'N/A') !== 0) {
        $bits[] = "Starring $actors.";
    }

    return implode(' ', $bits) ?: 'Overview not available for this title.';
}

function generateOverviewText($title, array $omdb) {
    if (aiProviderConfigured()) {
        $context = formatOmdbContext($omdb);
        $result = aiChatCompletion(
            'You write concise, patron-friendly movie overviews for a public library kiosk. '
            . 'Use only the provided movie facts. Write 2-4 sentences. Do not mention that you are an AI.',
            "Write an overview for the library movie \"{$title}\".\n\nMovie facts:\n{$context}"
        );
        if ($result['ok']) {
            return $result['text'];
        }
    }

    return buildFallbackOverview($omdb, $title);
}

function getOrCreateOverview($barcode, $title) {
    global $overviewCacheFile;

    $cache = loadJsonFile($overviewCacheFile);
    if (isset($cache[$barcode]['overview']) && trim($cache[$barcode]['overview']) !== '') {
        return [
            'ok' => true,
            'overview' => $cache[$barcode]['overview'],
            'cached' => true,
            'omdbCached' => true,
        ];
    }

    $omdbResult = fetchOmdbForMovie($barcode, $title);
    if (!$omdbResult['ok']) {
        return ['ok' => false, 'error' => $omdbResult['error'] ?? 'Could not fetch OMDb data'];
    }

    $overview = generateOverviewText($title, $omdbResult['data']);

    $cache[$barcode] = [
        'overview' => $overview,
        'createdAt' => date('c'),
        'title' => $title,
    ];
    saveJsonFile($overviewCacheFile, $cache);

    return [
        'ok' => true,
        'overview' => $overview,
        'cached' => false,
        'omdbCached' => !empty($omdbResult['cached']),
    ];
}

function getMovieContext($barcode, $title) {
    $overviewCache = loadJsonFile($GLOBALS['overviewCacheFile']);
    $overview = $overviewCache[$barcode]['overview'] ?? '';

    $omdbResult = fetchOmdbForMovie($barcode, $title);
    $omdbContext = $omdbResult['ok'] ? formatOmdbContext($omdbResult['data']) : '';

    return [
        'title' => $title,
        'overview' => $overview,
        'omdb' => $omdbContext,
    ];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'overview';
    if ($action !== 'overview') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Unknown action']);
        exit;
    }

    $barcode = trim($_GET['barcode'] ?? '');
    $title = trim($_GET['title'] ?? '');

    if ($barcode === '' || $title === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing barcode or title']);
        exit;
    }

    echo json_encode(getOrCreateOverview($barcode, $title));
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $barcode = trim($input['barcode'] ?? '');
    $title = trim($input['title'] ?? '');
    $question = trim($input['question'] ?? '');

    if ($barcode === '' || $title === '' || $question === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing barcode, title, or question']);
        exit;
    }

    if (!aiProviderConfigured()) {
        http_response_code(503);
        echo json_encode(['ok' => false, 'error' => 'AI assistant is not configured. Add GROQ_API_KEY to config.php.']);
        exit;
    }

    $qaKey = md5($barcode . '|' . strtolower($question));
    $qaCache = loadJsonFile($qaCacheFile);
    if (isset($qaCache[$qaKey]['answer'])) {
        echo json_encode([
            'ok' => true,
            'answer' => $qaCache[$qaKey]['answer'],
            'cached' => true,
        ]);
        exit;
    }

    $ctx = getMovieContext($barcode, $title);
    $prompt = "Movie title: {$ctx['title']}\n\n"
        . "Cached overview:\n{$ctx['overview']}\n\n"
        . "OMDb facts:\n{$ctx['omdb']}\n\n"
        . "Patron question: {$question}\n\n"
        . "Answer only about this movie. Keep the answer concise (2-5 sentences). "
        . "If the question is unrelated to this movie, politely redirect to the movie.";

    $result = aiChatCompletion(
        'You are a helpful movie guide at a public library kiosk. '
        . 'Answer questions using only the movie information provided. Stay focused on the selected movie.',
        $prompt
    );

    if (!$result['ok']) {
        http_response_code(502);
        echo json_encode($result);
        exit;
    }

    $qaCache[$qaKey] = [
        'barcode' => $barcode,
        'question' => $question,
        'answer' => $result['text'],
        'createdAt' => date('c'),
    ];
    saveJsonFile($qaCacheFile, $qaCache);

    echo json_encode([
        'ok' => true,
        'answer' => $result['text'],
        'cached' => false,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
