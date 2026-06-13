<?php
if (!defined('APP_NAME')) require_once __DIR__ . '/config.php';

// ── JSON I/O ──────────────────────────────────────────────────────────────────

function read_json(string $file): array {
    if (!file_exists($file)) return [];
    $handle = fopen($file, 'r');
    if (!$handle) return [];
    flock($handle, LOCK_SH);
    $content = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return json_decode($content, true) ?? [];
}

function write_json(string $file, array $data): void {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $handle = fopen($file, 'c');
    if (!$handle) return;
    flock($handle, LOCK_EX);
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    flock($handle, LOCK_UN);
    fclose($handle);
}

// ── HTTP FETCH (cURL preferred, file_get_contents fallback) ───────────────────

function fetch_url(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Mundial2026-App/1.0',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($result !== false && $code === 200) ? $result : null;
    }
    // Fallback for servers with allow_url_fopen enabled
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'Mundial2026-App/1.0']]);
    $result = @file_get_contents($url, false, $ctx);
    return $result !== false ? $result : null;
}

// ── MATCHES ───────────────────────────────────────────────────────────────────

function get_matches(): array {
    $cache_file = DATA_DIR . '/matches.json';
    $overrides  = read_json(DATA_DIR . '/results_override.json');

    // Use cache if fresh
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_TTL) {
        $data = read_json($cache_file);
        return apply_overrides($data, $overrides);
    }

    // Fetch from API
    $json = fetch_url(MATCHES_API_URL);
    if ($json !== null) {
        $raw = json_decode($json, true);
        if ($raw && isset($raw['matches'])) {
            $data = normalise_matches($raw['matches']);
            write_json($cache_file, $data);
            return apply_overrides($data, $overrides);
        }
    }

    // Fall back to stale cache
    if (file_exists($cache_file)) {
        return apply_overrides(read_json($cache_file), $overrides);
    }
    return [];
}

function normalise_matches(array $raw): array {
    $matches = [];
    foreach ($raw as $i => $m) {
        $id = match_id_from_raw($m, $i);
        $kickoff_utc = parse_kickoff_utc($m['date'] ?? '', $m['time'] ?? '');
        $matches[] = [
            'id'          => $id,
            'num'         => $m['num'] ?? ($i + 1),
            'round'       => $m['round'] ?? '',
            'group'       => $m['group'] ?? null,
            'date'        => $m['date'] ?? '',
            'time'        => $m['time'] ?? '',
            'kickoff_utc' => $kickoff_utc,
            'team1'       => $m['team1'] ?? null,
            'team2'       => $m['team2'] ?? null,
            'team1_ref'   => $m['team1_ref'] ?? null,
            'team2_ref'   => $m['team2_ref'] ?? null,
            'score'       => $m['score'] ?? null,
            'ground'      => $m['ground'] ?? null,
        ];
    }
    return $matches;
}

function match_id_from_raw(array $m, int $fallback_idx): string {
    if (!empty($m['num'])) return 'match_' . $m['num'];
    $t1 = slugify($m['team1'] ?? ($m['team1_ref'] ?? 'tbd1'));
    $t2 = slugify($m['team2'] ?? ($m['team2_ref'] ?? 'tbd2'));
    return ($m['date'] ?? 'tbd') . '_' . $t1 . '_vs_' . $t2;
}

function apply_overrides(array $matches, array $overrides): array {
    if (empty($overrides)) return $matches;
    $by_id = [];
    foreach ($overrides as $o) $by_id[$o['id']] = $o;
    foreach ($matches as &$m) {
        if (isset($by_id[$m['id']])) {
            $o = $by_id[$m['id']];
            if (isset($o['score'])) $m['score'] = $o['score'];
        }
    }
    return $matches;
}

function parse_kickoff_utc(string $date, string $time_str): ?string {
    if (!$date) return null;
    // Format: "13:00 UTC-6" or "20:00" (bare)
    if (preg_match('/(\d{1,2}):(\d{2})(?:\s+UTC([+-]\d+))?/', $time_str, $m)) {
        $h   = (int)$m[1];
        $min = (int)$m[2];
        $offset = isset($m[3]) ? (int)$m[3] : 0; // offset from UTC (e.g. -6)
        // Convert to UTC: UTC = local − offset
        $total_min = $h * 60 + $min - $offset * 60;
        $days_extra = 0;
        while ($total_min < 0)   { $total_min += 1440; $days_extra--; }
        while ($total_min >= 1440){ $total_min -= 1440; $days_extra++; }
        $uh = intdiv($total_min, 60);
        $um = $total_min % 60;
        try {
            $dt = new DateTime($date, new DateTimeZone('UTC'));
            if ($days_extra) $dt->modify("{$days_extra} days");
            $dt->setTime($uh, $um);
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) { return null; }
    }
    // Fallback: noon UTC of that date
    return $date . ' 12:00:00';
}

