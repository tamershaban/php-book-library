<?php
/**
 * اسم الطالب: تامر رياض شعبان
 * الرقم الجامعي: 120220322
 *
 */

// ============================================================================
// 1. بدء جلسة PHP (يجب أن يكون قبل أي مخرجات HTML)
// ============================================================================
session_start();

// ============================================================================
// 2. مصفوفة الأنواع المسموحة (تستخدم في القائمة المنسدلة)
// ============================================================================
$genres = ["Fiction", "Non-Fiction", "Science", "History", "Biography", "Technology"];

// ============================================================================
// 3. مصفوفة الكتب الافتراضية (مصفوفة متعددة الأبعاد)
// ============================================================================
// تم تعديل الكتب حسب طلب الطالب:
// 1. The Hundred Years' War on Palestine - رشيد خالدي
// 2. Mornings in Jenin - سوزان أبو الهوى
// 3. The Ethnic Cleansing of Palestine - إيلان بابي
// ============================================================================
$books = [
    [
        'id'     => 1,
        'title'  => 'The Hundred Years\' War on Palestine',
        'author' => 'Rashid Khalidi',
        'genre'  => 'History',
        'year'   => 2020,
        'pages'  => 336
    ],
    [
        'id'     => 2,
        'title'  => 'Mornings in Jenin',
        'author' => 'Susan Abulhawa',
        'genre'  => 'Fiction',
        'year'   => 2006,
        'pages'  => 352
    ],
    [
        'id'     => 3,
        'title'  => 'The Ethnic Cleansing of Palestine',
        'author' => 'Ilan Pappé',
        'genre'  => 'History',
        'year'   => 2006,
        'pages'  => 336
    ]
];

// ============================================================================
// 4. استرجاع الكتب من الجلسة (في حالة وجودها بعد إعادة التوجيه)
// ============================================================================
if (isset($_SESSION['books'])) {
    $books = $_SESSION['books'];
}

// ============================================================================
// 5. تهيئة المتغيرات المستخدمة في معالجة النموذج
// ============================================================================
$errors        = [];
$submittedData = [];
$editMode      = false;
$editId        = null;

// ============================================================================
// 6. كشف وضع التعديل (باستخدام ?edit_id= في الرابط)
// ============================================================================
if (isset($_GET['edit_id'])) {
    $editId   = (int)$_GET['edit_id'];
    $editMode = true;
    
    foreach ($books as $book) {
        if ($book['id'] == $editId) {
            $submittedData = $book;
            break;
        }
    }
}

// ============================================================================
// 7. معالجة النموذج عند الإرسال (POST)
// ============================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ========================================================================
    // 7.1 عملية الحذف
    // ========================================================================
    if (isset($_POST['delete_book']) && isset($_POST['delete_id'])) {
        $deleteId = (int)$_POST['delete_id'];
        
        $books = array_filter($books, function($book) use ($deleteId) {
            return $book['id'] != $deleteId;
        });
        
        $books = array_values($books);
        
        $_SESSION['books'] = $books;
        $_SESSION['success'] = "تم حذف الكتاب بنجاح!";
        
        header("Location: index.php");
        exit();
    }
    
    // ========================================================================
    // 7.2 تنظيف المدخلات (Sanitization)
    // ========================================================================
    $title  = trim(htmlspecialchars($_POST['title'] ?? ''));
    $author = trim(htmlspecialchars($_POST['author'] ?? ''));
    $genre  = $_POST['genre'] ?? '';
    $year   = trim($_POST['year'] ?? '');
    $pages  = trim($_POST['pages'] ?? '');
    
    $submittedData = [
        'title'  => $title,
        'author' => $author,
        'genre'  => $genre,
        'year'   => $year,
        'pages'  => $pages
    ];
    
    $errors = [];
    
    // ========================================================================
    // 7.3 التحقق من صحة البيانات (Validation)
    // ========================================================================
    if (empty($title)) {
        $errors['title'] = "العنوان مطلوب";
    } elseif (strlen($title) < 3 || strlen($title) > 120) {
        $errors['title'] = "العنوان يجب أن يكون بين 3 و 120 حرفاً";
    }
    
    if (empty($author)) {
        $errors['author'] = "اسم المؤلف مطلوب";
    } elseif (strpos($author, ' ') === false) {
        $errors['author'] = "يجب أن يحتوي اسم المؤلف على اسم وكنية";
    }
    
    if (empty($genre)) {
        $errors['genre'] = "الرجاء اختيار نوع الكتاب";
    } elseif (!in_array($genre, $genres)) {
        $errors['genre'] = "نوع الكتاب غير صالح";
    }
    
    $currentYear = date("Y");
    if (empty($year)) {
        $errors['year'] = "سنة النشر مطلوبة";
    } elseif (!is_numeric($year) || strlen($year) != 4 || $year < 1000 || $year > $currentYear) {
        $errors['year'] = "السنة يجب أن تكون بين 1000 و $currentYear";
    }
    
    if (empty($pages)) {
        $errors['pages'] = "عدد الصفحات مطلوب";
    } elseif (!is_numeric($pages) || $pages <= 0) {
        $errors['pages'] = "عدد الصفحات يجب أن يكون رقماً موجباً";
    }
    
    // ========================================================================
    // 7.4 إضافة أو تعديل الكتاب
    // ========================================================================
    if (empty($errors)) {
        
        if ($editMode && $editId) {
            // وضع التعديل
            foreach ($books as $key => $book) {
                if ($book['id'] == $editId) {
                    $books[$key] = [
                        'id'     => $editId,
                        'title'  => $title,
                        'author' => $author,
                        'genre'  => $genre,
                        'year'   => (int)$year,
                        'pages'  => (int)$pages
                    ];
                    $_SESSION['success'] = "تم تحديث الكتاب '{$title}' بنجاح!";
                    break;
                }
            }
        } else {
            // وضع الإضافة
            $maxId = 0;
            foreach ($books as $book) {
                if ($book['id'] > $maxId) {
                    $maxId = $book['id'];
                }
            }
            
            $books[] = [
                'id'     => $maxId + 1,
                'title'  => $title,
                'author' => $author,
                'genre'  => $genre,
                'year'   => (int)$year,
                'pages'  => (int)$pages
            ];
            $_SESSION['success'] = "تم إضافة الكتاب '{$title}' بنجاح!";
        }
        
        $_SESSION['books'] = $books;
        header("Location: index.php");
        exit();
    }
}

