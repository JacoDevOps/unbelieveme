<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {
    case 'generate_phase2':
        handleGeneratePhase2($input);
        break;
    case 'generate_phase3':
        handleGeneratePhase3($input);
        break;
    case 'save_answers':
        handleSaveAnswers($input);
        break;
    case 'generate_report':
        handleGenerateReport($input);
        break;
    case 'download_report':
        handleDownloadReport($input);
        break;
    case 'verify_stripe':
        handleVerifyStripe($input);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        break;
}

// ─────────────────────────────────────────────────────────────
// PHASE 2 GENERATION
// ─────────────────────────────────────────────────────────────
function handleGeneratePhase2($input) {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $answers = $input['answers'] ?? [];

    $answersText = formatAnswersForPrompt($answers, 1, 10);

    $systemPrompt = <<<EOT
You are a belief analyst specialising in identifying emotionally charged domains from qualitative life assessment answers.

Your task:
1. Read the 10 answers below carefully.
2. Identify the 2-3 domains with the highest emotional charge — areas where the person shows the most friction, avoidance, longing, or recurring patterns.
3. Generate exactly 6 behavioural, situational follow-up questions targeting ONLY those domains.

RULES for questions:
- Never ask about beliefs directly (do not use words like "believe", "belief", "think you believe")
- Questions must be situational and behavioural — about what the person actually does, avoids, feels, or experiences
- Each question must be specific enough that a vague answer would feel dishonest
- Questions should feel like they come from someone who was paying close attention to the answers
- Do not repeat anything the person already said — go deeper or sideways
- Vary the question structure — not all "When do you..." or "What do you..."
- Questions should feel uncomfortable in a productive way

Return ONLY valid JSON with no markdown, no backticks, no explanation:
{"questions": [{"id": "q11", "text": "..."}, {"id": "q12", "text": "..."}, {"id": "q13", "text": "..."}, {"id": "q14", "text": "..."}, {"id": "q15", "text": "..."}, {"id": "q16", "text": "..."}]}
EOT;

    $userMessage = "Here are the Phase 1 answers:\n\n" . $answersText;

    $response = callAnthropic($systemPrompt, $userMessage, 2000);

    if (isset($response['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $response['error']]);
        return;
    }

    $content = $response['content'][0]['text'] ?? '';
    $parsed = json_decode($content, true);

    if (!$parsed || !isset($parsed['questions'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to parse questions from AI response', 'raw' => $content]);
        return;
    }

    echo json_encode($parsed);
}

// ─────────────────────────────────────────────────────────────
// PHASE 3 GENERATION
// ─────────────────────────────────────────────────────────────
function handleGeneratePhase3($input) {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $answers = $input['answers'] ?? [];

    $phase1Text = formatAnswersForPrompt($answers, 1, 10);
    $phase2Text = formatAnswersForPrompt($answers, 11, 16);

    $systemPrompt = <<<EOT
You are a belief analyst using the downward arrow technique to surface core beliefs.

Your task:
1. Read all 20 answers (Phase 1 and Phase 2).
2. Identify the single most emotionally charged answer — the one with the most raw feeling, most avoidance, most contradiction, or most unresolved tension.
3. Generate exactly 4 downward arrow follow-up questions that dig into the CORE of that answer.

The downward arrow technique works by asking: "And if that were true, what would that mean?" or "And when that happens, what does that say about you?" — but phrased naturally, never mechanically.

RULES:
- Questions must follow a logical chain — each one digging beneath the last
- The final question should be pointing at something close to an identity-level belief (something about who they are, what they deserve, what is possible for them)
- Never use therapy jargon
- Never ask about "beliefs" directly
- Questions should be uncomfortable — productively so
- They must feel personal — like they come from someone who read every word

Return ONLY valid JSON with no markdown, no backticks:
{"questions": [{"id": "q17", "text": "..."}, {"id": "q18", "text": "..."}, {"id": "q19", "text": "..."}, {"id": "q20", "text": "..."}]}
EOT;

    $userMessage = "Phase 1 answers:\n\n" . $phase1Text . "\n\nPhase 2 answers:\n\n" . $phase2Text;

    $response = callAnthropic($systemPrompt, $userMessage, 2000);

    if (isset($response['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $response['error']]);
        return;
    }

    $content = $response['content'][0]['text'] ?? '';
    $parsed = json_decode($content, true);

    if (!$parsed || !isset($parsed['questions'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to parse questions', 'raw' => $content]);
        return;
    }

    echo json_encode($parsed);
}

// ─────────────────────────────────────────────────────────────
// SAVE ANSWERS
// ─────────────────────────────────────────────────────────────
function handleSaveAnswers($input) {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $sessionId = sanitiseSessionId($input['session_id'] ?? '');
    $answers = $input['answers'] ?? [];

    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing session_id']);
        return;
    }

    $filePath = DATA_DIR . '/session_' . $sessionId . '.json';

    $existing = [];
    if (file_exists($filePath)) {
        $existing = json_decode(file_get_contents($filePath), true) ?? [];
    }

    // Merge new answers into existing
    $existing['session_id'] = $sessionId;
    $existing['updated_at'] = date('c');
    if (!isset($existing['answers'])) {
        $existing['answers'] = [];
    }
    foreach ($answers as $key => $value) {
        $existing['answers'][$key] = $value;
    }

    file_put_contents($filePath, json_encode($existing, JSON_PRETTY_PRINT));

    echo json_encode(['ok' => true]);
}

// ─────────────────────────────────────────────────────────────
// GENERATE REPORT
// ─────────────────────────────────────────────────────────────
function handleGenerateReport($input) {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $sessionId = sanitiseSessionId($input['session_id'] ?? '');
    $answers = $input['answers'] ?? [];
    $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing session_id']);
        return;
    }

    $allAnswersText = formatAnswersForPrompt($answers, 1, 20);

    $systemPrompt = <<<EOT
You are a deeply insightful belief analyst. You have received someone's answers to a structured belief assessment. Your task is to generate a deeply personal report that shows them beliefs they did not know they held — inferred from their behaviour patterns, not from what they stated directly.

CRITICAL RULES:
- Name 3-5 beliefs maximum
- Each belief must be inferred from PATTERNS across multiple answers — never just reflect one answer back
- Use the person's own words and phrases wherever possible
- Never use therapy jargon
- The cost section must be concrete and uncomfortable — not abstract
- The fundamental shift must reference something from their own answers as evidence
- Write like an insightful person who has studied human behaviour deeply
- Be direct. Kindness and honesty are not opposites.

Return ONLY valid JSON with no markdown, no backticks:

{
  "pattern": "2-3 sentences describing the core pattern observed across all answers. Must name something the person did NOT explicitly say. Written as an outside observation.",
  "values": "1 paragraph about what they actually value beneath their stated goals — drawn from concrete and emotional content of answers.",
  "beliefs": [
    {
      "name": "The belief in 4-6 words, first person, sharp — e.g. I am not yet enough",
      "where_it_shows_up": "How this belief appears across their specific answers. Quote their words. Show the pattern.",
      "identity_protecting": "What sense of self this belief keeps intact. Why the person needs this belief. What they would have to confront without it.",
      "cost": "The concrete daily price of this belief. Specific to their answers. Should be uncomfortable to read.",
      "fundamental_shift": "From: [old identity statement]. To: [new identity statement]. Then 2-3 sentences using evidence from their own answers showing why the new identity is already available to them.",
      "first_move": "One specific action that is an expression of the new identity — not a step toward it. Concrete enough to do today. Not a life change — one move."
    }
  ],
  "goal_insight": "2-3 sentences about whether their goal comes from fear or values, what belief it is solving for, and what a goal that comes purely from their values might look like instead.",
  "closing_question": "One question written specifically for this person based on their dominant pattern. Present tense. Worth sitting with for days. Not rhetorical. Not motivational. Genuinely open."
}
EOT;

    $userMessage = "Here are all 20 answers from the assessment:\n\n" . $allAnswersText;

    $response = callAnthropic($systemPrompt, $userMessage, 4000);

    if (isset($response['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $response['error']]);
        return;
    }

    $content = $response['content'][0]['text'] ?? '';
    $report = json_decode($content, true);

    if (!$report) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to parse report', 'raw' => $content]);
        return;
    }

    // Save report
    $reportPath = DATA_DIR . '/report_' . $sessionId . '.json';
    $reportData = [
        'session_id' => $sessionId,
        'email' => $email,
        'generated_at' => date('c'),
        'report' => $report,
        'answers' => $answers,
    ];
    file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT));

    // Send email via Brevo
    if ($email) {
        sendReportEmail($email, $report, $answers, $sessionId);
    }

    echo json_encode(['ok' => true, 'report' => $report]);
}

// ─────────────────────────────────────────────────────────────
// DOWNLOAD REPORT PDF
// ─────────────────────────────────────────────────────────────
function handleDownloadReport($input) {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $sessionId = sanitiseSessionId($input['session_id'] ?? $_GET['session_id'] ?? '');

    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing session_id']);
        return;
    }

    $reportPath = DATA_DIR . '/report_' . $sessionId . '.json';

    if (!file_exists($reportPath)) {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found']);
        return;
    }

    $reportData = json_decode(file_get_contents($reportPath), true);
    $report = $reportData['report'] ?? [];

    $pdf = generateReportPDF($report);

    // Override the JSON content-type set at the top of the file
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="unbelieveme-report.pdf"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: no-cache');
    echo $pdf;
    exit();
}

// ─────────────────────────────────────────────────────────────
// VERIFY STRIPE
// ─────────────────────────────────────────────────────────────
function handleVerifyStripe($input) {
    $stripeSessionId = $input['stripe_session_id'] ?? $_GET['stripe_session_id'] ?? '';

    if (!$stripeSessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing stripe_session_id']);
        return;
    }

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($stripeSessionId));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code(400);
        echo json_encode(['paid' => false, 'error' => 'Stripe verification failed']);
        return;
    }

    $session = json_decode($response, true);
    $paid = ($session['payment_status'] ?? '') === 'paid';
    $email = $session['customer_details']['email'] ?? $session['customer_email'] ?? '';

    echo json_encode(['paid' => $paid, 'email' => $email]);
}

// ─────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────

function callAnthropic($systemPrompt, $userMessage, $maxTokens = 2000) {
    $payload = [
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => $maxTokens,
        'system' => $systemPrompt,
        'messages' => [
            ['role' => 'user', 'content' => $userMessage]
        ]
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'cURL error: ' . $curlError];
    }

    $decoded = json_decode($response, true);

    if ($httpCode !== 200) {
        return ['error' => $decoded['error']['message'] ?? 'Anthropic API error ' . $httpCode];
    }

    return $decoded;
}

function formatAnswersForPrompt($answers, $start, $end) {
    $lines = [];
    for ($i = $start; $i <= $end; $i++) {
        $key = 'q' . $i;
        if (isset($answers[$key]) && $answers[$key] !== '') {
            $lines[] = "Q{$i}: " . $answers[$key];
        }
    }
    return implode("\n\n", $lines);
}

function sanitiseSessionId($id) {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', $id);
}

function sendReportEmail($email, $report, $answers, $sessionId) {
    // Build HTML email body (always available)
    $html = buildReportEmailHTML($report);

    $payload = [
        'sender'      => ['name' => SENDER_NAME, 'email' => SENDER_EMAIL],
        'to'          => [['email' => $email]],
        'subject'     => 'Your Unbelieveme Belief Report',
        'htmlContent' => $html,
    ];

    // Attach PDFs only if FPDF is available — never crash if it isn't
    $fpdfPath = __DIR__ . '/vendor/fpdf/fpdf.php';
    if (file_exists($fpdfPath)) {
        require_once $fpdfPath;
    }

    if (class_exists('FPDF')) {
        try {
            $reportPdf = generateReportPDF($report);
            $rawPdf    = generateRawAnswersPDF($answers, $sessionId);
            $payload['attachment'] = [
                ['content' => base64_encode($reportPdf), 'name' => 'unbelieveme-report.pdf'],
                ['content' => base64_encode($rawPdf),    'name' => 'unbelieveme-answers.pdf'],
            ];
        } catch (Exception $e) {
            // PDF generation failed — send email without attachments
        }
    }

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . BREVO_API_KEY,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $brevoResponse = curl_exec($ch);
    $brevoCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr       = curl_error($ch);
    curl_close($ch);

    $logPath  = DATA_DIR . '/email_log.txt';
    $logEntry = date('c')
        . ' | to:'       . $email
        . ' | http:'     . $brevoCode
        . ' | curl_err:' . $curlErr
        . ' | response:' . $brevoResponse
        . "\n";
    @file_put_contents($logPath, $logEntry, FILE_APPEND);
}

function buildReportEmailHTML($report) {
    $beliefsHtml = '';
    foreach ($report['beliefs'] ?? [] as $belief) {
        $beliefsHtml .= '
        <div style="border-top:2px solid #7a6a48;padding-top:20px;margin-bottom:28px;">
            <p style="font-size:20px;font-style:italic;color:#ddd6cc;margin-bottom:16px;">' . htmlspecialchars($belief['name']) . '</p>
            <p style="color:#b8a070;font-size:9px;letter-spacing:3px;text-transform:uppercase;margin-bottom:6px;">Where it shows up</p>
            <p style="color:#5a5650;line-height:1.85;margin-bottom:14px;">' . nl2br(htmlspecialchars($belief['where_it_shows_up'])) . '</p>
            <p style="color:#b8a070;font-size:9px;letter-spacing:3px;text-transform:uppercase;margin-bottom:6px;">What it\'s protecting</p>
            <p style="color:#5a5650;line-height:1.85;margin-bottom:14px;">' . nl2br(htmlspecialchars($belief['identity_protecting'])) . '</p>
            <p style="color:#b8a070;font-size:9px;letter-spacing:3px;text-transform:uppercase;margin-bottom:6px;">What it\'s costing you</p>
            <p style="color:#5a5650;line-height:1.85;margin-bottom:14px;">' . nl2br(htmlspecialchars($belief['cost'])) . '</p>
            <p style="color:#b8a070;font-size:9px;letter-spacing:3px;text-transform:uppercase;margin-bottom:6px;">The shift</p>
            <p style="color:#5a5650;line-height:1.85;margin-bottom:14px;">' . nl2br(htmlspecialchars($belief['fundamental_shift'])) . '</p>
            <p style="color:#b8a070;font-size:9px;letter-spacing:3px;text-transform:uppercase;margin-bottom:6px;">First move</p>
            <p style="color:#5a5650;line-height:1.85;">' . nl2br(htmlspecialchars($belief['first_move'])) . '</p>
        </div>';
    }

    return '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="background:#1a1916;color:#ddd6cc;font-family:Georgia,serif;max-width:680px;margin:0 auto;padding:40px 20px;">
    <p style="color:#5a5650;text-align:center;font-size:11px;letter-spacing:4px;text-transform:uppercase;margin-bottom:48px;">Unbelieveme</p>
    <p style="color:#b8a070;font-size:10px;letter-spacing:3px;text-transform:uppercase;margin-bottom:10px;">What we found</p>
    <p style="color:#ddd6cc;line-height:1.85;font-style:italic;font-size:18px;">' . nl2br(htmlspecialchars($report['pattern'] ?? '')) . '</p>
    <p style="color:#b8a070;font-size:10px;letter-spacing:3px;text-transform:uppercase;margin-top:32px;margin-bottom:10px;">What you actually value</p>
    <p style="color:#5a5650;line-height:1.85;">' . nl2br(htmlspecialchars($report['values'] ?? '')) . '</p>
    <p style="color:#b8a070;font-size:10px;letter-spacing:3px;text-transform:uppercase;margin-top:32px;margin-bottom:16px;">Your beliefs</p>
    ' . $beliefsHtml . '
    <p style="color:#b8a070;font-size:10px;letter-spacing:3px;text-transform:uppercase;margin-top:32px;margin-bottom:10px;">Your goal</p>
    <p style="color:#5a5650;line-height:1.85;">' . nl2br(htmlspecialchars($report['goal_insight'] ?? '')) . '</p>
    <div style="text-align:center;margin:48px 0;padding:40px 24px;border-top:1px solid #2c2a26;border-bottom:1px solid #2c2a26;">
        <p style="font-size:20px;font-style:italic;color:#b8a070;line-height:1.55;">' . htmlspecialchars($report['closing_question'] ?? '') . '</p>
    </div>
    <p style="color:#38352f;font-size:11px;text-align:center;">© Unbelieveme 2026 · quiz@unbelieveme.com</p>
</body>
</html>';
}

function generateReportPDF($report) {
    $fpdfPath = __DIR__ . '/vendor/fpdf/fpdf.php';

    if (file_exists($fpdfPath)) {
        require_once $fpdfPath;
    }

    if (!class_exists('FPDF')) {
        return generateMinimalPDF('Unbelieveme Belief Report — install FPDF library to vendor/fpdf/fpdf.php for full PDF output.');
    }

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetMargins(20, 20, 20);

    // Title
    $pdf->SetFont('Helvetica', 'B', 20);
    $pdf->SetTextColor(184, 160, 112);
    $pdf->Cell(0, 14, 'UNBELIEVEME', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetTextColor(180, 175, 168);
    $pdf->Cell(0, 8, 'Your Belief Report', 0, 1, 'C');
    $pdf->Ln(10);

    // Pattern
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetTextColor(200, 150, 62);
    $pdf->Cell(0, 10, 'What we found', 0, 1);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->MultiCell(0, 7, $report['pattern'] ?? '');
    $pdf->Ln(6);

    // Values
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetTextColor(200, 150, 62);
    $pdf->Cell(0, 10, 'What you actually value', 0, 1);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->MultiCell(0, 7, $report['values'] ?? '');
    $pdf->Ln(6);

    // Beliefs
    foreach ($report['beliefs'] ?? [] as $belief) {
        if ($pdf->GetY() > 220) $pdf->AddPage();

        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->SetTextColor(200, 150, 62);
        $pdf->MultiCell(0, 8, $belief['name'] ?? '');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(50, 50, 50);

        $sections = [
            'Where it shows up' => 'where_it_shows_up',
            "What it's protecting" => 'identity_protecting',
            "What it's costing you" => 'cost',
            'The shift' => 'fundamental_shift',
            'First move' => 'first_move',
        ];

        foreach ($sections as $label => $key) {
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 6, $label, 0, 1);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(50, 50, 50);
            $pdf->MultiCell(0, 6, $belief[$key] ?? '');
            $pdf->Ln(2);
        }
        $pdf->Ln(4);
    }

    // Goal insight
    if ($pdf->GetY() > 220) $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetTextColor(200, 150, 62);
    $pdf->Cell(0, 10, 'Your goal', 0, 1);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->MultiCell(0, 7, $report['goal_insight'] ?? '');
    $pdf->Ln(6);

    // Closing question
    if ($pdf->GetY() > 220) $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'I', 14);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell(0, 8, $report['closing_question'] ?? '');

    // Footer
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 6, '© Unbelieveme 2026 · quiz@unbelieveme.com', 0, 1, 'C');

    return $pdf->Output('S');
}

