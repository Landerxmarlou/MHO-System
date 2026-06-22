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
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --sky:       #EAF6FB;
      --mint:      #5DCAA5;
      --mint-dark: #0F6E56;
      --mint-med:  #1D9E75;
      --mint-soft: #E1F5EE;
      --navy:      #085041;
      --white:     #FFFFFF;
      --text-main: #0F6E56;
      --text-muted:#4a8a75;
      --border:    #c2e8d8;
      --input-bg:  #f5fcf9;
      --error:     #e24b4a;
    }

    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: var(--sky);
      min-height: 100vh;
      color: var(--text-main);
    }

    nav {
      background: var(--mint-dark);
      padding: 0 2rem;
      height: 56px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .nav-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }

    .nav-logo {
      width: 44px;
      height: 44px;
      background: var(--mint);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
    }

    .nav-logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .nav-titles { display: flex; flex-direction: column; }
    .nav-org  { font-size: 10px; font-weight: 600; letter-spacing: 0.08em; color: #9FE1CB; text-transform: uppercase; }
    .nav-name { font-size: 13px; font-weight: 600; color: #ffffff; }

    .nav-badge {
      display: flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,0.1);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 999px;
      padding: 4px 12px;
      font-size: 11px;
      color: #9FE1CB;
      font-weight: 500;
    }

    .nav-badge::before {
      content: '';
      width: 6px;
      height: 6px;
      background: var(--mint);
      border-radius: 50%;
    }

    main {
      min-height: calc(100vh - 56px - 44px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1.5rem;
      position: relative;
      overflow: hidden;
    }

    main::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        linear-gradient(rgba(13, 110, 253, 0.10), rgba(15, 110, 86, 0.12)),
        url('<?= e(baseUrl("Photo/RHU%20pic.jpg")) ?>') center center / cover no-repeat;
      opacity: 0.62;
      pointer-events: none;
    }

    .login-wrap {
      display: flex;
      width: 100%;
      max-width: 860px;
      background: rgba(255, 255, 255, 0.92);
      border-radius: 20px;
      border: 1px solid var(--border);
      overflow: hidden;
      position: relative;
      z-index: 1;
    }

    .panel-left {
      flex: 1;
      background: var(--sky);
      padding: 2.4rem 2rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      border-right: 1px solid var(--border);
    }

    .secure-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--mint-soft);
      border: 1px solid var(--border);
      border-radius: 999px;
      padding: 4px 14px;
      font-size: 12px;
      font-weight: 600;
      color: var(--mint-dark);
      margin-bottom: 1.4rem;
      width: fit-content;
    }

    .secure-badge svg { width: 13px; height: 13px; stroke: var(--mint-med); fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

    .panel-left .org-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.1em;
      color: var(--text-muted);
      text-transform: uppercase;
      margin-bottom: 0.5rem;
    }

    .panel-left h1 {
      font-size: 1.9rem;
      font-weight: 700;
      color: var(--navy);
      line-height: 1.15;
      margin-bottom: 0.9rem;
    }

    .panel-left .subtitle {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.6;
      margin-bottom: 1.4rem;
    }

    .features { display: flex; flex-direction: column; gap: 9px; }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 13px;
      font-weight: 500;
      color: var(--mint-dark);
    }

    .feature-dot {
      width: 7px;
      height: 7px;
      background: var(--mint);
      border-radius: 50%;
      flex-shrink: 0;
    }

    .panel-right {
      width: 340px;
      flex-shrink: 0;
      padding: 2.35rem 2rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-eyebrow {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.1em;
      color: var(--text-muted);
      text-transform: uppercase;
      margin-bottom: 0.5rem;
    }

    .panel-right h2 {
      font-size: 1.45rem;
      font-weight: 700;
      color: var(--navy);
      margin-bottom: 0.35rem;
    }

    .panel-right .tagline {
      font-size: 13px;
      color: var(--text-muted);
      line-height: 1.5;
      margin-bottom: 1.45rem;
    }

    .field { margin-bottom: 1.1rem; }

    .field label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.08em;
      color: var(--text-muted);
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .input-wrap { position: relative; }

    .input-wrap svg {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 15px;
      height: 15px;
      stroke: var(--mint-med);
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      pointer-events: none;
    }

    .input-wrap input {
      width: 100%;
      padding: 10px 40px 10px 36px;
      border-radius: 9px;
      border: 1.5px solid var(--border);
      background: var(--input-bg);
      font-size: 14px;
      color: var(--navy);
      font-family: inherit;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
    }

    .input-wrap input::placeholder { color: #a0c4b8; }

    .input-wrap input:focus {
      border-color: var(--mint);
      box-shadow: 0 0 0 3px rgba(93,202,165,0.18);
      background: var(--white);
    }

    .toggle-pw {
      position: absolute;
      right: 11px;
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
      position: static;
      transform: none;
      width: 15px;
      height: 15px;
      stroke: var(--text-muted);
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .error-msg {
      display: none;
      align-items: center;
      gap: 6px;
      background: #fff5f5;
      border: 1px solid #f7c1c1;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 12px;
      color: var(--error);
      font-weight: 500;
      margin-bottom: 1rem;
    }

    .error-msg.show { display: flex; }

    .error-msg svg { width: 14px; height: 14px; stroke: var(--error); fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

    .btn-signin {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 11px 0;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      background: var(--mint);
      color: var(--navy);
      font-family: inherit;
      margin-top: 0.5rem;
      margin-bottom: 1rem;
      transition: opacity 0.15s, transform 0.1s;
    }

    .btn-signin:hover { opacity: 0.88; }
    .btn-signin:active { transform: scale(0.98); }
    .btn-signin svg { width: 16px; height: 16px; stroke: var(--navy); fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

    .btn-signin.loading { opacity: 0.7; pointer-events: none; }

    .need-access {
      text-align: center;
      font-size: 13px;
      color: var(--text-muted);
    }

    footer {
      background: var(--mint-dark);
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      text-align: center;
      gap: 2px;
      padding: 0 2rem;
      font-size: 11px;
      color: rgba(159, 225, 203, 0.75);
    }

    @media (max-width: 640px) {
      nav, footer { padding-left: 1rem; padding-right: 1rem; }
      .login-wrap { flex-direction: column; }
      .panel-left { border-right: none; border-bottom: 1px solid var(--border); padding: 1.75rem 1.25rem; }
      .panel-right { width: 100%; padding: 1.75rem 1.25rem; }
      main { padding: 1rem; }
      footer { height: auto; gap: 0.5rem; padding-top: 0.65rem; padding-bottom: 0.65rem; flex-direction: column; align-items: flex-start; }
    }

    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
  </style>
</head>
<body>
  <nav>
    <a class="nav-brand" href="index.php">
      <div class="nav-logo">
        <img src="<?= e(baseUrl('Photo/RHULOGO.jpg')) ?>" alt="RHU Logo">
      </div>
      <div class="nav-titles">
        <span class="nav-org">Municipality of Solano</span>
        <span class="nav-name">MHO Record Management System</span>
      </div>
    </a>
    <div class="nav-badge">Authorized Personnel Only</div>
  </nav>

  <main>
    <div class="login-wrap">
      <div class="panel-left">
        <div class="secure-badge">
          <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Secure Portal
        </div>
        <div class="org-label">Municipality of Solano</div>
        <h1>Record management built for health reporting.</h1>
        <p class="subtitle">Track submissions, manage indicators, and keep every report organized across programs and barangays.</p>
        <div class="features">
          <div class="feature-item"><div class="feature-dot"></div>Real-time submission tracking</div>
          <div class="feature-item"><div class="feature-dot"></div>Role-based access for staff and supervisors</div>
          <div class="feature-item"><div class="feature-dot"></div>Structured indicator encoding and review</div>
        </div>
      </div>

      <div class="panel-right">
        <div class="form-eyebrow">Access Portal</div>
        <h2>Sign in to your account</h2>
        <p class="tagline">Enter your credentials to continue.</p>

        <div class="error-msg <?= $error ? 'show' : '' ?>" id="errorMsg">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span id="errorText"><?= e($error ?: 'Invalid username or password.') ?></span>
        </div>

        <form method="POST" action="" id="loginForm" onsubmit="return handleSubmit(event)">
          <?= csrfField() ?>

          <div class="field">
            <label for="username">Username</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required value="<?= e($_POST['username'] ?? '') ?>" />
            </div>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required />
              <button type="button" class="toggle-pw" onclick="togglePassword()" aria-label="Show or hide password">
                <svg id="eyeIcon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-signin" id="submitBtn">
            <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Sign In
          </button>
        </form>

        <div class="need-access">Need access? Contact your administrator.</div>
      </div>
    </div>
  </main>

  <footer>
    <span>© <?= date('Y') ?> Municipality of Solano · MHO Record Management System</span>
    <span>Municipal Health Office · All rights reserved</span>
  </footer>

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
