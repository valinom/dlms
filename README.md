# DLMS — Digital Library Management System

A web-based Library Management System built with PHP, MariaDB/MySQL, and PHPMailer-compatible SMTP. Designed for institutional use.

---

## ⚙️ Before You Begin — Required Configuration

You **must** edit two files before the system will work. Do **not** run the project with placeholder values.

---

### 1. `includes/config.php` — Database Connection

Open this file and replace the placeholders with your actual database credentials:

```php
define('DB_HOST', 'localhost');        // e.g. localhost or your DB server IP
define('DB_PORT', '3306');             // default MySQL/MariaDB port
define('DB_NAME', 'your_db_name');     // name of your created database
define('DB_USER', 'your_db_user');     // database username
define('DB_PASS', 'your_db_password'); // database password
```

> **Tip:** If you're on shared hosting (e.g. InfinityFree, rf.gd), get these values from your hosting control panel under **MySQL Databases**.

---

### 2. Session Cookie Domain — `includes/about.php` (or wherever `session_set_cookie_params` is called)

The session cookie is hard-coded to a specific domain. **You must update the `domain` value** to match your own site, otherwise sessions will not work.

Find this block (starts at **line 4**) in the file that initializes the session (e.g. `includes/about.php` or your session init file):

```php
session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 30,
    'path'     => '/',
    'domain'   => 'dlms.page.gd',   // ← CHANGE THIS to your domain
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
```

Replace `'dlms.page.gd'` with your own domain, e.g.:
```php
'domain' => 'yourdomain.com',
```

> **Note:** `secure` is `true`, meaning cookies only work over **HTTPS**. On a local dev server (HTTP), either set `'secure' => false` temporarily, or use a tool like Laragon/XAMPP with a self-signed cert.

---

### 3. `includes/mailer.php` — Gmail SMTP (Email Sending)

This system sends emails for registration, password reset, and notifications. It uses Gmail with an App Password.

```php
define('SMTP_USER',      'your_gmail@gmail.com');   // your Gmail address
define('SMTP_PASS',      'xxxx xxxx xxxx xxxx');     // 16-char Gmail App Password (not your login password)
define('SMTP_FROM',      'your_gmail@gmail.com');    // same Gmail address
define('SMTP_FROM_NAME', 'DLMS Library');            // display name in emails
define('APP_URL',        'https://your-domain.com'); // your live site URL (no trailing slash)
```

#### How to generate a Gmail App Password:
1. Go to [myaccount.google.com](https://myaccount.google.com)
2. Navigate to **Security → 2-Step Verification** (must be enabled)
3. Scroll down to **App passwords**
4. Create a new app password for "Mail"
5. Copy the 16-character password and paste it into `SMTP_PASS`

> **Never use your regular Gmail password here.** App Passwords are separate and can be revoked anytime.

---

### 4. `about.php` — Contact Email

The contact email button is hard-coded at **line 424**:

```html
<a href="mailto:iconicmufassirul@gmail.com" class="mail-btn">
```

Replace `iconicmufassirul@gmail.com` with your own contact email:

```html
<a href="mailto:your_email@gmail.com" class="mail-btn">
```

---

## 🗄️ Database Setup

1. Create a new database in your MySQL/MariaDB server (e.g. `dlms_db`)
2. Import the provided SQL schema file:

```bash
mysql -u your_user -p your_db_name < database/dlms_schema.sql
```

Or use **phpMyAdmin → Import** if you're on shared hosting.

---

## 📂 Create Required Upload Folders

GitHub cannot track empty folders, so these directories are **not included** in the repo. You must create them manually on your server after cloning:

```bash
mkdir -p uploads/books
mkdir -p uploads/documents
```

Or create them manually via your hosting file manager:

```
dlms/
└── uploads/
    ├── books/       ← book cover images are stored here
    └── documents/   ← uploaded book PDF/doc files go here
```

> **Without these folders, file uploads will fail silently.** Make sure they exist before testing any upload features.

---

## 🌐 Server Requirements

| Requirement | Minimum Version |
|---|---|
| PHP | 8.0+ |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Apache | 2.4+ (with `mod_rewrite` enabled) |
| SSL/HTTPS | Required for secure cookies and SMTP |

---

## 📁 Folder Structure

```
dlms/
├── admin/          # Admin panel pages
├── ajax/           # AJAX handler scripts
├── assets/         # CSS, JS, images
├── includes/       # Core config files (db, mailer, helpers)
│   ├── config.php      ← Edit this: DB credentials
│   └── mailer.php      ← Edit this: Gmail SMTP credentials
├── student/        # Student dashboard pages
├── superuser/      # Superuser panel
├── .htaccess       # URL rewriting rules
├── index.php       # Public landing / login page
├── register.php    # Student registration
└── forgot-password.php
```

---

## 🔒 Security Notes

- The `includes/` folder is blocked from public access via `robots.txt` and should also be protected via `.htaccess`. Do not expose config files publicly.
- Never commit real credentials to Git. Use placeholder values in the repo and set real values only on the server.
- Session cookies are configured with `httponly`, `secure`, and `SameSite=Lax` for security.
- All forms use CSRF tokens. Do not disable CSRF verification.

---

## 🚀 Deployment Checklist

- [ ] Set real DB credentials in `includes/config.php`
- [ ] Update cookie `domain` to your domain in session init file (line 4)
- [ ] Set Gmail address + App Password in `includes/mailer.php`
- [ ] Set `APP_URL` to your actual domain in `includes/mailer.php`
- [ ] Update contact email in `about.php` line 424
- [ ] Import the SQL schema into your database
- [ ] Create `uploads/books/` and `uploads/documents/` folders on the server
- [ ] Ensure `mod_rewrite` is enabled on Apache
- [ ] Deploy over HTTPS (required for secure session cookies)
- [ ] Remove or restrict access to any test/debug files

---

## 👥 Contributing / Team

This project was developed as a final-year BSc Computer Science project at **Pandit Deendayal Upadhyaya Adarsha Mahavidyalaya, Dalgaon**.

For issues or questions, open a GitHub Issue or contact the repository owner.

---

## 📄 License

This project is for academic/institutional use. Please contact the author before reusing or redistributing.
