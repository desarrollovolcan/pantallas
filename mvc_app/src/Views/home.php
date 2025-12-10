<div class="grid">
    <div class="card">
        <p class="muted">Usuarios</p>
        <h2><?= (int) $userCount ?></h2>
        <span class="badge">Tabla users</span>
    </div>
    <div class="card">
        <p class="muted">Pantallas</p>
        <h2><?= (int) $screenCount ?></h2>
        <span class="badge">Tabla screens</span>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Actividad reciente</h3>
    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Email</th>
                <th>Creado</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recentUsers as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td class="muted"><?= htmlspecialchars($user['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0;">Pantallas cargadas</h3>
    <table>
        <thead>
            <tr>
                <th>Título</th>
                <th>Contenido</th>
                <th>Publicación</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recentScreens as $screen): ?>
            <tr>
                <td><?= htmlspecialchars($screen['title']) ?></td>
                <td><?= htmlspecialchars($screen['content']) ?></td>
                <td class="muted"><?= htmlspecialchars($screen['published_at'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
