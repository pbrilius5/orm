<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-5 bg-white rounded shadow-sm">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="display-5 fw-bold"><?= htmlspecialchars($title ?? 'Oryx ORM') ?></h1>
                        <p class="lead text-muted"><?= htmlspecialchars($description ?? 'Full-stack ORM with MVC pattern') ?></p>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <div class="d-flex flex-column flex-sm-row justify-content-lg-end gap-2">
                            <a href="/users" class="btn btn-primary btn-lg">Manage Users</a>
                            <a href="/users/create" class="btn btn-outline-secondary btn-lg">Create User</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="d-flex h-100 p-4 bg-dark text-white rounded flex-column justify-content-between">
                <div>
                    <h2 class="h4">MVC Pattern</h2>
                    <p class="mb-0 text-white-50">Traditional server-side rendering with controllers, views, and models.</p>
                </div>
                <a href="/users" class="btn btn-outline-light mt-3 align-self-start">Browse Users</a>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="d-flex h-100 p-4 bg-white border rounded flex-column justify-content-between">
                <div>
                    <h2 class="h4">API Ready</h2>
                    <p class="mb-0 text-muted">RESTful JSON endpoints for SPAs and mobile apps.</p>
                </div>
                <a href="/api/users" class="btn btn-outline-secondary mt-3 align-self-start">View API</a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-sm-6 col-md-4">
            <div class="d-flex flex-column h-100 p-4 bg-white border rounded">
                <h5 class="fw-bold">Doctrine ORM</h5>
                <p class="text-muted mb-0 flex-grow-1">Full-featured ORM with DQL, repositories, and entity management.</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="d-flex flex-column h-100 p-4 bg-white border rounded">
                <h5 class="fw-bold">Event System</h5>
                <p class="text-muted mb-0 flex-grow-1">League\Event integration for decoupled architecture and clean hooks.</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="d-flex flex-column h-100 p-4 bg-white border rounded">
                <h5 class="fw-bold">CLI Console</h5>
                <p class="text-muted mb-0 flex-grow-1">Symfony Console commands for database migrations and seeding.</p>
            </div>
        </div>
    </div>
</div>
