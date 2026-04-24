<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Credit Score Engine Platform</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800;900&family=Inter:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d0d0d;--bg3:#1a1a1a;
  --orange:#ff6b00;--orange2:#ff8c00;--orange3:#ffb347;
  --muted:#777;--border:rgba(255,107,0,.18);
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:#f0f0f0;min-height:100vh;
  background-image:radial-gradient(ellipse 80% 40% at 50% -10%,rgba(255,107,0,.06),transparent)}

nav{display:flex;justify-content:space-between;align-items:center;padding:18px 48px;
  background:rgba(10,10,10,.95);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:10}
.logo{font-family:'Syne',sans-serif;font-weight:900;font-size:1.3rem;color:#fff}
.logo span{color:var(--orange2)}
.logo-sub{font-family:'Space Mono',monospace;font-size:.52rem;color:var(--muted);letter-spacing:.18em;display:block;margin-top:2px}

.hero{text-align:center;padding:100px 24px 80px;max-width:860px;margin:0 auto}
.eyebrow{font-family:'Space Mono',monospace;font-size:.65rem;letter-spacing:.22em;text-transform:uppercase;
  color:var(--orange3);background:rgba(255,107,0,.08);border:1px solid rgba(255,107,0,.2);
  display:inline-block;padding:5px 16px;border-radius:20px;margin-bottom:28px}
.hero h1{font-family:'Syne',sans-serif;font-weight:900;font-size:clamp(2.2rem,6vw,4rem);
  line-height:1.05;margin-bottom:20px;color:#fff}
.hero h1 span{color:var(--orange2)}
.hero-sub{font-size:1rem;color:rgba(255,255,255,.5);line-height:1.8;max-width:580px;margin:0 auto 40px}

.btn-primary{display:inline-flex;align-items:center;gap:8px;
  background:linear-gradient(135deg,var(--orange),#c2410c);color:#fff;
  font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;
  padding:15px 34px;border-radius:12px;text-decoration:none;
  box-shadow:0 6px 24px rgba(255,107,0,.4)}

.features{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;
  max-width:900px;margin:60px auto;padding:0 24px}
@media(max-width:700px){.features{grid-template-columns:1fr}}
.feat{background:var(--bg3);border:1px solid var(--border);border-radius:14px;padding:24px}
.feat-icon{font-size:1.8rem;margin-bottom:12px}
.feat-title{font-family:'Syne',sans-serif;font-weight:800;font-size:.95rem;color:#f0f0f0;margin-bottom:6px}
.feat-desc{font-size:.8rem;color:var(--muted);line-height:1.7}

footer{text-align:center;padding:32px;font-family:'Space Mono',monospace;
  font-size:.62rem;color:#333;border-top:1px solid rgba(255,255,255,.04);margin-top:40px}
</style>
</head>
<body>

<nav>
  <div>
    <div class="logo">Credit<span>Score</span>.Engine</div>
    <div class="logo-sub">Student FinTech Project</div>
  </div>
</nav>

<div class="hero">
  <div class="eyebrow">✦ FinTech Lab · Credit Scoring</div>
  <h1>Build. Test.<br><span>Own Your Model.</span></h1>
  <p class="hero-sub">
    Design your own credit scoring engine. Set the rules. Set the weightages.
    Let the public apply — and your PHP backend scores them in real time.
  </p>
  <a href="build.php" class="btn-primary">🏗 Build My Credit Engine →</a>
</div>

<div class="features">
  <div class="feat">
    <div class="feat-icon">⚖️</div>
    <div class="feat-title">You Set the Weightages</div>
    <div class="feat-desc">
      Assign importance to Income, DSCR, Debt Load, Employment and Repayment history.
      Your weights define your model. Total must equal 100.
    </div>
  </div>
  <div class="feat">
    <div class="feat-icon">🧮</div>
    <div class="feat-title">PHP Scores in Backend</div>
    <div class="feat-desc">
      No JavaScript scoring. The engine runs in PHP — applicants submit a form,
      the server calculates DSCR and applies your weightages, then returns the verdict.
    </div>
  </div>
  <div class="feat">
    <div class="feat-icon">📊</div>
    <div class="feat-title">Real Financial Inputs</div>
    <div class="feat-desc">
      Income, expenses, existing EMIs, loan requested, employment years,
      loans taken and repayments made. Real data → real score → real decision.
    </div>
  </div>
</div>

<footer>
  CREDIT SCORE ENGINE · STUDENT FINTECH PROJECT · BUILT WITH PHP + MYSQL
</footer>

</body>
</html>
