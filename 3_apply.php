<?php
/*
 * ============================================================
 *  apply.php — Public Credit Application Form
 *  
 *  STUDENT REFERENCE CODE
 *  This is a simplified standalone version — no database needed.
 *  Engine config is read from $_SESSION (set in build.php).
 *  
 *  In a production system (like myfintechs.com) you would:
 *  1. Look up the engine config from MySQL using the slug in the URL
 *  2. Save each application result to the database
 *  3. Show the student a live dashboard of all applicants
 *  
 *  KEY CONCEPT — The Scoring Engine:
 *  This file contains the complete PHP credit scoring algorithm.
 *  Each financial factor is converted to a raw score (0-100),
 *  then multiplied by the student's weightage, then summed.
 *  The final score is compared to thresholds → decision made.
 *  ALL scoring happens in PHP on the server. No JavaScript math.
 * ============================================================
 */

session_start();

// ── LOAD ENGINE CONFIG ────────────────────────────────────────────
// In production: SELECT * FROM credit_engines WHERE public_slug = $_GET['slug']
$engine = $_SESSION['engine'] ?? null;

if (!$engine) {
    // No engine built yet — redirect to builder
    header('Location: build.php');
    exit;
}

// ── HELPER: HTML escape ───────────────────────────────────────────
function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// ── HELPER: Format currency ───────────────────────────────────────
function fmt($n) { return '€' . number_format((float)$n, 0, '.', ','); }


