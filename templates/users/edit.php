<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-sm">
                <div class="card-header bg-warning py-3">
                    <h5 class="mb-0">Edit User #<?= $user->getId() ?></h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="/users/<?= $user->getId() ?>/edit">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user->getEmail()) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password <small class="text-muted">(leave blank to keep current)</small></label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Roles</label>
                            <div class="card border p-3">
                                <?php $roles = $user->getRoles() ?: []; ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="ROLE_USER" id="role_user" <?= in_array('ROLE_USER', $roles) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="role_user">ROLE_USER</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="ROLE_ADMIN" id="role_admin" <?= in_array('ROLE_ADMIN', $roles) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="role_admin">ROLE_ADMIN</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="ROLE_EDITOR" id="role_editor" <?= in_array('ROLE_EDITOR', $roles) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="role_editor">ROLE_EDITOR</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" class="btn btn-warning">Update User</button>
                            <a href="/users" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
