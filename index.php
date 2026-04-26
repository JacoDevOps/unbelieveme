<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Unbelieveme — Let go of what you think you know.</title>
  <meta name="description" content="A 25-minute assessment that surfaces the beliefs running your life — the ones you didn't choose, and the ones that are costing you." />
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

    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: clamp(1rem, 1.5vw, 1.15rem);
      line-height: 1.8;
      -webkit-font-smoothing: subpixel-antialiased;
      -moz-osx-font-smoothing: auto;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }
    @keyframes scrollPulse {
      0%   { opacity: 0.2; transform: translateY(0); }
      60%  { opacity: 0.8; transform: translateY(6px); }
      100% { opacity: 0.2; transform: translateY(0); }
    }

    /* ────────────────────────────────────────────
       HERO
    ──────────────────────────────────────────── */
    .hero {
      min-height: 100vh;
      background-image:
        linear-gradient(
          to bottom,
          rgba(26,25,22,0.3)  0%,
          rgba(26,25,22,0.6)  50%,
          rgba(26,25,22,0.95) 85%,
          rgba(26,25,22,1)    100%
        ),
        url('hero.jpg');
      background-size: cover;
      background-position: center top;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding-bottom: 80px;
      position: relative;
    }

    .hero-content {
      max-width: 680px;
      margin: 0 auto;
      padding: 0 32px;
      text-align: center;
    }

    .hero-logo {
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: clamp(0.75rem, 1.5vw, 0.875rem);
      letter-spacing: 0.38em;
      text-transform: uppercase;
      color: #ddd6cc;
      margin-bottom: 6px;
    }

    .hero-tagline {
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: clamp(0.7rem, 1.2vw, 0.8rem);
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #9a9288;
      margin-bottom: 56px;
    }

    .hero-headline {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-style: italic;
      font-size: clamp(2.2rem, 5vw, 3.75rem);
      line-height: 1.1;
      color: var(--text);
      margin-bottom: 28px;
    }

    .hero-subheadline {
      font-size: clamp(1rem, 1.8vw, 1.15rem);
      color: #c8c0b4;
      max-width: 480px;
      margin: 0 auto 40px;
      line-height: 1.8;
    }

    .hero-details {
      font-size: 11px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #8a8278;
      margin-bottom: 32px;
    }

    .hero-divider {
      width: 40px;
      height: 1px;
      background: var(--border);
      margin: 0 auto 28px;
    }

    .hero-price {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-size: 22px;
      color: var(--accent);
      margin-bottom: 20px;
    }

    .btn-hero {
      display: inline-block;
      border: 1px solid var(--accent);
      color: var(--accent);
      background: transparent;
      padding: 18px 48px;
      font-family: 'DM Sans', sans-serif;
      font-size: 11px;
      font-weight: 300;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      cursor: pointer;
      transition: background 0.3s ease, color 0.3s ease;
      text-decoration: none;
      border-radius: 0;
    }
    .btn-hero:hover { background: var(--accent); color: var(--bg); }

    /* Scroll indicator */
    .scroll-indicator {
      position: absolute;
      bottom: 28px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      opacity: 0;
      animation: fadeIn 800ms ease 1200ms forwards;
    }
    .scroll-indicator-line {
      width: 1px;
      height: 32px;
      background: var(--muted);
      animation: scrollPulse 2s ease-in-out infinite;
    }
    .scroll-indicator-label {
      font-size: 9px;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: #7a7470;
    }

    /* Hero element animations */
    .hero-logo     { opacity: 0; animation: fadeUp 700ms ease 0ms    forwards; }
    .hero-tagline  { opacity: 0; animation: fadeUp 700ms ease 120ms  forwards; }
    .hero-headline { opacity: 0; animation: fadeUp 700ms ease 280ms  forwards; }
    .hero-subheadline { opacity: 0; animation: fadeUp 700ms ease 440ms forwards; }
    .hero-details  { opacity: 0; animation: fadeUp 700ms ease 580ms  forwards; }
    .hero-divider  { opacity: 0; animation: fadeIn 700ms ease 680ms  forwards; }
    .hero-price    { opacity: 0; animation: fadeUp 700ms ease 720ms  forwards; }
    .hero-cta      { opacity: 0; animation: fadeUp 700ms ease 860ms  forwards; }

    /* ────────────────────────────────────────────
       BELOW-FOLD SECTIONS
    ──────────────────────────────────────────── */
    .content-section {
      background: #1a1916;
      padding: 96px 32px;
    }

    .content-section-inner {
      max-width: 680px;
      margin: 0 auto;
    }

    .offer-section {
      background: #201f1c;
      padding: 96px 32px;
      border-top: 1px solid var(--border);
      text-align: center;
    }

    .offer-section-inner {
      max-width: 680px;
      margin: 0 auto;
    }

    .section-label {
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: clamp(0.7rem, 1.2vw, 0.85rem);
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 24px;
    }

    .section-headline {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-style: italic;
      font-size: clamp(1.8rem, 3.5vw, 2.5rem);
      line-height: 1.3;
      color: #ddd6cc;
      margin-bottom: 32px;
    }

    .section-body {
      font-size: 16px;
      color: #c8c0b4;
      line-height: 1.85;
      max-width: 560px;
    }

    .section-body p { margin-bottom: 20px; color: #c8c0b4; }
    .section-body p:last-child { margin-bottom: 0; }

    .section-rule {
      width: 40px;
      height: 1px;
      background: var(--border);
      margin: 56px 0;
    }

    /* ── How-it-works feature rows ── */
    .feature-rows {
      margin-top: 40px;
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    .feature-row {
      display: grid;
      grid-template-columns: 48px 1fr;
      gap: 24px;
      align-items: start;
      padding: 28px 0 28px 20px;
      border-left: 1px solid var(--accent-dim);
      margin-bottom: 8px;
    }

    .feature-row-num {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-size: 20px;
      color: var(--accent);
      line-height: 1.4;
      padding-top: 2px;
    }

    .feature-row-body {}

    .feature-row-title {
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: 12px;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: #ddd6cc;
      margin-bottom: 8px;
    }

    .feature-row-text {
      font-size: 15px;
      color: #c8c0b4;
      line-height: 1.8;
    }

    /* CTA note */
    .cta-note {
      font-size: 15px;
      color: #c8c0b4;
      max-width: 400px;
      margin: 0 auto 40px;
      line-height: 1.85;
    }

    .offer-divider {
      width: 40px;
      height: 1px;
      background: var(--border);
      margin: 0 auto 28px;
    }

    .offer-price {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-size: 22px;
      color: var(--accent);
      margin-bottom: 20px;
    }

    /* scroll-triggered fade */
    .scroll-fade {
      opacity: 0;
      transform: translateY(14px);
      transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .scroll-fade.visible {
      opacity: 1;
      transform: translateY(0);
    }
    .no-js .scroll-fade { opacity: 1; transform: none; }

    /* ────────────────────────────────────────────
       FOOTER
    ──────────────────────────────────────────── */
    footer {
      border-top: 1px solid var(--border);
      padding: 28px 32px;
      text-align: center;
      font-size: 11px;
      color: #5a5650;
      letter-spacing: 0.06em;
      background: #1a1916;
    }

    /* ────────────────────────────────────────────
       MOBILE
    ──────────────────────────────────────────── */
    @media (max-width: 768px) {
      .hero-headline { font-size: 2.2rem; }
      .hero { padding-bottom: 64px; }
      .hero-content { padding: 0 20px; }
      .section-headline { font-size: 1.8rem; }
      .content-section { padding: 64px 20px; }
      .offer-section { padding: 64px 20px; }
      .hero-tagline { margin-bottom: 36px; }
    }
  </style>
</head>
<body>

  <!-- ══════════════════════════════════════════
       SECTION 1 — HERO
  ═══════════════════════════════════════════ -->
  <section class="hero" id="hero">

    <div class="hero-content">
      <div class="hero-logo">Unbelieveme</div>
      <div class="hero-tagline">Let go of what you think you know.</div>

      <h1 class="hero-headline">
        What you believe<br>shapes your life.<br><em>What are you shaping?</em>
      </h1>

      <p class="hero-subheadline">
        A 25-minute assessment that surfaces the beliefs running your life —
        the ones you didn't choose, and the ones that are costing you.
      </p>

      <div class="hero-details">
        25 minutes &nbsp;·&nbsp; Personalised to your answers &nbsp;·&nbsp; Report delivered immediately
      </div>

      <div class="hero-divider"></div>

      <div class="hero-price">€36.90</div>

      <div class="hero-cta">
        <a class="btn-hero" href="<?php echo defined('STRIPE_PAYMENT_LINK') ? htmlspecialchars(STRIPE_PAYMENT_LINK) : '#'; ?>">
          Begin the assessment →
        </a>
      </div>
    </div>

    <div class="scroll-indicator" onclick="document.getElementById('section-belief').scrollIntoView({behavior:'smooth'})">
      <div class="scroll-indicator-line"></div>
      <div class="scroll-indicator-label">Scroll</div>
    </div>

  </section>

  <!-- ══════════════════════════════════════════
       SECTION 2 — The nature of belief
  ═══════════════════════════════════════════ -->
  <section class="content-section" id="section-belief">
    <div class="content-section-inner">

      <div class="section-label scroll-fade">What this is</div>

      <h2 class="section-headline scroll-fade">
        A belief is not something<br>you think. It's something<br>you see through.
      </h2>

      <div class="section-body scroll-fade">
        <p>Most of us know our opinions. We can argue them, update them, change our minds. Opinions are visible to us.</p>
        <p>Beliefs are different. A belief is a conclusion you drew at some point in your life that felt so confirmed by experience — so obviously true — that it stopped feeling like a conclusion. It started feeling like reality itself.</p>
        <p>You don't notice it working. You just notice the results: the same friction in the same places. The same ceiling. The same pattern showing up again under different names.</p>
        <p>That's not a personality trait. That's a belief you haven't looked at yet.</p>
      </div>

      <div class="section-rule scroll-fade"></div>

      <div class="section-label scroll-fade">Why you can't see them alone</div>

      <div class="section-body scroll-fade">
        <p>Beliefs don't reveal themselves through thinking. The harder you look for them in your own mind, the more invisible they become — because you're using the very lens they created to try to find them.</p>
        <p>What surfaces them is the pattern in your behaviour. The gap between what you say you want and what you actually do. The place where your story keeps going quiet.</p>
        <p>That's what this assessment reads.</p>
      </div>

    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 3 — How the assessment works
  ═══════════════════════════════════════════ -->
  <section class="content-section" style="border-top: 1px solid var(--border);">
    <div class="content-section-inner">

      <div class="section-label scroll-fade">How it works</div>

      <h2 class="section-headline scroll-fade">
        Three phases.<br>Twenty questions.<br>One mirror.
      </h2>

      <div class="feature-rows">

        <div class="feature-row scroll-fade">
          <div class="feature-row-num">I</div>
          <div class="feature-row-body">
            <div class="feature-row-title">Foundation — 10 questions</div>
            <div class="feature-row-text">
              You describe your life as it is: where it flows, where it stalls, what you want, what you avoid. No interpretation yet — just what's true.
            </div>
          </div>
        </div>

        <div class="feature-row scroll-fade">
          <div class="feature-row-num">II</div>
          <div class="feature-row-body">
            <div class="feature-row-title">Deepening — 6 personalised questions</div>
            <div class="feature-row-text">
              Based on your answers, the assessment identifies the 2–3 areas of highest emotional charge and generates targeted questions for those areas only. These questions come from what you said — not from a template.
            </div>
          </div>
        </div>

        <div class="feature-row scroll-fade">
          <div class="feature-row-num">III</div>
          <div class="feature-row-body">
            <div class="feature-row-title">The core — 4 downward arrow questions</div>
            <div class="feature-row-text">
              Following the most charged thread in your answers, these questions go toward the root — the identity-level conclusion underneath the pattern.
            </div>
          </div>
        </div>

      </div>

      <div class="section-rule scroll-fade"></div>

      <div class="section-label scroll-fade">What you receive</div>

      <div class="section-body scroll-fade">
        <p>A written report — generated immediately from your answers — that names 3 to 5 specific beliefs identified from patterns across your responses. Not from what you said you believe. From how you described behaving.</p>
        <p>For each belief: where it shows up in your answers, what it's protecting, what it's costing you, what a genuine shift would look like, and one concrete first move.</p>
        <p>The report is delivered on screen the moment it's ready, and sent to your email with a downloadable PDF.</p>
      </div>

    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 4 — Final CTA
  ═══════════════════════════════════════════ -->
  <section class="offer-section">
    <div class="offer-section-inner">

      <div class="section-label scroll-fade">Begin</div>

      <h2 class="section-headline scroll-fade">
        Twenty-five minutes.<br>Beliefs you've held for decades.
      </h2>

      <p class="cta-note scroll-fade">
        This is not a personality test. Not therapy. Not a course.<br>
        It is a mirror — one that shows you what your behaviour<br>
        has already been saying about what you believe.
      </p>

      <div class="offer-divider scroll-fade"></div>

      <div class="offer-price scroll-fade">€36.90</div>

      <div class="scroll-fade" style="margin-top: 20px;">
        <a class="btn-hero" href="<?php echo defined('STRIPE_PAYMENT_LINK') ? htmlspecialchars(STRIPE_PAYMENT_LINK) : '#'; ?>">
          Begin the assessment →
        </a>
      </div>

    </div>
  </section>

  <!-- ══════════════════════════════════════════
       FOOTER
  ═══════════════════════════════════════════ -->
  <footer>
    © Unbelieveme 2026 &nbsp;·&nbsp; quiz@unbelieveme.com
  </footer>

  <script>
    // Staggered page-load animation — no scroll dependency, nothing ever stays hidden
    document.addEventListener('DOMContentLoaded', function() {
      var scrollEls = document.querySelectorAll('.scroll-fade');
      scrollEls.forEach(function(el, i) {
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(14px)';
        setTimeout(function() {
          el.style.opacity = '1';
          el.style.transform = 'translateY(0)';
        }, i * 50);
      });
    });
  </script>

</body>
</html>
