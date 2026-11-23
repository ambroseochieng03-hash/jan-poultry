-- Web push: one row per phone/browser that said yes, plus a send queue.
-- Safe to run more than once.
CREATE TABLE IF NOT EXISTS push_subscriptions (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) DEFAULT NULL,
  guest_id varchar(100) DEFAULT NULL,
  endpoint text NOT NULL,
  endpoint_hash char(64) NOT NULL,
  p256dh varchar(255) NOT NULL,
  auth varchar(255) NOT NULL,
  user_agent varchar(255) DEFAULT NULL,
  failures tinyint(3) unsigned NOT NULL DEFAULT 0,
  created_at timestamp NULL DEFAULT current_timestamp(),
  last_success_at datetime DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_push_endpoint (endpoint_hash),
  KEY idx_push_user (user_id),
  KEY idx_push_guest (guest_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS push_outbox (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) DEFAULT NULL,
  guest_id varchar(100) DEFAULT NULL,
  title varchar(120) NOT NULL,
  body varchar(240) NOT NULL,
  url varchar(200) NOT NULL DEFAULT '/orders',
  tag varchar(60) DEFAULT NULL,
  status enum('pending','done') NOT NULL DEFAULT 'pending',
  created_at timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_push_outbox_status (status, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
