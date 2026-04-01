<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Oryx ORM</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 500px; margin: 0 auto; padding: 2rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.25rem; font-weight: bold; }
        input, select { width: 100%; padding: 0.5rem; box-sizing: border-box; }
        button { background: #0066cc; color: white; padding: 0.75rem 1.5rem; border: none; cursor: pointer; }
        button:hover { background: #0055aa; }
    </style>
</head>
<body>
    <h1>Edit User</h1>
    
    <form method="POST" action="/users/<?= $user->getId() ?>/edit">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user->getEmail()) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password (leave blank to keep current):</label>
            <input type="password" id="password" name="password">
        </div>
        
        <div class="form-group">
            <label for="roles">Roles:</label>
            <select id="roles" name="roles[]" multiple>
                <?php $roles = $user->getRoles() ?: []; ?>
                <option value="ROLE_USER" <?= in_array('ROLE_USER', $roles) ? 'selected' : '' ?>>ROLE_USER</option>
                <option value="ROLE_ADMIN" <?= in_array('ROLE_ADMIN', $roles) ? 'selected' : '' ?>>ROLE_ADMIN</option>
                <option value="ROLE_EDITOR" <?= in_array('ROLE_EDITOR', $roles) ? 'selected' : '' ?>>ROLE_EDITOR</option>
            </select>
        </div>
        
        <button type="submit">Update User</button>
        <a href="/users">Cancel</a>
    </form>
</body>
</html>
