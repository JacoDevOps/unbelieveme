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
      font-size: 16px;
      line-height: 1.8;
      -webkit-font-smoothing: antialiased;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }

    .fade-up { opacity: 0; animation: fadeUp 700ms ease forwards; }
    .d-0  { animation-delay: 0ms; }
    .d-1  { animation-delay: 180ms; }
    .d-2  { animation-delay: 360ms; }
    .d-3  { animation-delay: 540ms; }
    .d-4  { animation-delay: 720ms; }
    .d-5  { animation-delay: 900ms; }

    /* ── Section base ── */
    .section {
      max-width: 680px;
      margin: 0 auto;
      padding: 80px 32px;
    }

    /* ────────────────────────────────────────────
       HERO
    ──────────────────────────────────────────── */
    .hero {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 80px 32px;
      position: relative;
    }

    .hero-img-wrap {
      position: absolute;
      inset: 0;
      overflow: hidden;
      z-index: 0;
    }

    /* hero.jpg — save from: https://images.unsplash.com/photo-1620503374956-c942862f0372?w=1600&q=80 */
    .hero-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
      opacity: 0.12;
      filter: grayscale(100%);
    }

    .hero-content {
      position: relative;
      z-index: 1;
      max-width: 580px;
    }

    .wordmark {
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: 11px;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 6px;
    }

    .tagline-small {
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: 11px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--deep);
      margin-bottom: 72px;
    }

    .hero-headline {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-style: italic;
      font-size: 52px;
      line-height: 1.15;
      color: var(--text);
      margin-bottom: 28px;
    }

    .hero-sub {
      font-size: 16px;
      color: var(--muted);
      max-width: 460px;
      margin: 0 auto 48px;
      line-height: 1.8;
    }

    .hero-details {
      font-size: 11px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 32px;
    }

    .divider {
      width: 40px;
      height: 1px;
      background: var(--border);
      margin: 0 auto 32px;
    }

    .price {
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

    /* ────────────────────────────────────────────
       SECTION 2 — The nature of belief
    ──────────────────────────────────────────── */
    .section-label {
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: 10px;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 24px;
    }

    .section-headline {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-style: italic;
      font-size: 36px;
      line-height: 1.3;
      color: var(--text);
      margin-bottom: 32px;
    }

    .section-body {
      font-size: 16px;
      color: var(--muted);
      line-height: 1.85;
      max-width: 560px;
    }

    .section-body p { margin-bottom: 20px; }
    .section-body p:last-child { margin-bottom: 0; }

    .section-rule {
      width: 40px;
      height: 1px;
      background: var(--border);
      margin: 56px 0;
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

    /* ────────────────────────────────────────────
       SECTION 3 — How it works
    ──────────────────────────────────────────── */
    .steps {
      margin-top: 40px;
      display: flex;
      flex-direction: column;
      gap: 32px;
    }

    .step {
      display: grid;
      grid-template-columns: 32px 1fr;
      gap: 20px;
      align-items: start;
    }

    .step-num {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-size: 18px;
      color: var(--accent);
      line-height: 1.4;
      padding-top: 2px;
    }

    .step-text {
      font-size: 15px;
      color: var(--muted);
      line-height: 1.8;
    }

    .step-text strong {
      display: block;
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      font-size: 13px;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--text);
      margin-bottom: 6px;
    }

    /* ────────────────────────────────────────────
       SECTION 4 — Final CTA
    ──────────────────────────────────────────── */
    .cta-section {
      text-align: center;
      padding: 80px 32px;
      border-top: 1px solid var(--border);
    }

    .cta-section .section-headline {
      margin-bottom: 16px;
    }

    .cta-note {
      font-size: 12px;
      color: var(--muted);
      max-width: 360px;
      margin: 0 auto 48px;
      line-height: 1.8;
    }

    /* ────────────────────────────────────────────
       FOOTER
    ──────────────────────────────────────────── */
    footer {
      border-top: 1px solid var(--border);
      padding: 28px 32px;
      text-align: center;
      font-size: 11px;
      color: var(--deep);
      letter-spacing: 0.06em;
    }

    @media (max-width: 640px) {
      .hero-headline { font-size: 34px; }
      .section-headline { font-size: 26px; }
      .section { padding: 48px 20px; }
      .hero { padding: 48px 20px; min-height: auto; padding-top: 80px; padding-bottom: 80px; }
      .tagline-small { margin-bottom: 48px; }
    }
  </style>
</head>
<body>

  <!-- ══════════════════════════════════════════
       SECTION 1 — HERO
  ═══════════════════════════════════════════ -->
  <section class="hero">
    <div class="hero-img-wrap">
      <!-- Save hero.jpg from: https://images.unsplash.com/photo-1620503374956-c942862f0372?w=1600&q=80 -->
      <img class="hero-img" src="hero.jpg" alt="" onerror="this.style.display='none'" />
    </div>

    <div class="hero-content">
      <div class="wordmark fade-up d-0">Unbelieveme</div>
      <div class="tagline-small fade-up d-0">Let go of what you think you know.</div>

      <h1 class="hero-headline fade-up d-1">
        Most people are living<br>someone else's beliefs.
      </h1>

      <p class="hero-sub fade-up d-2">
        A 25-minute assessment that surfaces the beliefs running your life —
        the ones you didn't choose, and the ones that are costing you.
      </p>

      <div class="hero-details fade-up d-3">
        25 minutes &nbsp;·&nbsp; Personalised to your answers &nbsp;·&nbsp; Report delivered immediately
      </div>

      <div class="divider fade-up d-3"></div>

      <div class="price fade-up d-4">€36.90</div>

      <div class="fade-up d-4">
        <a class="btn-hero" href="<?php echo defined('STRIPE_PAYMENT_LINK') ? htmlspecialchars(STRIPE_PAYMENT_LINK) : '#'; ?>">
          Begin the assessment →
        </a>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       SECTION 2 — The nature of belief
  ═══════════════════════════════════════════ -->
  <section>
    <div class="section">
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
  <section style="border-top: 1px solid var(--border);">
    <div class="section">
      <div class="section-label scroll-fade">How it works</div>

      <h2 class="section-headline scroll-fade">
        Three phases.<br>Twenty questions.<br>One mirror.
      </h2>

      <div class="steps">
        <div class="step scroll-fade">
          <div class="step-num">I</div>
          <div class="step-text">
            <strong>Foundation — 10 questions</strong>
            You describe your life as it is: where it flows, where it stalls, what you want, what you avoid. No interpretation yet — just what's true.
          </div>
        </div>

        <div class="step scroll-fade">
          <div class="step-num">II</div>
          <div class="step-text">
            <strong>Deepening — 6 personalised questions</strong>
            Based on your answers, the assessment identifies the 2–3 areas of highest emotional charge and generates targeted questions for those areas only. These questions come from what you said — not from a template.
          </div>
        </div>

        <div class="step scroll-fade">
          <div class="step-num">III</div>
          <div class="step-text">
            <strong>The core — 4 downward arrow questions</strong>
            Following the most charged thread in your answers, these questions go toward the root — the identity-level conclusion underneath the pattern.
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
  <section class="cta-section">
    <div class="section-label scroll-fade">Begin</div>

    <h2 class="section-headline scroll-fade">
      Twenty-five minutes.<br>Beliefs you've held for decades.
    </h2>

    <p class="cta-note scroll-fade">
      This is not a personality test. Not therapy. Not a course.<br>
      It is a mirror — one that shows you what your behaviour<br>
      has already been saying about what you believe.
    </p>

    <div class="divider scroll-fade"></div>

    <div class="price scroll-fade">€36.90</div>

    <div style="margin-top: 20px;" class="scroll-fade">
      <a class="btn-hero" href="<?php echo defined('STRIPE_PAYMENT_LINK') ? htmlspecialchars(STRIPE_PAYMENT_LINK) : '#'; ?>">
        Begin the assessment →
      </a>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       FOOTER
  ═══════════════════════════════════════════ -->
  <footer>
    © Unbelieveme 2026 &nbsp;·&nbsp; quiz@unbelieveme.com
  </footer>

  <script>
    // Scroll-triggered fade for all .scroll-fade elements
    const scrollEls = document.querySelectorAll('.scroll-fade');
    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            e.target.classList.add('visible');
            obs.unobserve(e.target);
          }
        });
      }, { threshold: 0.12 });
      scrollEls.forEach(el => obs.observe(el));
    } else {
      scrollEls.forEach(el => el.classList.add('visible'));
    }
  </script>
</body>
</html>
