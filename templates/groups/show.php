<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><?= htmlspecialchars($group->getName()) ?></h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ID</label>
                        <p class="form-control-plaintext"><?= $group->getId() ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name</label>
                        <p class="form-control-plaintext"><?= htmlspecialchars($group->getName()) ?></p>
                    </div>
                    <?php if ($group->getDescription()): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <p class="form-control-plaintext"><?= htmlspecialchars($group->getDescription()) ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Created</label>
                        <p class="form-control-plaintext"><?= $group->getCreatedAt()->format('Y-m-d H:i:s') ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Users</label>
                        <p class="form-control-plaintext"><?= $group->getUsers()->count() ?> user(s)</p>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <a href="/groups/<?= $group->getId() ?>/edit" class="btn btn-primary">Edit</a>
                        <a href="/groups" class="btn btn-outline-secondary">Back to Groups</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
