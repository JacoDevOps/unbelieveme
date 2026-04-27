<?php
require_once 'config.php';
// DEV MODE — remove before launch
$devMode  = isset($_GET['dev']) && $_GET['dev'] === 'preview2026';
// TEST BYPASS — allows access without payment for testing
$testMode = isset($_GET['dev']) && $_GET['dev'] === 'ubm_test_2026';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Unbelieveme — Belief Assessment</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=DM+Sans:wght@300&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:         #1a1916;
      --surface:    #201f1c;
      --border:     #2c2a26;
      --muted:      #5a5650;
      --text:       #ddd6cc;
      --accent:     #b8a070;
      --accent-dim: #7a6a48;
      --deep:       #38352f;
    }

    html, body { height: 100%; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: clamp(1rem, 1.5vw, 1.15rem);
      line-height: 1.8;
      -webkit-font-smoothing: subpixel-antialiased;
      -moz-osx-font-smoothing: auto;
      overflow-x: hidden;
    }

    /* ════ Keyframes ════ */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }
    @keyframes fadeOutUp {
      from { opacity: 1; transform: translateY(0); }
      to   { opacity: 0; transform: translateY(-12px); }
    }
    @keyframes pulse {
      0%, 100% { opacity: 0.25; }
      50%       { opacity: 1; }
    }

    /* ════ Progress bar ════ */
    #progress-wrap {
      position: fixed;
      top: 0; left: 0;
      height: 1px;
      background: var(--border);
      width: 100%;
      z-index: 200;
    }
    #progress-fill {
      height: 100%;
      background: var(--accent);
      width: 0%;
      transition: width 0.7s ease;
    }

    /* ════ Fixed logo ════ */
    #fixed-logo {
      position: fixed;
      top: 22px;
      left: 50%;
      transform: translateX(-50%);
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: 10px;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: #8a8278;
      z-index: 100;
      opacity: 0;
      background: rgba(26,25,22,0.92);
      padding: 4px 16px;
      transition: opacity 0.6s ease;
      pointer-events: none;
    }
    #fixed-logo.visible { opacity: 1; }

    /* ════ Screen system ════ */
    .screen {
      position: fixed;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 80px 32px;
      opacity: 0;
      pointer-events: none;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
    }
    .screen.active {
      opacity: 1;
      pointer-events: all;
      animation: fadeIn 500ms ease forwards;
    }

    .inner {
      max-width: 680px;
      width: 100%;
    }

    /* ════ Button (universal) ════ */
    .btn {
      display: inline-block;
      border: 1px solid var(--accent-dim);
      color: var(--accent);
      background: transparent;
      padding: 14px 36px;
      font-family: 'DM Sans', sans-serif;
      font-size: 10px;
      font-weight: 300;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      border-radius: 0;
      cursor: pointer;
      transition: background 0.3s ease, color 0.3s ease;
      text-decoration: none;
    }
    .btn:hover, .btn:focus { background: var(--accent-dim); color: var(--bg); outline: none; }
    .btn[disabled], .btn:disabled { opacity: 0.35; pointer-events: none; }

    /* ════ IMMERSIVE QUOTE SCREEN ════ */
    .quote-screen {
      min-height: 100vh;
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px 32px;
      text-align: center;
    }

    .big-quote {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(1.8rem, 3.5vw, 2.6rem);
      font-weight: 300;
      font-style: italic;
      color: #f0ece6;
      line-height: 1.4;
      max-width: 600px;
      margin: 0 auto 24px;
      opacity: 0;
      animation: fadeIn 900ms ease 0ms forwards;
    }

    .big-cite {
      font-family: 'DM Sans', sans-serif;
      font-size: 11px;
      font-weight: 300;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: #7a7470;
      font-style: normal;
      opacity: 0;
      animation: fadeIn 900ms ease 800ms forwards;
    }

    /* ════ TRANSITION TEXT SCREEN ════ */
    .t-inner {
      max-width: 600px;
      width: 100%;
      text-align: center;
    }

    .t-body {
      font-size: clamp(1.1rem, 2vw, 1.3rem);
      color: #f0ece6;
      max-width: 560px;
      margin: 0 auto 40px;
      line-height: 1.85;
      opacity: 0;
      animation: fadeUp 700ms ease 0ms forwards;
    }
    .t-body p { margin-bottom: 18px; color: #f0ece6; }
    .t-body p { color: #f0ece6 !important; font-size: clamp(1rem, 1.8vw, 1.1rem); }
    .t-body p:last-child { margin-bottom: 0; }

    .t-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(1.6rem, 3vw, 2.2rem);
      font-weight: 300;
      color: #f0ece6;
      line-height: 1.3;
      margin-bottom: 32px;
      opacity: 0;
      animation: fadeUp 700ms ease 0ms forwards;
    }

    .t-btn-wrap {
      text-align: center;
      opacity: 0;
      animation: fadeIn 500ms ease 600ms forwards;
    }

    /* ════ OPENING SCREEN ════ */
    #screen-opening .t-inner { text-align: left; }
    #screen-opening .t-body  { text-align: left; margin: 0 0 40px; }
    #screen-opening .t-btn-wrap { text-align: left; }

    /* ════ QUESTION SCREEN ════ */
    .q-wrap { max-width: 600px; width: 100%; }

    .q-number {
      font-size: 10px;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 20px;
      display: none;
    }

    .q-text {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(1.4rem, 2.8vw, 1.9rem);
      font-weight: 300;
      color: #f0ece6;
      line-height: 1.5;
      max-width: 560px;
      margin-bottom: 12px;
    }

    /* Question clarification hint */
    .q-hint {
      font-family: 'DM Sans', sans-serif;
      font-size: clamp(0.8rem, 1.4vw, 0.9rem);
      font-weight: 300;
      color: #7a7470;
      margin-top: 10px;
      margin-bottom: 20px;
      line-height: 1.6;
      font-style: italic;
    }

    textarea.q-input {
      background: #201f1c;
      border: 1px solid var(--border);
      color: #ddd6cc;
      font-family: 'DM Sans', sans-serif;
      font-size: 16px;
      font-weight: 300;
      padding: 18px;
      border-radius: 0;
      outline: none;
      resize: none;
      width: 100%;
      min-height: 140px;
      line-height: 1.8;
      caret-color: var(--accent);
      transition: border-color 0.25s ease;
      margin-bottom: 8px;
    }
    textarea.q-input::placeholder { color: var(--deep); }
    textarea.q-input:focus { border-color: var(--accent-dim); }

    .q-kbd {
      font-size: 10px;
      color: #5a5650;
      letter-spacing: 0.08em;
      margin-bottom: 20px;
      font-style: italic;
    }

    /* question fade animations */
    .q-out .q-number,
    .q-out .q-text,
    .q-out .q-hint,
    .q-out textarea.q-input,
    .q-out .q-kbd {
      animation: fadeOutUp 350ms ease forwards;
    }

    .q-in .q-number  { opacity: 0; animation: fadeUp 400ms ease 0ms   forwards; }
    .q-in .q-text    { opacity: 0; animation: fadeUp 400ms ease 40ms  forwards; }
    .q-in .q-hint    { opacity: 0; animation: fadeUp 400ms ease 80ms  forwards; }
    .q-in textarea.q-input { opacity: 0; animation: fadeUp 400ms ease 100ms forwards; }
    .q-in .q-kbd     { opacity: 0; animation: fadeUp 400ms ease 120ms forwards; }

    /* ════ LOADING SCREEN ════ */
    .loading-wrap { text-align: center; }

    .loading-dots {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
    }
    .loading-dots span {
      display: inline-block;
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--accent);
      margin: 0 4px;
      animation: pulse 1.4s ease-in-out infinite;
    }
    .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
    .loading-dots span:nth-child(3) { animation-delay: 0.4s; }

    .loading-label {
      font-size: 11px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #8a8278;
      opacity: 0;
      animation: fadeIn 600ms ease 400ms forwards;
    }

    /* ════ REPORT SCREEN ════ */
    #screen-report {
      position: relative;
      padding-top: 72px;
      padding-bottom: 120px;
      justify-content: flex-start;
    }

    .report-logo {
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: 10px;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: #8a8278;
      text-align: center;
      margin-bottom: 72px;
    }

    /* Report section fade-up — animated on render, not on scroll */
    .rs {
      opacity: 0;
      transform: translateY(10px);
      transition: opacity 0.55s ease, transform 0.55s ease;
    }
    .rs.visible { opacity: 1; transform: translateY(0); }

    .section-label {
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: clamp(0.65rem, 1vw, 0.8rem);
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 16px;
    }

    .pattern-text {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-style: italic;
      font-size: clamp(1.2rem, 2.2vw, 1.6rem);
      color: #f0ece6;
      line-height: 1.6;
    }

    .section-body {
      font-size: 15px;
      color: #c8c0b4;
      line-height: 1.85;
    }

    .report-section { margin-bottom: 56px; }

    /* ── Belief card ── */
    .belief-card {
      border-top: 2px solid var(--accent-dim);
      padding-top: 28px;
      margin-bottom: 44px;
    }

    .belief-name {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(1.5rem, 2.8vw, 2rem);
      font-weight: 300;
      color: #f0ece6;
      line-height: 1.3;
      margin-bottom: 28px;
    }

    .belief-row { margin-bottom: 22px; }

    .belief-row-label {
      font-size: clamp(0.65rem, 1vw, 0.8rem);
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 8px;
    }

    .belief-body {
      font-size: 15px;
      color: #c8c0b4;
      line-height: 1.85;
    }

    /* ── Report divider ── */
    .report-rule {
      width: 40px;
      height: 1px;
      background: var(--border);
      margin: 0 auto 56px;
    }

    /* ── CLOSING MOMENT ── */
    .closing-moment {
      padding: 80px 24px;
      text-align: center;
      max-width: 560px;
      margin: 60px auto 0;
    }

    .closing-rule {
      width: 40px;
      height: 1px;
      background: var(--border);
      margin: 0 auto 40px;
    }

    .closing-label {
      font-family: 'DM Sans', sans-serif;
      font-size: 9px;
      font-weight: 300;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 24px;
    }

    .closing-question {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(1.6rem, 3vw, 2.2rem);
      font-weight: 300;
      font-style: italic;
      color: #f0ece6;
      line-height: 1.45;
    }

    /* ── Report CTAs ── */
    .report-ctas {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
    }

    .cta-primary {
      display: block;
      width: 100%;
      max-width: 380px;
      margin: 0 auto;
      padding: 18px 36px;
      border: 1px solid var(--accent);
      color: var(--accent);
      background: transparent;
      font-family: 'DM Sans', sans-serif;
      font-size: 11px;
      font-weight: 300;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      text-align: center;
      cursor: pointer;
      transition: background 0.3s ease, color 0.3s ease;
      text-decoration: none;
      border-radius: 0;
    }
    .cta-primary:hover { background: var(--accent); color: var(--bg); }

    .cta-secondary {
      display: block;
      width: 100%;
      max-width: 380px;
      margin: 0 auto;
      padding: 18px 36px;
      border: 1px solid var(--border);
      color: var(--muted);
      background: transparent;
      font-family: 'DM Sans', sans-serif;
      font-size: 11px;
      font-weight: 300;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.3s ease, color 0.3s ease;
      text-decoration: none;
      border-radius: 0;
    }
    .cta-secondary:hover { border-color: var(--muted); color: var(--text); }

    .email-note {
      font-size: 11px;
      color: #8a8278;
      text-align: center;
      margin-bottom: 56px;
    }

    /* ── What to do ── */
    .what-to-do-label {
      font-size: 9px;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 20px;
    }

    .what-to-do-body {
      font-size: 15px;
      color: #c8c0b4;
      max-width: 560px;
      line-height: 1.85;
    }
    .what-to-do-body p { margin-bottom: 18px; color: #c8c0b4; }
    .what-to-do-body p:last-child { margin-bottom: 0; }

    .belief-sequence {
      margin: 32px 0;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 0;
    }
    .belief-sequence-step {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(1.2rem, 2.2vw, 1.5rem);
      font-weight: 300;
      color: #f0ece6;
      padding: 14px 24px;
      border: 1px solid var(--border);
      width: 100%;
      max-width: 360px;
    }
    .belief-sequence-arrow {
      font-size: 1.2rem;
      color: var(--accent);
      padding: 6px 24px;
      line-height: 1;
    }

    .shift-from, .shift-to {
      padding: 16px 20px;
      margin-bottom: 8px;
      border-left: 2px solid var(--accent-dim);
      color: #c8c0b4;
      font-size: clamp(0.9rem, 1.6vw, 1rem);
      line-height: 1.8;
    }
    .shift-to {
      border-left-color: var(--accent);
      color: #f0ece6;
    }
    .shift-label {
      display: block;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.6rem;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 8px;
    }

    .report-footer {
      font-size: 10px;
      color: var(--deep);
      text-align: center;
      margin-top: 80px;
    }

    /* ── Toast ── */
    #toast {
      position: fixed;
      bottom: 28px;
      left: 50%;
      transform: translateX(-50%) translateY(10px);
      background: var(--surface);
      border: 1px solid var(--border);
      color: var(--muted);
      font-size: 11px;
      letter-spacing: 0.12em;
      padding: 10px 22px;
      opacity: 0;
      transition: opacity 0.3s, transform 0.3s;
      pointer-events: none;
      z-index: 500;
      white-space: nowrap;
    }
    #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

    /* ── Error banner ── */
    #err-banner {
      position: fixed;
      top: 16px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--surface);
      border: 1px solid var(--accent-dim);
      color: var(--muted);
      font-size: 12px;
      padding: 10px 22px;
      z-index: 600;
      display: none;
      max-width: 460px;
      text-align: center;
    }

    @media (max-width: 640px) {
      .screen { padding: 70px 20px 70px; }
      .big-quote { font-size: 1.6rem; }
      .q-text { font-size: 1.3rem; }
      .pattern-text { font-size: 1.15rem; }
      .closing-question { font-size: 1.5rem; }
      .t-body { font-size: 1rem; }
    }

    /* ════ DEV NAV (dev mode only) ════ */
    #dev-nav {
      display: none;
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 99999;
      flex-direction: column;
      align-items: flex-end;
      gap: 8px;
      background: rgba(20,18,15,0.96);
      border: 1px solid #b8a070;
      padding: 14px 18px;
    }
    #dev-nav.active { display: flex; }
    .dev-label {
      font-size: 9px;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: #c8c0b4;
      margin-bottom: 4px;
      text-align: right;
    }
    .dev-btns {
      display: flex;
      gap: 6px;
    }
    .dev-btn {
      background: #ffffff;
      border: 2px solid #ffffff;
      color: #1a1916;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px;
      font-weight: 400;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 10px 20px;
      cursor: pointer;
      border-radius: 0;
      transition: background 0.2s, border-color 0.2s;
    }
    .dev-btn:hover { background: #b8a070; border-color: #b8a070; color: #1a1916; }
    .dev-step-label {
      font-size: 9px;
      letter-spacing: 0.12em;
      color: #c8c0b4;
      text-align: right;
    }

    /* ════ PRINT ════ */
    @media print {
      /* Hide everything except the report */
      #progress-wrap, #fixed-logo, #dev-nav, #toast, #err-banner,
      #screen-gate, #screen-opening-quote, #screen-opening,
      #screen-question, #screen-load-p2, #screen-load-p3,
      #screen-t12-quote, #screen-t12-text,
      #screen-t23-quote, #screen-t23-text,
      #screen-pre-quote, #screen-pre-text, #screen-load-report,
      #rs-ctas, .report-ctas, .email-note { display: none !important; }

      /* Reset body + page */
      body {
        background: #f8f6f2 !important;
        color: #2a2520 !important;
        font-size: 11pt;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      /* Unfix the report screen so it flows normally */
      #screen-report {
        position: static !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        padding: 24pt 0 !important;
        overflow: visible !important;
      }

      /* Make all fade-in sections fully visible */
      .rs {
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
      }

      /* Colours adapted for warm paper */
      .report-logo        { color: #8a8278 !important; }
      .section-label      { color: #b8a070 !important; }
      .pattern-text       { color: #2a2520 !important; }
      .section-body,
      .belief-body,
      .what-to-do-body    { color: #4a4540 !important; }
      .belief-name        { color: #1a1714 !important; }
      .closing-question   { color: #2a2520 !important; }
      .report-rule        { background: #d4cfc8 !important; }
      .belief-card        { border-top-color: #b8a070 !important; }
      .shift-from         { border-left-color: #b8a070 !important; color: #4a4540 !important; }
      .shift-to           { border-left-color: #8a6a30 !important; color: #2a2520 !important; }
      .belief-row-label   { color: #b8a070 !important; }
      .closing-label      { color: #b8a070 !important; }
      .closing-rule       { background: #d4cfc8 !important; }

      /* Belief sequence on paper */
      .belief-sequence-step { border-color: #d4cfc8 !important; color: #2a2520 !important; }
      .belief-sequence-arrow { color: #b8a070 !important; }

      /* Page break rules */
      .belief-card        { page-break-inside: avoid; break-inside: avoid; }
      .report-section     { page-break-inside: avoid; break-inside: avoid; }
      .closing-moment     { page-break-before: auto; }

      /* Inner width */
      .inner { max-width: 100% !important; }
    }
  </style>
</head>
<body
  id="app"
  data-calendly="<?php echo defined('CALENDLY_LINK') ? htmlspecialchars(CALENDLY_LINK) : '#'; ?>"
  data-dev="<?php echo $devMode ? '1' : '0'; ?>"
  data-test="<?php echo $testMode ? '1' : '0'; ?>"
>

  <div id="progress-wrap"><div id="progress-fill"></div></div>
  <div id="fixed-logo">Unbelieveme</div>
  <div id="err-banner"></div>

  <!-- ══════════════════════════════════════
       GATE — no valid session
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-gate">
    <div class="inner" style="text-align:center;">
      <p style="font-family:'Cormorant Garamond',serif;font-style:italic;font-size:22px;color:var(--muted);margin-bottom:28px;">
        Please complete payment to begin.
      </p>
      <a class="btn" href="index.php">Visit Unbelieveme →</a>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       OPENING QUOTE SCREEN
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-opening-quote">
    <div class="quote-screen">
      <blockquote class="big-quote">"It ain't what you don't know that gets you into trouble. It's what you know for sure that just ain't so."</blockquote>
      <cite class="big-cite">— Mark Twain</cite>
      <div style="margin-top:40px;"><button class="btn" id="btn-opening">Continue →</button></div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       OPENING SCREEN
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-opening">
    <div class="t-inner" style="text-align:left;max-width:600px;">
      <div class="t-body" style="text-align:left;margin:0 0 40px;">
        <p>You are about to do something most people never do.</p>
        <p>Not because it's difficult. Because nobody ever showed them how.</p>
        <p>Most of us move through life reacting — to situations, to people, to opportunities — without ever asking why we react the way we do. We feel stuck in the same patterns. We want things we can't seem to get. We make the same decisions again and again and wonder why the results don't change.</p>
        <p>The answer is almost never laziness. It's almost never lack of information. It's beliefs.</p>
        <p>A belief is not an opinion. You know you have opinions — you can see them, argue them, change them. A belief is something deeper. A conclusion you drew at some point in your life that felt so true, so confirmed by experience, that it stopped feeling like a conclusion and started feeling like reality itself.</p>
        <p>These beliefs don't feel like beliefs. They feel like facts. They feel like just the way things are.</p>
        <p>This assessment will ask you about your life — specific situations, real feelings, actual patterns — and from your answers, surface the beliefs running underneath. Not the ones you think you have. The ones your behaviour reveals.</p>
        <p>Answer with the first thing that comes. Not the best version of yourself — the honest one. There are no right answers. There is only what's true.</p>
      </div>
      <div class="t-btn-wrap" style="text-align:left;">
        <button class="btn" id="btn-open">I'm ready to begin →</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       QUESTION SCREEN
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-question">
    <div class="q-wrap" id="q-wrap">
      <div class="q-number" id="q-number">Question 1 of 20</div>
      <div class="q-text"   id="q-text"></div>
      <p   class="q-hint"   id="q-hint"></p>
      <textarea class="q-input" id="q-input" placeholder="Write your answer here…" rows="5"></textarea>
      <p class="q-kbd">Cmd + Enter to continue</p>
      <div style="margin-top:4px;">
        <button class="btn" id="btn-continue" disabled>Continue →</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       T12 — QUOTE SCREEN
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-t12-quote">
    <div class="quote-screen">
      <blockquote class="big-quote">"We don't see things as they are. We see them as we are."</blockquote>
      <cite class="big-cite">— Anaïs Nin</cite>
      <div style="margin-top:40px;"><button class="btn" id="btn-q1">Continue →</button></div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       T12 — TEXT SCREEN
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-t12-text">
    <div class="t-inner">
      <div class="t-title">Your filter is not neutral.</div>
      <div class="t-body">
        <p>You've just described your life — where it feels stuck, where it flows, what you want, what you avoid.</p>
        <p>A belief isn't just a thought you have. It's a filter through which all experience passes. Your brain selects what to notice based on what it already believes to be true. If you believe 'I have to earn my place,' it will gather evidence for that belief continuously, automatically, and without your permission. It will filter out — literally not register — the moments that contradict it.</p>
        <p>This isn't a flaw. It's how perception works for every human being.</p>
        <p>The question is: what is your filter set to?</p>
        <p>The next questions go deeper into the specific areas of your life where your beliefs are most active right now. Stay specific. The more concrete your answers, the clearer the picture becomes.</p>
      </div>
      <div class="t-btn-wrap">
        <button class="btn" id="btn-t12">Continue →</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       LOADING PHASE 2
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-load-p2">
    <div class="loading-wrap">
      <div class="loading-dots"><span></span><span></span><span></span></div>
      <div class="loading-label">Reading your answers carefully...</div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       T23 — QUOTE SCREEN
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-t23-quote">
    <div class="quote-screen">
      <blockquote class="big-quote">"Until you make the unconscious conscious, it will direct your life and you will call it fate."</blockquote>
      <cite class="big-cite">— Carl Jung</cite>
      <div style="margin-top:40px;"><button class="btn" id="btn-q2">Continue →</button></div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       T23 — TEXT SCREEN
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-t23-text">
    <div class="t-inner">
      <div class="t-title">Something specific surfaced.</div>
      <div class="t-body">
        <p>Something specific appeared in what you just shared. A pattern. A feeling that kept returning in different forms.</p>
        <p>The next questions follow that thread directly. They are generated from your answers — not random.</p>
        <p>A note on what you might feel right now: sometimes people notice a subtle resistance here. A desire to give a slightly safer answer than the true one. That's worth noticing. It usually means the questions are getting close to something the belief has been protecting.</p>
        <p>Every belief you hold was formed for a reason. At some moment — often in childhood or early adulthood — you had an experience, drew a conclusion, and that conclusion became a lens. These conclusions weren't wrong at the time. They were logical responses to real experiences.</p>
        <p>The problem is that beliefs don't automatically update when the circumstances that created them change. That's what you're doing right now. Looking.</p>
      </div>
      <div class="t-btn-wrap">
        <button class="btn" id="btn-t23">Continue →</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       LOADING PHASE 3
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-load-p3">
    <div class="loading-wrap">
      <div class="loading-dots"><span></span><span></span><span></span></div>
      <div class="loading-label">Going deeper...</div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       PRE-REPORT — QUOTE SCREEN
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-pre-quote">
    <div class="quote-screen">
      <blockquote class="big-quote">"The cave you fear to enter holds the treasure you seek."</blockquote>
      <cite class="big-cite">— Joseph Campbell</cite>
      <div style="margin-top:40px;"><button class="btn" id="btn-pre-quote">Continue →</button></div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       PRE-REPORT — TEXT SCREEN
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-pre-text">
    <div class="t-inner" style="text-align:center;">
      <div class="t-title">Reading what you wrote.</div>
      <div class="t-body" style="text-align:center;">
        <p>Your answers are being read carefully now.</p>
        <p>Not to judge. Not to diagnose.</p>
        <p>To find the patterns that are difficult to see from inside your own life — and name them clearly, so you can finally decide what to do with them.</p>
        <p>What you're about to read was written from your answers. If something lands as true, it's because it came from you.</p>
      </div>
      <div class="t-btn-wrap">
        <button class="btn" id="btn-pre-text">See your report →</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       LOADING REPORT
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-load-report">
    <div class="loading-wrap">
      <div class="loading-dots"><span></span><span></span><span></span></div>
      <div class="loading-label">Your report is being written...</div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
       REPORT SCREEN
  ═══════════════════════════════════════ -->
  <div class="screen" id="screen-report">
    <div class="inner">

      <div class="report-logo">Unbelieveme</div>

      <!-- 1. Pattern -->
      <div class="report-section rs" id="rs-pattern">
        <div class="section-label">What we found</div>
        <div class="pattern-text" id="r-pattern"></div>
      </div>

      <!-- 2. Values -->
      <div class="report-section rs" id="rs-values">
        <div class="section-label">What you actually value</div>
        <div class="section-body" id="r-values"></div>
      </div>

      <!-- 3. Beliefs -->
      <div class="report-section rs" id="rs-beliefs">
        <div class="section-label">Your beliefs</div>
        <div id="r-beliefs-container"></div>
      </div>

      <!-- 4. Goal insight -->
      <div class="report-section rs" id="rs-goal">
        <div class="section-label">Your goal</div>
        <div class="section-body" id="r-goal"></div>
      </div>

      <div class="report-rule rs" id="rs-rule1"></div>

      <!-- 5. What to do (moved before closing question) -->
      <div class="report-section rs" id="rs-what">
        <div class="what-to-do-label">After the assessment</div>
        <div class="what-to-do-body">
          <p>Awareness is the first step. But awareness without direction fades.</p>
          <p>Beliefs don't change through thinking. They change through experience.</p>
          <p>You cannot argue yourself out of a belief that feels like fact. The only thing that changes a belief is a new experience that contradicts it — followed by the reflection that integrates what that experience meant.</p>
          <div class="belief-sequence">
            <div class="belief-sequence-step">New behaviour</div>
            <div class="belief-sequence-arrow">↓</div>
            <div class="belief-sequence-step">New experience</div>
            <div class="belief-sequence-arrow">↓</div>
            <div class="belief-sequence-step">Reflection</div>
            <div class="belief-sequence-arrow">↓</div>
            <div class="belief-sequence-step">Belief update</div>
          </div>
          <p>This is why the first move for each belief matters more than any insight in this report. The insight shows you the belief. The behaviour begins to change it.</p>
          <p>One more thing: beliefs become identity. 'I am someone who struggles with money.' 'I am someone who doesn't finish things.' When a belief becomes identity, changing it feels like self-erasure. That's why the shift described for each belief isn't a new thought — it's a new way of being. Built one action at a time.</p>
        </div>
      </div>

      <!-- 6. Closing question (emotional peak) -->
      <div class="rs" id="rs-closing">
        <div class="closing-moment">
          <div class="closing-rule"></div>
          <p class="closing-label">A question to stay with</p>
          <p class="closing-question" id="r-closing"></p>
          <div class="closing-rule" style="margin-top:40px;"></div>
        </div>
      </div>

      <!-- 7. CTAs -->
      <div class="report-section rs" id="rs-ctas">
        <div class="report-ctas">
          <a class="cta-primary" id="cta-book" href="#" target="_blank" rel="noopener">Book a session →</a>
          <button class="cta-secondary" id="cta-download">Download report →</button>
          <button class="cta-secondary" id="cta-share">Share this →</button>
        </div>
        <p class="email-note">A copy of your report has been sent to your email.</p>
      </div>

      <div class="report-footer">© Unbelieveme 2026 · quiz@unbelieveme.com</div>

    </div>
  </div>

  <!-- Toast -->
  <div id="toast">Link copied</div>

  <!-- Dev nav (dev mode only) -->
  <div id="dev-nav">
    <div class="dev-label">Dev Preview</div>
    <div class="dev-btns">
      <button class="dev-btn" id="dev-prev" onclick="devPrev()">← Prev</button>
      <button class="dev-btn" id="dev-next" onclick="devNext()">Next →</button>
    </div>
    <div class="dev-step-label" id="dev-step-label">—</div>
  </div>

  <!-- ══════════════════════════════════════
       JAVASCRIPT
  ═══════════════════════════════════════ -->
  <script>
  (function() {

    /* ── Session ─────────────────────────── */
    const params    = new URLSearchParams(window.location.search);
    const devMode   = document.getElementById('app').dataset.dev  === '1';
    const testMode  = document.getElementById('app').dataset.test === '1';
    let sessionId   = params.get('session_id') || sessionStorage.getItem('session_id');
    let userEmail   = params.get('email')      || sessionStorage.getItem('user_email') || '';
    const CALENDLY  = document.getElementById('app').dataset.calendly || '#';

    if (devMode) {
      sessionId = 'dev_session_001';
      userEmail = 'dev@test.com';
      sessionStorage.setItem('session_id', sessionId);
      sessionStorage.setItem('user_email', userEmail);
      document.getElementById('dev-nav').classList.add('active');
      setTimeout(function() {
        if (typeof _devSteps !== 'undefined' && typeof initDevMode === 'function') {
          initDevMode();
        }
      }, 50);
      return;
    }

    // TEST BYPASS — ?dev=ubm_test_2026 skips payment check for testing
    if (testMode && !sessionId) {
      sessionId = 'test_' + Date.now();
      sessionStorage.setItem('session_id', sessionId);
    }

    // PAYWALL DISABLED FOR DEV — re-enable before launch
    if (!sessionId) { sessionId = 'open_' + Date.now(); sessionStorage.setItem('session_id', sessionId); }
    sessionStorage.setItem('session_id', sessionId);
    if (userEmail) sessionStorage.setItem('user_email', userEmail);

    /* ── State ───────────────────────────── */
    const TOTAL  = 20;
    let phase    = 1, qIdx = 0;
    let answers  = {}, p2Qs = [], p3Qs = [];
    let reportReady = false, reportData = null;

    const phase1Qs = [
      { id:'q1',  hint:'The first words that come — not the considered ones.',
        text:'If you had to describe where you are in your life right now in three words — not how you want to be, just honestly where you are — what would those three words be?' },
      { id:'q2',  hint:'The honest answer, not the noble one.',
        text:'What do you want most right now — the thing that, if it changed, would change everything else?' },
      { id:'q3',  hint:'The place where trying harder doesn\'t seem to help.',
        text:'Where in your life do you feel the most friction — the place where effort doesn\'t seem to translate into progress, or where the same situation keeps returning?' },
      { id:'q4',  hint:'What makes that area different — what you do, feel, or allow there.',
        text:'Think about the area of your life you\'re most satisfied with right now. What\'s different about that area compared to the ones that feel harder?' },
      { id:'q5',  hint:'Something real that you\'ve been circling without landing on.',
        text:'What\'s something you\'ve wanted for a long time that you haven\'t allowed yourself to fully pursue? Not something impossible — something that was possible, but didn\'t happen.' },
      { id:'q6',  hint:'Concrete. Where do you wake up. What do you do first. Who is there.',
        text:'When you imagine the version of your life that would feel fully right — not perfect, just right — what does a normal Tuesday look like?' },
      { id:'q7',  hint:'Not what you fear — what you actively arrange your days around not feeling.',
        text:'What feeling are you most trying to avoid in your daily life — the one you organise things around not having?' },
      { id:'q8',  hint:'The condition that keeps moving forward every time you get close to it.',
        text:'What do you believe you need to have or be before your life can really begin — the condition you\'re waiting to meet?' },
      { id:'q9',  hint:'Two different answers — one place where you\'re real, one where you perform.',
        text:'Where in your life do you feel most like yourself — most natural, most unguarded? And where do you feel most like a version of yourself you\'re performing?' },
      { id:'q10', hint:'The observation that lands even when you wish it didn\'t.',
        text:'What do people close to you say about you — the things they notice that you sometimes wish they didn\'t?' },
    ];

    /* ── DOM ─────────────────────────────── */
    const progressFill = document.getElementById('progress-fill');
    const fixedLogo    = document.getElementById('fixed-logo');
    const qWrap        = document.getElementById('q-wrap');
    const qNumber      = document.getElementById('q-number');
    const qText        = document.getElementById('q-text');
    const qHint        = document.getElementById('q-hint');
    const qInput       = document.getElementById('q-input');
    const btnContinue  = document.getElementById('btn-continue');
    const errBanner    = document.getElementById('err-banner');

    /* ── Screen ──────────────────────────── */
    function show(id) {
      document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
      const el = document.getElementById(id);
      if (el) el.classList.add('active');
      window.scrollTo(0, 0);
    }

    function setProgress(n) {
      progressFill.style.width = Math.round((n / TOTAL) * 100) + '%';
    }

    function showErr(msg, dur = 7000) {
      errBanner.textContent = msg;
      errBanner.style.display = 'block';
      setTimeout(() => { errBanner.style.display = 'none'; }, dur);
    }

    /* ── Current question set ────────────── */
    function curQs() {
      return phase === 1 ? phase1Qs : phase === 2 ? p2Qs : p3Qs;
    }

    function globalN() {
      return phase === 1 ? qIdx + 1 : phase === 2 ? 10 + qIdx + 1 : 16 + qIdx + 1;
    }

    /* ── Render question ─────────────────── */
    function renderQ(animate) {
      const q   = curQs()[qIdx];
      const num = globalN();

      const doRender = () => {
        qNumber.textContent = 'Question ' + num + ' of ' + TOTAL;
        qText.textContent   = q.text;

        // Hint: show for Phase 1 only
        if (phase === 1 && q.hint) {
          qHint.textContent = q.hint;
          qHint.style.display = 'block';
        } else {
          qHint.textContent = '';
          qHint.style.display = 'none';
        }

        qInput.value = answers[q.id] || '';
        updateBtn();
        setProgress(num - 1);

        if (animate) {
          qWrap.classList.remove('q-in', 'q-out');
          void qWrap.offsetHeight;
          qWrap.classList.add('q-in');
          setTimeout(() => qWrap.classList.remove('q-in'), 460);
        }

        show('screen-question');
        fixedLogo.classList.add('visible');
        setTimeout(() => qInput.focus(), 480);
      };

      if (animate) {
        qWrap.classList.add('q-out');
        setTimeout(doRender, 430);
      } else {
        doRender();
      }
    }

    function updateBtn() {
      btnContinue.disabled = qInput.value.trim().length < 8;
    }

    qInput.addEventListener('input', updateBtn);
    qInput.addEventListener('keydown', e => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'Enter' && !btnContinue.disabled) {
        btnContinue.click();
      }
    });

    /* ── Save ────────────────────────────── */
    async function save(id, val) {
      answers[id] = val;
      try {
        await fetch('api.php?action=save_answers', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ session_id: sessionId, answers: { [id]: val } })
        });
      } catch (_) {}
    }

    /* ── Continue ────────────────────────── */
    btnContinue.addEventListener('click', async () => {
      const q   = curQs()[qIdx];
      const val = qInput.value.trim();
      if (val.length < 8) return;
      btnContinue.disabled = true;
      await save(q.id, val);
      btnContinue.disabled = false;
      qIdx++;

      if (phase === 1) {
        if (qIdx < phase1Qs.length) { renderQ(true); }
        else { setProgress(10); showT12Quote(); }
      } else if (phase === 2) {
        if (qIdx < p2Qs.length) { renderQ(true); }
        else { setProgress(16); showT23Quote(); }
      } else {
        if (qIdx < p3Qs.length) { renderQ(true); }
        else { setProgress(20); showPreQuote(); }
      }
    });

    /* ── Quote → text auto-transitions ───── */
    function showT12Quote() {
      show('screen-t12-quote');
      document.getElementById('btn-q1').onclick = () => show('screen-t12-text');
    }

    function showT23Quote() {
      show('screen-t23-quote');
      document.getElementById('btn-q2').onclick = () => show('screen-t23-text');
    }

    function showPreQuote() {
      show('screen-pre-quote');
      // Start fetching report in background immediately so it's ready when the user arrives
      const reportPromise = fetchReport();
      document.getElementById('btn-pre-quote').onclick = () => {
        show('screen-pre-text');
        document.getElementById('btn-pre-text').onclick = () => {
          show('screen-load-report');
          // Report may already be done, or wait for it
          reportPromise.then(r => {
            if (r) { renderReport(r); show('screen-report'); initIO(); }
          });
        };
      };
    }

    /* ── Fetch Phase 2 ───────────────────── */
    async function fetchP2() {
      show('screen-load-p2');
      try {
        const res  = await fetch('api.php?action=generate_phase2', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ session_id: sessionId, answers })
        });
        const data = await res.json();
        if (data.questions?.length) { p2Qs = data.questions; return true; }
        throw new Error(data.error || 'No questions');
      } catch (e) {
        showErr('Could not generate personalised questions. Please try again.');
        show('screen-t12-text');
        return false;
      }
    }

    /* ── Fetch Phase 3 ───────────────────── */
    async function fetchP3() {
      show('screen-load-p3');
      try {
        const res  = await fetch('api.php?action=generate_phase3', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ session_id: sessionId, answers })
        });
        const data = await res.json();
        if (data.questions?.length) { p3Qs = data.questions; return true; }
        throw new Error(data.error || 'No questions');
      } catch (e) {
        showErr('Could not generate deeper questions. Please try again.');
        show('screen-t23-text');
        return false;
      }
    }

    /* ── Fetch Report ────────────────────── */
    async function fetchReport() {
      try {
        const res  = await fetch('api.php?action=generate_report', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ session_id: sessionId, email: userEmail, answers })
        });
        const data = await res.json();
        if (data.report) { reportData = data.report; return data.report; }
        throw new Error(data.error || 'No report');
      } catch (e) {
        showErr('Could not generate your report. Please refresh and try again.', 12000);
        return null;
      }
    }

    /* ── Render report ───────────────────── */
    function esc(s) {
      return (s || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\n/g,'<br>');
    }

    function renderReport(r) {
      // Report has its own logo (.report-logo) — hide the floating fixed logo
      fixedLogo.classList.remove('visible');

      document.getElementById('r-pattern').innerHTML = esc(r.pattern || '');
      document.getElementById('r-values').innerHTML  = esc(r.values  || '');
      document.getElementById('r-goal').innerHTML    = esc(r.goal_insight || '');
      document.getElementById('r-closing').innerHTML = esc(r.closing_question || '');

      const container = document.getElementById('r-beliefs-container');
      container.innerHTML = '';
      (r.beliefs || []).forEach(b => {
        const card = document.createElement('div');
        card.className = 'belief-card';
        card.innerHTML =
          '<div class="belief-name">'    + esc(b.name) + '</div>' +
          bRow('Where it shows up',       b.where_it_shows_up) +
          bRow("What it's protecting",    b.identity_protecting) +
          bRow("What it's costing you",   b.cost) +
          bRow('The shift',               b.fundamental_shift) +
          bRow('First move',              b.first_move);
        container.appendChild(card);
      });

      document.getElementById('cta-book').href = CALENDLY;
    }

    function bRow(label, text) {
      var content = esc(text || '');
      if (label === 'The shift' && text) {
        var fromMatch = text.match(/From:\s*(.+?)(?=\s*To:|$)/is);
        var toMatch   = text.match(/To:\s*(.+?)(?=\s*$)/is);
        if (fromMatch && toMatch) {
          content = '<div class="shift-from"><span class="shift-label">From</span>' + esc(fromMatch[1].trim()) + '</div>' +
                    '<div class="shift-to"><span class="shift-label">To</span>' + esc(toMatch[1].trim()) + '</div>';
        }
      }
      return '<div class="belief-row">' +
        '<div class="belief-row-label">' + esc(label) + '</div>' +
        '<div class="belief-body">' + content + '</div>' +
        '</div>';
    }

    /* ── Staggered report reveal (no scroll dependency) ── */
    function initIO() {
      const els = document.querySelectorAll('#screen-report .rs');
      els.forEach((el, i) => {
        setTimeout(() => el.classList.add('visible'), i * 120);
      });
    }

    /* ── CTAs ────────────────────────────── */
    document.getElementById('cta-download').addEventListener('click', () => {
      window.print();
    });

    document.getElementById('cta-share').addEventListener('click', () => {
      const url  = 'https://unbelieveme.com';
      const text = 'I just did something I\'ve never done before — a belief assessment that showed me what\'s actually been running my life. Worth 20 minutes of your time: unbelieveme.com';
      if (navigator.share) {
        navigator.share({ text, url });
      } else {
        navigator.clipboard.writeText(url).then(() => {
          const t = document.getElementById('toast');
          t.textContent = 'Link copied';
          t.classList.add('show');
          setTimeout(() => t.classList.remove('show'), 2800);
        });
      }
    });

    /* ── Transition buttons ──────────────── */
    document.getElementById('btn-open').addEventListener('click', () => {
      phase = 1; qIdx = 0; renderQ(false);
    });

    document.getElementById('btn-t12').addEventListener('click', async () => {
      const ok = await fetchP2();
      if (ok) { phase = 2; qIdx = 0; renderQ(false); }
    });

    document.getElementById('btn-t23').addEventListener('click', async () => {
      const ok = await fetchP3();
      if (ok) { phase = 3; qIdx = 0; renderQ(false); }
    });

    /* ── Init ────────────────────────────── */
    show('screen-opening-quote');
    setProgress(0);
    document.getElementById('btn-opening').onclick = () => show('screen-opening');

  })();

  /* ════════════════════════════════════════
     DEV MODE — preview all screens
  ════════════════════════════════════════ */
  var _devStepIdx = 0;

  var _devSteps = [
    { label: 'Opening Quote',   fn: function() { _devShow('screen-opening-quote'); _devProgress(0); } },
    { label: 'Opening',         fn: function() { _devShow('screen-opening'); _devProgress(0); } },
    { label: 'Q1 — Phase 1',    fn: function() { _devShowQ(1, 1, 'If you had to describe where you are in your life right now in three words — not how you want to be, just honestly where you are — what would those three words be?', 'The first words that come — not the considered ones.'); } },
    { label: 'Q5 — Phase 1',    fn: function() { _devShowQ(5, 1, 'What\'s something you\'ve wanted for a long time that you haven\'t allowed yourself to fully pursue?', 'Something real that you\'ve been circling without landing on.'); } },
    { label: 'Q10 — Phase 1',   fn: function() { _devShowQ(10, 1, 'What do people close to you say about you — the things they notice that you sometimes wish they didn\'t?', 'The observation that lands even when you wish it didn\'t.'); } },
    { label: 'T12 Quote',       fn: function() { _devShow('screen-t12-quote'); _devProgress(10); } },
    { label: 'T12 Text',        fn: function() { _devShow('screen-t12-text'); } },
    { label: 'Loading P2',      fn: function() { _devShow('screen-load-p2'); } },
    { label: 'Q11 — Phase 2',   fn: function() { _devShowQ(11, 2, 'When you describe your relationship with money, what feeling comes before any thought about numbers?', null); } },
    { label: 'Q14 — Phase 2',   fn: function() { _devShowQ(14, 2, 'Think about a time you stepped back from something you wanted. What did that feel like in your body — before you found the reason?', null); } },
    { label: 'Q16 — Phase 2',   fn: function() { _devShowQ(16, 2, 'If there were no external obstacles — no money, time, or other people involved — what would you change first?', null); } },
    { label: 'T23 Quote',       fn: function() { _devShow('screen-t23-quote'); _devProgress(16); } },
    { label: 'T23 Text',        fn: function() { _devShow('screen-t23-text'); } },
    { label: 'Loading P3',      fn: function() { _devShow('screen-load-p3'); } },
    { label: 'Q17 — Phase 3',   fn: function() { _devShowQ(17, 3, 'What would it mean about you if you failed at the thing you want most?', null); } },
    { label: 'Q20 — Phase 3',   fn: function() { _devShowQ(20, 3, 'If the person you were at 12 could see how you live now, what would surprise them most?', null); } },
    { label: 'Pre-report Quote', fn: function() { _devShow('screen-pre-quote'); _devProgress(20); } },
    { label: 'Pre-report Text',  fn: function() { _devShow('screen-pre-text'); } },
    { label: 'Loading Report',   fn: function() { _devShow('screen-load-report'); } },
    { label: 'Report',           fn: function() {
        _devRenderReport();
        _devShow('screen-report');
        var els = document.querySelectorAll('#screen-report .rs');
        els.forEach(function(el, i) {
          setTimeout(function() { el.classList.add('visible'); }, i * 120);
        });
      }
    }
  ];

  function _devShow(id) {
    document.querySelectorAll('.screen').forEach(function(s) { s.classList.remove('active'); });
    var el = document.getElementById(id);
    if (el) el.classList.add('active');
    window.scrollTo(0, 0);
    document.getElementById('fixed-logo').classList.add('visible');
  }

  function _devProgress(n) {
    document.getElementById('progress-fill').style.width = Math.round((n / 20) * 100) + '%';
  }

  function _devShowQ(num, ph, text, hint) {
    document.getElementById('q-number').textContent = 'Question ' + num + ' of 20';
    document.getElementById('q-text').textContent   = text;
    var hintEl = document.getElementById('q-hint');
    if (hint && ph === 1) {
      hintEl.textContent   = hint;
      hintEl.style.display = 'block';
    } else {
      hintEl.textContent   = '';
      hintEl.style.display = 'none';
    }
    document.getElementById('q-input').value = '';
    document.getElementById('btn-continue').disabled = false;
    _devProgress(num - 1);
    _devShow('screen-question');
    document.getElementById('fixed-logo').classList.add('visible');
  }

  function _devRenderReport() {
    var mockReport = {
      pattern: 'Across your answers, a single thread surfaces with unusual consistency: you understand what you want, you can articulate it clearly, and then you arrange your life in ways that make it just out of reach. This is not avoidance of failure. It is the avoidance of being seen trying — and the specific consequence that would follow if you tried and succeeded.',
      values: 'What you actually value, beneath what you say you want: integrity over performance. You want to do things that are real. The problem is that most of your current structures were built to look good rather than be good — and part of you knows it. The dissatisfaction you feel is not about having the wrong life. It\'s about performing a life you haven\'t fully chosen.',
      beliefs: [
        {
          name: '"If I let people see what I actually want, they\'ll use it against me."',
          where_it_shows_up: 'In your answer about where you feel most like yourself — the contrast between the public version and the private one is wide enough to be its own belief system. You perform competence in the spaces where you most want to be seen as real.',
          identity_protecting: 'This belief was formed in an environment where visibility led to some form of exposure or control. It is protecting you from the feeling of being known and then dismissed — which, at some point, was a realistic risk.',
          cost: 'The cost is that you have learned to want things quietly, which means pursuing them half-heartedly, which means not getting them, which confirms that wanting things leads nowhere. The belief is self-fulfilling.',
          fundamental_shift: 'The shift is not to become more open. It is to notice that the people who have hurt you by knowing what you wanted are not the same as all people. The belief has overgeneralised from a real experience to a permanent rule.',
          first_move: 'Tell one specific person — someone you already trust — one specific thing you actually want. Not a hope or a direction. A real want. Watch what happens. The data will matter more than the intention.'
        },
        {
          name: '"I have to earn the right to rest."',
          where_it_shows_up: 'Your description of the area where you feel most friction — and the pattern of effort not translating to progress — points here. You are working hard in the wrong direction because moving feels morally correct and stopping feels like failure.',
          identity_protecting: 'This belief is protecting a sense of self-worth that was built around productivity. At some point, your value as a person became tied to your output. Rest became a withdrawal from the contract.',
          cost: 'The cost is exhaustion as an identity. You are not tired because of your workload. You are tired because you have been trying to earn something that cannot be earned — approval from an internal judge who moves the goalposts.',
          fundamental_shift: 'The shift is to separate your worth from your output. Not as a concept you agree with — you already agree with it. As a practice: to stop, before you have "earned" it, and observe that nothing bad happens.',
          first_move: 'Take one hour this week where you do nothing that counts as productive. Do not justify it afterward. Simply let it have happened. Notice the discomfort and do not fix it.'
        }
      ],
      goal_insight: 'What you describe wanting — more freedom, more alignment, a life that feels chosen — is not actually far from where you are. The distance is not structural. You are not missing resources, relationships, or opportunity. What you are missing is permission — specifically, the belief that you are allowed to have the thing you want without first becoming someone who deserves it.',
      closing_question: 'What would you do differently this week if you genuinely believed that you were already enough?'
    };

    document.getElementById('r-pattern').innerHTML = mockReport.pattern;
    document.getElementById('r-values').innerHTML  = mockReport.values;
    document.getElementById('r-goal').innerHTML    = mockReport.goal_insight;
    document.getElementById('r-closing').innerHTML = mockReport.closing_question;

    var container = document.getElementById('r-beliefs-container');
    container.innerHTML = '';
    mockReport.beliefs.forEach(function(b) {
      var card = document.createElement('div');
      card.className = 'belief-card';
      function bRow(label, text) {
        return '<div class="belief-row"><div class="belief-row-label">' + label + '</div><div class="belief-body">' + (text || '') + '</div></div>';
      }
      card.innerHTML =
        '<div class="belief-name">' + b.name + '</div>' +
        bRow('Where it shows up',    b.where_it_shows_up) +
        bRow("What it's protecting", b.identity_protecting) +
        bRow("What it's costing you", b.cost) +
        bRow('The shift',            b.fundamental_shift) +
        bRow('First move',           b.first_move);
      container.appendChild(card);
    });

    document.getElementById('cta-book').href = '#dev-calendly';
  }

  function _devUpdateLabel() {
    var step = _devSteps[_devStepIdx];
    document.getElementById('dev-step-label').textContent =
      (_devStepIdx + 1) + ' / ' + _devSteps.length + ' — ' + step.label;
  }

  function initDevMode() {
    _devStepIdx = 0;
    _devSteps[0].fn();
    _devUpdateLabel();
  }

  function devNext() {
    if (_devStepIdx < _devSteps.length - 1) {
      _devStepIdx++;
      _devSteps[_devStepIdx].fn();
      _devUpdateLabel();
    }
  }

  function devPrev() {
    if (_devStepIdx > 0) {
      _devStepIdx--;
      _devSteps[_devStepIdx].fn();
      _devUpdateLabel();
    }
  }

  // Auto-init dev mode after all steps defined
  if (document.getElementById('app').dataset.dev === '1') {
    initDevMode();
  }
  </script>
</body>
</html>
