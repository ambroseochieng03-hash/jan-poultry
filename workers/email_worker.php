#!/usr/bin/env php
<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| JAN Poultry email worker (systemd: janpoultry-email-worker.service)
|
| Sends the rows that pages put in email_outbox. A failed send is retried
| after 1, 2, 4, 8 minutes and then marked failed. A row left "sending" by a
| crash is put back after 10 minutes. Gentle on a shared VM: one email at a
| time, sleeps when idle, restarts itself after about an hour.
|--------------------------------------------------------------------------
*/
if (PHP_SAPI !== 'cli') {
    exit(1);
}

chdir(dirname(__DIR__));
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/MailService.php';
require_once __DIR__ . '/../app/services/ReceiptService.php';

const MAX_ATTEMPTS = 5;
const IDLE_SLEEP   = 5;
const MAX_RUNTIME  = 3600;

function wlog(string $level, string $msg, array $ctx = []): void
{
    fwrite(STDOUT, json_encode(['t' => date('c'), 'level' => $level, 'msg' => $msg] + $ctx) . "\n");
}

$db      = (new Database())->getConnection();
$mail    = new MailService();
$started = time();
$stop    = false;

if (function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, static function () use (&$stop): void { $stop = true; });
    pcntl_signal(SIGINT, static function () use (&$stop): void { $stop = true; });
}

wlog('info', 'email worker started');

while (!$stop && (time() - $started) < MAX_RUNTIME) {
    try {
        // Put back rows a crashed worker left half-done.
        $db->exec("UPDATE email_outbox SET status = 'pending' WHERE status = 'sending' AND locked_at < (NOW() - INTERVAL 10 MINUTE)");

        $due = $db->query("SELECT id FROM email_outbox WHERE status = 'pending' AND next_attempt_at <= NOW() ORDER BY id LIMIT 5")
            ->fetchAll(PDO::FETCH_COLUMN);

        if (!$due) {
            sleep(IDLE_SLEEP);
            continue;
        }

        foreach ($due as $id) {
            $claim = $db->prepare("UPDATE email_outbox SET status = 'sending', locked_at = NOW() WHERE id = ? AND status = 'pending'");
            $claim->execute([$id]);
            if ($claim->rowCount() !== 1) {
                continue; // another worker took it
            }

            $row = $db->query('SELECT * FROM email_outbox WHERE id = ' . (int) $id)->fetch(PDO::FETCH_ASSOC);

            $attachment = null;
            if (!empty($row['receipt_order_id'])) {
                try {
                    $attachment = (new ReceiptService())->generate((int) $row['receipt_order_id']);
                } catch (Throwable $e) {
                    wlog('warn', 'receipt failed, sending without it', ['id' => $id, 'error' => $e->getMessage()]);
                }
            }

            $ok = $mail->send((string) $row['to_email'], (string) $row['to_name'], (string) $row['subject'], (string) $row['body'], $attachment);

            if ($ok) {
                $db->prepare("UPDATE email_outbox SET status = 'sent', sent_at = NOW(), locked_at = NULL, last_error = NULL WHERE id = ?")->execute([$id]);
                wlog('info', 'sent', ['id' => $id]);
                continue;
            }

            $attempts = (int) $row['attempts'] + 1;
            if ($attempts >= MAX_ATTEMPTS) {
                $db->prepare("UPDATE email_outbox SET status = 'failed', attempts = ?, locked_at = NULL, last_error = 'send failed, see worker log' WHERE id = ?")->execute([$attempts, $id]);
                wlog('error', 'giving up', ['id' => $id]);
            } else {
                $wait = 60 * (2 ** ($attempts - 1));
                $db->prepare("UPDATE email_outbox SET status = 'pending', attempts = ?, locked_at = NULL, next_attempt_at = NOW() + INTERVAL ? SECOND, last_error = 'send failed, will retry' WHERE id = ?")->execute([$attempts, $wait, $id]);
                wlog('warn', 'will retry', ['id' => $id, 'in_seconds' => $wait]);
            }
            sleep(1); // never hammer the mail server
        }
    } catch (Throwable $e) {
        wlog('error', 'loop error', ['error' => $e->getMessage()]);
        sleep(15);
    }
}

wlog('info', 'email worker exiting (systemd restarts it)');
