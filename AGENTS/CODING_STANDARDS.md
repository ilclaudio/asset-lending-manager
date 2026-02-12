# CODING_STANDARDS.md

## General Guidelines

Write code in compliance with the official WordPress plugin and theme guidelines.

## Code Quality Priorities

Pay particular attention to the following aspects when writing code:

- **Absence of bugs:** Write defensive code and handle edge cases
- **Absence of vulnerabilities:** Maximum security, sanitize all inputs, escape all outputs
- **Responsive pages:** Mobile-first design approach
- **Page accessibility:** This point is very important - follow WCAG guidelines
- **Compliance with WordPress best practices:** Follow WordPress Coding Standards
- **Simple, readable, and modular code:** Write code that is easy to understand and maintain

## WordPress Coding Standards

### Formatting Rules

**Use tabs, not spaces**
Indentation must use tabs, not spaces.

**Comments in English**
- Place comments in English at the beginning of each file and before every function
- Comments must end with a period "."

**File Headers**
Each file should have a descriptive header comment:
```php
/**
 * Brief description of the file.
 *
 * Longer description if needed.
 *
 * @package AssetLendingManager
 */
```

**Function Documentation**
Every function must have a docblock:
```php
/**
 * Brief description of what the function does.
 *
 * Longer description if needed.
 *
 * @param string $param1 Description of parameter.
 * @param int    $param2 Description of parameter.
 * @return mixed Description of return value.
 */
function my_function( $param1, $param2 ) {
	// Function body.
}
```

### Naming Conventions

Use WordPress naming conventions:

**Classes:** `Class_Name_With_Underscores`
```php
class ALM_Asset_Manager {
	// Class body.
}
```

**Files:** `file-name-with-hyphens.php`

**Variables:** `$variable_name_with_underscores`
```php
$post_id = get_the_ID();
$user_name = get_user_name();
```

**Constants:** `CONSTANT_NAME_UPPERCASE`
```php
define( 'THEME_VERSION', '1.0.0' );
```

**Functions:** `function_name_with_underscores()`
```php
function alm_get_asset_field( $post, $field_name ) {
	// Function body.
}
```

### Assignment Alignment

**Align consecutive assignments** using spaces (not tabs) so the `=` signs line up:

```php
$items   = array();
$options = get_option( 'polylang' );
```

**Align related assignments** in longer blocks:
```php
$asset_type       = alm_get_asset_field( $post, 'type' );
$archive_page_obj = alm_get_archive_page( ALM_ASSET_POST_TYPE );
$archive_page     = $archive_page_obj ? get_permalink( $archive_page_obj->ID ) : '';
```

### Security Best Practices

**Always escape output:**
```php
// For HTML content
echo esc_html( $variable );

// For attributes
echo '<div class="' . esc_attr( $class ) . '">';

// For URLs
echo '<a href="' . esc_url( $url ) . '">';

// For translation with HTML
echo wp_kses_post( $content );
```

**Always sanitize input:**
```php
// Text input
$text = sanitize_text_field( $_POST['text'] );

// Email
$email = sanitize_email( $_POST['email'] );

// URL
$url = esc_url_raw( $_POST['url'] );
```

**Use nonces for forms:**
```php
// Creating nonce
wp_nonce_field( 'my_action_name', 'my_nonce_field' );

// Verifying nonce
if ( ! wp_verify_nonce( $_POST['my_nonce_field'], 'my_action_name' ) ) {
	wp_die( 'Security check failed' );
}
```

**Check user capabilities:**
```php
if ( ! current_user_can( 'edit_posts' ) ) {
	wp_die( 'You do not have permission to perform this action.' );
}
```

### Code Organization

**Keep functions focused:** Each function should do one thing well.

**Use early returns:** Reduce nesting by returning early when conditions aren't met.

```php
function my_function( $post_id ) {
	if ( ! $post_id ) {
		return false;
	}
	
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return false;
	}
	
	// Main logic here.
}
```

**Avoid deep nesting:** Keep nesting to 3-4 levels maximum.

**Extract complex conditions:** Use descriptive variable names for complex conditions.

```php
$is_valid_post = $post && $post->post_status === 'publish';
$user_can_edit = current_user_can( 'edit_post', $post->ID );

if ( $is_valid_post && $user_can_edit ) {
	// Do something.
}
```

