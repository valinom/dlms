    <div class="footer">
        <div>
            <a class="foot-links" href="/"><i class="fa-solid
            fa-book-open-reader"></i><b> DLMS | LIBRARY</b></a>
        </div>
        &copy; <?= date('Y') ?> DLMS — Digital Library Management System. Built by the DLMS Team.
        <div>
            <a class="foot-links" href="/">Home</a>
            <span> &nbsp;|&nbsp; </span>
            <a class="foot-links" href="/about.php">About Us</a>
        </div>
    </div>

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
        --sidebar-width:   240px;
        --radius:       16px;
        --shadow:       0 4px 24px rgba(58, 86, 180, .10);
    }
    .footer {
        background: var(--card);
        text-align: center;
        padding: 1.5rem;
        font-size: .78rem;
        color: var(--muted);
        border-top: 1px solid var(--border);
    }
    
    .foot-links{
        color: var(--accent);
        text-decoration:none;
    }
    
    
    @media (min-width: 768px) {
        .footer {
            margin-left: var(--sidebar-width);
            padding: .7rem 1rem;
        }
    }
</style>
