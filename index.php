<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UniResult - Academic Result Management System</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">

<style>

    :root {
        --navy: #003366;
        --navy-deep: #002347;
        --blue: #0066cc;
        --light-blue: #4da3ff;
        --bg: #f5f7fa;
        --text: #1e293b;
        --muted: #64748b;
        --border: #e5e7eb;
        --green: #0f9d68;
        --amber: #b8770a;
        --red: #c23636;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: "Inter", Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
        -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3 {
        font-family: "Sora", "Segoe UI", Arial, sans-serif;
    }

    a { color: inherit; }

    img, svg { display: block; max-width: 100%; }

    :focus-visible {
        outline: 3px solid var(--light-blue);
        outline-offset: 2px;
    }

    @media (prefers-reduced-motion: reduce) {
        * { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
    }

    .reveal {
        opacity: 0;
        transform: translateY(18px);
        transition: opacity .6s ease, transform .6s ease;
    }
    .reveal.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* NAVBAR */

    .navbar {
        position: sticky;
        top: 0;
        z-index: 50;
        background: var(--navy);
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 clamp(20px, 6vw, 7%);
        color: white;
        box-shadow: 0 2px 14px rgba(0, 20, 50, .18);
    }

    .logo {
        font-family: "Sora", sans-serif;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: .3px;
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .logo-mark {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--light-blue), var(--blue));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 800;
        color: var(--navy-deep);
        flex-shrink: 0;
    }

    .logo span { color: #7fbaff; }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 34px;
    }

    .nav-links a:not(.login-btn) {
        color: rgba(255,255,255,.85);
        text-decoration: none;
        font-weight: 500;
        font-size: 15px;
        position: relative;
        padding-bottom: 4px;
    }

    .nav-links a:not(.login-btn)::after {
        content: "";
        position: absolute;
        left: 0;
        bottom: 0;
        width: 0;
        height: 2px;
        background: var(--light-blue);
        transition: width .25s ease;
    }

    .nav-links a:not(.login-btn):hover {
        color: #fff;
    }
    .nav-links a:not(.login-btn):hover::after {
        width: 100%;
    }

    .login-btn {
        background: white;
        color: var(--navy) !important;
        text-decoration: none;
        padding: 9px 20px;
        border-radius: 7px;
        font-weight: 700 !important;
        font-size: 14px;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .login-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(0,0,0,.18);
    }

    /* HERO */

    .hero {
        max-width: 1280px;
        margin: 0 auto;
        min-height: calc(100vh - 72px);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 70px clamp(20px, 6vw, 7%);
        gap: 60px;
    }

    .eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-family: "IBM Plex Mono", monospace;
        font-size: 12.5px;
        font-weight: 600;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: var(--blue);
        background: #e8f2ff;
        padding: 6px 14px;
        border-radius: 20px;
        margin-bottom: 22px;
    }

    .eyebrow::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--blue);
    }

    .hero-content {
        max-width: 620px;
    }

    .hero-content h1 {
        font-size: clamp(34px, 4.4vw, 50px);
        line-height: 1.14;
        font-weight: 800;
        color: var(--navy);
        margin-bottom: 20px;
        letter-spacing: -.5px;
    }

    .hero-content h1 span {
        color: var(--blue);
    }

    .hero-content p {
        font-size: 17.5px;
        line-height: 1.7;
        color: var(--muted);
        margin-bottom: 32px;
        max-width: 540px;
    }

    .hero-buttons {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 28px;
    }

    .primary-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--navy);
        color: white;
        text-decoration: none;
        padding: 14px 26px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        transition: background .2s ease, transform .2s ease;
    }

    .primary-btn:hover {
        background: var(--blue);
        transform: translateY(-1px);
    }

    .secondary-btn {
        display: inline-flex;
        align-items: center;
        border: 1.5px solid #cbd5e1;
        color: var(--navy);
        text-decoration: none;
        padding: 12.5px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        transition: border-color .2s ease, background .2s ease;
    }

    .secondary-btn:hover {
        border-color: var(--navy);
        background: #eef4fb;
    }

    .trust-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .trust-pill {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--muted);
        background: white;
        border: 1px solid var(--border);
        padding: 6px 13px;
        border-radius: 20px;
    }

    /* TRANSCRIPT CARD — signature element */

    .transcript-wrap {
        flex-shrink: 0;
        position: relative;
    }

    .transcript-card {
        width: 400px;
        background: white;
        border-radius: 16px;
        padding: 26px 26px 22px;
        box-shadow: 0 20px 50px rgba(0, 30, 70, .16);
        border: 1px solid #edf1f6;
        position: relative;
        z-index: 2;
    }

    .transcript-card::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 16px;
        padding: 1px;
        background: linear-gradient(135deg, rgba(77,163,255,.5), transparent 40%);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
    }

    .t-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 16px;
        margin-bottom: 16px;
        border-bottom: 1px dashed var(--border);
    }

    .t-header-label {
        font-family: "IBM Plex Mono", monospace;
        font-size: 11px;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 4px;
    }

    .t-header-name {
        font-weight: 700;
        color: var(--navy);
        font-size: 15.5px;
    }

    .t-badge {
        background: #e8f2ff;
        color: var(--blue);
        font-size: 11px;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 6px;
        font-family: "IBM Plex Mono", monospace;
        white-space: nowrap;
    }

    .t-row {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 12px;
        align-items: center;
        padding: 9px 0;
    }

    .t-row + .t-row {
        border-top: 1px solid #f1f4f8;
    }

    .t-course-code {
        font-family: "IBM Plex Mono", monospace;
        font-size: 12px;
        color: var(--muted);
        display: block;
        margin-bottom: 2px;
    }

    .t-course-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--text);
    }

    .t-score {
        font-family: "IBM Plex Mono", monospace;
        font-size: 13.5px;
        color: var(--muted);
        font-weight: 500;
    }

    .t-grade {
        font-family: "IBM Plex Mono", monospace;
        font-size: 12.5px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 6px;
        text-align: center;
        min-width: 38px;
    }

    .grade-a { background: #e5f7ee; color: var(--green); }
    .grade-b { background: #eef4fb; color: var(--blue); }
    .grade-c { background: #fdf3e0; color: var(--amber); }

    .t-footer {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px dashed var(--border);
        display: flex;
        justify-content: space-between;
    }

    .t-stat-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: var(--muted);
        margin-bottom: 3px;
    }

    .t-stat-value {
        font-family: "IBM Plex Mono", monospace;
        font-weight: 700;
        font-size: 18px;
        color: var(--navy);
    }

    .floating-chip {
        position: absolute;
        background: white;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px 14px;
        box-shadow: 0 10px 24px rgba(0,30,70,.12);
        font-size: 12.5px;
        font-weight: 600;
        color: var(--navy);
        display: flex;
        align-items: center;
        gap: 8px;
        z-index: 3;
    }

    .chip-1 {
        top: -18px;
        right: -22px;
    }

    .chip-1 .dot { background: var(--green); }

    .chip-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--green);
    }

    /* SECTION SHARED */

    .section-head {
        text-align: center;
        max-width: 620px;
        margin: 0 auto 46px;
    }

    .section-eyebrow {
        font-family: "IBM Plex Mono", monospace;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 1.3px;
        text-transform: uppercase;
        color: var(--blue);
        margin-bottom: 10px;
    }

    .section-head h2 {
        color: var(--navy);
        font-size: clamp(26px, 3vw, 34px);
        font-weight: 800;
        margin-bottom: 12px;
        letter-spacing: -.4px;
    }

    .section-head p {
        color: var(--muted);
        font-size: 16px;
        line-height: 1.6;
    }

    /* FEATURES */

    .features {
        background: white;
        padding: 90px clamp(20px, 6vw, 7%);
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
    }

    .feature-grid {
        max-width: 1180px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }

    .feature-card {
        padding: 32px 28px;
        background: var(--bg);
        border-radius: 14px;
        text-align: left;
        border-top: 3px solid var(--blue);
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .feature-card:nth-child(2) { border-top-color: var(--light-blue); }
    .feature-card:nth-child(3) { border-top-color: var(--navy); }

    .feature-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 30px rgba(0,30,70,.08);
    }

    .feature-icon {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-bottom: 18px;
        box-shadow: 0 2px 8px rgba(0,30,70,.08);
    }

    .feature-card h3 {
        color: var(--navy);
        font-size: 18px;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .feature-card p {
        color: var(--muted);
        line-height: 1.6;
        font-size: 14.5px;
    }

    /* HOW IT WORKS */

    .how {
        padding: 90px clamp(20px, 6vw, 7%);
        max-width: 1180px;
        margin: 0 auto;
    }

    .steps {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        counter-reset: step;
        position: relative;
    }

    .step {
        position: relative;
        padding-top: 8px;
    }

    .step-number {
        font-family: "Sora", sans-serif;
        font-size: 42px;
        font-weight: 800;
        color: #dbe6f4;
        line-height: 1;
        margin-bottom: 14px;
    }

    .step h3 {
        color: var(--navy);
        font-size: 17px;
        margin-bottom: 8px;
        font-weight: 700;
    }

    .step p {
        color: var(--muted);
        font-size: 14.5px;
        line-height: 1.6;
    }

    .step:not(:last-child)::after {
        content: "";
        position: absolute;
        top: 24px;
        left: calc(100% + 15px);
        width: calc(30px - 15px);
        height: 1px;
        background: var(--border);
        display: none;
    }

    @media (min-width: 851px) {
        .step:not(:last-child)::after { display: block; }
    }

    /* CTA BAND */

    .cta-band {
        background: linear-gradient(120deg, var(--navy-deep), var(--navy) 60%, #01438c);
        color: white;
        padding: 64px clamp(20px, 6vw, 7%);
        text-align: center;
    }

    .cta-band h2 {
        font-size: clamp(24px, 3vw, 30px);
        margin-bottom: 12px;
        font-weight: 800;
    }

    .cta-band p {
        color: rgba(255,255,255,.75);
        margin-bottom: 28px;
        font-size: 16px;
    }

    .cta-band .primary-btn {
        background: white;
        color: var(--navy);
    }

    .cta-band .primary-btn:hover {
        background: #e8f2ff;
    }

    /* FOOTER */

    footer {
        background: var(--navy-deep);
        color: rgba(255,255,255,.7);
        padding: 60px clamp(20px, 6vw, 7%) 26px;
    }

    .footer-grid {
        max-width: 1180px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 40px;
        padding-bottom: 36px;
        border-bottom: 1px solid rgba(255,255,255,.1);
    }

    .footer-brand .logo { color: white; margin-bottom: 14px; }
    .footer-brand p {
        font-size: 14px;
        line-height: 1.7;
        max-width: 320px;
        color: rgba(255,255,255,.55);
    }

    .footer-col h4 {
        color: white;
        font-size: 14px;
        margin-bottom: 16px;
        font-weight: 700;
    }

    .footer-col a {
        display: block;
        text-decoration: none;
        color: rgba(255,255,255,.6);
        font-size: 14px;
        margin-bottom: 11px;
        transition: color .2s ease;
    }

    .footer-col a:hover { color: white; }

    .footer-bottom {
        max-width: 1180px;
        margin: 22px auto 0;
        font-size: 13px;
        color: rgba(255,255,255,.45);
        text-align: center;
    }

    /* MOBILE */

    @media (max-width: 850px) {

        .hero {
            flex-direction: column;
            text-align: center;
            padding-top: 46px;
            gap: 46px;
        }

        .hero-content { max-width: 100%; }
        .hero-content p { margin-left: auto; margin-right: auto; }

        .hero-buttons { justify-content: center; }
        .trust-row { justify-content: center; }

        .transcript-card { width: 100%; max-width: 420px; }
        .chip-1 { right: 6px; top: -14px; }

        .feature-grid { grid-template-columns: 1fr; }
        .steps { grid-template-columns: 1fr; gap: 34px; }

        .footer-grid { grid-template-columns: 1fr; gap: 30px; text-align: left; }
        .footer-brand p { max-width: 100%; }

        .nav-links a:not(.login-btn) { display: none; }
    }

    @media (max-width: 420px) {
        .transcript-card { padding: 20px 18px 18px; }
        .t-course-name { font-size: 13px; }
    }

</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
<?php include __DIR__ . '/brand.html'; ?>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="#features">Features</a>
        <a href="#how">How it works</a>
        <a href="login.php" class="login-btn">Login</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">

    <div class="hero-content">
        <span class="eyebrow">Academic result management</span>

        <h1>
            Academic results, <span>organized</span> and always within reach.
        </h1>

        <p>
            COT-Result brings students, lecturers and administrators onto one secure
            platform to manage courses, continuous assessment, examinations and
            final grades — from enrollment to transcript.
        </p>

        <div class="hero-buttons">
            <a href="login.php" class="primary-btn">Login to COT-Result →</a>
            <a href="#how" class="secondary-btn">See how it works</a>
        </div>

        <div class="trust-row">
            <span class="trust-pill">🔒 Role-based access</span>
            <span class="trust-pill">📊 Real-time results</span>
            <span class="trust-pill">✓ Approval workflow built in</span>
        </div>
    </div>

    <div class="transcript-wrap reveal">

        <div class="floating-chip chip-1">
            <span class="chip-dot"></span>
            Results published
        </div>

        <div class="transcript-card">

            <div class="t-header">
                <div>
                    <div class="t-header-label">Student transcript</div>
                    <div class="t-header-name">BEKONGCHO SIMPLICE · 200 Level</div>
                </div>
                <span class="t-badge">SEM 2 · 25/26</span>
            </div>

            <div class="t-row">
                <div>
                    <span class="t-course-code">CSC301</span>
                    <span class="t-course-name">Data Structures</span>
                </div>
                <span class="t-score">78 / 100</span>
                <span class="t-grade grade-a">A</span>
            </div>

            <div class="t-row">
                <div>
                    <span class="t-course-code">MTH204</span>
                    <span class="t-course-name">Linear Algebra</span>
                </div>
                <span class="t-score">65 / 100</span>
                <span class="t-grade grade-b">B+</span>
            </div>

            <div class="t-row">
                <div>
                    <span class="t-course-code">PHY102</span>
                    <span class="t-course-name">Applied Physics</span>
                </div>
                <span class="t-score">58 / 100</span>
                <span class="t-grade grade-c">C+</span>
            </div>

            <div class="t-footer">
                <div>
                    <div class="t-stat-label">Semester GPA</div>
                    <div class="t-stat-value">3.62</div>
                </div>
                <div>
                    <div class="t-stat-label">Cumulative CGPA</div>
                    <div class="t-stat-value">3.48</div>
                </div>
            </div>

        </div>
    </div>

</section>

<!-- FEATURES -->
<section class="features" id="features">

    <div class="section-head reveal">
        <div class="section-eyebrow">Built for every role</div>
        <h2>One platform, three perspectives</h2>
        <p>Everyone who touches a result — from the student sitting the exam to the admin who signs off on it — gets a workspace suited to what they need to do.</p>
    </div>

    <div class="feature-grid">

        <div class="feature-card reveal">
            <div class="feature-icon">🎓</div>
            <h3>Students</h3>
            <p>View results, grades, GPA, CGPA and full academic history as soon as they're published — no more waiting on a notice board.</p>
        </div>

        <div class="feature-card reveal">
            <div class="feature-icon">📝</div>
            <h3>Lecturers</h3>
            <p>Manage assigned courses and enter continuous assessment and examination marks, with validation before submission.</p>
        </div>

        <div class="feature-card reveal">
            <div class="feature-icon">🛡️</div>
            <h3>Administrators</h3>
            <p>Manage students, lecturers, courses and academic sessions, and review results before they're approved for release.</p>
        </div>

    </div>

</section>

<!-- HOW IT WORKS -->
<section class="how" id="how">

    <div class="section-head reveal">
        <div class="section-eyebrow">The process</div>
        <h2>From mark to transcript in three steps</h2>
        <p>A result moves through the same clear path every time, so nothing gets published without the right eyes on it first.</p>
    </div>

    <div class="steps">

        <div class="step reveal">
            <div class="step-number">01</div>
            <h3>Sign in with your role</h3>
            <p>Students, lecturers and administrators log in with role-based credentials to reach their own dashboard.</p>
        </div>

        <div class="step reveal">
            <div class="step-number">02</div>
            <h3>Marks are entered</h3>
            <p>Lecturers submit continuous assessment and exam scores for their courses while students track progress in real time.</p>
        </div>

        <div class="step reveal">
            <div class="step-number">03</div>
            <h3>Results are approved & published</h3>
            <p>Administrators review and approve results before they're released to student transcripts.</p>
        </div>

    </div>

</section>

<!-- CTA BAND -->
<section class="cta-band reveal">
    <h2>Ready to check or manage your results?</h2>
    <p>Log in with your student, lecturer or administrator account to get started.</p>
    <a href="login.php" class="primary-btn">Login to COT-Result →</a>
</section>

<!-- FOOTER -->
<footer>

    <div class="footer-grid">

        <div class="footer-brand">
           <?php include __DIR__ . '/brand.html'; ?>
            <p>An academic result management system built to make results clear, secure and easy to reach for every student, lecturer and administrator.</p>
        </div>

        <div class="footer-col">
            <h4>Platform</h4>
            <a href="index.php">Home</a>
            <a href="#features">Features</a>
            <a href="#how">How it works</a>
            <a href="login.php">Login</a>
        </div>

        <div class="footer-col">
            <h4>Support</h4>
            <a href="#">Help center</a>
            <a href="#">Contact us</a>
            <a href="#">Privacy policy</a>
        </div>

    </div>

    <p class="footer-bottom">&copy; <span id="year"></span> COT-Result. </p>

</footer>

<script>
    document.getElementById('year').textContent = new Date().getFullYear();

    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    } else {
        document.querySelectorAll('.reveal').forEach(el => el.classList.add('is-visible'));
    }
    document.getElementById('adminTrigger').addEventListener('dblclick',
        function() {
            window.location.href='admin/login.php';
        }
    );
</script>

</body>
</html>