### WordPress-Specific Rules

**Use WordPress functions when available:**
```php
// Good
wp_remote_get( $url );

// Avoid
file_get_contents( $url );
```

**Prefix all custom functions and classes:**
Use a consistent prefix (e.g., `alm_` for this plugin) to avoid conflicts.

**Use WordPress coding standards for SQL:**
```php
global $wpdb;
$results = $wpdb->get_results( 
	$wpdb->prepare(
		"SELECT * FROM {$wpdb->posts} WHERE post_type = %s",
		'page'
	)
);
```

**Enqueue scripts and styles properly:**
```php
function my_theme_enqueue_scripts() {
	wp_enqueue_style( 'my-style', get_template_directory_uri() . '/style.css', array(), '1.0.0' );
	wp_enqueue_script( 'my-script', get_template_directory_uri() . '/script.js', array(), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_scripts' );
```

### Internationalization (i18n)

**Make all strings translatable:**
```php
__( 'Text to translate', 'asset-lending-manager' );
_e( 'Text to translate and echo', 'asset-lending-manager' );
esc_html__( 'Text to translate and escape', 'asset-lending-manager' );
esc_html_e( 'Text to translate, escape and echo', 'asset-lending-manager' );
```

**Use sprintf for dynamic strings:**
```php
/* translators: %s: post title */
$message = sprintf( __( 'Post "%s" was updated', 'asset-lending-manager' ), $post_title );
```

### Testing

**Write testable code:** Keep functions small and focused.

**Add PHPUnit tests** for complex logic and utility functions.

For available test and lint commands, see `ARCHITECTURE.md` § "Commands".

### JavaScript Conventions

**Vanilla JavaScript only — do not use jQuery or other libraries.**

**Namespace and structure:**
- Wrap each file in an IIFE: `(function(){ ... })();`
- Use namespace objects: `ALM_Frontend` for public-facing JS, `ALM_Admin` for back-office JS.
- Use `camelCase` for function and variable names.
- Use `const` and `let` (never `var`).

**DOM and data interaction:**
- Use native DOM API: `document.querySelector()`, `document.querySelectorAll()`, `addEventListener()`.
- Use `data-*` attributes for JS hooks instead of CSS classes (e.g., `data-alm-action="request-loan"`).
- Access localized data via `window.almFrontendData` / `window.almAdminData` (set by `wp_localize_script`).
- AJAX calls use `fetch()` API. Action names use snake_case to match PHP: `alm_submit_loan_request`.

**Example:**
```js
(function() {
	'use strict';

	const ALM_Frontend = {
		init: function() {
			document.querySelectorAll('[data-alm-action="request-loan"]').forEach(function(btn) {
				btn.addEventListener('click', ALM_Frontend.handleLoanRequest);
			});
		},
		handleLoanRequest: function(e) {
			e.preventDefault();
			const button = e.currentTarget;
			// fetch() call using window.almFrontendData.ajaxUrl.
		}
	};

	document.addEventListener('DOMContentLoaded', function() {
		ALM_Frontend.init();
	});
})();
```

### CSS Conventions

**Naming:**
- Prefix all custom classes with `.alm-` to avoid conflicts.
- Use BEM-like naming: `.alm-block`, `.alm-block__element`, `.alm-block--modifier`.

**Responsive design (mobile-first):**
- Breakpoints: 1024px (desktop), 768px (tablet), 480px (small mobile).
- Write base styles for mobile, then use `min-width` media queries to add desktop styles.
- Responsive tables use `data-label` attributes on `<td>` elements for mobile card layout.

**Color scheme:**
- Primary: WordPress blue `#0073aa`.
- Status colors: green (`#46b450`) for available/approved, red (`#dc3232`) for rejected/retired, yellow (`#ffb900`) for pending/on-loan.

**Example:**
```css
.alm-asset-card {
	padding: 1rem;
	border: 1px solid #ddd;
}

.alm-asset-card--on-loan {
	border-left: 4px solid #ffb900;
}

@media (min-width: 768px) {
	.alm-asset-card {
		display: flex;
		gap: 1.5rem;
	}
}
```

