<?php
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    redirect(roleUrl($_SESSION['role'], 'dashboard.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            $error = 'Please fill in all fields.';
        } else {
            try {
                $db = getDB();
                $loginError = login($db, $username, $password);
                if ($loginError === null) {
                    redirect(roleUrl($_SESSION['role'], 'dashboard.php'));
                }
                $error = $loginError;
            } catch (PDOException $e) {
                $error = 'Database connection failed. Please ensure mho_db is set up.';
            }
        }
    }
}

$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --role-accent:       #1d9e75;
      --role-accent-dark:  #085041;
      --role-accent-light: #5dcaa5;
      --role-accent-soft:  #e8f5f1;
      --role-text-muted:   #4a8a75;
      --sky:       #EAF6FB;
      --mint:      var(--role-accent-light);
      --mint-dark: #0F6E56;
      --mint-med:  var(--role-accent);
      --mint-soft: var(--role-accent-soft);
      --mint-glow: #6ee7b7;
      --navy:      var(--role-accent-dark);
      --white:     #FFFFFF;
      --text-main: #0F6E56;
      --text-muted:var(--role-text-muted);
      --border:    #c2e8d8;
      --input-bg:  #f5fcf9;
      --error:     #e24b4a;
      --hero-bg:   #0f5240;
      --hero-mid:  #1a6b55;
      --panel-bg:  #1a3830;
      --icon-color:var(--role-accent-dark);
      --icon-bg:   rgba(255, 255, 255, 0.88);
      --curve-line:#86C2A1;
    }

    body {
      font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: var(--panel-bg);
      min-height: 100vh;
      color: var(--text-main);
      -webkit-font-smoothing: antialiased;
    }

    .login-page {
      display: flex;
      min-height: 100vh;
      position: relative;
      overflow: hidden;
    }

    .auth-badge {
      position: absolute;
      top: 1.25rem;
      right: 1.75rem;
      z-index: 4;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.45rem 0.95rem;
      background: #eef2f8;
      border: 1px solid #c5cdd9;
      border-radius: 999px;
      font-size: 0.8125rem;
      font-weight: 600;
      color: #7a3b3b;
      letter-spacing: 0.01em;
      white-space: nowrap;
      box-shadow: 0 1px 3px rgba(15, 40, 60, 0.06);
    }

    .auth-badge-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #e04b4b;
      box-shadow: 0 0 0 2px rgba(224, 75, 75, 0.18);
      flex-shrink: 0;
    }

    .login-page-footer {
      position: absolute;
      bottom: 1rem;
      left: 50%;
      transform: translateX(-50%);
      z-index: 4;
      width: min(92%, 56rem);
      text-align: center;
      font-size: 0.6875rem;
      font-weight: 500;
      line-height: 1.55;
      color: #000;
      padding: 0 1.25rem;
      pointer-events: none;
    }

    /* Left hero panel */
    .login-hero {
      flex: 0 0 58%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 3rem 3.5rem 3rem 4rem;
      position: relative;
      z-index: 1;
      overflow: hidden;
      clip-path: url(#heroCurve);
      -webkit-clip-path: url(#heroCurve);
    }

    .login-hero > :not(.hero-bg):not(.hero-wave):not(.hero-brand) {
      position: relative;
      z-index: 2;
    }

    .hero-bg {
      position: absolute;
      inset: 0;
      z-index: 0;
      overflow: hidden;
      clip-path: url(#heroCurve);
      -webkit-clip-path: url(#heroCurve);
      background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.78) 0%, rgba(234, 246, 251, 0.62) 50%, rgba(225, 245, 238, 0.55) 100%),
        url('<?= e(baseUrl("Photo/RHU%20pic.jpg")) ?>') center center / cover no-repeat;
    }

    .hero-bg::after {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 90% 55% at 50% 100%, rgba(93, 202, 165, 0.14) 0%, transparent 65%),
        repeating-linear-gradient(
          0deg,
          transparent,
          transparent 39px,
          rgba(29, 158, 117, 0.05) 39px,
          rgba(29, 158, 117, 0.05) 40px
        ),
        repeating-linear-gradient(
          90deg,
          transparent,
          transparent 39px,
          rgba(29, 158, 117, 0.05) 39px,
          rgba(29, 158, 117, 0.05) 40px
        );
      pointer-events: none;
      mask-image: linear-gradient(to top, black 0%, transparent 58%);
      -webkit-mask-image: linear-gradient(to top, black 0%, transparent 58%);
    }

    .hero-wave {
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 180px;
      pointer-events: none;
      z-index: 1;
      overflow: hidden;
      clip-path: url(#heroCurve);
      -webkit-clip-path: url(#heroCurve);
    }

    .hero-wave svg {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    .curve-defs {
      position: absolute;
      width: 0;
      height: 0;
      overflow: hidden;
    }

    .curve-stroke {
      position: absolute;
      top: 0;
      left: 0;
      width: 58%;
      height: 100%;
      z-index: 3;
      pointer-events: none;
    }

    .curve-stroke path {
      stroke: var(--curve-line);
      stroke-width: 9;
      vector-effect: non-scaling-stroke;
      fill: none;
    }

    .curve-stroke .curve-glow {
      stroke: var(--curve-line);
      stroke-width: 20;
      opacity: 0.35;
    }

    .hero-brand {
      position: absolute;
      top: 1.25rem;
      left: 1.75rem;
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      z-index: 3;
    }

    .hero-brand-logo {
      width: 48px;
      height: 48px;
      background: var(--white);
      border: 2px solid var(--border);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
      box-shadow: 0 4px 14px rgba(8, 80, 65, 0.08);
    }

    .hero-brand-logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .hero-brand-titles { display: flex; flex-direction: column; gap: 2px; }
    .hero-brand-name { font-size: 1.05rem; font-weight: 800; color: var(--role-accent-dark); line-height: 1.25; letter-spacing: -0.02em; }
    .hero-brand-sub  { font-size: 0.78rem; font-weight: 600; color: var(--role-accent); letter-spacing: 0.01em; line-height: 1.35; }

    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 34rem;
      align-self: flex-start;
      margin-left: 8.5rem;
      text-align: left;
    }

    .hero-content-label {
      display: block;
      font-size: clamp(2.35rem, 4.8vw, 3.5rem);
      font-weight: 800;
      color: var(--role-accent);
      line-height: 1.2;
      margin-bottom: 0.75rem;
      letter-spacing: -0.02em;
      white-space: nowrap;
    }

    .hero-headline {
      font-size: clamp(2rem, 3.5vw, 3rem);
      font-weight: 800;
      color: var(--role-accent-dark);
      line-height: 1.02;
      margin-bottom: 1.25rem;
      letter-spacing: -0.03em;
    }

    .hero-headline-line {
      display: block;
    }

    .hero-subtitle {
      font-size: 15px;
      font-weight: 500;
      color: #000;
      line-height: 1.7;
      margin-bottom: 0;
    }

    /* Right login panel — fills region to the right of the curve */
    .login-panel {
      position: absolute;
      inset: 0;
      z-index: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding-left: 58%;
      padding-right: 1.25rem;
      background: linear-gradient(165deg, var(--panel-bg) 0%, #142e27 55%, #122820 100%);
      clip-path: url(#loginCurve);
      -webkit-clip-path: url(#loginCurve);
      overflow: hidden;
    }

    .login-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at 20% 15%, rgba(93, 202, 165, 0.1) 0%, transparent 45%),
        radial-gradient(circle at 80% 85%, rgba(15, 110, 86, 0.15) 0%, transparent 40%);
      pointer-events: none;
    }

    .login-card {
      width: 100%;
      max-width: 560px;
      min-height: 540px;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(194, 232, 216, 0.9);
      border-radius: 32px;
      padding: 2.75rem 2.5rem 2.5rem;
      box-shadow:
        0 20px 50px rgba(8, 80, 65, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-card-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1.25rem;
      margin-bottom: 2.25rem;
    }

    .login-card-intro {
      flex: 1;
      min-width: 0;
      padding-top: 0.15rem;
    }

    .login-card-title {
      font-size: clamp(1.35rem, 2.4vw, 1.65rem);
      font-weight: 800;
      color: var(--navy);
      line-height: 1.25;
      margin-bottom: 0.35rem;
      letter-spacing: -0.02em;
    }

    .login-card-subtitle {
      font-size: 0.9375rem;
      font-weight: 500;
      color: var(--role-text-muted);
      line-height: 1.45;
    }

    .login-card-seal {
      width: 84px;
      height: 84px;
      border-radius: 50%;
      flex-shrink: 0;
      overflow: hidden;
      box-shadow: 0 4px 16px rgba(8, 80, 65, 0.08);
      background: var(--white);
    }

    .login-card-seal img {
      width: 128%;
      height: 128%;
      margin: -14%;
      object-fit: cover;
      object-position: center;
      border-radius: 50%;
      display: block;
    }

    .field { margin-bottom: 1.35rem; }

    .field label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--navy);
      margin-bottom: 0.55rem;
      letter-spacing: 0;
    }

    .input-wrap { position: relative; }

    .input-wrap input {
      width: 100%;
      padding: 0.95rem 2.75rem 0.95rem 1.25rem;
      border-radius: 999px;
      border: 1.5px solid var(--border);
      background: var(--input-bg);
      font-size: 0.9375rem;
      color: var(--navy);
      font-family: inherit;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
    }

    .input-wrap input::placeholder { color: #a0c4b8; }

    .input-wrap input:focus {
      border-color: var(--mint);
      box-shadow: 0 0 0 3px rgba(93, 202, 165, 0.18);
      background: var(--white);
    }

    .toggle-pw {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 2px;
      display: flex;
      align-items: center;
    }

    .toggle-pw svg {
      width: 17px;
      height: 17px;
      stroke: var(--icon-color);
      fill: none;
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .error-msg {
      display: none;
      align-items: center;
      gap: 6px;
      background: #fff5f5;
      border: 1px solid #f7c1c1;
      border-radius: 999px;
      padding: 8px 14px;
      font-size: 12px;
      color: var(--error);
      font-weight: 500;
      margin-bottom: 1rem;
    }

    .error-msg.show { display: flex; }

    .error-msg svg {
      width: 14px;
      height: 14px;
      stroke: var(--error);
      fill: none;
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
      flex-shrink: 0;
    }

    .btn-signin {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 1rem 0;
      border-radius: 999px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      background: linear-gradient(135deg, var(--mint-med) 0%, var(--mint-dark) 100%);
      color: var(--white);
      font-family: inherit;
      margin-top: 0.35rem;
      transition: opacity 0.15s, transform 0.1s, box-shadow 0.15s;
      box-shadow: 0 4px 14px rgba(15, 110, 86, 0.25);
    }

    .btn-signin:hover {
      opacity: 0.92;
      box-shadow: 0 6px 18px rgba(15, 110, 86, 0.3);
    }

    .btn-signin:active { transform: scale(0.98); }

    .btn-signin svg {
      width: 16px;
      height: 16px;
      stroke: var(--white);
      fill: none;
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .btn-signin.loading { opacity: 0.7; pointer-events: none; }

    .login-card-or {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      margin: 1.5rem 0;
    }

    .login-card-or::before,
    .login-card-or::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .login-card-or span {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--role-text-muted);
      letter-spacing: 0.04em;
    }

    .btn-signin-secondary {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.65rem;
      width: 100%;
      padding: 0.95rem 1rem;
      border-radius: 999px;
      border: 1.5px solid var(--border);
      background: var(--white);
      color: var(--navy);
      font-size: 0.9375rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      transition: border-color 0.15s, background 0.15s;
    }

    .btn-signin-secondary:hover {
      border-color: var(--mint);
      background: var(--input-bg);
    }

    .btn-google-icon {
      width: 18px;
      height: 18px;
      flex-shrink: 0;
    }

    .login-card-footer {
      text-align: center;
      font-size: 0.8125rem;
      font-weight: 500;
      color: var(--role-text-muted);
      margin-top: 1.5rem;
      line-height: 1.5;
    }

    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    @media (max-width: 991.98px) {
      .login-page { flex-direction: column; }

      .curve-stroke { display: none; }

      .login-hero,
      .login-panel {
        flex: none;
        width: 100%;
      }

      .login-hero,
      .hero-bg,
      .hero-wave {
        clip-path: none;
        -webkit-clip-path: none;
      }

      .login-panel {
        position: relative;
        inset: auto;
        clip-path: none;
        -webkit-clip-path: none;
        padding: 1.5rem 1.25rem 2rem;
        width: 100%;
      }

      .login-hero {
        padding: 2.5rem 2rem;
        align-items: center;
        text-align: center;
      }

      .hero-brand {
        top: 1rem;
        left: 1.25rem;
      }

      .hero-content {
        margin-left: 4rem;
        align-self: flex-start;
        text-align: left;
        max-width: 100%;
      }

      .hero-headline,
      .hero-subtitle {
        max-width: 100%;
      }

    }

    @media (max-width: 640px) {
      .login-hero { padding: 2rem 1.25rem; }

      .hero-brand {
        top: 0.85rem;
        left: 1rem;
      }

      .hero-content {
        margin-left: 2.5rem;
      }

      .login-card {
        max-width: 100%;
        min-height: auto;
        padding: 2rem 1.5rem 1.75rem;
        border-radius: 24px;
      }

      .login-panel { padding: 1rem; }

      .auth-badge {
        top: 0.85rem;
        right: 1rem;
        font-size: 0.75rem;
        padding: 0.4rem 0.75rem;
      }

      .login-page-footer {
        bottom: 0.75rem;
        font-size: 0.625rem;
        padding: 0 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="login-page">
    <div class="auth-badge" role="status">
      <span class="auth-badge-dot" aria-hidden="true"></span>
      Authorized Personnel Only
    </div>

    <svg aria-hidden="true" class="curve-defs">
      <defs>
        <clipPath id="heroCurve" clipPathUnits="objectBoundingBox">
          <path d="M 0,0 L 0.86,0 C 1,0.17 1,0.83 0.86,1 L 0,1 Z" />
        </clipPath>
        <clipPath id="loginCurve" clipPathUnits="objectBoundingBox">
          <path d="M 0.4988,0 C 0.58,0.17 0.58,0.83 0.4988,1 L 1,1 L 1,0 Z" />
        </clipPath>
      </defs>
    </svg>

    <section class="login-hero">
      <div class="hero-bg" aria-hidden="true"></div>
      <a class="hero-brand" href="index.php">
        <div class="hero-brand-logo">
          <img src="<?= e(baseUrl('Photo/RHULOGO.jpg')) ?>" alt="RHU Logo">
        </div>
        <div class="hero-brand-titles">
          <span class="hero-brand-name"><?= e(APP_OFFICE_NAME) ?></span>
          <span class="hero-brand-sub"><?= e(APP_TAGLINE) ?></span>
        </div>
      </a>

      <div class="hero-content">
        <span class="hero-content-label"><?= e(APP_OFFICE_NAME) ?></span>
        <h1 class="hero-headline">
          <span class="hero-headline-line">Health</span>
          <span class="hero-headline-line">Entry</span>
          <span class="hero-headline-line">Access</span>
          <span class="hero-headline-line">Records and</span>
          <span class="hero-headline-line">Tracking System</span>
        </h1>
        <p class="hero-subtitle"><?= e(APP_DESCRIPTION) ?></p>
      </div>

      <div class="hero-wave" aria-hidden="true">
        <svg viewBox="0 0 1200 180" preserveAspectRatio="none">
          <defs>
            <linearGradient id="waveFill" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="rgba(93,202,165,0.08)" />
              <stop offset="100%" stop-color="rgba(234,246,251,0.35)" />
            </linearGradient>
          </defs>
          <path d="M0,120 C200,60 400,160 600,100 C800,40 1000,140 1200,80 L1200,180 L0,180 Z" fill="url(#waveFill)" />
          <path d="M0,120 C200,60 400,160 600,100 C800,40 1000,140 1200,80" fill="none" stroke="rgba(29,158,117,0.28)" stroke-width="1.5" />
        </svg>
      </div>
    </section>

    <svg class="curve-stroke" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
      <path class="curve-glow" d="M 86,0 C 100,17 100,83 86,100" />
      <path class="curve-line" d="M 86,0 C 100,17 100,83 86,100" />
    </svg>

    <section class="login-panel">
      <div class="login-card">
        <div class="login-card-top">
          <div class="login-card-intro">
            <h2 class="login-card-title">Welcome Back to <?= e(APP_SHORT_NAME) ?>!</h2>
            <p class="login-card-subtitle">Sign in to your account to continue</p>
          </div>
          <div class="login-card-seal">
            <img src="<?= e(baseUrl('Photo/Heart.png')) ?>" alt="<?= e(APP_SHORT_NAME) ?> logo">
          </div>
        </div>

        <div class="error-msg <?= $error ? 'show' : '' ?>" id="errorMsg">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span id="errorText"><?= e($error ?: 'Invalid username or password.') ?></span>
        </div>

        <form method="POST" action="" id="loginForm" onsubmit="return handleSubmit(event)">
          <?= csrfField() ?>

          <div class="field">
            <label for="username">Username</label>
            <div class="input-wrap">
              <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required value="<?= e($_POST['username'] ?? '') ?>" />
            </div>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
              <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required />
              <button type="button" class="toggle-pw" onclick="togglePassword()" aria-label="Show or hide password">
                <svg id="eyeIcon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-signin" id="submitBtn">
            Sign in
            <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </form>

        <div class="login-card-or" aria-hidden="true"><span>OR</span></div>

        <button type="button" class="btn-signin-secondary">
          <svg class="btn-google-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          Sign in with Google
        </button>

        <p class="login-card-footer">Need access? Contact your administrator.</p>
      </div>
    </section>

    <footer class="login-page-footer">
      © <?= date('Y') ?> Municipal Government of Solano · <?= e(APP_OFFICE_NAME) ?> <?= e(APP_TAGLINE) ?> · All rights reserved.
    </footer>
  </div>

  <script>
    function togglePassword() {
      const pw = document.getElementById('password');
      const icon = document.getElementById('eyeIcon');
      if (pw.type === 'password') {
        pw.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
      } else {
        pw.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
      }
    }

    function handleSubmit(e) {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const errorMsg = document.getElementById('errorMsg');

      if (!username || !password) {
        e.preventDefault();
        document.getElementById('errorText').textContent = 'Please fill in all fields.';
        errorMsg.classList.add('show');
        return false;
      }

      errorMsg.classList.remove('show');
      const btn = document.getElementById('submitBtn');
      btn.classList.add('loading');
      btn.innerHTML = '<svg viewBox="0 0 24 24" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" stroke-dasharray="56" stroke-dashoffset="56" stroke="currentColor" fill="none" stroke-width="2.5" stroke-linecap="round"/></svg> Signing in…';
      return true;
    }
  </script>
</body>
</html>
