CREATE DATABASE jan_poultry;

USE jan_poultry;



CREATE TABLE users (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100),

    email VARCHAR(150) UNIQUE,

    phone VARCHAR(30),

    password_hash VARCHAR(255),

    role ENUM('customer','admin','delivery','staff') DEFAULT 'customer',

    status ENUM('active','suspended','deleted') DEFAULT 'active',

    is_guest BOOLEAN DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);



CREATE TABLE products (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150),

    description TEXT,

    price DECIMAL(10,2),

    image VARCHAR(255),

    category VARCHAR(100),

    is_available BOOLEAN DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);



CREATE TABLE orders (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    guest_id VARCHAR(100) NULL,

    status ENUM('pending','in_transit','complete') DEFAULT 'pending',

    total_price DECIMAL(10,2),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);



CREATE TABLE order_items (

    id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT,

    product_id INT,

    quantity INT,

    price_snapshot DECIMAL(10,2)

);



CREATE TABLE password_resets (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    token_hash CHAR(64),

    expires_at DATETIME,

    used BOOLEAN DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);



CREATE TABLE remember_tokens (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    selector CHAR(24) NOT NULL,

    token_hash CHAR(64) NOT NULL,

    expires_at DATETIME NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,



    UNIQUE(selector),



    FOREIGN KEY (user_id)

    REFERENCES users(id)

    ON DELETE CASCADE

);



CREATE TABLE admin_logs (

    id INT AUTO_INCREMENT PRIMARY KEY,

    admin_id INT NOT NULL,

    action VARCHAR(255) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,



    FOREIGN KEY (admin_id)

    REFERENCES users(id)

    ON DELETE CASCADE

);



CREATE TABLE carts (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    guest_id VARCHAR(100) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);



CREATE TABLE cart_items (

    id INT AUTO_INCREMENT PRIMARY KEY,

    cart_id INT,

    product_id INT,

    quantity INT DEFAULT 1,



    FOREIGN KEY (cart_id)

    REFERENCES carts(id)

    ON DELETE CASCADE,



    FOREIGN KEY (product_id)

    REFERENCES products(id)

    ON DELETE CASCADE

);



CREATE TABLE addresses (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    county VARCHAR(100),

    town VARCHAR(100),

    address TEXT,

    phone VARCHAR(30),



    FOREIGN KEY (user_id)

    REFERENCES users(id)

    ON DELETE CASCADE

);



CREATE TABLE notifications (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    guest_id VARCHAR(100) NULL,

    order_id INT NULL,

    title VARCHAR(255) NOT NULL,

    message TEXT NOT NULL,

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP,

    INDEX(user_id),

    INDEX(guest_id),

    INDEX(order_id)

);

ALTER TABLE notifications
ADD COLUMN type VARCHAR(50) NULL AFTER order_id,
ADD COLUMN expires_at DATETIME NULL AFTER created_at;


CREATE TABLE contact_messages (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150),

    email VARCHAR(150),

    subject VARCHAR(255),

    message TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);



CREATE TABLE testimonials (

    id INT AUTO_INCREMENT PRIMARY KEY,

    customer_name VARCHAR(150),

    testimonial TEXT,

    is_approved BOOLEAN DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE,
    setting_value TEXT
);

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'JAN Poultry'),
('currency_mode', 'BOTH'),
('maintenance_mode', '0'),
('contact_email', 'admin@janpoultry.com');

CREATE TABLE login_attempts (

    id INT AUTO_INCREMENT PRIMARY KEY,

    email VARCHAR(150) NOT NULL,

    ip_address VARCHAR(45) NOT NULL,

    failed_attempts INT NOT NULL DEFAULT 0,

    locked_until DATETIME DEFAULT NULL,

    last_attempt DATETIME DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),

    INDEX idx_ip (ip_address),

    INDEX idx_locked_until (locked_until)

);

ALTER TABLE login_attempts
ADD COLUMN lock_level INT NOT NULL DEFAULT 0
AFTER failed_attempts;


ALTER TABLE orders

ADD CONSTRAINT fk_orders_user

FOREIGN KEY (user_id)

REFERENCES users(id)

ON DELETE SET NULL;



ALTER TABLE order_items

