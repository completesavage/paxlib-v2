<?php
/**
 * Shared helpers for movie list / recordset sync
 */

function isItemAvailable($status) {
    $s = strtolower(trim($status ?? ''));
    if ($s === '') {
        return false;
    }
    if (strpos($s, 'in') === 0
        && strpos($s, 'transit') === false
        && strpos($s, 'hold') === false
        && strpos($s, 'processing') === false) {
        return true;
    }
    return false;
}

function normalizeRating($rating) {
    $rating = strtoupper(trim($rating ?? ''));
    $map = [
        'PG13' => 'PG-13',
        'PG 13' => 'PG-13',
        'NC17' => 'NC-17',
        'NC 17' => 'NC-17',
        'NOT RATED' => 'NR',
        'UNRATED' => 'NR',
        '' => 'NR',
    ];
    return $map[$rating] ?? $rating;
}

function extractRatingFromCallNumber($callNumber) {
    $cn = strtoupper(trim($callNumber));

    $patterns = [
        '/^(PG-13)\b/' => 'PG-13',
        '/^(PG13)\b/'  => 'PG-13',
        '/^(NC-17)\b/' => 'NC-17',
        '/^(NC17)\b/'  => 'NC-17',
        '/^(PG)\b/'    => 'PG',
        '/^(NR)\b/'    => 'NR',
        '/^(R)\b/'     => 'R',
        '/^(G)\b/'     => 'G',
    ];

    foreach ($patterns as $pattern => $rating) {
        if (preg_match($pattern, $cn)) {
            return $rating;
        }
    }

    return 'NR';
}

function enrichMovieAvailability(array $movie) {
    if (!isset($movie['available'])) {
        $movie['available'] = isItemAvailable($movie['status'] ?? '');
    }
    if (empty($movie['status'])) {
        $movie['status'] = $movie['available'] ? 'In' : 'Out';
    }
    if (!empty($movie['rating'])) {
        $movie['rating'] = normalizeRating($movie['rating']);
    }
    return $movie;
}

function noCoverPath() {
    return defined('NO_COVER_PATH') ? NO_COVER_PATH : '/img/no-cover.svg';
}

function isUsableCover($cover) {
    if ($cover === null || $cover === '') {
        return false;
    }
    $cover = trim((string)$cover);
    if ($cover === noCoverPath()) {
        return false;
    }
    if (substr($cover, -12) === 'no-cover.svg') {
        return false;
    }
    return true;
}

function loadCoverCacheFile($path) {
    if (!file_exists($path)) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function loadLegacyMovieCovers($dataDir) {
    $legacyFile = rtrim($dataDir, '/\\') . '/movies_cache.json';
    $map = [];

    if (!file_exists($legacyFile)) {
        return $map;
    }

    $raw = json_decode(file_get_contents($legacyFile), true);
    if (!is_array($raw) || isset($raw['statuses'])) {
        return $map;
    }

    $isList = array_keys($raw) === range(0, count($raw) - 1);
    if ($isList) {
        foreach ($raw as $movie) {
            if (!is_array($movie) || empty($movie['barcode'])) {
                continue;
            }
            if (isUsableCover($movie['cover'] ?? null)) {
                $map[$movie['barcode']] = $movie['cover'];
            }
        }
        return $map;
    }

    foreach ($raw as $barcode => $movie) {
        if (!is_array($movie)) {
            continue;
        }
        $bc = $movie['barcode'] ?? (is_string($barcode) ? $barcode : null);
        if ($bc && isUsableCover($movie['cover'] ?? null)) {
            $map[$bc] = $movie['cover'];
        }
    }

    return $map;
}

function buildCoverMaps($dataDir) {
    return [
        'covers' => loadCoverCacheFile(rtrim($dataDir, '/\\') . '/covers_cache.json'),
        'legacy' => loadLegacyMovieCovers($dataDir),
    ];
}

function resolveMovieCover($barcode, array $movie, array $coverMaps) {
    if (isUsableCover($movie['cover'] ?? null)) {
        return $movie['cover'];
    }
    if (isset($coverMaps['covers'][$barcode]) && isUsableCover($coverMaps['covers'][$barcode])) {
        return $coverMaps['covers'][$barcode];
    }
    if (isset($coverMaps['legacy'][$barcode]) && isUsableCover($coverMaps['legacy'][$barcode])) {
        return $coverMaps['legacy'][$barcode];
    }
    return null;
}

function coverFromBibInfo(array $bib) {
    $client = defined('SYNDETICS_CLIENT') ? SYNDETICS_CLIENT : 'ilheartland';
    $base = "https://secure.syndetics.com/index.aspx?isbn=/MC.GIF&client={$client}";

    if (!empty($bib['UPCNumber'])) {
        $upc = preg_replace('/[^0-9]/', '', $bib['UPCNumber']);
        if ($upc !== '') {
            return $base . '&upc=' . rawurlencode($upc);
        }
    }
    if (!empty($bib['OCLCNumber'])) {
        return $base . '&oclc=' . rawurlencode($bib['OCLCNumber']);
    }
    if (!empty($bib['ISBN'])) {
        $isbn = preg_replace('/[^0-9X]/i', '', strtoupper($bib['ISBN']));
        if ($isbn !== '') {
            return $base . '&isbn=' . rawurlencode($isbn);
        }
    }

    return null;
}

function applyCoverMapsToMovie(array $movie, array $coverMaps) {
    $cover = resolveMovieCover($movie['barcode'] ?? '', $movie, $coverMaps);
    if ($cover !== null) {
        $movie['cover'] = $cover;
    }
    return $movie;
}
