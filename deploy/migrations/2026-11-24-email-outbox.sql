-- Outgoing email queue. Pages only insert a row here; workers/email_worker.php
-- sends it, retries with back-off, and keeps a record of what happened.
-- Safe to run more than once.
CREATE TABLE IF NOT EXISTS email_outbox (
  id int(11) NOT NULL AUTO_INCREMENT,
  dedupe_key varchar(120) DEFAULT NULL,
  to_email varchar(190) NOT NULL,
  to_name varchar(150) NOT NULL DEFAULT '',
  subject varchar(255) NOT NULL,
  body mediumtext NOT NULL,
  receipt_order_id int(11) DEFAULT NULL,
  status enum('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
  attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
  next_attempt_at datetime NOT NULL DEFAULT current_timestamp(),
  locked_at datetime DEFAULT NULL,
  last_error varchar(255) DEFAULT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  sent_at datetime DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email_outbox_dedupe (dedupe_key),
  KEY idx_email_outbox_due (status, next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