function is_locked(array $match): bool {
    if (!$match['kickoff_utc']) return false;
    return strtotime($match['kickoff_utc']) <= time();
}

function is_qf_started(array $matches): bool {
    foreach ($matches as $m) {
        $round_lc = strtolower($m['round']);
        $is_qf = false;
        foreach (QF_ROUND_KEYWORDS as $kw) {
            if (str_contains($round_lc, $kw)) { $is_qf = true; break; }
        }
        if ($is_qf && $m['kickoff_utc'] && strtotime($m['kickoff_utc']) <= time()) {
            return true;
        }
    }
    return false;
}

function get_all_teams(array $matches): array {
    $teams = [];
    foreach ($matches as $m) {
        if ($m['team1']) $teams[$m['team1']] = true;
        if ($m['team2']) $teams[$m['team2']] = true;
    }
    ksort($teams);
    return array_keys($teams);
}

// ── AUTH ──────────────────────────────────────────────────────────────────────

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('mundial2026');
        session_start();
    }
}

function current_user(): ?array {
    start_session();
    return $_SESSION['user'] ?? null;
}

function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: index.php'); exit; }
    return $u;
}

function find_user(string $name): ?array {
    $users = read_json(DATA_DIR . '/users.json');
    foreach ($users as $u) {
        if (mb_strtolower($u['name']) === mb_strtolower($name)) return $u;
    }
    return null;
}

