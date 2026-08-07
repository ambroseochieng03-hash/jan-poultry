# JAN Poultry

A secure poultry management and e-commerce system built with PHP, MariaDB, and JavaScript.

---

## Overview

JAN Poultry is a personal software engineering project developed to explore the design of a secure, maintainable, and business-oriented e-commerce application.

Rather than focusing only on selling products, the project emphasizes system architecture, inventory consistency, secure authentication, transactional order processing, and clean separation of responsibilities across the application.

The system follows a layered architecture that separates presentation, business logic, and data access, making the codebase easier to maintain, extend, and understand.

---

## Features

* Customer and guest shopping
* Product and inventory management
* Shopping cart and checkout
* Order lifecycle management
* Receipt generation
* Email notifications
* Role-based access control
* Secure media uploads
* Image optimization and video processing
* Administrative dashboard

---

## Security

Security is integrated throughout the application.

Implemented protections include:

* Argon2id password hashing
* CSRF protection
* Progressive login lockout
* Secure session management
* Session ID regeneration
* Role-based authorization
* Trusted server-side session tokens
* Browser and lightweight IP fingerprint validation
* Secure file upload validation
* MIME type verification
* Cryptographically secure random token generation

---

## Inventory & Order Integrity

The application maintains inventory consistency using transactional workflows.

Key implementation details include:

* Database transactions
* Row-level locking (`SELECT ... FOR UPDATE`)
* Atomic stock deduction
* Automatic stock restoration on order cancellation
* Controlled order state transitions
* Protection against race conditions

---

## Architecture

```
Client
   │
   ▼
Controllers
   │
   ▼
Services
   │
   ▼
Models
   │
   ▼
MariaDB
```

Business logic is implemented within the service layer while models remain responsible for database interaction. This keeps application logic separate from presentation and database access.

---

## Technology Stack

* PHP 8
* MariaDB
* PDO
* Apache
* HTML5
* CSS3
* JavaScript
* FFmpeg

---

## Project Goals

This project was developed to gain practical experience in:

* Software architecture
* Authentication and authorization
* Secure session handling
* Inventory management
* Transaction processing
* Secure file handling
* Business rule implementation
* Database consistency
* Backend development using native PHP

---

## Repository

```bash
git clone https://github.com/ambroseochieng03-hash/jan-poultry.git
```

---

## Future Improvements

* REST API
* Payment gateway integration
* Reporting and analytics
* Docker support
* Automated testing
* Mobile-friendly improvements

---

## Author

**Ambitious**

**Real Name:** Ambrose Onyango

GitHub: https://github.com/ambroseochieng03-hash

---

## Copyright

Copyright © 2026 Ambrose Onyango (Ambitious).

This project is an original work created by the author.

The source code is provided for learning, reference, and portfolio purposes. You may study the implementation, but you may not present this project or substantial portions of it as your own work. If you wish to reuse significant parts of the project, please obtain permission from the author or follow the terms of the project's license.

---

## License

This repository currently does not include an open-source license.

All rights reserved unless otherwise stated.