// ============================================================================
// 8. استرجاع رسالة النجاح
// ============================================================================
$successMessage = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المكتبة الشخصية - إدارة الكتب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container py-4">
    
    <h1 class="text-center mb-4">
        <i class="bi bi-book"></i> المكتبة الشخصية - إدارة الكتب
    </h1>
    
    <div class="text-center text-muted mb-4">
        <small>الطالب: تامر رياض شعبان | الرقم الجامعي: 120220322</small>
    </div>
    
    <div class="row">
        
        <!-- ====================================================================
             القسم الأيسر: نموذج إضافة/تعديل كتاب
             ==================================================================== -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <?php if ($editMode): ?>
                            <i class="bi bi-pencil-square"></i> تعديل كتاب
                        <?php else: ?>
                            <i class="bi bi-plus-circle"></i> إضافة كتاب جديد
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> 
                            <strong>الرجاء تصحيح الأخطاء التالية:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-journal"></i> عنوان الكتاب *
                            </label>
                            <input type="text" name="title" 
                                   class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($submittedData['title'] ?? '') ?>"
                                   placeholder="أدخل عنوان الكتاب">
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?= $errors['title'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-person"></i> اسم المؤلف *
                            </label>
                            <input type="text" name="author" 
                                   class="form-control <?= isset($errors['author']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($submittedData['author'] ?? '') ?>"
                                   placeholder="مثال: John Smith">
                            <?php if (isset($errors['author'])): ?>
                                <div class="invalid-feedback"><?= $errors['author'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-tag"></i> نوع الكتاب *
                            </label>
                            <select name="genre" class="form-select <?= isset($errors['genre']) ? 'is-invalid' : '' ?>">
                                <option value="">-- اختر نوع الكتاب --</option>
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
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-calendar"></i> سنة النشر *
                            </label>
                            <input type="number" name="year" 
                                   class="form-control <?= isset($errors['year']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($submittedData['year'] ?? '') ?>"
                                   placeholder="مثال: 2024">
                            <?php if (isset($errors['year'])): ?>
                                <div class="invalid-feedback"><?= $errors['year'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-files"></i> عدد الصفحات *
                            </label>
                            <input type="number" name="pages" 
                                   class="form-control <?= isset($errors['pages']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($submittedData['pages'] ?? '') ?>"
                                   placeholder="مثال: 300">
                            <?php if (isset($errors['pages'])): ?>
                                <div class="invalid-feedback"><?= $errors['pages'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <?php if ($editMode): ?>
                                <i class="bi bi-pencil-square"></i> تحديث الكتاب
                            <?php else: ?>
                                <i class="bi bi-plus-circle"></i> إضافة الكتاب
                            <?php endif; ?>
                        </button>
                        
                        <?php if ($editMode): ?>
                            <a href="index.php" class="btn btn-secondary w-100 mt-2">
                                <i class="bi bi-x-circle"></i> إلغاء
                            </a>
                        <?php endif; ?>
                        
                    </form>
                </div>
            </div>
        </div>
        
        <!-- ====================================================================
             القسم الأيمن: جدول عرض الكتب
             ==================================================================== -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-collection"></i> قائمة الكتب
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>العنوان</th>
                                    <th>المؤلف</th>
                                    <th>النوع</th>
                                    <th>السنة</th>
                                    <th>الصفحات</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($books)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="bi bi-info-circle"></i> لا توجد كتب حالياً. أضف كتابك الأول!
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
                                            </span>
                                            <td><?= htmlspecialchars($book['year']) ?></span>
                                            <td><?= htmlspecialchars($book['pages']) ?></span>
                                            <td class="text-nowrap">
                                                <a href="?edit_id=<?= $book['id'] ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> تعديل
                                                </a>
                                                
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal<?= $book['id'] ?>">
                                                    <i class="bi bi-trash"></i> حذف
                                                </button>
                                                
                                                <!-- Modal تأكيد الحذف -->
                                                <div class="modal fade" id="deleteModal<?= $book['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title">
                                                                    <i class="bi bi-exclamation-triangle"></i> تأكيد الحذف
                                                                </h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                هل أنت متأكد من حذف كتاب 
                                                                <strong><?= htmlspecialchars($book['title']) ?></strong>؟
                                                                <br>
                                                                <small class="text-muted">هذا الإجراء لا يمكن التراجع عنه.</small>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                    <i class="bi bi-x-circle"></i> إلغاء
                                                                </button>
                                                                <form method="POST" style="display:inline;">
                                                                    <input type="hidden" name="delete_id" value="<?= $book['id'] ?>">
                                                                    <button type="submit" name="delete_book" class="btn btn-danger">
                                                                        <i class="bi bi-check-circle"></i> نعم، احذف
                                                                    </button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>