ADD CONSTRAINT fk_order_items_order

FOREIGN KEY (order_id)

REFERENCES orders(id)

ON DELETE CASCADE;



ALTER TABLE order_items

ADD CONSTRAINT fk_order_items_product

FOREIGN KEY (product_id)

REFERENCES products(id)

ON DELETE CASCADE;



ALTER TABLE password_resets

ADD CONSTRAINT fk_password_resets_user

FOREIGN KEY (user_id)

REFERENCES users(id)

ON DELETE CASCADE;



ALTER TABLE users

ADD COLUMN last_login DATETIME NULL AFTER is_guest,

ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;



CREATE INDEX idx_users_email

ON users(email);



CREATE INDEX idx_products_available

ON products(is_available);



CREATE INDEX idx_orders_status

ON orders(status);



CREATE INDEX idx_password_resets_hash

ON password_resets(token_hash);

ALTER TABLE users
MODIFY role ENUM(
'customer',
'admin',
'delivery',
'staff',
'super_admin'
)
DEFAULT 'customer';

ALTER TABLE products
ADD COLUMN stock_quantity INT DEFAULT 0;

ALTER TABLE products
ADD COLUMN sku VARCHAR(100) NULL;

ALTER TABLE products
ADD COLUMN updated_at TIMESTAMP NULL
DEFAULT NULL
ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE products
ADD COLUMN product_type ENUM(
    'live_chicken',
    'meat'
) NOT NULL;

ALTER TABLE products
ADD COLUMN breed VARCHAR(100) NULL;

ALTER TABLE products
ADD COLUMN age_weeks INT NULL;

ALTER TABLE products
ADD COLUMN weight_kg DECIMAL(5,2) NULL;

ALTER TABLE products
ADD COLUMN updated_at TIMESTAMP NULL
DEFAULT NULL
ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE orders
MODIFY status ENUM(
    'pending',
    'in_transit',
    'complete',
    'cancelled'
)
DEFAULT 'pending';

ALTER TABLE cart_items
ADD UNIQUE (
    cart_id,
    product_id
);

ALTER TABLE cart_items
MODIFY quantity INT NOT NULL DEFAULT 1;

ALTER TABLE orders
ADD COLUMN customer_name VARCHAR(150) NULL;

ALTER TABLE orders
ADD COLUMN customer_email VARCHAR(150) NULL;

ALTER TABLE orders
ADD COLUMN customer_phone VARCHAR(30) NULL;

ALTER TABLE orders
ADD COLUMN delivery_address TEXT NULL;

ALTER TABLE orders
ADD COLUMN completed_at DATETIME NULL;

ALTER TABLE orders
MODIFY status ENUM(
    'pending',
    'accepted',
    'in_transit',
    'completed',
    'rejected',
    'cancelled'
)
DEFAULT 'pending';

ALTER TABLE orders

ADD COLUMN accepted_at DATETIME NULL
AFTER delivery_address,

ADD COLUMN in_transit_at DATETIME NULL
AFTER accepted_at;

ALTER TABLE orders

ADD COLUMN customer_name VARCHAR(150) NULL
AFTER guest_id,

ADD COLUMN customer_phone VARCHAR(50) NULL
AFTER customer_email,

ADD COLUMN delivery_address TEXT NULL
AFTER customer_phone,

ADD COLUMN approved_at DATETIME NULL
AFTER delivery_address,

ADD COLUMN in_transit_at DATETIME NULL
AFTER approved_at,

ADD COLUMN completed_at DATETIME NULL
AFTER in_transit_at,

ADD COLUMN cancelled_at DATETIME NULL
AFTER completed_at,

ADD COLUMN rejected_at DATETIME NULL
AFTER cancelled_at;

ALTER TABLE users
ADD COLUMN session_token CHAR(64) NULL
AFTER last_login;

ALTER TABLE orders
ADD cancelled_at DATETIME NULL;

CREATE INDEX idx_cart_user
ON carts(user_id);

CREATE INDEX idx_cart_guest
ON carts(guest_id);

CREATE INDEX idx_cart_items_cart
ON cart_items(cart_id);

CREATE INDEX idx_cart_items_product
ON cart_items(product_id);