# Clothing Store E‑Commerce Platform

A lightweight PHP/MySQL e‑commerce application for a clothing store. Supports product catalog, cart & checkout, coupons, wallet (store credit), loyalty points logic (described), returns management, and an admin dashboard with notifications.

---
## 1. Features Overview

### Storefront
- Responsive catalog with categories (Men, Women, Kids, Accessories, etc.)
- Search with auto suggestions (`ajax/search-suggestions.php`)
- Product variants (size/color) & primary images
- Wishlist & Cart (session for guests, persistent for logged users)
- Checkout with: coupons (percentage / fixed, min purchase), wallet usage, COD exclusion for coupons
- Dynamic order column handling (works whether coupon columns exist or not)
- Order tracking & return requests
- Loyalty points (logic documented; not fully persisted yet)
- Help page (`help.php`) explaining site usage & loyalty program

### Admin Panel (`/admin`)
- Dashboard with notification bell (orders needing attention, returns, new users, subscribers, cancellations, paid orders)
- Product CRUD & image management
- Category management
- Coupon management: create, update, delete, usage tracking
- Order status updating (delivered triggers loyalty coupon email logic)
- Return request handling & refund processing
- Basic analytics placeholder

### Security & UX Enhancements
- Login throttle: locks user for 60s after 5 failed attempts (`login.php`) + alert email
- Soft delete support for users (guard in `is_logged_in()`)
- Session cookie scope set app‑wide (`config.php`)
- ReCAPTCHA support on customer login (configurable via constants)

---
## 2. Tech Stack
- PHP 8.2 (procedural + mysqli)
- MySQL / MariaDB (sample schema: `clothing.sql`)
- Font Awesome 6 for icons
- Basic vanilla JS for UI (dropdowns, notifications polling, coupon apply/remove)
- PHPMailer (available in `PHPMailer-master/`, not yet deeply integrated except placeholder `send_email()` wrapper)

---
## 3. Folder Structure
```
clothing/
  about.php, index.php, login.php, ... (storefront pages)
  help.php                # Documentation UI for end users
  config.php              # DB connection + helper functions
  clothing.sql            # Schema + seed sample data
  assets/                 # CSS, JS, images
  ajax/                   # Storefront async endpoints
  admin/                  # Admin panel (includes/, ajax/, migrations/)
  uploads/                # Uploaded product & user images
  vendor/                 # Third‑party libs (e.g., phpmailer)
```

Key runtime helpers for availability:
- `send_email($to,$subject,$html)` – basic mail() wrapper (HTML capable)
- `is_logged_in()` – checks session & soft delete
- `format_currency()` / `format_price()` – consistent currency output

---
## 4. Prerequisites
| Component | Version / Notes |
|-----------|-----------------|
| PHP       | 8.0+ (8.2 recommended) with `mysqli`, `json`, `curl` extensions |
| MySQL     | 5.7+ or MariaDB 10.x |
| Web Server| Apache (recommended) or Nginx (adjust document root to `/clothing`) |
| Composer  | Optional for advanced PHPMailer usage |

Windows (XAMPP) quick start works out‑of‑the‑box.

---
## 5. Initial Setup (Local)
1. Clone or copy repository into your web root (e.g., `c:/xampp/htdocs/clothing`).
2. Create database:
   - Open phpMyAdmin or connect via CLI.
   - Create a database named `clothing`.
   - Import `clothing.sql`.
3. Adjust DB port if needed: in `config.php` change `DB_PORT` (default `3308` in sample).
4. Ensure `storage/` directory (created automatically by login throttle) is writable.
5. Visit `http://localhost/clothing/index.php`.
6. Admin login: `admin@clothingstore.com / admin123` (change immediately; password not hashed yet!).

---
## 6. Configuration (`config.php`)
- Update DB constants: `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`.
- Currency settings: `CURRENCY_CODE`, `CURRENCY_SYMBOL`, `TAX_RATE`, `SHIPPING_FEE`, `FREE_SHIPPING_THRESHOLD`.
- ReCAPTCHA (optional): override via environment or set new keys.
- Timezone: defaults to `Asia/Yangon` – adjust if deploying elsewhere.

### Email
Currently uses PHP `mail()` in `send_email()`. For production:
- Integrate PHPMailer (already present) and set SMTP credentials.
- Replace `send_email()` implementation with PHPMailer usage.

Example PHPMailer quick replacement sketch:
```php
function send_email($to,$subject,$html){
  $mail = new PHPMailer(true);
  // SMTP config ...
  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body = $html;
  $mail->addAddress($to);
  return $mail->send();
}
```

---
## 7. Loyalty Points (Logic Only)
Not fully persisted yet; page `help.php` describes expected behavior:
- Earn 1 point per $1 post‑discount (excludes shipping & tax), credited after delivery.
- Redeem 100 points = $1 when >=500 points, capped at 20% subtotal.
- Points expire after 12 months.
To implement persistence, add a `loyalty_points` table and update order completion logic.