// ════════════════════════════════════════════════════════════════
//  THE SCORING ENGINE — Core PHP Algorithm
//  This function is the heart of the project.
//  Students can modify the factor scoring logic below.
// ════════════════════════════════════════════════════════════════
function score_applicant(array $engine, array $input): array {

    // ── Extract inputs ────────────────────────────────────────────
    $income      = max(0, (float)$input['monthly_income']);
    $expenses    = max(0, (float)$input['monthly_expenses']);
    $emis        = max(0, (float)$input['existing_emis']);
    $loan        = max(0, (float)$input['loan_requested']);
    $emp_years   = max(0, (float)$input['employment_years']);
    $loans_taken = max(0, (int)$input['loans_taken']);
    $repaid      = max(0, (int)$input['repayments_made']);

    // ── Derived values ────────────────────────────────────────────
    $net_income = $income - $expenses;

    // Estimated new EMI: loan repaid over 60 months at ~10% annual interest
    // Formula: Principal × (1 + interest_rate) / months
    $new_emi = $loan > 0 ? ($loan * 1.10) / 60 : 0;

    $total_debt_service = $emis + $new_emi;

    // ── DSCR (Debt Service Coverage Ratio) ────────────────────────
    // DSCR = Net Income ÷ Total Debt Obligations
    // DSCR > 1.5 = strong   (income comfortably covers debt)
    // DSCR 1.0–1.5 = adequate
    // DSCR < 1.0 = cannot service debt → high risk of default
    if ($total_debt_service > 0) {
        $dscr = $net_income / $total_debt_service;
    } else {
        $dscr = $net_income > 0 ? 5.0 : 0.0; // No existing debt = excellent
    }

    // ════════════════════════════════════════════════════════════
    //  RAW FACTOR SCORES (0 to 100 each)
    //  Students: this is where you can change the scoring logic.
    //  Each factor must return a value between 0 and 100.
    // ════════════════════════════════════════════════════════════

    // FACTOR 1: Income Stability
    // Measures what % of gross income remains after expenses
    $income_ratio = $income > 0 ? $net_income / $income : 0;
    $s_income = max(0, min(100, (int)round($income_ratio * 100)));

    // FACTOR 2: DSCR Score
    // Converts DSCR ratio into a 0-100 score
    if      ($dscr >= 2.5) $s_dscr = 100;
    elseif  ($dscr >= 2.0) $s_dscr = 90;
    elseif  ($dscr >= 1.5) $s_dscr = 78;
    elseif  ($dscr >= 1.2) $s_dscr = 62;
    elseif  ($dscr >= 1.0) $s_dscr = 42;
    elseif  ($dscr >= 0.7) $s_dscr = 22;
    else                   $s_dscr = max(0, (int)round($dscr * 25));

    // FACTOR 3: Existing Debt Load
    // EMI burden as % of income — lower existing debt = higher score
    $debt_ratio = $income > 0 ? $emis / $income : 1;
    $s_debt = max(0, min(100, (int)round((1 - $debt_ratio) * 100)));

    // FACTOR 4: Employment Stability
    // More years = more stable income source
    if      ($emp_years >= 10) $s_employment = 100;
    elseif  ($emp_years >= 5)  $s_employment = (int)round(70 + ($emp_years - 5) * 6);
    elseif  ($emp_years >= 2)  $s_employment = (int)round(35 + ($emp_years - 2) * 11.7);
    elseif  ($emp_years >= 1)  $s_employment = 20;
    else                       $s_employment = 8;

    // FACTOR 5: Repayment Track Record
    // How many of previous loans were fully repaid?
    if ($loans_taken === 0) {
        $s_repayment = 55; // No history → neutral score
    } else {
        $ratio = min(1.0, $repaid / $loans_taken);
        $s_repayment = (int)round($ratio * 100);
    }

    // ════════════════════════════════════════════════════════════
    //  WEIGHTED TOTAL SCORE
    //  Each raw factor score (0-100) is multiplied by its weight
    //  divided by 100, then all contributions are summed.
    //
    //  Example:
    //    Income raw = 75, weight = 20  → contributes 75 × 20/100 = 15 pts
    //    DSCR raw   = 90, weight = 30  → contributes 90 × 30/100 = 27 pts
    //    etc.
    //  Total score = sum of all contributions (max 100)
    // ════════════════════════════════════════════════════════════
    $score = (int)round(
        $s_income     * $engine['w_income']     / 100 +
        $s_dscr       * $engine['w_dscr']       / 100 +
        $s_debt       * $engine['w_debt']       / 100 +
        $s_employment * $engine['w_employment'] / 100 +
        $s_repayment  * $engine['w_repayment']  / 100
    );
    $score = max(0, min(100, $score));

    // ── DECISION ─────────────────────────────────────────────────
    if      ($score >= $engine['threshold_approve']) $decision = 'Approve';
    elseif  ($score >= $engine['threshold_hold'])    $decision = 'Hold';
    else                                              $decision = 'Reject';

    return [
        'score'         => $score,
        'decision'      => $decision,
        'dscr'          => round($dscr, 3),
        'new_emi'       => round($new_emi, 2),
        'net_income'    => $net_income,
        'score_income'  => $s_income,
        'score_dscr'    => $s_dscr,
        'score_debt'    => $s_debt,
        'score_employment' => $s_employment,
        'score_repayment'  => $s_repayment,
    ];
}


// ── PROCESS FORM SUBMISSION ───────────────────────────────────────
$result    = null;
$applicant = null;
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $applicant = [
        'name'             => trim($_POST['applicant_name']  ?? ''),
        'monthly_income'   => (float)($_POST['monthly_income']   ?? 0),
        'monthly_expenses' => (float)($_POST['monthly_expenses'] ?? 0),
        'existing_emis'    => (float)($_POST['existing_emis']    ?? 0),
        'loan_requested'   => (float)($_POST['loan_requested']   ?? 0),
        'employment_years' => (float)($_POST['employment_years'] ?? 0),
        'loans_taken'      => (int)  ($_POST['loans_taken']      ?? 0),
        'repayments_made'  => (int)  ($_POST['repayments_made']  ?? 0),
    ];

    // Validate inputs
    if (strlen($applicant['name']) < 2)
        $errors[] = "Please enter your name.";
    if ($applicant['monthly_income'] <= 0)
        $errors[] = "Monthly income must be greater than zero.";
    if ($applicant['monthly_expenses'] < 0)
        $errors[] = "Monthly expenses cannot be negative.";
    if ($applicant['loan_requested'] <= 0)
        $errors[] = "Please enter the loan amount you are requesting.";
    if ($applicant['repayments_made'] > $applicant['loans_taken'])
        $errors[] = "Repayments made cannot exceed total loans taken.";

    if (empty($errors)) {
        // ── RUN THE SCORING ENGINE ────────────────────────────────
        $result = score_applicant($engine, $applicant);
        // In production: save $result to engine_applications table in MySQL
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($engine['company_name']) ?> · Credit Score Check</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=Inter:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d0d0d;--bg2:#111;--bg3:#1a1a1a;
  --orange:#ff6b00;--orange2:#ff8c00;--orange3:#ffb347;
  --red:#ff3b3b;--green:#22c55e;--amber:#f59e0b;
  --muted:#666;--border:rgba(255,255,255,.07);
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:#f0f0f0;min-height:100vh;
  background-image:radial-gradient(ellipse 80% 40% at 50% -5%,rgba(255,107,0,.06),transparent)}

