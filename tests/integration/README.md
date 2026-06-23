# Integration Tests — Setup Guide

These tests use `WP_UnitTestCase` and run against a real WordPress environment.
Three things are required before you can run them: a `wordpress-develop` checkout,
a dedicated empty MySQL database, and a configuration file.

---

## Prerequisites

- PHP 8.x in the system PATH
- Composer installed
- LocalWP (or another local WordPress environment) with MySQL/MariaDB running
- Git

---

## Step 1 — Clone wordpress-develop

The WordPress test framework is not included in this plugin. It must be cloned separately.

```bash
git clone https://github.com/WordPress/wordpress-develop.git C:/WordpressDEV/wordpress-develop
```

The path is not mandatory, but if you use a different one you will need to set
the `WP_TESTS_DIR` environment variable (see Step 4).

---

## Step 2 — Find your MySQL host and port

The connection details depend on how your local environment is set up.

### If you use LocalWP

LocalWP does not use the standard MySQL port 3306. It uses a dynamic port that
varies between installations. To find it:

**Option A — PowerShell**

```powershell
Get-Process mysqld | Select-Object Id | ForEach-Object { netstat -ano | Select-String $_.Id }
```

Look for the `LISTENING` line on `127.0.0.1`. The number after the colon is the
port, for example `127.0.0.1:10028`.

**Option B — From LocalWP**

Open LocalWP → select the site → Database tab → the port is shown in the
connection details.

Use `127.0.0.1:<port>` as `DB_HOST` in `wp-tests-config.php` (Step 4).

### If you use a standard MySQL or MariaDB installation

If MySQL is running as a standard service (XAMPP, MAMP, a system service, or a
remote server), it almost always listens on the default port 3306.

Use `127.0.0.1` or `localhost` as `DB_HOST`. If the server is remote, use its
IP address or hostname directly:

```
define( 'DB_HOST', '127.0.0.1' );        // local standard installation
define( 'DB_HOST', '192.168.1.100' );    // remote server, default port
define( 'DB_HOST', '192.168.1.100:3307' ); // remote server, custom port
```

Your credentials (`DB_USER`, `DB_PASSWORD`) must belong to a MySQL user that
has `CREATE`, `DROP`, `ALTER`, and `SELECT` privileges on the test database.

---

## Step 3 — Create the test database

The test database must be empty and separate from the site database.
The framework drops and recreates all tables on every run.

### If you use LocalWP

Use the LocalWP MySQL binary (adjust the path and port to match your machine):

```bash
"C:/Users/<YOUR_USERNAME>/AppData/Roaming/Local/lightning-services/mariadb-10.4.10+4/bin/win32/bin/mysql.exe" \
  -u root -proot -h 127.0.0.1 -P 10028 \
  -e "CREATE DATABASE IF NOT EXISTS alm_wordpress_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

You can also create it graphically via Adminer (LocalWP → Database → Open Adminer).

### If you use a standard MySQL or MariaDB installation

Use the standard `mysql` client, which is available in your system PATH:

```bash
mysql -u root -p -h 127.0.0.1 \
  -e "CREATE DATABASE IF NOT EXISTS alm_wordpress_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or connect to the server and run the statement interactively:

```sql
CREATE DATABASE IF NOT EXISTS alm_wordpress_test
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

If the user running the tests is not `root`, make sure it has the necessary
privileges on the test database:

```sql
GRANT ALL PRIVILEGES ON alm_wordpress_test.* TO 'youruser'@'localhost';
FLUSH PRIVILEGES;
```

---

## Step 4 — Create wp-tests-config.php

Create the file `wp-tests-config.php` inside the `tests/phpunit/` folder of your
`wordpress-develop` checkout.

**Target path:**
`C:/WordpressDEV/wordpress-develop/tests/phpunit/wp-tests-config.php`

This file is machine-specific and must not be committed to the repository.
Use the template below and fill in your own port and credentials:

```php
<?php
/**
 * WordPress test configuration for ALM integration tests.
 *
 * WARNING: these tests DROP ALL TABLES in the database below on every run.
 * Never point this at a production or shared database.
 */