Example table:
```sql
CREATE TABLE loyalty_points (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  points INT NOT NULL,
  source ENUM('earn','redeem') NOT NULL,
  order_id INT NULL,
  expires_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

---
## 8. Coupons
- Stored in `coupons` table (see schema).
- Applied via `ajax/apply-coupon.php`.
- Removal via `ajax/remove-coupon.php`.
- Supports min purchase, usage limit, percentage with cap, or fixed amount.
- Disallows usage with COD based on session logic.

### Adding new coupon types
Extend validation logic in apply endpoint; ensure discount math & order insertion reflect new columns if added.

---
## 9. Wallet (Store Credit)
- Deducts on checkout when selected.
- Uses transactions for atomic balance updates.
- For debugging: log queries or temporarily echo `$conn->error` after statements.

---
## 10. Login Throttle & Security
- File based store: `storage/login_throttle.json`.
- Lock after 5 failures for 60s; alert email sent once per lock event.
- Improve by moving to DB table for multi‑server scaling.

### Hardening Suggestions
| Area | Suggestion |
|------|------------|
| Admin passwords | Hash with `password_hash()` instead of plain text |
| CSRF | Add tokens to forms (checkout, profile updates) |
| XSS | Audit echo points & enforce `htmlspecialchars()` (already used in many) |
| Sessions | Consider `session_regenerate_id()` more broadly |

---
## 11. Migrations
`admin/migrations/` contains incremental changes (e.g., soft delete support, FK updates). Apply manually or write a small runner.

Order:
1. `20251102_add_soft_delete_users.php`
2. `20251103_update_orders_fk_set_null.php`

Review each file and run queries in MySQL before deploying.

---
## 12. Customization
| Goal | Where |
|------|-------|
| Change logo/icon color | `assets/css/style.css` (`.logo a { color: #f53d2d; }`) |
| Add new category | Insert into `categories` table (slug required) |
| Adjust tax/shipping | Constants in `config.php` |
| Add payment gateway | Extend `checkout.php` and add server integration |
| Add REST API | Create `api/` endpoints returning JSON |

---
## 13. Deployment (Another Device / Laptop)
1. Install PHP + MySQL (XAMPP recommended).
2. Copy entire `clothing/` folder to web root (preserving structure).
3. Import `clothing.sql` into a new `clothing` database.
4. Update `config.php` DB credentials/port.
5. Ensure write permission for:
   - `uploads/`
   - `storage/`
6. Test storefront: `http://localhost/clothing/`.
7. Test admin: `http://localhost/clothing/admin/login.php`.
8. Change admin password immediately (and implement hashing).
9. Optional: Set up SMTP & upgrade `send_email()`.

### Environment Differences
- If running under a subfolder different from `/clothing`, `SITE_URL` logic in `config.php` still builds host + `/clothing`. Adjust manually if renamed.
- If using Nginx: map location `/clothing` to document root; enable PHP‑FPM.

---
## 14. Troubleshooting
| Issue | Cause | Fix |
|-------|-------|-----|
| Blank page | PHP fatal error | Check Apache/PHP error log; enable `display_errors` during dev |
| Emails not sending | mail() blocked | Switch to PHPMailer SMTP |
| Coupon not applied | Missing columns | Ensure `orders` table includes `coupon_id`, `discount_amount` or rely on schema‑agnostic code |
| Login throttle not resetting | Corrupt JSON | Delete `storage/login_throttle.json` to reset |
| Images not showing | Wrong path | Verify uploads path & permissions |
| Session not persisting | Cookie path mismatch | Confirm session cookie path in `config.php` matches application base |

---
## 15. Roadmap / Suggested Improvements
- Replace procedural blocks with a minimal MVC or class‑based services
- Centralize validation & error handling
- Implement loyalty points persistence & redemption UI
- Introduce hashed passwords in `admins` table
- Add unit/integration tests (PHPUnit)
- Queue / worker for sending emails & processing refunds
- Add internationalization layer (already partially via Google Translate)

---
## 16. License
Internal project (no explicit OSS license given). Add a LICENSE file if distributing publicly.

---
## 17. Credits
- Font Awesome
- PHPMailer
- Inspiration from common modern clothing storefront layouts

---
## 18. Quick Reference Commands (Optional)
```bash
# Import database (CLI example)
mysql -u root -p clothing < clothing.sql

# Check PHP version
php -v

# Permissions (Linux/macOS)
chmod -R 775 uploads storage
```

---
## 19. Security Disclaimer
This project is a learning / prototype system. Before production harden:
- Hash all passwords (users + admins)
- Enforce HTTPS & secure cookies
- Add CSRF tokens
- Sanitize all SQL inputs (prepared statements mostly used already)
- Implement rate limiting at server/proxy level

---
Happy building! Reach out via `contact.php` for feedback integration.
