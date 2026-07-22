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

function normalizeMovieIdentityText($value) {
    $value = preg_replace('/\s*\[videorecording\]\s*/i', '', (string)$value);
    $value = strtoupper(trim($value));
    return preg_replace('/[^A-Z0-9]+/', '', $value);
}

function movieCoverIdentity(array $movie) {
    return [
        'itemRecordId' => (int)($movie['itemRecordId'] ?? 0),
        'bibRecordId' => (int)($movie['bibRecordId'] ?? 0),
        'titleKey' => normalizeMovieIdentityText($movie['title'] ?? ''),
    ];
}

function coverIdentityMatches(array $movie, $cachedMeta) {
    if (!is_array($cachedMeta)) {
        return false;
    }

    $current = movieCoverIdentity($movie);
    $cachedItem = (int)($cachedMeta['itemRecordId'] ?? 0);
    $cachedBib = (int)($cachedMeta['bibRecordId'] ?? 0);
    $cachedTitle = (string)($cachedMeta['titleKey'] ?? '');

    // Item record ID is the strongest signal and changes when a barcode is reused.
    if ($current['itemRecordId'] > 0 && $cachedItem > 0) {
        return $current['itemRecordId'] === $cachedItem;
    }

    // Bib ID is useful when the item endpoint has already supplied it.
    if ($current['bibRecordId'] > 0 && $cachedBib > 0) {
        return $current['bibRecordId'] === $cachedBib;
    }

    // Last-resort compatibility check for records created before metadata existed.
    return $current['titleKey'] !== '' && $cachedTitle !== '' && $current['titleKey'] === $cachedTitle;
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
                $map[$movie['barcode']] = [
                    'cover' => $movie['cover'],
                    'meta' => movieCoverIdentity($movie),
                ];
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
            $map[$bc] = [
                'cover' => $movie['cover'],
                'meta' => movieCoverIdentity($movie),
            ];
        }
    }

    return $map;
}

function buildCoverMaps($dataDir) {
    return [
        'covers' => loadCoverCacheFile(rtrim($dataDir, '/\\') . '/covers_cache.json'),
        'meta' => loadCoverCacheFile(rtrim($dataDir, '/\\') . '/covers_cache_meta.json'),
        'legacy' => loadLegacyMovieCovers($dataDir),
    ];
}

function resolveLocalUploadCover($barcode, $coversDir = null) {
    $barcode = trim((string)$barcode);
    if ($barcode === '') {
        return null;
    }

    $coversDir = $coversDir ?? dirname(__DIR__) . '/uploads/covers';
    if (!is_dir($coversDir)) {
        return null;
    }

    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
        $path = rtrim($coversDir, '/\\') . '/' . $barcode . '.' . $ext;
        if (file_exists($path)) {
            return '/uploads/covers/' . $barcode . '.' . $ext;
        }
    }

    return null;
}

function resolveMovieCover($barcode, array $movie, array $coverMaps) {
    $local = resolveLocalUploadCover($barcode);
    if (isUsableCover($local)) {
        return $local;
    }

    $meta = $coverMaps['meta'][$barcode] ?? null;
    $trusted = coverIdentityMatches($movie, $meta);

    // Only trust an in-list or cache cover when it belongs to this exact item/bib.
    if ($trusted && isUsableCover($movie['cover'] ?? null)) {
        return $movie['cover'];
    }
    if ($trusted && isset($coverMaps['covers'][$barcode]) && isUsableCover($coverMaps['covers'][$barcode])) {
        return $coverMaps['covers'][$barcode];
    }

    $legacy = $coverMaps['legacy'][$barcode] ?? null;
    if (is_array($legacy)
        && coverIdentityMatches($movie, $legacy['meta'] ?? null)
        && isUsableCover($legacy['cover'] ?? null)) {
        return $legacy['cover'];
    }

    return null;
}

function firstIdentifierValue($value, $pattern) {
    $values = is_array($value) ? $value : [$value];
    foreach ($values as $candidate) {
        if (is_array($candidate)) {
            $candidate = implode(' ', array_filter($candidate, 'is_scalar'));
        }
        if (!is_scalar($candidate)) {
            continue;
        }
        if (preg_match($pattern, (string)$candidate, $m)) {
            return $m[1] ?? $m[0];
        }
    }
    return null;
}

