<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>About | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        * {
            -webkit-tap-highlight-color: transparent !important;
        }
        button, 
        a {
            -webkit-tap-highlight-color: transparent !important;
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent:       hsl(225, 40%, 55%);
            --accent-light: hsl(225, 70%, 95%);
            --accent-dark:  hsl(225, 35%, 38%);
            --bg:           #f4f6fb;
            --card:         #ffffff;
            --text:         #1a1f36;
            --muted:        #6b7280;
            --border:       #e5e7ef;
            --radius:       16px;
            --shadow:       0 4px 24px rgba(58, 86, 180, .10);
        }

        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        a { color: var(--accent); text-decoration: none; }

        /* ── NAV ── */
        .top-bar {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: .9rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .top-bar .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .top-bar .back-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .4rem .9rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: .83rem;
            font-weight: 600;
            color: var(--muted);
            transition: background .15s, color .15s;
        }
        .top-bar .back-btn:hover {
            background: var(--accent-light);
            color: var(--accent);
            border-color: var(--accent);
            text-decoration: none;
        }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent) 60%, hsl(225,50%,68%) 100%);
            color: #fff;
            text-align: center;
            padding: 5rem 2rem 4rem;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-tag {
            display: inline-block;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 99px;
            padding: .3rem 1rem;
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 1.2rem;
        }
        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: .8rem;
        }
        .hero p {
            font-size: 1.05rem;
            opacity: .85;
            max-width: 540px;
            margin: 0 auto;
        }

        /* ── SECTIONS ── */
        .section {
            max-width: 1000px;
            margin: 0 auto;
            padding: 4rem 1.5rem;
        }
        .section-label {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: .5rem;
        }
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.9rem;
            font-weight: 900;
            color: var(--text);
            margin-bottom: .5rem;
        }
        .section-sub {
            color: var(--muted);
            font-size: .95rem;
            margin-bottom: 2.5rem;
            max-width: 520px;
        }

        /* ── ADVISER CARD ── */
        .adviser-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem 2.2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 3.5rem;
            position: relative;
            overflow: hidden;
        }
        .adviser-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 5px;
            background: linear-gradient(to bottom, var(--accent), hsl(225,50%,68%));
            border-radius: 4px 0 0 4px;
        }
        .adviser-avatar {
            width: 90px; height: 90px;
            border-radius: 50%;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(58,86,180,.25);
            object-fit: cover;
            border: 3px solid var(--accent-light);
        }
        .adviser-body { flex: 1; min-width: 0; }
        .adviser-role {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: .25rem;
        }
        .adviser-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: .3rem;
        }
        .adviser-desc {
            font-size: .88rem;
            color: var(--muted);
            margin-bottom: .9rem;
        }
        .view-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1.1rem;
            background: var(--accent);
            color: #fff;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 600;
            transition: background .15s, transform .1s;
        }
        .view-btn:hover {
            background: var(--accent-dark);
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* ── TEAM GRID ── */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.2rem;
        }
        .team-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.6rem 1.4rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: transform .18s, box-shadow .18s;
        }
        .team-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 32px rgba(58,86,180,.13);
        }
        .team-avatar {
            width: 68px; height: 68px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            display: block;
            border: 3px solid var(--accent-light);
            box-shadow: 0 2px 10px rgba(58,86,180,.15);
        }
        .team-name {
            font-weight: 700;
            font-size: .97rem;
            color: var(--text);
            margin-bottom: .2rem;
        }
        .team-role {
            font-size: .78rem;
            color: var(--muted);
            font-weight: 500;
        }



        /* ── DIVIDER ── */
        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 0;
        }

        /* ── CONTACT ── */
        .contact-section {
            background: var(--card);
            border-top: 1px solid var(--border);
        }
        .contact-inner {
            max-width: 1000px;
            margin: 0 auto;
            padding: 4rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .contact-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 900;
            margin-bottom: .4rem;
        }
        .contact-text p {
            color: var(--muted);
            font-size: .92rem;
        }
        .mail-btn {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .75rem 1.6rem;
            background: var(--accent);
            color: #fff;
            border-radius: 10px;
            font-size: .92rem;
            font-weight: 700;
            transition: background .15s, transform .1s, box-shadow .15s;
            box-shadow: 0 4px 14px rgba(58,86,180,.25);
            flex-shrink: 0;
        }
        .mail-btn:hover {
            background: var(--accent-dark);
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(58,86,180,.35);
        }
        .mail-btn i { font-size: 1rem; }

        /* ── FOOTER ── */
        .footer {
            text-align: center;
            padding: 1.5rem;
            font-size: .78rem;
            color: var(--muted);
            border-top: 1px solid var(--border);
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 600px) {
            .adviser-card { flex-direction: column; text-align: center; gap: 1.2rem; }
            .adviser-card::before { width: 100%; height: 4px; bottom: auto; right: 0; border-radius: 4px 4px 0 0; }
            .contact-inner { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

    <!-- Nav -->
    <nav class="top-bar">
        <div class="logo">
            <i class="fa-solid fa-book-open-reader"></i> DLMS
        </div>
        <a href="index.php" class="back-btn">
            <i class="fa-solid fa-arrow-left fa-xs"></i> Back
        </a>
    </nav>

    <!-- Hero -->
    <div class="hero">
        <div class="hero-tag"><i class="fa-solid fa-users fa-xs"></i> &nbsp; Our Team</div>
        <h1>The People Behind DLMS</h1>
        <p>A dedicated team building a smarter, more accessible digital library experience for students and educators.</p>
    </div>

    <!-- Adviser -->
    <div class="section" style="padding-bottom:0">
        <div class="section-label"><i class="fa-solid fa-star fa-xs"></i> Project Adviser</div>
        <h2 class="section-title">Our Mentor</h2>
        <p class="section-sub">Guiding the vision and direction of the DLMS project.</p>

        <div class="adviser-card">
            <img src="/assets/credits/subrat.jpg" alt="Subrat Chetia" class="adviser-avatar">
            <div class="adviser-body">
                <div class="adviser-role">Project Adviser &amp; Instructor</div>
                <div class="adviser-name">Subrat Chetia</div>
                <div class="adviser-desc">Faculty adviser and mentor for the DLMS project. Providing guidance on system design, development practices, and academic standards.</div>
                <a href="https://pdduamdalgaon.in/department-of-computer-science/" class="view-btn" target="_blank">
                    <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i> View Profile
                </a>
            </div>
        </div>
    </div>

    <hr class="divider" style="max-width:1000px; margin: 0 auto;">

    <!-- Team -->
    <div class="section">
        <div class="section-label"><i class="fa-solid fa-code fa-xs"></i> Development Team</div>
        <h2 class="section-title">Meet the Team</h2>
        <p class="section-sub">Five students who designed, developed, and deployed the DLMS platform.</p>

        <div class="team-grid">
            <div class="team-card">
                <img src="/assets/credits/mufassirul.jpg" alt="Mufassirul Islam" class="team-avatar">
                <div class="team-name">Mufassirul Islam</div>
                <div class="team-role"> Project Coordinator &amp; Lead Developer</div>
                <a href="https://linktr.ee/Mufassirul" class="view-btn" target="_blank">
                    <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i> View Profile
                </a>
            </div>
            <div class="team-card">
                <img src="/assets/credits/arif.jpg" alt="Arif Al Amin" class="team-avatar">
                <div class="team-name">Arif Al Amin</div>
                <div class="team-role">Testing &amp; Reporting</div>
            </div>
            <div class="team-card">
                <img src="/assets/credits/moulah.jpg" alt="Golam Moulah" class="team-avatar">
                <div class="team-name">Golam Moulah</div>
                <div class="team-role">UI / UX Designer</div>
            </div>
            <div class="team-card">
                <img src="/assets/credits/shehnaz.jpg" alt="Shehnaz Sultana" class="team-avatar">
                <div class="team-name">Shehnaz Sultana</div>
                <div class="team-role">UI / UX Designer</div>
            </div>
            <div class="team-card">
                <img src="/assets/credits/nekibur.jpg" alt="Nekibur Alam" class="team-avatar">
                <div class="team-name">Nekibur Alam</div>
                <div class="team-role">UI / UX Designer</div>
            </div>
        </div>
    </div>

    <!-- Contact -->
    <div class="contact-section">
        <div class="contact-inner">
            <div class="contact-text">
                <h2>Get in Touch</h2>
                <p>Have questions about DLMS? We'd love to hear from you.</p>
            </div>
            <a href="mailto:iconicmufassirul@gmail.com" class="mail-btn">
                <i class="fa-solid fa-envelope"></i>
                Send us a Message
            </a>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; <?= date('Y') ?> DLMS — Digital Library Management System. Built by the DLMS Team.
    </div>

</body>
</html>
