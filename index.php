<?php
// ==========================================
// 1. بدء الجلسة (لازم في البداية)
// ==========================================
session_start();

// ==========================================
// 2. مصفوفة الأنواع المسموحة
// ==========================================
$genres = ["Fiction", "Non-Fiction", "Science", "History", "Biography", "Technology"];

// ==========================================
// 3. الكتب الافتراضية
// ==========================================
$books = [
    ['id' => 1, 'title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald', 'genre' => 'Fiction', 'year' => 1925, 'pages' => 180],
    ['id' => 2, 'title' => 'Sapiens', 'author' => 'Yuval Noah Harari', 'genre' => 'History', 'year' => 2011, 'pages' => 443],
    ['id' => 3, 'title' => 'Clean Code', 'author' => 'Robert Martin', 'genre' => 'Technology', 'year' => 2008, 'pages' => 464]
];

// ==========================================
// 4. استرجاع الكتب من الجلسة إذا وجدت
// ==========================================
if (isset($_SESSION['books'])) {
    $books = $_SESSION['books'];
}

// ==========================================
// 5. متغيرات للمعالجة
// ==========================================
$errors = [];
$submittedData = [];
$editMode = false;
$editId = null;

// ==========================================
// 6. معالجة طلب التعديل (Edit)
// ==========================================
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $editMode = true;
    
    // البحث عن الكتاب المطلوب تعديله
    foreach ($books as $book) {
        if ($book['id'] == $editId) {
            $submittedData = $book;
            break;
        }
    }
}

// ==========================================
// 7. معالجة إرسال النموذج (POST)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ========== منطق الحذف ==========
    if (isset($_POST['delete_book']) && isset($_POST['delete_id'])) {
        $deleteId = (int)$_POST['delete_id'];
        
        // استخدام array_filter لحذف الكتاب
        $books = array_filter($books, function($book) use ($deleteId) {
            return $book['id'] != $deleteId;
        });
        
        // إعادة ترتيب المصفوفة (إعادة الفهرسة)
        $books = array_values($books);
        
        // حفظ التغييرات في الجلسة
        $_SESSION['books'] = $books;
        $_SESSION['success'] = "Book deleted successfully!";
        
        header("Location: index.php");
        exit();
    }
    // =================================
    
    // ========== تنظيف المدخلات ==========
    $title = trim(htmlspecialchars($_POST['title'] ?? ''));
    $author = trim(htmlspecialchars($_POST['author'] ?? ''));
    $genre = $_POST['genre'] ?? '';
    $year = trim($_POST['year'] ?? '');
    $pages = trim($_POST['pages'] ?? '');
    
    $submittedData = [
        'title' => $title,
        'author' => $author,
        'genre' => $genre,
        'year' => $year,
        'pages' => $pages
    ];
    
    $errors = [];
    
    // ========== التحقق من صحة المدخلات (Validation) ==========
    // التحقق من العنوان
    if (empty($title)) {
        $errors['title'] = "Title is required";
    } elseif (strlen($title) < 3 || strlen($title) > 120) {
        $errors['title'] = "Title must be between 3 and 120 characters";
    }
    
    // التحقق من المؤلف
    if (empty($author)) {
        $errors['author'] = "Author is required";
    } elseif (strpos($author, ' ') === false) {
        $errors['author'] = "Author must have first and last name";
    }
    
    // التحقق من النوع
    if (empty($genre)) {
        $errors['genre'] = "Genre is required";
    } elseif (!in_array($genre, $genres)) {
        $errors['genre'] = "Invalid genre selected";
    }
    
    // التحقق من السنة
    $currentYear = date("Y");
    if (empty($year)) {
        $errors['year'] = "Year is required";
    } elseif (!is_numeric($year) || strlen($year) != 4 || $year < 1000 || $year > $currentYear) {
        $errors['year'] = "Year must be between 1000 and $currentYear";
    }
    
    // التحقق من الصفحات
    if (empty($pages)) {
        $errors['pages'] = "Pages is required";
    } elseif (!is_numeric($pages) || $pages <= 0) {
        $errors['pages'] = "Pages must be a positive number";
    }
    
    // ========== إذا ما في أخطاء، نضيف أو نعدل الكتاب ==========
    if (empty($errors)) {
        if ($editMode && $editId) {
            // === وضع التعديل: تحديث كتاب موجود ===
            foreach ($books as $key => $book) {
                if ($book['id'] == $editId) {
                    $books[$key] = [
                        'id' => $editId,
                        'title' => $title,
                        'author' => $author,
                        'genre' => $genre,
                        'year' => (int)$year,
                        'pages' => (int)$pages
                    ];
                    $_SESSION['success'] = "Book '{$title}' updated successfully!";
                    break;
                }
            }
        } else {
            // === وضع الإضافة: إضافة كتاب جديد ===
            $maxId = 0;
            foreach ($books as $book) {
                if ($book['id'] > $maxId) {
                    $maxId = $book['id'];
                }
            }
            $books[] = [
                'id' => $maxId + 1,
                'title' => $title,
                'author' => $author,
                'genre' => $genre,
                'year' => (int)$year,
                'pages' => (int)$pages
            ];
            $_SESSION['success'] = "Book '{$title}' added successfully!";
        }
        
        $_SESSION['books'] = $books;
        header("Location: index.php");
        exit();
    }
}

