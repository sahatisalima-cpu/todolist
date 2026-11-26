<?php
declare(strict_types=1);

// Configuration de la base de données
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'todolist');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');

$taches = [];
$erreur = '';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = isset($_POST['action']) && is_string($_POST['action'])
            ? $_POST['action']
            : '';

        if ($action === 'new') {
            $title = isset($_POST['title']) && is_string($_POST['title'])
                ? trim($_POST['title'])
                : '';

            if ($title !== '') {
                $stmt = $pdo->prepare('INSERT INTO todo (title) VALUES (:title)');
                $stmt->execute(['title' => $title]);
            }
        } elseif ($action === 'delete' || $action === 'toggle') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if ($id !== false && $id !== null && $id > 0) {
                if ($action === 'delete') {
                    $stmt = $pdo->prepare('DELETE FROM todo WHERE id = :id');
                    $stmt->execute(['id' => $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE todo SET done = 1 - done WHERE id = :id');
                    $stmt->execute(['id' => $id]);
                }
            }
        }

        header('Location: index.php');
        exit;
    }

    $taches = $pdo
        ->query('SELECT id, title, done, created_at FROM todo ORDER BY created_at DESC, id DESC')
        ->fetchAll();
} catch (PDOException $e) {
    $erreur = 'Connexion impossible. Vérifie MySQL, la base todolist et les paramètres DB.';
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ma liste de tâches</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Ma ToDo List</a>
        </div>
    </nav>

    <main class="container py-4">
        <h1 class="mb-4">Mes tâches</h1>

        <?php if ($erreur !== ''): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php else: ?>
            <form method="post" class="d-flex gap-2 mb-4">
                <input type="hidden" name="action" value="new">
                <input
                    type="text"
                    name="title"
                    class="form-control"
                    maxlength="2048"
                    placeholder="Écris une nouvelle tâche"
                    required
                >
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>

            <?php if (count($taches) === 0): ?>
                <p class="text-muted">Aucune tâche pour le moment.</p>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($taches as $tache): ?>
                        <?php $terminee = (int) $tache->done === 1; ?>
                        <li class="list-group-item d-flex flex-column flex-sm-row gap-3 justify-content-between align-items-sm-center <?= $terminee ? 'list-group-item-success' : 'list-group-item-warning' ?>">
                            <span class="<?= $terminee ? 'text-decoration-line-through' : '' ?>">
                                <?= htmlspecialchars($tache->title, ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <form method="post" class="d-flex gap-2">
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $tache->id ?>"
                                >
                                <button
                                    type="submit"
                                    name="action"
                                    value="toggle"
                                    class="btn btn-sm btn-outline-success"
                                >
                                    <?= $terminee ? 'À faire' : 'Terminer' ?>
                                </button>
                                <button
                                    type="submit"
                                    name="action"
                                    value="delete"
                                    class="btn btn-sm btn-outline-danger"
                                >
                                    Supprimer
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
