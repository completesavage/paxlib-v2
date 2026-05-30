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
