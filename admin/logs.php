<?php

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../app/helpers/Auth.php';

Auth::requireAdmin();

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_log_id'])
) {

    try {

        $logId = (int) $_POST['delete_log_id'];

        $deleteStmt = $pdo->prepare("
            DELETE FROM admin_logs
            WHERE id = ?
            LIMIT 1
        ");

        $deleteStmt->execute([$logId]);

    } catch (Throwable $e) {

        jan_log('DELETE_ERROR', $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);

    }

    header('Location: /admin/logs');
    exit;
}

$pdo = (new Database())->getConnection();

$stmt = $pdo->query("
    SELECT
        al.*,
        u.name
    FROM admin_logs al
    LEFT JOIN users u ON al.admin_id = u.id
    ORDER BY al.id DESC
    LIMIT 500
");

$logs = $stmt->fetchAll();

$pageCss =
'logs.css';

include __DIR__ . '/../includes/admin-header.php';

?>

<div class="jan-logs">

    <div class="jan-logs__header">

        <div>
            <h1>System Logs</h1>
            <p>Application errors, warnings, and system events</p>
        </div>

    </div>

    <div class="jan-logs__container">

        <?php if (empty($logs)): ?>

            <div class="jan-logs__empty">
                No logs found.
            </div>

        <?php else: ?>

            <?php foreach ($logs as $log): ?>

                <?php
                    $level = strtolower($log['action'] ?? 'info');
                ?>

                <div class="jan-log-card jan-log-card--<?= $level ?>">

                    <div class="jan-log-card__top">

                        <span class="jan-log-card__badge">
                            <?= strtoupper($level) ?>
                        </span>

                        <div class="jan-log-card__top-right">

                            <span class="jan-log-card__time">
                                <?= date('d M Y H:i', strtotime($log['created_at'])) ?>
                            </span>

                            <form method="POST" class="jan-log-delete-form">

                                <input type="hidden" name="delete_log_id" value="<?= $log['id'] ?>">

                                <button
                                    type="submit"
                                    class="jan-log-delete-btn"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    </div>

                    <div class="jan-log-card__message">
                        <?= htmlspecialchars($log['action']) ?>
                    </div>

                    <div class="jan-log-card__meta">

                        <span>
                            Admin: <?= htmlspecialchars($log['name'] ?? 'System') ?>
                        </span>

                        <span>
                            ID: #<?= $log['id'] ?>
                        </span>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>