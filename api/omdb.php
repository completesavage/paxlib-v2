<?php
/**
 * OMDb API client with local JSON cache (keyed by barcode).
 */

function omdbApiKey() {
    return defined('OMDB_API_KEY') ? trim(OMDB_API_KEY) : '';
}

function omdbCachePath() {
    return __DIR__ . '/../data/omdb_cache.json';
}

function loadOmdbCache() {
    $path = omdbCachePath();
    if (!file_exists($path)) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveOmdbCache(array $cache) {
    $dir = dirname(omdbCachePath());
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(omdbCachePath(), json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function cleanTitleForOmdb($title) {
    $title = preg_replace('/\s*\[videorecording\]\s*/i', '', (string)$title);
    $title = trim($title, " \t\n\r\0\x0B/:-");
    return trim($title);
}

function extractYearFromTitle($title) {
    if (preg_match('/\((\d{4})\)/', $title, $m)) {
        return $m[1];
    }
    return null;
}

function stripYearFromTitle($title) {
    return trim(preg_replace('/\s*\(\d{4}\)\s*/', ' ', $title));
}

function getCachedOmdb($barcode) {
    $cache = loadOmdbCache();
    return $cache[$barcode] ?? null;
}

function cacheOmdbResult($barcode, array $data) {
    $cache = loadOmdbCache();
    $cache[$barcode] = [
        'fetchedAt' => date('c'),
        'data' => $data,
    ];
    saveOmdbCache($cache);
}

function fetchOmdbFromApi($title, $year = null) {
    $key = omdbApiKey();
    if ($key === '') {
        return ['ok' => false, 'error' => 'OMDB_API_KEY not configured'];
    }

    $searchTitle = stripYearFromTitle(cleanTitleForOmdb($title));
    if ($searchTitle === '') {
        return ['ok' => false, 'error' => 'Empty title'];
    }

    $params = [
        't' => $searchTitle,
        'apikey' => $key,
        'plot' => 'full',
        'r' => 'json',
    ];
    if ($year) {
        $params['y'] = $year;
    }

    $url = 'https://www.omdbapi.com/?' . http_build_query($params);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 12,
            'ignore_errors' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return ['ok' => false, 'error' => 'OMDb request failed'];
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return ['ok' => false, 'error' => 'Invalid OMDb response'];
    }

    if (($json['Response'] ?? '') !== 'True') {
        return ['ok' => false, 'error' => $json['Error'] ?? 'Movie not found on OMDb'];
    }

    return ['ok' => true, 'data' => $json];
}

function fetchOmdbForMovie($barcode, $title) {
    $barcode = trim((string)$barcode);
    if ($barcode !== '') {
        $cached = getCachedOmdb($barcode);
        if ($cached && !empty($cached['data'])) {
            return ['ok' => true, 'data' => $cached['data'], 'cached' => true];
        }
    }

    $year = extractYearFromTitle($title);
    $result = fetchOmdbFromApi($title, $year);

    if ($result['ok'] && $barcode !== '') {
        cacheOmdbResult($barcode, $result['data']);
    }

    if ($result['ok']) {
        $result['cached'] = false;
    }

    return $result;
}

function omdbPosterUrl(array $omdbData) {
    $poster = trim($omdbData['Poster'] ?? '');
    if ($poster === '' || strcasecmp($poster, 'N/A') === 0) {
        return null;
    }
    return $poster;
}

function formatOmdbContext(array $omdb) {
    $parts = [];
    foreach (['Title', 'Year', 'Rated', 'Runtime', 'Genre', 'Director', 'Actors', 'Plot', 'Awards', 'imdbRating'] as $field) {
        $val = trim($omdb[$field] ?? '');
        if ($val !== '' && strcasecmp($val, 'N/A') !== 0) {
            $parts[] = "$field: $val";
        }
    }
    return implode("\n", $parts);
}
