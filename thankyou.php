<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment Confirmed — Unbelieveme</title>
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

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: 16px;
      line-height: 1.8;
      -webkit-font-smoothing: antialiased;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 48px 24px;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }
    @keyframes pulse {
      0%, 100% { opacity: 0.25; }
      50%       { opacity: 1; }
    }

    .page {
      max-width: 480px;
      width: 100%;
      text-align: center;
    }

    .logo {
      font-size: 11px;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 64px;
      animation: fadeUp 600ms ease 0ms both;
    }

    .state { display: none; }
    .state.active { display: block; }

    /* Loading */
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
      color: var(--muted);
      animation: fadeIn 600ms ease 400ms both;
    }

    /* Success */
    .success-headline {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-style: italic;
      font-size: 36px;
      color: var(--text);
      line-height: 1.2;
      margin-bottom: 12px;
      animation: fadeUp 700ms ease 0ms both;
    }

    .success-sub {
      font-size: 16px;
      color: var(--muted);
      margin-bottom: 40px;
      animation: fadeUp 700ms ease 180ms both;
    }

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
      margin-bottom: 28px;
      animation: fadeUp 700ms ease 360ms both;
    }
    .btn:hover { background: var(--accent-dim); color: var(--bg); }

    .support-note {
      font-size: 12px;
      color: var(--deep);
      animation: fadeIn 600ms ease 540ms both;
    }

    /* Error */
    .error-msg {
      font-size: 15px;
      color: var(--muted);
      line-height: 1.8;
      margin-bottom: 28px;
    }
    .back-link {
      font-size: 11px;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--accent-dim);
      text-decoration: none;
    }
    .back-link:hover { color: var(--accent); }
  </style>
</head>
<body>
  <div class="page">

    <div class="logo">Unbelieveme</div>

    <div class="state active" id="state-loading">
      <div class="loading-dots"><span></span><span></span><span></span></div>
      <div class="loading-label">Confirming payment</div>
    </div>

    <div class="state" id="state-success">
      <h1 class="success-headline">Payment confirmed.</h1>
      <p class="success-sub">Your assessment is ready.</p>
      <a class="btn" id="begin-btn" href="#">Begin →</a>
      <p class="support-note">If you experience any issues: quiz@unbelieveme.com</p>
    </div>

    <div class="state" id="state-error">
      <p class="error-msg" id="error-msg">
        Payment could not be confirmed. If you completed payment, please wait a moment and refresh.
        If the issue persists, contact quiz@unbelieveme.com.
      </p>
      <a href="index.php" class="back-link">← Back to Unbelieveme</a>
    </div>

  </div>

  <script>
  (function() {
    const params = new URLSearchParams(window.location.search);
    const stripeSessionId = params.get('session_id');

    function show(id) {
      document.querySelectorAll('.state').forEach(el => el.classList.remove('active'));
      document.getElementById(id).classList.add('active');
    }

    function showError(msg) {
      if (msg) document.getElementById('error-msg').textContent = msg;
      show('state-error');
    }

    if (!stripeSessionId) {
      showError('No payment session found. Please complete payment at unbelieveme.com.');
      return;
    }

    fetch('api.php?action=verify_stripe', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ stripe_session_id: stripeSessionId })
    })
    .then(r => r.json())
    .then(data => {
      if (data.paid) {
        const sessionId = 'sess_' + stripeSessionId.replace(/[^a-zA-Z0-9]/g, '').slice(0, 24);
        sessionStorage.setItem('session_id', sessionId);
        sessionStorage.setItem('user_email', data.email || '');

        document.getElementById('begin-btn').href =
          'assessment.php?session_id=' + encodeURIComponent(sessionId) +
          '&email=' + encodeURIComponent(data.email || '');

        show('state-success');
      } else {
        showError(data.error || 'Payment status could not be confirmed. Please refresh or contact support.');
      }
    })
    .catch(() => {
      showError('Network error while confirming payment. Please refresh the page.');
    });
  })();
  </script>
</body>
</html>
