<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/models/Category.php';

Auth::requireAdmin();

$categories = new Category();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!Security::verifyCsrfToken()) {
        $_SESSION['category_flash'] = ['error', 'Your session expired. Please try again.'];
        header('Location: /admin/categories');
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $result = match ($_POST['action'] ?? '') {
        'add'    => $categories->create((string) ($_POST['name'] ?? '')),
        'rename' => $categories->rename($id, (string) ($_POST['name'] ?? '')),
        'toggle' => $categories->toggle($id),
        'delete' => $categories->delete($id),
        default  => [false, 'Unknown action.'],
    };

    $_SESSION['category_flash'] = [$result[0] ? 'success' : 'error', $result[1]];
    header('Location: /admin/categories');
    exit;
}

$flash = $_SESSION['category_flash'] ?? null;
unset($_SESSION['category_flash']);
$rows = $categories->allWithCounts();
$csrf = Security::generateCsrfToken();

$pageCss = 'create-product.css';
include __DIR__ . '/../includes/admin-header.php';
?>
<style>
    .jp-cat { max-width: 760px; }
    .jp-cat h1 { margin: 0 0 4px; }
    .jp-cat p.hint { margin: 0 0 16px; color: #64748b; font-size: 14px; }
    .jp-cat .msg { padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-size: 14px; }
    .jp-cat .msg.success { background: #e7f6ec; color: #14532d; }
    .jp-cat .msg.error { background: #fdecec; color: #7f1d1d; }
    .jp-cat form.add { display: flex; gap: 8px; margin-bottom: 18px; }
    .jp-cat input[type=text] { flex: 1; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; }
    .jp-cat button { padding: 9px 14px; border: 0; border-radius: 8px; cursor: pointer; font-size: 13px; background: #166534; color: #fff; }
    .jp-cat button.ghost { background: #e2e8f0; color: #0f172a; }
    .jp-cat button.danger { background: #b91c1c; }
    .jp-cat table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; }
    .jp-cat th, .jp-cat td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eef2f7; font-size: 14px; vertical-align: middle; }
    .jp-cat td.actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .jp-cat td form { display: inline-flex; gap: 6px; margin: 0; }
    .jp-cat .off { color: #94a3b8; }
</style>
<div class="jp-cat">
    <h1>Categories</h1>
    <p class="hint">Customers filter the home page by these. A category appears there only when it has at least one available product. Hidden categories stay available in the product forms.</p>

    <?php if ($flash): ?>
        <div class="msg <?= $flash[0] === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <form class="add" method="post" action="/admin/categories">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="add">
        <input type="text" name="name" maxlength="100" placeholder="New category name" required>
        <button type="submit">Add category</button>
    </form>

    <table>
        <thead><tr><th>Name</th><th>Products</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td class="<?= $row['is_active'] ? '' : 'off' ?>"><?= htmlspecialchars($row['name']) ?></td>
                <td><?= (int) $row['product_count'] ?></td>
                <td><?= $row['is_active'] ? 'Shown' : 'Hidden' ?></td>
                <td class="actions">
                    <form method="post" action="/admin/categories">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="rename">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <input type="text" name="name" maxlength="100" value="<?= htmlspecialchars($row['name']) ?>" required>
                        <button type="submit" class="ghost">Rename</button>
                    </form>
                    <form method="post" action="/admin/categories">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="ghost"><?= $row['is_active'] ? 'Hide' : 'Show' ?></button>
                    </form>
                    <form method="post" action="/admin/categories" onsubmit="return confirm('Delete this category?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="4">No categories yet. Add your first one above.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