function create_user(string $name, string $pin): array {
    $users = read_json(DATA_DIR . '/users.json');
    $user  = [
        'id'         => uniqid('u', true),
        'name'       => $name,
        'pin_hash'   => password_hash($pin, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
    ];
    $users[] = $user;
    write_json(DATA_DIR . '/users.json', $users);
    return $user;
}

// ── PREDICTIONS ───────────────────────────────────────────────────────────────

function get_user_prediction(string $user_id, string $match_id): ?array {
    foreach (read_json(DATA_DIR . '/predictions.json') as $p) {
        if ($p['user_id'] === $user_id && $p['match_id'] === $match_id) return $p;
    }
    return null;
}

function save_prediction(string $user_id, string $user_name, string $match_id, int $g1, int $g2): void {
    $preds = read_json(DATA_DIR . '/predictions.json');
    $now   = date('Y-m-d H:i:s');
    foreach ($preds as &$p) {
        if ($p['user_id'] === $user_id && $p['match_id'] === $match_id) {
            $p['g1'] = $g1; $p['g2'] = $g2; $p['updated_at'] = $now;
            write_json(DATA_DIR . '/predictions.json', $preds);
            return;
        }
    }
    $preds[] = compact('user_id', 'user_name', 'match_id', 'g1', 'g2') + ['created_at' => $now, 'updated_at' => $now];
    write_json(DATA_DIR . '/predictions.json', $preds);
}

function get_user_top4(string $user_id): ?array {
    foreach (read_json(DATA_DIR . '/top4.json') as $t) {
        if ($t['user_id'] === $user_id) return $t;
    }
    return null;
}

function save_top4(string $user_id, string $user_name, array $positions): void {
    $top4 = read_json(DATA_DIR . '/top4.json');
    $now  = date('Y-m-d H:i:s');
    foreach ($top4 as &$t) {
        if ($t['user_id'] === $user_id) {
            $t = array_merge($t, $positions, ['updated_at' => $now]);
            write_json(DATA_DIR . '/top4.json', $top4);
            return;
        }
    }
    $top4[] = ['user_id' => $user_id, 'user_name' => $user_name] + $positions + ['created_at' => $now, 'updated_at' => $now];
    write_json(DATA_DIR . '/top4.json', $top4);
}

// ── SCORING ───────────────────────────────────────────────────────────────────

function calc_match_points(array $pred, array $match): int {
    $score = $match['score'];
    if (!$score || !isset($score['ft'])) return 0;
    [$r1, $r2] = $score['ft'];
    [$p1, $p2] = [$pred['g1'], $pred['g2']];

    if ($p1 === $r1 && $p2 === $r2) return PTS_EXACT;
    $r_out = $r1 <=> $r2;
    $p_out = $p1 <=> $p2;
    return ($p_out === $r_out) ? PTS_OUTCOME : 0;
}

function calc_top4_points(array $pred, array $final_positions): int {
    // $final_positions = [1=>'Brazil', 2=>'Argentina', 3=>'France', 4=>'Spain']
    if (empty($final_positions)) return 0;
    $pts = 0;
    $top4_teams = array_values($final_positions);
    foreach ([1, 2, 3, 4] as $pos) {
        $key = "pos{$pos}";
        if (!isset($pred[$key])) continue;
        $picked = $pred[$key];
        if (in_array($picked, $top4_teams, true)) {
            $pts += PTS_IN_TOP4;
        }
        if (isset($final_positions[$pos]) && $final_positions[$pos] === $picked) {
            $bonus = PTS_POS[$pos] ?? 0;
            $pts += $bonus;
        }
    }
    return $pts;
}

function get_leaderboard(): array {
    $matches    = get_matches();
    $matches_by_id = [];
    foreach ($matches as $m) $matches_by_id[$m['id']] = $m;

    $preds  = read_json(DATA_DIR . '/predictions.json');
    $top4s  = read_json(DATA_DIR . '/top4.json');
    $finals = read_json(DATA_DIR . '/final_positions.json'); // [1=>team,...] admin-set

    // Group predictions by user
    $user_match_pts = [];
    foreach ($preds as $p) {
        $uid = $p['user_id'];
        $mid = $p['match_id'];
        if (!isset($matches_by_id[$mid])) continue;
        $pts = calc_match_points($p, $matches_by_id[$mid]);
        $user_match_pts[$uid]['name']   = $p['user_name'];
        $user_match_pts[$uid]['match']  = ($user_match_pts[$uid]['match'] ?? 0) + $pts;
        $user_match_pts[$uid]['count']  = ($user_match_pts[$uid]['count'] ?? 0) + 1;
        $user_match_pts[$uid]['exact']  = ($user_match_pts[$uid]['exact'] ?? 0) + ($pts === PTS_EXACT ? 1 : 0);
    }

    // Top4 points
    $user_top4_pts = [];
    foreach ($top4s as $t) {
        $user_top4_pts[$t['user_id']] = [
            'name' => $t['user_name'],
            'pts'  => calc_top4_points($t, $finals),
            'pred' => $t,
        ];
    }

    // Start from ALL registered users so everyone appears even with 0 pts
    $all_users = read_json(DATA_DIR . '/users.json');
    $board = [];
    foreach ($all_users as $u) {
        $uid        = $u['id'];
        $match_info = $user_match_pts[$uid] ?? ['match' => 0, 'count' => 0, 'exact' => 0];
        $top4_pts   = $user_top4_pts[$uid]['pts'] ?? 0;
        $board[] = [
            'user_id'     => $uid,
            'name'        => $u['name'],
            'match_pts'   => $match_info['match'],
            'top4_pts'    => $top4_pts,
            'total'       => $match_info['match'] + $top4_pts,
            'predictions' => $match_info['count'],
            'exact'       => $match_info['exact'],
            'top4_pred'   => $user_top4_pts[$uid]['pred'] ?? null,
        ];
    }
    usort($board, fn($a, $b) => $b['total'] <=> $a['total'] ?: $b['exact'] <=> $a['exact'] ?: strcmp($a['name'], $b['name']));
    return $board;
}

// ── UI HELPERS ────────────────────────────────────────────────────────────────

function h(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $s): string {
    return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $s));
}

