<?php
require_once 'config.php';

// ── HTTP Basic Auth ──────────────────────────────────────────
$authUser = $_SERVER['PHP_AUTH_USER'] ?? '';
$authPass = $_SERVER['PHP_AUTH_PW']   ?? '';

if ($authUser !== 'admin' || $authPass !== 'ubm_admin_2026') {
    header('WWW-Authenticate: Basic realm="Unbelieveme Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access denied.';
    exit;
}

// ── Read data files ─────────────────────────────────────────
$sessions = [];
$reports  = [];

if (is_dir(DATA_DIR)) {
    foreach (glob(DATA_DIR . '/session_*.json') as $f) {
        $d = json_decode(file_get_contents($f), true);
        if ($d) $sessions[$d['session_id'] ?? basename($f)] = $d;
    }
    foreach (glob(DATA_DIR . '/report_*.json') as $f) {
        $d = json_decode(file_get_contents($f), true);
        if ($d) $reports[$d['session_id'] ?? basename($f)] = $d;
    }
}

// ── Determine phase reached per session ─────────────────────
// Phase 1: q1–q10  Phase 2: q11–q16  Phase 3: q17–q20  Complete: report exists
function phaseReached($session, $hasReport) {
    if ($hasReport) return 'Complete';
    $answers = $session['answers'] ?? [];
    $maxQ = 0;
    foreach (array_keys($answers) as $k) {
        if (preg_match('/^q(\d+)$/', $k, $m)) $maxQ = max($maxQ, (int)$m[1]);
    }
    if ($maxQ === 0)  return 'Started';
    if ($maxQ <= 10)  return 'Phase 1';
    if ($maxQ <= 16)  return 'Phase 2';
    if ($maxQ <= 20)  return 'Phase 3';
    return 'Phase 3';
}

// ── Build rows ───────────────────────────────────────────────
$rows = [];
foreach ($sessions as $sid => $s) {
    $hasReport = isset($reports[$sid]);
    $rows[] = [
        'date'      => $s['updated_at'] ?? ($s['created_at'] ?? ''),
        'email'     => $reports[$sid]['email'] ?? '—',
        'phase'     => phaseReached($s, $hasReport),
        'complete'  => $hasReport,
        'session'   => $sid,
    ];
}
// Also include sessions that only exist in reports (edge case)
foreach ($reports as $sid => $r) {
    if (!isset($sessions[$sid])) {
        $rows[] = [
            'date'     => $r['generated_at'] ?? '',
            'email'    => $r['email'] ?? '—',
            'phase'    => 'Complete',
            'complete' => true,
            'session'  => $sid,
        ];
    }
}

// Sort newest first
usort($rows, fn($a, $b) => strcmp($b['date'], $a['date']));

// ── Stats ────────────────────────────────────────────────────
$totalSessions  = count($rows);
$totalReports   = count($reports);
$completionRate = $totalSessions > 0 ? round($totalReports / $totalSessions * 100) : 0;

$dropoff = ['Started' => 0, 'Phase 1' => 0, 'Phase 2' => 0, 'Phase 3' => 0, 'Complete' => 0];
foreach ($rows as $r) {
    $p = $r['phase'];
    if (isset($dropoff[$p])) $dropoff[$p]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Unbelieveme — Admin</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #1a1916;
      color: #ddd6cc;
      font-family: Helvetica, Arial, sans-serif;
      font-size: 14px;
      line-height: 1.7;
      padding: 48px 32px;
    }
    h1 {
      font-size: 11px;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: #b8a070;
      margin-bottom: 48px;
    }
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 16px;
      margin-bottom: 48px;
    }
    .stat {
      background: #201f1c;
      border: 1px solid #2c2a26;
      padding: 20px 24px;
    }
    .stat-label {
      font-size: 9px;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: #5a5650;
      margin-bottom: 8px;
    }
    .stat-value {
      font-size: 28px;
      font-weight: 300;
      color: #b8a070;
      line-height: 1;
    }
    .section-title {
      font-size: 9px;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: #5a5650;
      margin-bottom: 16px;
    }
    .dropoff {
      display: flex;
      gap: 12px;
      margin-bottom: 48px;
      flex-wrap: wrap;
    }
    .dropoff-item {
      background: #201f1c;
      border: 1px solid #2c2a26;
      padding: 12px 20px;
      min-width: 120px;
    }
    .dropoff-phase {
      font-size: 9px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #5a5650;
      margin-bottom: 4px;
    }
    .dropoff-count {
      font-size: 22px;
      color: #ddd6cc;
      font-weight: 300;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    thead th {
      font-size: 9px;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: #5a5650;
      text-align: left;
      padding: 10px 16px;
      border-bottom: 1px solid #2c2a26;
    }
    tbody tr {
      border-bottom: 1px solid #201f1c;
    }
    tbody tr:hover {
      background: #201f1c;
    }
    tbody td {
      padding: 12px 16px;
      color: #c8c0b4;
      font-size: 13px;
      vertical-align: middle;
    }
    .badge {
      display: inline-block;
      font-size: 8px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      padding: 3px 8px;
      border: 1px solid;
    }
    .badge-yes  { color: #b8a070; border-color: #7a6a48; }
    .badge-no   { color: #5a5650; border-color: #2c2a26; }
    .badge-phase { color: #8a8278; border-color: #2c2a26; }
    .session-id { font-size: 11px; color: #5a5650; font-family: monospace; }
  </style>
</head>
<body>

  <h1>Unbelieveme — Admin Dashboard</h1>

  <div class="stats">
    <div class="stat">
      <div class="stat-label">Sessions started</div>
      <div class="stat-value"><?php echo $totalSessions; ?></div>
    </div>
    <div class="stat">
      <div class="stat-label">Reports generated</div>
      <div class="stat-value"><?php echo $totalReports; ?></div>
    </div>
    <div class="stat">
      <div class="stat-label">Completion rate</div>
      <div class="stat-value"><?php echo $completionRate; ?>%</div>
    </div>
  </div>

  <div class="section-title" style="margin-bottom:16px;">Drop-off by phase</div>
  <div class="dropoff">
    <?php foreach ($dropoff as $phase => $count): ?>
    <div class="dropoff-item">
      <div class="dropoff-phase"><?php echo htmlspecialchars($phase); ?></div>
      <div class="dropoff-count"><?php echo $count; ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="section-title">Sessions</div>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Email</th>
        <th>Phase reached</th>
        <th>Complete</th>
        <th>Session ID</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
      <tr><td colspan="5" style="color:#5a5650;padding:24px 16px;">No sessions yet.</td></tr>
      <?php else: foreach ($rows as $row): ?>
      <tr>
        <td><?php echo $row['date'] ? date('d M Y  H:i', strtotime($row['date'])) : '—'; ?></td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><span class="badge badge-phase"><?php echo htmlspecialchars($row['phase']); ?></span></td>
        <td><span class="badge <?php echo $row['complete'] ? 'badge-yes' : 'badge-no'; ?>"><?php echo $row['complete'] ? 'Yes' : 'No'; ?></span></td>
        <td><span class="session-id"><?php echo htmlspecialchars($row['session']); ?></span></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

</body>
</html>
