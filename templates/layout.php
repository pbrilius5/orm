<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Oryx ORM' ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; flex-direction: column; background: #f5f7fa; }
        main { flex: 1 0 auto; padding: 0; }
        footer { flex-shrink: 0; background: #fff; border-top: 1px solid #e9ecef; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="/">Oryx ORM</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <div class="d-flex flex-column flex-sm-row ms-sm-auto gap-2 gap-sm-0">
                    <a class="nav-link" href="/">Home</a>
                    <a class="nav-link" href="/users">Users</a>
                </div>
            </div>
        </div>
    </nav>

    <?php if (!empty($breadcrumbs)): ?>
    <nav class="bg-white border-bottom" aria-label="breadcrumb">
        <div class="container-fluid px-4 py-2">
            <ol class="breadcrumb mb-0 small">
                <?php foreach ($breadcrumbs as $i => $crumb): ?>
                    <?php if ($i === count($breadcrumbs) - 1): ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($crumb['label']) ?></li>
                    <?php else: ?>
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($crumb['url']) ?>" class="text-decoration-none"><?= htmlspecialchars($crumb['label']) ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </div>
    </nav>
    <?php endif; ?>

    <main>
        <?= $content ?? '' ?>
    </main>

    <footer class="py-3">
        <div class="container-fluid d-flex flex-column flex-sm-row justify-content-between align-items-center gap-1">
            <span class="text-muted small">&copy; <?= date('Y') ?> Oryx ORM</span>
            <span class="text-muted small">prototype.in — <em>ship fast, iterate faster</em></span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
