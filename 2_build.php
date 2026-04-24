<?php
/*
 * ============================================================
 *  build.php — Credit Score Engine Builder
 *  
 *  STUDENT REFERENCE CODE
 *  This is a simplified standalone version — no database needed.
 *  The engine configuration is stored in PHP $_SESSION.
 *  
 *  In a production system (like myfintechs.com) you would:
 *  1. Store engine config in a MySQL database table
 *  2. Require user login before accessing this page
 *  3. Generate a unique public URL (slug) per student
 *  
 *  Key concepts demonstrated here:
 *  - Collecting weightages from a form (POST)
 *  - Validating that weightages sum to exactly 100
 *  - Setting approval/hold thresholds
 *  - Storing engine config in session
 *  - Generating the public applicant URL
 * ============================================================
 */

session_start();

$msg = '';
$msg_type = '';

// ── Load engine from session (replaces database in this demo) ─────
$engine = $_SESSION['engine'] ?? null;

// ── SAVE ENGINE ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $company  = trim($_POST['company_name'] ?? '');
    $tagline  = trim($_POST['tagline']      ?? '');

    // Weightages — each factor gets a percentage of the total score
    $w_income     = max(0, min(100, (int)($_POST['w_income']     ?? 20)));
    $w_dscr       = max(0, min(100, (int)($_POST['w_dscr']       ?? 30)));
    $w_debt       = max(0, min(100, (int)($_POST['w_debt']       ?? 20)));
    $w_employment = max(0, min(100, (int)($_POST['w_employment'] ?? 15)));
    $w_repayment  = max(0, min(100, (int)($_POST['w_repayment']  ?? 15)));

    // Thresholds — student decides where Approve/Hold/Reject lines are
    $threshold_approve = max(1, min(99, (int)($_POST['threshold_approve'] ?? 70)));
    $threshold_hold    = max(1, min(99, (int)($_POST['threshold_hold']    ?? 50)));

    $total_weight = $w_income + $w_dscr + $w_debt + $w_employment + $w_repayment;

    // ── VALIDATION ────────────────────────────────────────────────
    if (strlen($company) < 2) {
        $msg = "Please enter your company name.";
        $msg_type = "error";
    } elseif ($total_weight !== 100) {
        $msg = "Weightages must total exactly 100. Your total: {$total_weight}.";
        $msg_type = "error";
    } elseif ($threshold_hold >= $threshold_approve) {
        $msg = "Hold threshold must be lower than Approve threshold.";
        $msg_type = "error";
    } else {
        // ── SAVE TO SESSION ───────────────────────────────────────
        // In production: INSERT or UPDATE in MySQL database
        $_SESSION['engine'] = [
            'company_name'      => $company,
            'tagline'           => $tagline,
            'w_income'          => $w_income,
            'w_dscr'            => $w_dscr,
            'w_debt'            => $w_debt,
            'w_employment'      => $w_employment,
            'w_repayment'       => $w_repayment,
            'threshold_approve' => $threshold_approve,
            'threshold_hold'    => $threshold_hold,
            // In production: generate a unique slug and save to DB
            'slug'              => strtolower(preg_replace('/[^a-z0-9]+/', '-', $company)),
        ];
        $engine = $_SESSION['engine'];
        $msg = "✅ Engine saved! Share the link below with the public.";
        $msg_type = "success";
    }
}

// Public URL (in production this would be a unique slug from the database)
$public_url = $engine ? 'apply.php?slug=' . $engine['slug'] : '';

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Engine Builder · Credit Score Engine</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=Inter:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d0d0d;--bg2:#111;--bg3:#1a1a1a;
  --orange:#ff6b00;--orange2:#ff8c00;--orange3:#ffb347;
  --red:#ff3b3b;--green:#22c55e;--amber:#f59e0b;
  --muted:#777;--border:rgba(255,107,0,.18);
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:#f0f0f0;min-height:100vh;
  background-image:radial-gradient(ellipse 60% 30% at 50% 0,rgba(255,107,0,.05),transparent)}
