<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Groups</h1>
        <a href="/groups/create" class="btn btn-primary">Create Group</a>
    </div>

    <?php if (empty($groups)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <p class="text-muted mb-0">No groups yet. Create your first group!</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                    <tr>
                        <td><?= $group->getId() ?></td>
                        <td><?= htmlspecialchars($group->getName()) ?></td>
                        <td><?= $group->getCreatedAt()->format('Y-m-d H:i') ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/groups/<?= $group->getId() ?>" class="btn btn-outline-primary">View</a>
                                <a href="/groups/<?= $group->getId() ?>/edit" class="btn btn-outline-secondary">Edit</a>
                                <a href="/groups/<?= $group->getId() ?>/delete" class="btn btn-outline-danger" onclick="return confirm('Delete this group?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
