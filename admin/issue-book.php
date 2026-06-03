<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth-admin.php';

if (isset($_POST['issue_book'])) {

    /* CSRF validation */
    verify_csrf();

    $studentId = (int) ($_POST['student_id'] ?? 0);
    $bookId = (int) ($_POST['book_id'] ?? 0);
    $days = (int) ($_POST['issue_days'] ?? 0);

    if (!$studentId || !$bookId || !$days) {
        flash('error', 'All fields are required.');
    } else {

        $issueDate = date('Y-m-d');
        $returnDate = date('Y-m-d', strtotime("+$days days"));

        try {
            $pdo->beginTransaction();

            /* Check book availability (lock row) */
            $stmt = $pdo->prepare("
                SELECT quantity 
                FROM books 
                WHERE id = :book_id 
                FOR UPDATE
            ");
            $stmt->execute([':book_id' => $bookId]);
            $book = $stmt->fetch();

            if (!$book) {
                throw new Exception("Invalid book selected.");
            }

            if ((int) $book['quantity'] < 1) {
                throw new Exception("Book is currently not available.");
            }


            /* Prevent duplicate issue */
            $stmt = $pdo->prepare("
                SELECT id 
                FROM issued_books 
                WHERE student_id = :student_id 
                  AND book_id = :book_id 
                  AND status = 'issued'
                LIMIT 1
            ");
            $stmt->execute([
                ':student_id' => $studentId,
                ':book_id' => $bookId
            ]);

            if ($stmt->fetch()) {
                throw new Exception("This book is already issued to this student.");
            }

            /* Insert issue record */
            $stmt = $pdo->prepare("
                INSERT INTO issued_books
                (student_id, book_id, issue_date, return_date, status)
                VALUES
                (:student_id, :book_id, :issue_date, :return_date, 'issued')
            ");
            $stmt->execute([
                ':student_id' => $studentId,
                ':book_id' => $bookId,
                ':issue_date' => $issueDate,
                ':return_date' => $returnDate
            ]);

            /* Reduce book quantity */
            $stmt = $pdo->prepare("
                UPDATE books 
                SET quantity = quantity - 1 
                WHERE id = :book_id
            ");
            $stmt->execute([':book_id' => $bookId]);

            $pdo->commit();
            flash('success', 'Book issued successfully.');

        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', $e->getMessage());
        }
    }
    header("Location: issue-book.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <title>Issue Book | DLMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/variable.css">
    <link rel="stylesheet" href="../assets/css/alert.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="../assets/css/navigation.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/book.css"> <!-- SEARCH UI -->
    <link rel="stylesheet" href="assets/css/adminui.css">
</head>

<body>
    <?php include('includes/admin-header.php'); ?>
    <?php include '../includes/_toast.php'; ?>

    <main class="main">
        <div class="page-container">

            <h2 class="page-title">Issue Book</h2>


            <form class="form-card" method="POST">

                <!-- Student -->
                <label>Student (Name / ID)</label>
                <div class="search-box">
                    <input type="text" id="studentSearch" placeholder="Type name or ID" autocomplete="off">
                    <input type="hidden" name="student_id" required>
                    <div id="studentInfo" class="info-card" style="display:none"></div>
                    <ul class="search-results" id="studentResults"></ul>

                </div>

                <!-- Book -->
                <label>Book (Name / ISBN)</label>
                <div class="search-box">
                    <input type="text" id="bookSearch" placeholder="Type book name or ISBN" autocomplete="off">
                    <input type="hidden" name="book_id" required>
                    <ul class="search-results" id="bookResults"></ul>
                    <div id="bookInfo" class="info-card" style="display:none"></div>

                </div>

                <!-- Days -->
                <label>Total Days</label>
                <input type="number" id="issueDays" name="issue_days" min="1" required>

                <!-- Return Date -->
                <div class="info-box">
                    Return Date:
                    <strong id="returnDate">—</strong>
                </div>

                <button type="submit" name="issue_book">
                    <i class="fa-solid fa-book-open-reader"></i> Issue Book
                </button>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            </form>
        </div>
    </main>
    <script>
        /* ========= STUDENT SEARCH ========= */
        const studentInput = document.getElementById('studentSearch');
        const studentResults = document.getElementById('studentResults');
        const studentInfo = document.getElementById('studentInfo');
        const studentHidden = document.querySelector('input[name="student_id"]');

        studentInput.addEventListener('input', () => {
            const q = studentInput.value.trim();
            if (q.length < 2) {
                studentResults.innerHTML = '';
                return;
            }

            fetch(`ajax/search-students.php?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    studentResults.innerHTML = '';

                    if (data.length === 0) {
                        studentResults.classList.remove('active');
                        return;
                    }

                    data.forEach(s => {
                        const li = document.createElement('li');
                        li.textContent = `${s.user_id} - ${s.fullname}`;
                        li.onclick = () => selectStudent(s);
                        studentResults.appendChild(li);
                    });

                    studentResults.classList.add('active');
                });

        });

        function selectStudent(s) {
            studentInput.value = `${s.user_id} - ${s.fullname}`;
            studentHidden.value = s.id;
            studentResults.innerHTML = '';

            studentInfo.style.display = 'block';
            studentInfo.innerHTML = `
        <p><strong>${s.fullname}</strong></p>
        <p style="color: var(--text-muted);">ID: ${s.user_id}</p>
        <p style="color: var(--text-muted);">Email: ${s.email}</p>
        <p style="color: var(--text-muted);">Mobile: ${s.mobile}</p>
        <p style="color: var(--text-muted);">Department: ${s.department}</p>
    `;
        }

        /* ========= BOOK SEARCH ========= */
        const bookInput = document.getElementById('bookSearch');
        const bookResults = document.getElementById('bookResults');
        const bookInfo = document.getElementById('bookInfo');
        const bookHidden = document.querySelector('input[name="book_id"]');

        bookInput.addEventListener('input', () => {
            const q = bookInput.value.trim();
            if (q.length < 2) {
                bookResults.innerHTML = '';
                return;
            }

            fetch(`ajax/search-books.php?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    bookResults.innerHTML = '';

                    if (data.length === 0) {
                        bookResults.classList.remove('active');
                        return;
                    }

                    data.forEach(b => {
                        const li = document.createElement('li');
                        li.textContent = `${b.book_name} - ${b.isbn}`;
                        li.onclick = () => selectBook(b);
                        bookResults.appendChild(li);
                    });

                    bookResults.classList.add('active');
                });

        });

        function selectBook(b) {
            bookInput.value = `${b.book_name} (${b.isbn})`;
            bookHidden.value = b.id;
            bookResults.innerHTML = '';

            const img = b.image
                ? `../ajax/book-cover.php?file=${b.image}`
                : `../assets/img/no-book.png`;

            bookInfo.style.display = 'flex';
            bookInfo.innerHTML = `
        <img src="${img}" style="width:80px;height:110px;border-radius:8px;object-fit:cover">
        <div style="margin-left:10px">
            <strong>${b.book_name}</strong></p>
            <p style="color: var(--text-muted);">Author: ${b.author_name}</p>
            <p style="color: var(--text-muted);">ISBN: ${b.isbn}</p>
            <p style="color: var(--text-muted);">Available: ${b.quantity}</p>
        </div>
    `;
        }

        /* ========= RETURN DATE ========= */
        document.getElementById('issueDays').addEventListener('input', e => {
            const days = parseInt(e.target.value);
            if (!days) return;

            const d = new Date();
            d.setDate(d.getDate() + days);
            document.getElementById('returnDate').textContent = d.toDateString();
        });


        document.addEventListener('click', e => {
            if (!e.target.closest('.search-box')) {
                document.querySelectorAll('.search-results')
                    .forEach(el => el.classList.remove('active'));
            }
        });

    </script>
    <?php include '../includes/_footer.php'; ?>
</body>

</html>