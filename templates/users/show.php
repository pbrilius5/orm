<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">User #<?= $user->getId() ?></h5>
                    <a href="/users/<?= $user->getId() ?>/edit" class="btn btn-sm btn-light">Edit</a>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-3">
                        <div class="row">
                            <div class="col-sm-4 text-muted fw-semibold">Email</div>
                            <div class="col-sm-8"><?= htmlspecialchars($user->getEmail()) ?></div>
                        </div>
                        <?php $workGroup = $user->getWorkGroup(); ?>
                        <div class="row">
                            <div class="col-sm-4 text-muted fw-semibold">Work Group</div>
                            <div class="col-sm-8"><?= $workGroup ? htmlspecialchars($workGroup->getName()) : 'None' ?></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4 text-muted fw-semibold">Roles</div>
                            <div class="col-sm-8 d-flex flex-wrap gap-1">
                                <?php foreach ($user->getGamificationRoles() as $role): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($role->getName()) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4 text-muted fw-semibold">Created</div>
                            <div class="col-sm-8"><?= $user->getCreatedAt()->format('Y-m-d H:i:s') ?></div>
                        </div>
                        <?php if ($user->getUpdatedAt()): ?>
                        <div class="row">
                            <div class="col-sm-4 text-muted fw-semibold">Updated</div>
                            <div class="col-sm-8"><?= $user->getUpdatedAt()->format('Y-m-d H:i:s') ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <a href="/users" class="btn btn-outline-secondary mt-3">&larr; Back to Users</a>
        </div>
    </div>
</div>
