<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>DLMS • Service Unavailable</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            /*Main Background colour */
            --bg-main: hsl(220, 25%, 92%);
            --bg-nav: hsl(220, 22%, 96%);
            --bg-sidebar: hsl(220, 22%, 96%);
            --bg-hover: hsl(225, 30%, 90%);
            --border-soft: hsl(220, 18%, 85%);
            --bg-card: hsl(220, 22%, 94%);

            /* Text Colour */
            --text-main: hsl(225, 15%, 30%);
            --text-muted: hsl(225, 10%, 45%);
            --accent: hsl(225, 35%, 55%);
            --accent-soft: hsl(225, 20%, 55%);


            --sidebar-width: 260px;
            /* Aside Sidebar Width  */
            --navbar-height: 64px;

        }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: var(--bg-main);
            font-family: var(--font-main);
            color: var(--text-main);
        }

        .db-error-card {
            max-width: 420px;
            padding: 2rem;
            border-radius: 14px;
            background: var(--bg-card);
            box-shadow: var(--shadow-soft);
            text-align: center;
        }

        .db-error-card i {
            font-size: 3rem;
            color: var(--danger);
            margin-bottom: 1rem;
        }

        .db-error-card h1 {
            font-size: 1.4rem;
            margin-bottom: .5rem;
        }

        .db-error-card p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .db-error-card small {
            display: block;
            margin-top: 1rem;
            color: var(--text-soft);
        }

        /* Base alert */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid;
            font-size: 0.95rem;
            transition:
                background 0.2s ease,
                box-shadow 0.2s ease,
                transform 0.15s ease;
        }

        .alert.warning {
            --bg: hsl(45, 90%, 92%);
            --bg-hover: hsl(45, 90%, 88%);
            --text: hsl(35, 80%, 30%);
            --border: hsl(40, 85%, 70%);
        }

        /* Apply variables */
        .alert {
            background: var(--bg) !important;
            color: var(--text) !important;
            border-color: var(--border) !important;
        }
    </style>
</head>

<body>

    <div class="db-error-card">
        <i class="fa-solid fa-database"></i>
        <h1>Service Temporarily Unavailable</h1>
        <p>
            We’re having trouble connecting to the database.<br>
            Please try again in a few moments.
        </p>

        <div class="alert warning">
            If the problem persists, contact the administrator.
        </div>

        <small>DLMS • <?= date('Y') ?></small>
    </div>

</body>

</html>