.topbar{display:flex;justify-content:space-between;align-items:center;padding:14px 32px;
  background:#0a0a0a;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:10}
.logo{font-family:'Syne',sans-serif;font-weight:900;font-size:1rem;color:#fff}
.logo span{color:var(--orange2)}
.nav-btn{color:var(--muted);font-size:.78rem;text-decoration:none;
  padding:6px 14px;border:1px solid #333;border-radius:6px}
.nav-btn:hover{color:var(--orange2);border-color:var(--orange)}

.wrap{max-width:820px;margin:0 auto;padding:32px 24px 80px}
.page-title{font-family:'Syne',sans-serif;font-weight:900;font-size:1.5rem;color:#fff;margin-bottom:6px}
.page-sub{font-size:.85rem;color:var(--muted);line-height:1.7;margin-bottom:28px}

/* ALERT */
.alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:.86rem;line-height:1.5}
.alert-success{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.3);color:#22c55e}
.alert-error{background:rgba(255,59,59,.08);border:1px solid rgba(255,59,59,.3);color:var(--red)}

/* CARDS */
.card{background:var(--bg3);border:1px solid var(--border);border-radius:14px;padding:24px;margin-bottom:18px}
.card-title{font-family:'Syne',sans-serif;font-weight:800;font-size:.92rem;color:#f0f0f0;
  margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid rgba(255,107,0,.1)}

/* FORM */
.fg{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.fg label{font-size:.68rem;color:var(--muted);letter-spacing:.1em;text-transform:uppercase}
.fg input{background:var(--bg2);border:1.5px solid rgba(255,107,0,.2);border-radius:8px;
  color:#f0f0f0;padding:11px 14px;font-size:.9rem;outline:none;transition:.2s}
.fg input:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,107,0,.1)}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:600px){.two-col{grid-template-columns:1fr}}

/* WEIGHTAGE SLIDERS */
.w-row{display:grid;grid-template-columns:170px 1fr 64px;align-items:center;
  gap:14px;margin-bottom:18px;padding-bottom:18px;border-bottom:1px solid rgba(255,255,255,.04)}
.w-row:last-of-type{border:none;margin-bottom:0;padding-bottom:0}
@media(max-width:600px){.w-row{grid-template-columns:1fr;gap:6px}}
.w-label{font-size:.85rem;font-weight:600;color:#f0f0f0}
.w-desc{font-size:.72rem;color:var(--muted);margin-top:3px;line-height:1.5}
.w-slider{-webkit-appearance:none;width:100%;height:6px;
  background:rgba(255,107,0,.15);border-radius:99px;outline:none;cursor:pointer}
.w-slider::-webkit-slider-thumb{-webkit-appearance:none;width:18px;height:18px;
  border-radius:50%;background:var(--orange);cursor:pointer}
.w-num{background:var(--bg2);border:1.5px solid rgba(255,107,0,.25);border-radius:7px;
  color:var(--orange2);font-family:'Space Mono',monospace;font-size:1rem;font-weight:700;
  padding:6px;text-align:center;outline:none;width:62px}

.total-box{background:rgba(255,107,0,.06);border:1px solid rgba(255,107,0,.2);
  border-radius:10px;padding:14px 18px;margin-top:18px;
  display:flex;align-items:center;justify-content:space-between}
.total-num{font-family:'Syne',sans-serif;font-weight:900;font-size:2.8rem;line-height:1}
.ok{color:var(--green)!important}
.warn{color:var(--red)!important}

/* THRESHOLDS */
.thresh-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px}
.thresh-card{background:var(--bg2);border:1.5px solid rgba(255,107,0,.2);border-radius:10px;padding:16px;text-align:center}
.thresh-label{font-size:.68rem;color:var(--muted);letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px}
.thresh-input{width:100%;background:transparent;border:none;outline:none;
  font-family:'Syne',sans-serif;font-weight:900;font-size:2.2rem;color:var(--orange2);text-align:center}
.thresh-note{font-size:.72rem;color:var(--muted);margin-top:4px}

/* PUBLISH BOX */
.pub-box{background:rgba(34,197,94,.06);border:1.5px solid rgba(34,197,94,.25);
  border-radius:12px;padding:20px;margin-top:20px}
.pub-title{font-family:'Syne',sans-serif;font-weight:800;font-size:.9rem;color:#22c55e;margin-bottom:10px}
.pub-url{font-family:'Space Mono',monospace;font-size:.82rem;color:var(--orange3);
  background:rgba(0,0,0,.4);padding:10px 14px;border-radius:8px;word-break:break-all;margin-bottom:12px}

/* BUTTON */
.btn{display:inline-flex;align-items:center;gap:6px;padding:12px 26px;border-radius:8px;
  border:none;cursor:pointer;font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem;transition:.15s}
.btn-primary{background:linear-gradient(135deg,var(--orange),#c2410c);color:#fff;
  box-shadow:0 4px 14px rgba(255,107,0,.35);width:100%;justify-content:center;margin-top:18px;padding:13px}
.btn-primary:disabled{opacity:.35;cursor:not-allowed}

/* CODE NOTE */
.code-note{background:rgba(255,107,0,.04);border-left:3px solid var(--orange);
  padding:12px 16px;margin-top:28px;border-radius:0 8px 8px 0;font-size:.8rem;color:var(--muted);line-height:1.8}
.code-note strong{color:var(--orange3)}
</style>
</head>
<body>

<div class="topbar">
  <div class="logo">Credit<span>Score</span>.Engine <span style="font-size:.55rem;color:#444;font-family:'Space Mono',monospace;margin-left:8px">// Build</span></div>
  <a href="index.php" class="nav-btn">← Home</a>
</div>

<div class="wrap">
  <div class="page-title"><?= $engine ? '✏️ Edit Your Engine' : '🏗 Build Your Credit Score Engine' ?></div>
  <div class="page-sub">
    Set your company name, assign weightages to each scoring factor, and define your thresholds.<br>
    Once saved, share the public URL — anyone who visits it submits their finances and your engine scores them.
  </div>

  <?php if($msg): ?>
  <div class="alert alert-<?= $msg_type ?>"><?= h($msg) ?></div>
  <?php endif; ?>

  <form method="POST">

    <!-- ── COMPANY IDENTITY ─────────────────────────────────── -->
    <div class="card">
      <div class="card-title">🏢 Company Identity</div>
      <div class="two-col">
        <div class="fg">
          <label>Credit Company Name *</label>
          <input type="text" name="company_name" required maxlength="80"
            placeholder="e.g. SmartRisk AI, TrustMetric"
            value="<?= h($engine['company_name'] ?? '') ?>">
        </div>
        <div class="fg">
          <label>Tagline</label>
          <input type="text" name="tagline" maxlength="160"
            placeholder="e.g. Credit Intelligence You Can Trust"
            value="<?= h($engine['tagline'] ?? '') ?>">
        </div>
      </div>
    </div>

    <!-- ── WEIGHTAGES ────────────────────────────────────────── -->
    <!--
      KEY CONCEPT: Weightages
      Each factor is scored 0-100 (raw score).
      The raw score is then multiplied by its weight/100.
      Final score = sum of all weighted factor scores.
      Example: if Income raw score = 80 and weight = 20,
               Income contributes 80 × 20/100 = 16 points to final score.
    -->
    <div class="card">
      <div class="card-title">⚖️ Scoring Factor Weightages <span style="font-size:.7rem;color:var(--muted);font-weight:400;font-family:'Space Mono',monospace">must total 100</span></div>

      <?php
      $factors = [
        ['w_income',     '💰 Income Stability',
         'Net income ÷ gross income. How much of your income is left after expenses.',
         $engine['w_income'] ?? 20],
        ['w_dscr',       '⚖️ DSCR',
         'Debt Service Coverage Ratio: Net Income ÷ (Existing EMIs + New EMI). The core metric.',
         $engine['w_dscr'] ?? 30],
        ['w_debt',       '💳 Existing Debt Load',
         'Existing EMIs as % of income. Lower burden = higher score.',
         $engine['w_debt'] ?? 20],
        ['w_employment', '🏢 Employment Stability',
         'Years of continuous employment. More years = more stable income.',
         $engine['w_employment'] ?? 15],
        ['w_repayment',  '📈 Repayment Track Record',
         'Repayments completed ÷ loans taken. Higher ratio = better credit discipline.',
         $engine['w_repayment'] ?? 15],
      ];
      foreach($factors as [$key, $label, $desc, $val]): ?>
      <div class="w-row">
        <div>
          <div class="w-label"><?= $label ?></div>
          <div class="w-desc"><?= $desc ?></div>
        </div>
        <input type="range" class="w-slider" min="0" max="100" value="<?= $val ?>"
          oninput="syncW('<?= $key ?>',this.value)" id="sl-<?= $key ?>">
        <input type="number" class="w-num" name="<?= $key ?>" min="0" max="100"
          value="<?= $val ?>" id="n-<?= $key ?>"
          oninput="syncW('<?= $key ?>',this.value)">
      </div>
      <?php endforeach; ?>

      <div class="total-box">
        <div>
          <div style="font-size:.75rem;color:var(--muted);margin-bottom:4px">Total Weightage</div>
          <div id="w-msg" style="font-size:.8rem;color:var(--muted)">Adjust until total = 100</div>
        </div>
        <div style="text-align:right">
          <div class="total-num" id="w-total">100</div>
          <div style="font-size:.65rem;color:var(--muted);font-family:'Space Mono',monospace">/ 100</div>
        </div>
      </div>
    </div>

    <!-- ── THRESHOLDS ────────────────────────────────────────── -->
    <!--
      KEY CONCEPT: Decision Thresholds
      After computing the final score (0-100), the engine decides:
        score >= threshold_approve  →  APPROVE
        score >= threshold_hold     →  HOLD (further review)
        score <  threshold_hold     →  REJECT
      Students choose these thresholds — a strict lender sets high thresholds,
      a lenient lender sets lower ones.
    -->
    <div class="card">
      <div class="card-title">🎯 Decision Thresholds</div>
      <div style="font-size:.83rem;color:var(--muted);margin-bottom:14px;line-height:1.7">
        Score ≥ Approve → <strong style="color:#22c55e">Approve</strong> &nbsp;|&nbsp;
        Score ≥ Hold → <strong style="color:var(--amber)">Hold</strong> &nbsp;|&nbsp;
        Below Hold → <strong style="color:var(--red)">Reject</strong>
      </div>
      <div class="thresh-row">
        <div class="thresh-card">
          <div class="thresh-label">✅ Approve if score ≥</div>
          <input type="number" class="thresh-input" name="threshold_approve" min="1" max="99"
            value="<?= $engine['threshold_approve'] ?? 70 ?>" oninput="checkThresh()">
          <div class="thresh-note" style="color:#22c55e">Strong creditworthiness</div>
        </div>
        <div class="thresh-card">
          <div class="thresh-label">⏸ Hold if score ≥</div>
          <input type="number" class="thresh-input" name="threshold_hold" min="1" max="99"
            value="<?= $engine['threshold_hold'] ?? 50 ?>" oninput="checkThresh()">
          <div class="thresh-note" style="color:var(--amber)">Needs further review</div>
        </div>
      </div>
      <div id="thresh-warn" style="display:none;margin-top:10px;font-size:.82rem;color:var(--red)">
        ⚠️ Hold threshold must be lower than Approve threshold.
      </div>
    </div>

    <button type="submit" class="btn btn-primary" id="save-btn">
      <?= $engine ? '💾 Save Changes' : '🚀 Publish Engine' ?>
    </button>
  </form>

  <!-- ── PUBLISHED URL ─────────────────────────────────────── -->
  <?php if($engine): ?>
  <div class="pub-box">
    <div class="pub-title">🟢 Your Engine is Live</div>
    <div class="pub-url"><?= htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $public_url) ?></div>
    <div style="font-size:.82rem;color:#888;line-height:1.7">
      Share this URL on LinkedIn or Instagram.<br>
      Anyone who visits it can submit their financial profile and your engine will score them instantly.
    </div>
    <a href="<?= $public_url ?>" target="_blank"
      style="display:inline-flex;align-items:center;gap:6px;margin-top:12px;
      background:linear-gradient(135deg,var(--orange),#c2410c);color:#fff;
      font-family:'Syne',sans-serif;font-weight:700;font-size:.86rem;
      padding:10px 22px;border-radius:8px;text-decoration:none">
      🔗 Open Public Page →
    </a>
  </div>
  <?php endif; ?>

  <!-- ── CODE EXPLANATION ──────────────────────────────────── -->
  <div class="code-note">
    <strong>📌 How this works (for developers):</strong><br>
    1. Student fills this form → POST to build.php → PHP validates weightages sum = 100<br>
    2. Engine config saved to <strong>$_SESSION['engine']</strong> (in production: MySQL database)<br>
    3. A public URL is generated with the company slug<br>
    4. Public visits <strong>apply.php?slug=xxx</strong> → fills financial form → PHP calculates score<br>
    5. Score = Σ (factor_raw_score × weight / 100) — all in PHP backend, no JavaScript scoring<br>
    6. Decision made by comparing score to thresholds → result shown to applicant
  </div>
</div>

<script>
const W_KEYS = ['w_income','w_dscr','w_debt','w_employment','w_repayment'];

function syncW(key, val) {
  val = Math.max(0, Math.min(100, parseInt(val) || 0));
  document.getElementById('sl-'+key).value = val;
  document.getElementById('n-'+key).value  = val;
  updateTotal();
}

function updateTotal() {
  const total = W_KEYS.reduce((s,k) => s + (parseInt(document.getElementById('n-'+k)?.value) || 0), 0);
  const el  = document.getElementById('w-total');
  const msg = document.getElementById('w-msg');
  const btn = document.getElementById('save-btn');
  if (el) { el.textContent = total; el.className = 'total-num ' + (total === 100 ? 'ok' : 'warn'); }
  if (msg) msg.textContent = total === 100 ? '✅ Perfect — ready to publish.' : `Adjust sliders. Current: ${total}`;
  if (btn) btn.disabled = (total !== 100);
}

function checkThresh() {
  const ta = parseInt(document.getElementById('t-approve')?.value) || 70;
  const th = parseInt(document.getElementById('t-hold')?.value)    || 50;
  const w  = document.getElementById('thresh-warn');
  const b  = document.getElementById('save-btn');
  if (w) w.style.display = th >= ta ? 'block' : 'none';
  if (b) b.disabled = th >= ta;
}

updateTotal();
</script>
</body>
</html>
