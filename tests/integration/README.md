# Integration Tests

Integration tests use `WP_UnitTestCase` with a real WordPress Test Suite and a dedicated MySQL/MariaDB database. Use this lane for hooks, persistence, permissions, REST behaviour, and loan-workflow transitions.

For common commands, safety rules, and test-lane selection, see the [test guide](../README.md).

## Required environment

You need PHP and Composer, a local `wordpress-develop` checkout, an empty disposable database, and a machine-local `wp-tests-config.php` file.

The default WordPress Test Suite location is `C:/WordpressDEV/wordpress-develop/tests/phpunit`. Set `WP_TESTS_DIR` when your checkout uses a different location:

```bash
WP_TESTS_DIR="C:/your/path/wordpress-develop/tests/phpunit" composer test:integration
```

## Set up WordPress Test Suite

Clone `wordpress-develop` outside this repository:

```bash
git clone https://github.com/WordPress/wordpress-develop.git C:/WordpressDEV/wordpress-develop
```

Create an empty database dedicated exclusively to tests. The test framework creates and changes tables in it; never use a site, shared, or production database.

Create this uncommitted file:

`C:/WordpressDEV/wordpress-develop/tests/phpunit/wp-tests-config.php`

```php
<?php
define( 'ABSPATH', dirname( __DIR__, 2 ) . '/src/' );
define( 'WP_DEFAULT_THEME', 'default' );
define( 'WP_DEBUG', true );

define( 'DB_NAME', 'alm_wordpress_test' );
define( 'DB_USER', 'your_test_user' );
define( 'DB_PASSWORD', 'your_test_password' );
define( 'DB_HOST', '127.0.0.1:your_port' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';
```

For LocalWP, read the site's Database tab to obtain its current port. If Local recreates the site, verify the port again.

## Run and troubleshoot

```bash
composer test:integration
```

The command uses `phpunit-integration.xml` and `tests/bootstrap-integration.php`. If WordPress Test Suite cannot be found, check `WP_TESTS_DIR` and the location of `wp-tests-config.php`. A database connection failure usually means LocalWP is stopped or `DB_HOST` has the wrong port.

Update the external `wordpress-develop` checkout only when you intentionally align it with a WordPress version under test or investigate an environment-level failure.