/* COMPANY HEADER */
.header{padding:36px 24px 0;max-width:720px;margin:0 auto;text-align:center}
.co-badge{font-family:'Space Mono',monospace;font-size:.6rem;letter-spacing:.18em;text-transform:uppercase;
  color:var(--orange3);background:rgba(255,107,0,.08);border:1px solid rgba(255,107,0,.2);
  display:inline-block;padding:4px 14px;border-radius:20px;margin-bottom:16px}
.co-name{font-family:'Syne',sans-serif;font-weight:900;font-size:clamp(2rem,6vw,3.2rem);
  color:#fff;line-height:1.05;margin-bottom:6px}
.co-name span{color:var(--orange2)}
.co-tagline{font-size:.92rem;color:var(--muted);margin-bottom:6px}
.co-credit{font-family:'Space Mono',monospace;font-size:.6rem;color:#2a2a2a;margin-bottom:32px}

/* FORM */
.wrap{max-width:680px;margin:0 auto;padding:0 24px 80px}
.form-card{background:var(--bg3);border:1px solid var(--border);border-radius:16px;padding:26px;margin-bottom:16px}
.section-title{font-family:'Syne',sans-serif;font-weight:800;font-size:.9rem;
  color:#f0f0f0;margin-bottom:16px;padding-bottom:10px;
  border-bottom:1px solid rgba(255,107,0,.1);display:flex;align-items:center;gap:8px}
.fg{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.fg label{font-size:.68rem;color:var(--muted);letter-spacing:.1em;text-transform:uppercase}
.fg input{background:var(--bg2);border:1.5px solid rgba(255,255,255,.08);
  border-radius:8px;color:#f0f0f0;padding:12px 14px;font-size:.92rem;outline:none;transition:.2s}
.fg input:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,107,0,.1)}
.fg .note{font-size:.72rem;color:#444;line-height:1.5;margin-top:2px}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:520px){.two-col{grid-template-columns:1fr}}

.submit-btn{width:100%;padding:15px;
  background:linear-gradient(135deg,var(--orange),#c2410c);color:#fff;
  border:none;border-radius:10px;font-family:'Syne',sans-serif;
  font-weight:800;font-size:1rem;cursor:pointer;
  box-shadow:0 6px 20px rgba(255,107,0,.4)}

.errors{background:rgba(255,59,59,.08);border:1px solid rgba(255,59,59,.3);
  border-radius:8px;padding:12px 16px;margin-bottom:16px;
  font-size:.84rem;color:#ff6b6b;line-height:1.8}

/* RESULT */
.result-wrap{max-width:680px;margin:0 auto;padding:0 24px 80px}
.score-circle{width:150px;height:150px;border-radius:50%;
  margin:0 auto 20px;display:flex;flex-direction:column;
  align-items:center;justify-content:center;border:4px solid}
.sc-a{background:rgba(34,197,94,.08);border-color:#22c55e;box-shadow:0 0 40px rgba(34,197,94,.12)}
.sc-h{background:rgba(245,158,11,.08);border-color:var(--amber);box-shadow:0 0 40px rgba(245,158,11,.12)}
.sc-r{background:rgba(255,59,59,.08);border-color:var(--red);box-shadow:0 0 40px rgba(255,59,59,.12)}
.score-num{font-family:'Syne',sans-serif;font-weight:900;font-size:3rem;line-height:1}
.score-sub{font-family:'Space Mono',monospace;font-size:.6rem;color:var(--muted);margin-top:4px}

.decision{font-family:'Syne',sans-serif;font-weight:900;font-size:1.5rem;text-align:center;margin-bottom:8px}
.d-a{color:#22c55e}.d-h{color:var(--amber)}.d-r{color:var(--red)}
.decision-desc{font-size:.86rem;color:var(--muted);text-align:center;
  max-width:420px;margin:0 auto 28px;line-height:1.7}

.breakdown{background:var(--bg3);border:1px solid var(--border);border-radius:14px;padding:22px;margin-bottom:16px}
.bd-title{font-family:'Syne',sans-serif;font-weight:800;font-size:.88rem;color:#f0f0f0;margin-bottom:16px}
.factor-row{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.f-label{font-size:.78rem;color:var(--muted);width:165px;flex-shrink:0;line-height:1.4}
.f-bar{flex:1;height:6px;background:rgba(255,255,255,.05);border-radius:99px;overflow:hidden}
.f-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--orange),var(--orange2))}
.f-score{font-family:'Space Mono',monospace;font-size:.72rem;color:var(--orange2);width:34px;text-align:right}
.f-pts{font-family:'Space Mono',monospace;font-size:.68rem;color:#444;width:44px;text-align:right}

.summary{background:var(--bg2);border-radius:10px;padding:16px;margin:12px 0}
.s-row{display:flex;justify-content:space-between;padding:5px 0;
  border-bottom:1px solid rgba(255,255,255,.03);font-size:.8rem}
.s-row:last-child{border:none}
.s-key{color:var(--muted)}
.s-val{font-family:'Space Mono',monospace;color:#f0f0f0;font-size:.78rem}

.disclaimer{background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);
  border-radius:10px;padding:14px 16px;font-size:.76rem;color:#333;
  line-height:1.8;text-align:center;margin-bottom:16px}

.try-again{width:100%;padding:13px;background:transparent;color:var(--orange3);
  border:1.5px solid rgba(255,107,0,.3);border-radius:10px;
  font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem;cursor:pointer}

.footer{text-align:center;padding:20px;font-family:'Space Mono',monospace;
  font-size:.6rem;color:#222;letter-spacing:.08em}
</style>
</head>
<body>

<!-- COMPANY HEADER — branded with student's company name -->
<div class="header">
  <div class="co-badge">✦ Credit Score Simulator</div>
  <div class="co-name"><?= h($engine['company_name']) ?><span style="color:var(--muted);font-size:1.4rem"> AI</span></div>
  <?php if($engine['tagline']): ?>
  <div class="co-tagline"><?= h($engine['tagline']) ?></div>
  <?php endif; ?>
  <div class="co-credit">A student-built credit scoring engine · myfintechs.com</div>
</div>

<?php if($result !== null): ?>
<!-- ════════════════════════════════════
     RESULT SCREEN
     Show after form submission
════════════════════════════════════ -->
<?php
$dec = $result['decision'];
$sc_class = ['Approve'=>'sc-a','Hold'=>'sc-h','Reject'=>'sc-r'][$dec];
$dc_class = ['Approve'=>'d-a','Hold'=>'d-h','Reject'=>'d-r'][$dec];
$dec_color = ['Approve'=>'#22c55e','Hold'=>'var(--amber)','Reject'=>'var(--red)'][$dec];
$dec_texts = [
    'Approve' => ['✅ Approved', 'Your financial profile meets this engine\'s creditworthiness criteria. Your repayment capacity is sufficient for the requested loan.'],
    'Hold'    => ['⏸ Under Review', 'Your profile shows mixed signals. A real lender would request additional documentation before proceeding.'],
    'Reject'  => ['❌ Not Approved', 'Your repayment capacity or credit profile does not meet this engine\'s thresholds at this time.'],
];
?>
<div class="result-wrap">
  <div style="text-align:center;padding:32px 0 24px">
    <div style="font-family:'Syne',sans-serif;font-size:1.1rem;color:#fff;margin-bottom:4px">
      Result for <?= h($applicant['name']) ?>
    </div>

    <div class="score-circle <?= $sc_class ?>">
      <div class="score-num" style="color:<?= $dec_color ?>"><?= $result['score'] ?></div>
      <div class="score-sub">out of 100</div>
    </div>

    <div class="decision <?= $dc_class ?>"><?= $dec_texts[$dec][0] ?></div>
    <div class="decision-desc"><?= $dec_texts[$dec][1] ?></div>
  </div>

  <!-- FACTOR BREAKDOWN — shows how each factor contributed -->
  <div class="breakdown">
    <div class="bd-title">📊 How Your Score Was Calculated</div>

    <?php
    $factors = [
        ['💰 Income Stability',      $result['score_income'],     $engine['w_income'],     'Net income ÷ gross income'],
        ['⚖️ DSCR',                  $result['score_dscr'],       $engine['w_dscr'],       'DSCR = '.number_format($result['dscr'],2)],
        ['💳 Existing Debt Load',    $result['score_debt'],       $engine['w_debt'],       'EMI burden vs income'],
        ['🏢 Employment Stability',  $result['score_employment'], $engine['w_employment'], $applicant['employment_years'].' years employed'],
        ['📈 Repayment Track Record',$result['score_repayment'],  $engine['w_repayment'],  $applicant['repayments_made'].' of '.$applicant['loans_taken'].' loans repaid'],
    ];
    foreach($factors as [$label, $raw, $weight, $note]):
        $contribution = round($raw * $weight / 100);
    ?>
    <div class="factor-row">
      <div class="f-label">
        <?= $label ?>
        <div style="font-size:.65rem;color:#333;margin-top:1px"><?= $note ?></div>
      </div>
      <div class="f-bar"><div class="f-fill" style="width:<?= $raw ?>%"></div></div>
      <div class="f-score"><?= $raw ?></div>
      <div class="f-pts">+<?= $contribution ?>pts</div>
    </div>
    <?php endforeach; ?>

    <!-- Final score total -->
    <div style="display:flex;justify-content:space-between;align-items:center;
      margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.05)">
      <span style="font-size:.8rem;color:var(--muted)">Final Score</span>
      <span style="font-family:'Syne',sans-serif;font-weight:900;font-size:1.8rem;color:<?= $dec_color ?>">
        <?= $result['score'] ?> / 100
      </span>
    </div>
    <div style="font-family:'Space Mono',monospace;font-size:.7rem;color:var(--muted);margin-top:6px">
      Approve ≥ <?= $engine['threshold_approve'] ?> &nbsp;|&nbsp;
      Hold ≥ <?= $engine['threshold_hold'] ?> &nbsp;|&nbsp;
      Reject &lt; <?= $engine['threshold_hold'] ?>
    </div>
  </div>

  <!-- SUBMITTED DATA SUMMARY -->
  <div class="breakdown">
    <div class="bd-title">📋 Your Submitted Figures</div>
    <div class="summary">
      <?php
      $rows = [
        ['Monthly Income',            fmt($applicant['monthly_income'])],
        ['Monthly Expenses',          fmt($applicant['monthly_expenses'])],
        ['Existing Loan EMIs',        fmt($applicant['existing_emis']).' / mo'],
        ['Loan Amount Requested',     fmt($applicant['loan_requested'])],
        ['Estimated New EMI',         fmt($result['new_emi']).' / mo'],
        ['Net Income After Expenses', fmt($result['net_income']).' / mo'],
        ['Calculated DSCR',           number_format($result['dscr'],2)],
        ['Employment',                $applicant['employment_years'].' years'],
        ['Loans Taken / Repaid',      $applicant['loans_taken'].' / '.$applicant['repayments_made']],
      ];
      foreach($rows as [$k,$v]): ?>
      <div class="s-row"><span class="s-key"><?= $k ?></span><span class="s-val"><?= $v ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="disclaimer">
    ⚠️ <strong style="color:#444">Simulation Disclaimer</strong><br>
    This credit assessment is produced by a student-built engine created as part of a FinTech education programme.
    It is a <strong style="color:#444">pure simulator</strong> and does not constitute real financial advice,
    a real credit check, or a lending decision. No personal data is retained after you leave this page.
  </div>

  <button class="try-again" onclick="location.href=location.href">← Try Another Scenario</button>
</div>

<?php else: ?>
<!-- ════════════════════════════════════
     APPLICATION FORM
════════════════════════════════════ -->
<div class="wrap">
  <?php if(!empty($errors)): ?>
  <div class="errors">
    <?php foreach($errors as $e): ?>⚠️ <?= h($e) ?><br><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST">

    <div class="form-card">
      <div class="section-title">👤 About You</div>
      <div class="fg">
        <label>Your Name *</label>
        <input type="text" name="applicant_name" required maxlength="100"
          placeholder="Enter your full name"
          value="<?= h($applicant['name'] ?? '') ?>">
      </div>
    </div>

    <div class="form-card">
      <div class="section-title">💰 Monthly Finances</div>
      <div class="two-col">
        <div class="fg">
          <label>Monthly Income (€) *</label>
          <input type="number" name="monthly_income" required min="0" step="100"
            placeholder="e.g. 3500" value="<?= h($applicant['monthly_income'] ?? '') ?>">
          <div class="note">Total gross monthly income</div>
        </div>
        <div class="fg">
          <label>Monthly Expenses (€) *</label>
          <input type="number" name="monthly_expenses" required min="0" step="100"
            placeholder="e.g. 1800" value="<?= h($applicant['monthly_expenses'] ?? '') ?>">
          <div class="note">Rent, food, utilities, transport</div>
        </div>
      </div>
      <div class="fg">
        <label>Existing Monthly Loan EMIs (€)</label>
        <input type="number" name="existing_emis" min="0" step="50"
          placeholder="0 if none" value="<?= h($applicant['existing_emis'] ?? '0') ?>">
        <div class="note">Total of all current loan repayments per month</div>
      </div>
    </div>

    <div class="form-card">
      <div class="section-title">🏦 Loan Request</div>
      <div class="fg">
        <label>Loan Amount Requested (€) *</label>
        <input type="number" name="loan_requested" required min="0" step="1000"
          placeholder="e.g. 25000" value="<?= h($applicant['loan_requested'] ?? '') ?>">
        <div class="note">Engine will estimate a 60-month repayment EMI at 10% interest</div>
      </div>
    </div>

    <div class="form-card">
      <div class="section-title">🏢 Employment & Credit History</div>
      <div class="fg">
        <label>Years in Employment *</label>
        <input type="number" name="employment_years" required min="0" step="0.5"
          placeholder="e.g. 4.5" value="<?= h($applicant['employment_years'] ?? '') ?>">
        <div class="note">Total continuous years in current or recent job</div>
      </div>
      <div class="two-col">
        <div class="fg">
          <label>Total Loans Ever Taken</label>
          <input type="number" name="loans_taken" min="0" step="1"
            placeholder="e.g. 2" value="<?= h($applicant['loans_taken'] ?? '0') ?>">
          <div class="note">All loans including credit cards, mortgages</div>
        </div>
        <div class="fg">
          <label>Loans Fully Repaid</label>
          <input type="number" name="repayments_made" min="0" step="1"
            placeholder="e.g. 2" value="<?= h($applicant['repayments_made'] ?? '0') ?>">
          <div class="note">How many of those loans are fully closed</div>
        </div>
      </div>
    </div>

    <div class="disclaimer">
      ⚠️ This is a student simulation. No data is stored. Not a real credit check.
    </div>

    <button type="submit" class="submit-btn">
      Check My Score with <?= h($engine['company_name']) ?> →
    </button>
  </form>
</div>
<?php endif; ?>

<div class="footer">
  POWERED BY <?= strtoupper(h($engine['company_name'])) ?> · STUDENT FINTECH SIMULATOR
</div>

</body>
</html>