function team_flag(string $name): string {
    static $flags = [
        'Mexico'       => '🇲🇽', 'USA'          => '🇺🇸', 'United States' => '🇺🇸',
        'Canada'       => '🇨🇦', 'Brazil'       => '🇧🇷', 'Argentina'     => '🇦🇷',
        'Colombia'     => '🇨🇴', 'Ecuador'      => '🇪🇨', 'Uruguay'       => '🇺🇾',
        'Venezuela'    => '🇻🇪', 'Chile'        => '🇨🇱', 'Peru'          => '🇵🇪',
        'Bolivia'      => '🇧🇴', 'Paraguay'     => '🇵🇾', 'Honduras'      => '🇭🇳',
        'Panama'       => '🇵🇦', 'Costa Rica'   => '🇨🇷', 'Jamaica'       => '🇯🇲',
        'El Salvador'  => '🇸🇻', 'Cuba'         => '🇨🇺', 'Haiti'         => '🇭🇹',
        'Trinidad and Tobago' => '🇹🇹',
        'Germany'      => '🇩🇪', 'France'       => '🇫🇷', 'Spain'        => '🇪🇸',
        'England'      => '🏴󠁧󠁢󠁥󠁮󠁧󠁿', 'Portugal'    => '🇵🇹', 'Netherlands'  => '🇳🇱',
        'Italy'        => '🇮🇹', 'Belgium'      => '🇧🇪', 'Croatia'      => '🇭🇷',
        'Switzerland'  => '🇨🇭', 'Austria'      => '🇦🇹', 'Denmark'      => '🇩🇰',
        'Sweden'       => '🇸🇪', 'Norway'       => '🇳🇴', 'Poland'       => '🇵🇱',
        'Czech Republic'=> '🇨🇿', 'Slovakia'    => '🇸🇰', 'Hungary'      => '🇭🇺',
        'Romania'      => '🇷🇴', 'Serbia'       => '🇷🇸', 'Ukraine'      => '🇺🇦',
        'Turkey'       => '🇹🇷', 'Greece'       => '🇬🇷', 'Scotland'     => '🏴󠁧󠁢󠁳󠁣󠁴󠁿',
        'Wales'        => '🏴󠁧󠁢󠁷󠁬󠁳󠁿', 'Ireland'      => '🇮🇪', 'Albania'      => '🇦🇱',
        'Slovenia'     => '🇸🇮', 'Georgia'      => '🇬🇪', 'Kosovo'       => '🇽🇰',
        'Morocco'      => '🇲🇦', 'Nigeria'      => '🇳🇬', 'Senegal'      => '🇸🇳',
        'Algeria'      => '🇩🇿', 'Tunisia'      => '🇹🇳', 'South Africa' => '🇿🇦',
        'Cameroon'     => '🇨🇲', 'Ghana'        => '🇬🇭', 'DR Congo'     => '🇨🇩',
        'Congo DR'     => '🇨🇩', 'Ivory Coast'  => '🇨🇮', "Côte d'Ivoire"=> '🇨🇮',
        'Mali'         => '🇲🇱', 'Egypt'        => '🇪🇬', 'Tanzania'     => '🇹🇿',
        'Zambia'       => '🇿🇲', 'Comoros'      => '🇰🇲', 'Mozambique'   => '🇲🇿',
        'Guinea'       => '🇬🇳', 'Benin'        => '🇧🇯', 'Namibia'      => '🇳🇦',
        'Japan'        => '🇯🇵', 'South Korea'  => '🇰🇷', 'Korea Republic' => '🇰🇷',
        'Saudi Arabia' => '🇸🇦', 'Iran'         => '🇮🇷', 'Australia'    => '🇦🇺',
        'Qatar'        => '🇶🇦', 'UAE'          => '🇦🇪', 'Indonesia'    => '🇮🇩',
        'Uzbekistan'   => '🇺🇿', 'Oman'         => '🇴🇲', 'Iraq'         => '🇮🇶',
        'Jordan'       => '🇯🇴', 'China'        => '🇨🇳', 'India'        => '🇮🇳',
        'Thailand'     => '🇹🇭', 'Vietnam'      => '🇻🇳', 'New Zealand'  => '🇳🇿',
    ];
    return $flags[$name] ?? '🏳️';
}

function format_match_time(string $kickoff_utc): string {
    try {
        $dt = new DateTime($kickoff_utc, new DateTimeZone('UTC'));
        return $dt->format('d M · H:i') . ' UTC';
    } catch (Exception $e) { return $kickoff_utc; }
}

function outcome_label(int $g1, int $g2): string {
    if ($g1 > $g2) return 'Gana equipo local';
    if ($g2 > $g1) return 'Gana equipo visitante';
    return 'Empate';
}