function generateRawAnswersPDF($answers, $sessionId) {
    $fpdfPath = __DIR__ . '/vendor/fpdf/fpdf.php';

    if (file_exists($fpdfPath)) {
        require_once $fpdfPath;
    }

    if (!class_exists('FPDF')) {
        return generateMinimalPDF('Unbelieveme — Your Assessment Answers. Install FPDF to vendor/fpdf/fpdf.php for full PDF output.');
    }

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetMargins(20, 20, 20);

    $pdf->SetFont('Helvetica', 'B', 18);
    $pdf->SetTextColor(184, 160, 112);
    $pdf->Cell(0, 12, 'UNBELIEVEME', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetTextColor(140, 135, 128);
    $pdf->Cell(0, 8, 'Your Assessment Answers', 0, 1, 'C');
    $pdf->Ln(8);

    $questions = getQuestionTexts();

    for ($i = 1; $i <= 20; $i++) {
        $key = 'q' . $i;
        $answer = $answers[$key] ?? '';
        if (!$answer) continue;

        if ($pdf->GetY() > 240) $pdf->AddPage();

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(100, 100, 100);
        $qText = isset($questions[$key]) ? 'Q' . $i . ': ' . $questions[$key] : 'Q' . $i;
        $pdf->MultiCell(0, 6, $qText);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->MultiCell(0, 7, $answer);
        $pdf->Ln(4);
    }

    return $pdf->Output('S');
}

function generateMinimalPDF($message) {
    // Bare-bones valid PDF
    $content = "%PDF-1.4\n1 0 obj<</Type /Catalog /Pages 2 0 R>>endobj\n";
    $content .= "2 0 obj<</Type /Pages /Kids [3 0 R] /Count 1>>endobj\n";
    $content .= "3 0 obj<</Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources <</Font <</F1 5 0 R>>>>>>endobj\n";
    $stream = "BT /F1 12 Tf 50 750 Td (" . addslashes($message) . ") Tj ET";
    $content .= "4 0 obj<</Length " . strlen($stream) . ">>\nstream\n" . $stream . "\nendstream\nendobj\n";
    $content .= "5 0 obj<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>endobj\n";
    $xrefOffset = strlen($content);
    $content .= "xref\n0 6\n0000000000 65535 f \n";
    $content .= "trailer<</Size 6 /Root 1 0 R>>\nstartxref\n" . $xrefOffset . "\n%%EOF";
    return $content;
}

function getQuestionTexts() {
    return [
        'q1' => 'If you had to describe where you are in your life right now in three words — not how you want to be, just honestly where you are — what would those three words be?',
        'q2' => 'What do you want most right now — the thing that, if it changed, would change everything else?',
        'q3' => 'Where in your life do you feel the most friction — the place where effort doesn\'t seem to translate into progress, or where the same situation keeps returning?',
        'q4' => 'Think about the area of your life you\'re most satisfied with right now. What\'s different about that area compared to the ones that feel harder?',
        'q5' => 'What\'s something you\'ve wanted for a long time that you haven\'t allowed yourself to fully pursue? Not something impossible — something that was possible, but didn\'t happen.',
        'q6' => 'When you imagine the version of your life that would feel fully right — not perfect, just right — what does a normal Tuesday look like?',
        'q7' => 'What feeling are you most trying to avoid in your daily life — the one you organise things around not having?',
        'q8' => 'What do you believe you need to have or be before your life can really begin — the condition you\'re waiting to meet?',
        'q9' => 'Where in your life do you feel most like yourself — most natural, most unguarded? And where do you feel most like a version of yourself you\'re performing?',
        'q10' => 'What do people close to you say about you — the things they notice that you sometimes wish they didn\'t?',
    ];
}
