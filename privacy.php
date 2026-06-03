<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Privacy Policy | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* Reusing your exact variables and base styles from about.php */
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
            margin: 0;
        }

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
            display: flex; align-items: center; gap: .5rem;
        }
        .top-bar .back-btn {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .4rem .9rem; border: 1px solid var(--border);
            border-radius: 8px; font-size: .83rem; font-weight: 600;
            color: var(--muted); text-decoration: none;
        }

        /* ── CONTENT ── */
        .container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 0 1.5rem;
        }
        .policy-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 3rem;
            box-shadow: var(--shadow);
        }
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--text);
        }
        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin: 2rem 0 1rem;
            color: var(--accent-dark);
        }
        p { margin-bottom: 1.2rem; color: var(--muted); }
        .last-updated {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--accent);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        /* ── FOOTER ── */
        .footer {
            text-align: center;
            padding: 2rem;
            font-size: .78rem;
            color: var(--muted);
            border-top: 1px solid var(--border);
            margin-top: 4rem;
        }
    </style>
</head>
<body>

    <nav class="top-bar">
        <div class="logo">
            <i class="fa-solid fa-book-open-reader"></i> DLMS
        </div>
        <a href="index.php" class="back-btn">
            <i class="fa-solid fa-arrow-left fa-xs"></i> Back
        </a>
    </nav>

    <div class="container">
        <div class="policy-card">
            <div class="last-updated">Last Updated: March 2026</div>
            <h1>Privacy Policy</h1>
            
            <p>This Privacy Policy outlines how the Digital Library Management System (DLMS) handles user information. This project is created for academic purposes.</p>

            <h2>1. Data Collection</h2>
            <p>We collect basic information required for library operations, such as your name, student ID, and email address. This data is used solely for managing book transactions and account security within the DLMS platform.</p>

            <h2>2. Academic Use</h2>
            <p>This website is a student project and is not a commercial entity. All data stored is used for testing and demonstrating the system's capabilities for Pandit Deendayal Upadhyaya Adarsha Mahavidyalaya.</p>

            <h2>3. Security</h2>
            <p>We implement industry-standard security measures, including password hashing (PDO/Bcrypt) and protected SMTP routing for emails, to ensure your information remains secure.</p>

            <h2>4. Third-Party Links</h2>
            <p>Our site may contain links to institutional resources. We are not responsible for the privacy practices of these external sites.</p>
        </div>
    </div>

    <div class="footer">
        &copy; <?= date('Y') ?> DLMS — Digital Library Management System. Academic Project.
    </div>

</body>
</html>
