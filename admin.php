<?php
// FastCGI / PHP-FPM fallback: parse Authorization header manually
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
               ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
               ?? '';
    if (stripos($authHeader, 'Basic ') === 0) {
        $decoded = base64_decode(substr($authHeader, 6));
        $parts   = explode(':', $decoded, 2);
        $_SERVER['PHP_AUTH_USER'] = $parts[0] ?? '';
        $_SERVER['PHP_AUTH_PW']   = $parts[1] ?? '';
    }
}

if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== 'admin' ||
    $_SERVER['PHP_AUTH_PW'] !== 'ubm_admin_2026') {
    header('WWW-Authenticate: Basic realm="Unbelieveme Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access denied.';
    exit;
}

require_once 'config.php';

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

// ── Embed session + report data for JS ───────────────────────
$jsSessionData = json_encode($sessions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
$jsReportData  = json_encode($reports,  JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

// Phase 1 question texts (q1–q10 are fixed; q11–q20 are AI-generated per session and not stored)
$questionTexts = [
    'q1'  => 'If you had to describe where you are in your life right now in three words — not how you want to be, just honestly where you are — what would those three words be?',
    'q2'  => 'What do you want most right now — the thing that, if it changed, would change everything else?',
    'q3'  => 'Where in your life do you feel the most friction — the place where effort doesn\'t seem to translate into progress, or where the same situation keeps returning?',
    'q4'  => 'Think about the area of your life you\'re most satisfied with right now. What\'s different about that area compared to the ones that feel harder?',
    'q5'  => 'What\'s something you\'ve wanted for a long time that you haven\'t allowed yourself to fully pursue? Not something impossible — something that was possible, but didn\'t happen.',
    'q6'  => 'When you imagine the version of your life that would feel fully right — not perfect, just right — what does a normal Tuesday look like?',
    'q7'  => 'What feeling are you most trying to avoid in your daily life — the one you organise things around not having?',
    'q8'  => 'What do you believe you need to have or be before your life can really begin — the condition you\'re waiting to meet?',
    'q9'  => 'Where in your life do you feel most like yourself — most natural, most unguarded? And where do you feel most like a version of yourself you\'re performing?',
    'q10' => 'What do people close to you say about you — the things they notice that you sometimes wish they didn\'t?',
];
$jsQuestionTexts = json_encode($questionTexts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
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
    tbody tr.data-row {
      border-bottom: 1px solid #201f1c;
      cursor: pointer;
      user-select: none;
    }
    tbody tr.data-row:hover {
      background: #201f1c;
    }
    tbody tr.data-row.open {
      background: #201f1c;
      border-bottom-color: transparent;
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
    .badge-yes   { color: #b8a070; border-color: #7a6a48; }
    .badge-no    { color: #5a5650; border-color: #2c2a26; }
    .badge-phase { color: #8a8278; border-color: #2c2a26; }
    .session-id  { font-size: 11px; color: #5a5650; font-family: monospace; }

    /* ── Toggle arrow ── */
    .toggle-icon {
      display: inline-block;
      width: 14px;
      color: #5a5650;
      font-size: 10px;
      transition: transform 0.15s ease;
      margin-right: 4px;
    }
    .data-row.open .toggle-icon {
      transform: rotate(90deg);
    }

    /* ── Detail row ── */
    .detail-row { display: none; }
    .detail-row.open { display: table-row; }
    .detail-cell { padding: 0 !important; border-bottom: 2px solid #2c2a26 !important; }

    .detail-wrap {
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: #1e1d1a;
      border-top: 1px solid #2c2a26;
    }
    .detail-col {
      padding: 28px 28px;
      border-right: 1px solid #2c2a26;
      overflow-wrap: break-word;
    }
    .detail-col:last-child { border-right: none; }

    .detail-col-title {
      font-size: 9px;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: #b8a070;
      margin-bottom: 20px;
    }

    /* Q&A */
    .detail-qa {
      margin-bottom: 18px;
      padding-bottom: 18px;
      border-bottom: 1px solid #2c2a26;
    }
    .detail-qa:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .detail-qa-label {
      font-size: 9px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #5a5650;
      margin-bottom: 6px;
    }
    .detail-qa-q {
      color: #7a7470;
      font-size: 12px;
      line-height: 1.55;
      font-style: italic;
      margin-bottom: 8px;
    }
    .detail-qa-ans {
      color: #c8c0b4;
      font-size: 13px;
      line-height: 1.65;
      white-space: pre-wrap;
    }
    .closing-q {
      color: #b8a070;
      font-style: italic;
    }

    /* Report */
    .detail-rep-section {
      margin-bottom: 20px;
      padding-bottom: 20px;
      border-bottom: 1px solid #2c2a26;
    }
    .detail-rep-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .detail-rep-label {
      font-size: 9px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #5a5650;
      margin-bottom: 8px;
    }
    .detail-rep-body {
      color: #c8c0b4;
      font-size: 13px;
      line-height: 1.7;
      white-space: pre-wrap;
    }

    /* Beliefs */
    .detail-belief {
      background: #1a1916;
      border: 1px solid #2c2a26;
      padding: 14px 16px;
      margin-bottom: 12px;
    }
    .detail-belief:last-child { margin-bottom: 0; }
    .detail-belief-name {
      font-size: 14px;
      color: #ddd6cc;
      margin-bottom: 12px;
      font-weight: 300;
    }
    .detail-belief-field { margin-bottom: 10px; }
    .detail-belief-field:last-child { margin-bottom: 0; }
    .detail-belief-field-label {
      display: block;
      font-size: 9px;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: #5a5650;
      margin-bottom: 3px;
    }
    .detail-belief-field-val {
      font-size: 13px;
      color: #c8c0b4;
      line-height: 1.65;
      white-space: pre-wrap;
    }

    .detail-empty {
      color: #5a5650;
      font-size: 12px;
      padding: 20px 0;
    }
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

  <div class="section-title">Sessions <span style="color:#38352f;font-size:9px;letter-spacing:0.1em;">&nbsp;— click a row to expand</span></div>
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
      <?php else: foreach ($rows as $row):
        $sid     = htmlspecialchars($row['session']);
        $detailId = 'detail-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['session']);
      ?>
      <tr class="data-row" data-sid="<?php echo $sid; ?>" data-detail="<?php echo $detailId; ?>">
        <td>
          <span class="toggle-icon">&#9658;</span><?php echo $row['date'] ? date('d M Y  H:i', strtotime($row['date'])) : '—'; ?>
        </td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><span class="badge badge-phase"><?php echo htmlspecialchars($row['phase']); ?></span></td>
        <td><span class="badge <?php echo $row['complete'] ? 'badge-yes' : 'badge-no'; ?>"><?php echo $row['complete'] ? 'Yes' : 'No'; ?></span></td>
        <td><span class="session-id"><?php echo $sid; ?></span></td>
      </tr>
      <tr class="detail-row" id="<?php echo $detailId; ?>">
        <td class="detail-cell" colspan="5"></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

<script>
const SESSIONS   = <?php echo $jsSessionData; ?>;
const REPORTS    = <?php echo $jsReportData; ?>;
const Q_TEXTS    = <?php echo $jsQuestionTexts; ?>;

// Phase labels for AI-generated questions
const Q_PHASE = {
  q11:'Phase 2 follow-up', q12:'Phase 2 follow-up', q13:'Phase 2 follow-up',
  q14:'Phase 2 follow-up', q15:'Phase 2 follow-up', q16:'Phase 2 follow-up',
  q17:'Phase 3 deep-dive', q18:'Phase 3 deep-dive', q19:'Phase 3 deep-dive',
  q20:'Phase 3 deep-dive'
};

function esc(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function buildDetail(sid) {
  const sess    = SESSIONS[sid] || null;
  const repFile = REPORTS[sid]  || null;
  // Report content is nested one level deeper: repFile.report
  const rep     = repFile ? (repFile.report || null) : null;
  // Prefer answers from report file (complete set); fall back to session file
  const answers = (repFile && repFile.answers) ? repFile.answers
                : (sess && sess.answers)        ? sess.answers
                : null;

  let html = '<div class="detail-wrap">';

  // ── Left column: Q&A ──────────────────────────────────────
  html += '<div class="detail-col"><div class="detail-col-title">Questions &amp; Answers</div>';
  if (answers && Object.keys(answers).length) {
    const keys = Object.keys(answers).sort(function(a, b) {
      var na = parseInt(a.replace(/\D/g, '')) || 0;
      var nb = parseInt(b.replace(/\D/g, '')) || 0;
      return na - nb;
    });
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      var v = answers[k];
      var qNum    = k.replace(/\D/g, '');
      var qLabel  = 'Q' + qNum;
      var qText   = Q_TEXTS[k] || Q_PHASE[k] || null;
      html += '<div class="detail-qa">'
            +   '<div class="detail-qa-label">' + esc(qLabel) + '</div>'
            +   (qText ? '<div class="detail-qa-q">' + esc(qText) + '</div>' : '')
            +   '<div class="detail-qa-ans">' + esc(v) + '</div>'
            + '</div>';
    }
  } else {
    html += '<div class="detail-empty">No answers recorded.</div>';
  }
  html += '</div>';

  // ── Right column: Report ──────────────────────────────────
  html += '<div class="detail-col"><div class="detail-col-title">Report</div>';
  if (rep) {
    if (rep.pattern) {
      html += '<div class="detail-rep-section">'
            +   '<div class="detail-rep-label">Opening Pattern</div>'
            +   '<div class="detail-rep-body">' + esc(rep.pattern) + '</div>'
            + '</div>';
    }

    if (rep.values) {
      var valText = Array.isArray(rep.values)
        ? rep.values.map(function(v){ return esc(v); }).join(' &middot; ')
        : esc(rep.values);
      html += '<div class="detail-rep-section">'
            +   '<div class="detail-rep-label">Values</div>'
            +   '<div class="detail-rep-body">' + valText + '</div>'
            + '</div>';
    }

    if (rep.beliefs && rep.beliefs.length) {
      html += '<div class="detail-rep-section"><div class="detail-rep-label">Beliefs</div>';
      for (var b = 0; b < rep.beliefs.length; b++) {
        var belief = rep.beliefs[b];
        html += '<div class="detail-belief">'
              +   '<div class="detail-belief-name">' + esc(belief.name || '') + '</div>';
        var fieldOrder = ['where_it_shows_up','identity_protecting','cost','fundamental_shift','first_move'];
        for (var fo = 0; fo < fieldOrder.length; fo++) {
          var field = fieldOrder[fo];
          var fval  = belief[field];
          if (fval && typeof fval === 'string' && fval.trim()) {
            html += '<div class="detail-belief-field">'
                  +   '<span class="detail-belief-field-label">' + esc(field.replace(/_/g, ' ')) + '</span>'
                  +   '<span class="detail-belief-field-val">'   + esc(fval) + '</span>'
                  + '</div>';
          }
        }
        html += '</div>';
      }
      html += '</div>';
    }

    if (rep.goal_insight) {
      html += '<div class="detail-rep-section">'
            +   '<div class="detail-rep-label">Goal Insight</div>'
            +   '<div class="detail-rep-body">' + esc(rep.goal_insight) + '</div>'
            + '</div>';
    }

    if (rep.closing_question) {
      html += '<div class="detail-rep-section">'
            +   '<div class="detail-rep-label">Closing Question</div>'
            +   '<div class="detail-rep-body closing-q">' + esc(rep.closing_question) + '</div>'
            + '</div>';
    }
  } else {
    html += '<div class="detail-empty">No report generated yet.</div>';
  }
  html += '</div>';

  html += '</div>'; // .detail-wrap
  return html;
}

document.querySelectorAll('.data-row').forEach(function(tr) {
  tr.addEventListener('click', function() {
    var sid        = tr.dataset.sid;
    var detailId   = tr.dataset.detail;
    var detailRow  = document.getElementById(detailId);
    if (!detailRow) return;

    var isOpen = tr.classList.toggle('open');
    if (isOpen) {
      detailRow.classList.add('open');
      if (!detailRow.dataset.built) {
        detailRow.querySelector('.detail-cell').innerHTML = buildDetail(sid);
        detailRow.dataset.built = '1';
      }
    } else {
      detailRow.classList.remove('open');
    }
  });
});
</script>
</body>
</html>
