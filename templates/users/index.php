<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <h2 class="mb-0">Users</h2>
                <a href="/users/create" class="btn btn-primary">+ Add New User</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th class="d-none d-sm-table-cell py-3">#</th>
                                    <th class="d-none d-md-table-cell py-3">UUID</th>
                                    <th class="py-3">Email</th>
                                    <th class="d-none d-lg-table-cell py-3">Roles</th>
                                    <th class="d-none d-lg-table-cell py-3">Created</th>
                                    <th class="text-end py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <p class="mb-2">No users found.</p>
                                        <a href="/users/create" class="btn btn-sm btn-primary">Create one</a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php $rowNum = 0;
                                foreach ($users as $user): ?>
                                <tr>
                                    <td class="d-none d-sm-table-cell text-muted fw-bold"><?= ++$rowNum ?></td>
                                    <td class="d-none d-md-table-cell text-muted small font-monospace"><?= $user->getId() ?></td>
                                    <td>
                                        <a href="/users/<?= $user->getId() ?>" class="text-decoration-none fw-semibold">
                                            <?= htmlspecialchars($user->getEmail()) ?>
                                        </a>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($user->getRoles() as $role): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($role) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="d-none d-xl-table-cell text-muted small"><?= $user->getCreatedAt()->format('Y-m-d') ?></td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-1">
                                            <a href="/users/<?= $user->getId() ?>" class="btn btn-sm btn-outline-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                            </a>
                                            <a href="/users/<?= $user->getId() ?>/edit" class="btn btn-sm btn-outline-warning">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5v.5a.5.5 0 0 1-.5.5H4a.5.5 0 0 1-.5-.5v-.5a.5.5 0 0 1 .146-.354z"/></svg>
                                            </a>
                                            <a href="/users/<?= $user->getId() ?>/delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