define( 'ABSPATH', dirname( __DIR__, 2 ) . '/src/' );  // wordpress-develop/src/

define( 'WP_DEFAULT_THEME', 'default' );
define( 'WP_DEBUG', true );

define( 'DB_NAME',     'alm_wordpress_test' );  // database created in Step 3
define( 'DB_USER',     'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST',     '127.0.0.1:10028' );     // port found in Step 2

define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'AUTH_KEY',         'alm-test-auth-key' );
define( 'SECURE_AUTH_KEY',  'alm-test-secure-auth-key' );
define( 'LOGGED_IN_KEY',    'alm-test-logged-in-key' );
define( 'NONCE_KEY',        'alm-test-nonce-key' );
define( 'AUTH_SALT',        'alm-test-auth-salt' );
define( 'SECURE_AUTH_SALT', 'alm-test-secure-auth-salt' );
define( 'LOGGED_IN_SALT',   'alm-test-logged-in-salt' );
define( 'NONCE_SALT',       'alm-test-nonce-salt' );

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL',  'admin@example.org' );
define( 'WP_TESTS_TITLE',  'ALM Test Blog' );

define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
```

---

## Step 5 — (Optional) WP_TESTS_DIR environment variable

The plugin bootstrap (`tests/bootstrap-integration.php`) reads the `WP_TESTS_DIR`
environment variable to locate the test framework. If you cloned `wordpress-develop`
to the default path (`C:/WordpressDEV/wordpress-develop`) you do not need to set it.

If you used a different path, set the variable before running tests:

```powershell
# PowerShell
$env:WP_TESTS_DIR = "C:/your/path/wordpress-develop/tests/phpunit"
composer test:integration
```

```bash
# Bash
WP_TESTS_DIR="C:/your/path/wordpress-develop/tests/phpunit" composer test:integration
```

---

## Running the tests

```bash
composer test:integration
```

Expected output on first run (WordPress installs its tables into the test database):

```
Installing...
Running as single site...
PHPUnit 9.6.34 by Sebastian Bergmann and contributors.
......
OK (6 tests, 13 assertions)
```

---

## How it works internally

1. `composer test:integration` runs PHPUnit with `phpunit-integration.xml`.
2. PHPUnit loads `tests/bootstrap-integration.php`.
3. The bootstrap locates the WordPress framework via `WP_TESTS_DIR` and registers
   the plugin through the `muplugins_loaded` filter.
4. The framework installs WordPress into the test database (tables prefixed with
   `wptests_`) and boots the environment.
5. Each test class extends `WP_UnitTestCase`, which rolls back database changes
   between tests automatically.
6. All test data is cleaned up when the suite finishes.

---

## Keeping wordpress-develop up to date

`wordpress-develop` is a Git repository and should be updated with `git pull`.
In practice you rarely need to do this urgently — the test framework is very
stable and almost never changes in a breaking way.

Update it in these two situations:

- **When you update WordPress in your local site.** If LocalWP moves from WP 6.4
  to WP 6.7, align `wordpress-develop` to the same version so your integration
  tests run against the WordPress version the plugin will actually ship with.

- **When an integration test suddenly fails with no code changes on your side.**
  This may indicate an incompatibility between the framework and your current PHP
  or WordPress version.

To update:

```bash
cd C:/WordpressDEV/wordpress-develop
git pull
```

To check which version you currently have:

```bash
cd C:/WordpressDEV/wordpress-develop
git describe --tags
```

---

## Common errors

**`Error establishing a database connection`**
The port in `wp-tests-config.php` is wrong or LocalWP is not running.
Verify the port using Step 2 and make sure the LocalWP site is started.

**`WordPress test library not found`**
`WP_TESTS_DIR` points to a non-existent path or `wordpress-develop` has not been
cloned. Check the path and repeat Step 1 if needed.

**`wp-tests-config.php is missing`**
The file was not created or is in the wrong location. It must be at
`wordpress-develop/tests/phpunit/wp-tests-config.php`.

**Tests are modifying the production database**
`DB_NAME` in `wp-tests-config.php` points to the wrong database. Make sure it
is `alm_wordpress_test` and not `local`.
