<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Oryx ORM' ?></title>
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

    <main>
        <?= $content ?? '' ?>
    </main>

    <footer class="py-3">
        <div class="container-fluid d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span class="text-muted small">&copy; <?= date('Y') ?> Oryx ORM</span>
            <div class="d-flex gap-3">
                <a href="/" class="text-muted small text-decoration-none">Home</a>
                <a href="/users" class="text-muted small text-decoration-none">Users</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