function coverFromBibInfo(array $bib) {
    $client = defined('SYNDETICS_CLIENT') ? SYNDETICS_CLIENT : 'ilheartland';

    // Do not strip all non-digits from an entire field: Polaris can return
    // multiple identifiers, and concatenating them creates a bogus number.
    $isbn = firstIdentifierValue($bib['ISBN'] ?? null, '/(?<!\d)(97[89]\d{10}|\d{9}[\dXx])(?!\d)/');
    $upc = firstIdentifierValue($bib['UPCNumber'] ?? null, '/(?<!\d)(\d{12,14})(?!\d)/');

    $oclcRaw = null;
    $oclcValues = is_array($bib['OCLCNumber'] ?? null) ? $bib['OCLCNumber'] : [$bib['OCLCNumber'] ?? null];
    foreach ($oclcValues as $candidate) {
        if (!is_scalar($candidate)) {
            continue;
        }
        if (preg_match('/(?:\(OCOLC\)|ocm|ocn|on)?\s*0*(\d{6,15})/i', (string)$candidate, $m)) {
            $oclcRaw = '(OCOLC)' . str_pad($m[1], 15, '0', STR_PAD_LEFT);
            break;
        }
    }

    if (!$isbn && !$upc && !$oclcRaw) {
        return null;
    }

    $isbnPart = $isbn ? strtoupper($isbn) . '/MC.GIF' : '/MC.GIF';
    $url = 'https://secure.syndetics.com/index.aspx?isbn=' . $isbnPart
        . '&client=' . rawurlencode($client);

    if ($upc) {
        $url .= '&upc=' . rawurlencode($upc);
    }
    if ($oclcRaw) {
        // Keep the familiar (OCOLC) prefix readable; digits remain URL-safe.
        $url .= '&oclc=' . $oclcRaw;
    }

    return $url;
}

function applyCoverMapsToMovie(array $movie, array $coverMaps) {
    $cover = resolveMovieCover($movie['barcode'] ?? '', $movie, $coverMaps);
    if ($cover !== null) {
        $movie['cover'] = $cover;
    }
    return $movie;
}

/**
 * Extract shelf number from call number (last 1–4 digit token).
 * e.g. "PG13 DVD 86" → "86", "G DVD 7" → "7"
 */
function extractShelfNumberFromCallNumber($callNumber) {
    $callNumber = trim($callNumber ?? '');
    if ($callNumber === '') {
        return null;
    }

    $parts = preg_split('/\s+/', $callNumber);
    for ($i = count($parts) - 1; $i >= 0; $i--) {
        if (preg_match('/^\d{1,4}$/', $parts[$i])) {
            return $parts[$i];
        }
    }

    return null;
}

function enrichMovieShelfNumber(array $movie) {
    if (empty($movie['shelfNumber']) && !empty($movie['callNumber'])) {
        $sn = extractShelfNumberFromCallNumber($movie['callNumber']);
        if ($sn !== null) {
            $movie['shelfNumber'] = $sn;
        }
    }
    if (!empty($movie['shelfNumber'])) {
        $movie['dvdId'] = (string)$movie['shelfNumber'];
    }
    return $movie;
}

function parseMovieDate($value) {
    if ($value === null || $value === '') {
        return null;
    }
    $ts = strtotime((string)$value);
    return $ts !== false ? $ts : null;
}

function isStatusIn($status) {
    return isItemAvailable($status);
}

function isHotMovie(array $movie, $days = 14) {
    if (!isStatusIn($movie['status'] ?? '')) {
        return false;
    }
    $activity = parseMovieDate($movie['lastActivity'] ?? null);
    if ($activity === null) {
        return false;
    }
    return $activity >= strtotime("-{$days} days");
}

function isNewArrivalMovie(array $movie) {
    // Polaris is the source of truth: anything shelved as "New Arrivals"
    // should remain in the kiosk row regardless of when it was first synced.
    $shelfLocation = strtolower(trim((string)($movie['shelfLocation'] ?? '')));
    if ($shelfLocation === 'new arrivals') {
        return true;
    }

    // Backward-compatible fallback for CSV/older cached records that do not
    // contain ShelfLocation.
    $dateAdded = parseMovieDate($movie['dateAdded'] ?? null);
    if ($dateAdded === null) {
        return false;
    }
    $monthStart = strtotime(date('Y-m-01 00:00:00'));
    $monthEnd = strtotime(date('Y-m-t 23:59:59'));
    return $dateAdded >= $monthStart && $dateAdded <= $monthEnd;
}

function enrichMovieCatalogFlags(array $movie) {
    $movie['isHot'] = isHotMovie($movie);
    $movie['isNew'] = isNewArrivalMovie($movie);
    return $movie;
}

function sortMoviesByDateField(array $movies, $field, $desc = true) {
    usort($movies, function ($a, $b) use ($field, $desc) {
        $ta = parseMovieDate($a[$field] ?? null) ?? 0;
        $tb = parseMovieDate($b[$field] ?? null) ?? 0;
        if ($ta === $tb) {
            return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
        }
        return $desc ? ($tb <=> $ta) : ($ta <=> $tb);
    });
    return $movies;
}

function filterHotMovies(array $movies, $days = 14) {
    return sortMoviesByDateField(
        array_values(array_filter($movies, fn($m) => isHotMovie($m, $days))),
        'lastActivity',
        true
    );
}

function filterNewArrivalMovies(array $movies) {
    return sortMoviesByDateField(
        array_values(array_filter($movies, fn($m) => isNewArrivalMovie($m))),
        'dateAdded',
        true
    );
}