// ==========================================
// 8. رسالة النجاح
// ==========================================
$successMessage = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Book Library</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <h1 class="text-center mb-4">📚 My Book Library</h1>
    
    <div class="row">
        <!-- ========================================== -->
        <!-- النموذج (الجانب الأيسر) -->
        <!-- ========================================== -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?= $editMode ? '✏️ Edit Book' : '➕ Add New Book' ?></h5>
                </div>
                <div class="card-body">
                    
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= htmlspecialchars($successMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <!-- حقل العنوان -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Title *</label>
                            <input type="text" name="title" 
                                   class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($submittedData['title'] ?? '') ?>">
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?= $errors['title'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- حقل المؤلف -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Author *</label>
                            <input type="text" name="author" 
                                   class="form-control <?= isset($errors['author']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($submittedData['author'] ?? '') ?>">
                            <?php if (isset($errors['author'])): ?>
                                <div class="invalid-feedback"><?= $errors['author'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- حقل النوع (منسدل) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Genre *</label>
                            <select name="genre" class="form-select <?= isset($errors['genre']) ? 'is-invalid' : '' ?>">
                                <option value="">-- Select Genre --</option>
                                <?php foreach ($genres as $genre): ?>
                                    <option value="<?= $genre ?>" 
                                        <?= (($submittedData['genre'] ?? '') == $genre) ? 'selected' : '' ?>>
                                        <?= $genre ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['genre'])): ?>
                                <div class="invalid-feedback"><?= $errors['genre'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- حقل السنة -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Year *</label>
                            <input type="number" name="year" 
                                   class="form-control <?= isset($errors['year']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($submittedData['year'] ?? '') ?>">
                            <?php if (isset($errors['year'])): ?>
                                <div class="invalid-feedback"><?= $errors['year'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- حقل عدد الصفحات -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pages *</label>
                            <input type="number" name="pages" 
                                   class="form-control <?= isset($errors['pages']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($submittedData['pages'] ?? '') ?>">
                            <?php if (isset($errors['pages'])): ?>
                                <div class="invalid-feedback"><?= $errors['pages'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <?= $editMode ? '✏️ Update Book' : '➕ Add Book' ?>
                        </button>
                        
                        <?php if ($editMode): ?>
                            <a href="index.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- ========================================== -->
        <!-- الجدول (الجانب الأيمن) -->
        <!-- ========================================== -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">📖 Books Collection</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Genre</th>
                                    <th>Year</th>
                                    <th>Pages</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($books)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No books yet. Add your first book!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($books as $book): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($book['id']) ?></td>
                                            <td><?= htmlspecialchars($book['title']) ?></td>
                                            <td><?= htmlspecialchars($book['author']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($book['genre']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($book['year']) ?></td>
                                            <td><?= htmlspecialchars($book['pages']) ?></td>
                                            <td class="text-nowrap">
                                                <!-- زر Edit -->
                                                <a href="?edit_id=<?= $book['id'] ?>" class="btn btn-sm btn-warning">
                                                    ✏️ Edit
                                                </a>
                                                
                                                <!-- زر Delete - يفتح Modal -->
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $book['id'] ?>">
                                                    🗑️ Delete
                                                </button>
                                                
                                                <!-- Modal التأكيد (لكل كتاب) -->
                                                <div class="modal fade" id="deleteModal<?= $book['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title">⚠️ Confirm Delete</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Are you sure you want to delete <strong><?= htmlspecialchars($book['title']) ?></strong>?
                                                                <br>
                                                                <small class="text-muted">This action cannot be undone.</small>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <form method="POST" style="display:inline;">
                                                                    <input type="hidden" name="delete_id" value="<?= $book['id'] ?>">
                                                                    <button type="submit" name="delete_book" class="btn btn-danger">Yes, Delete</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle (لازم عشان الـ Modal يشتغل) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>