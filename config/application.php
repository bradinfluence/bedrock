<?php
/**
 * Your base production configuration goes in this file. Environment-specific
 * overrides go in their respective config/environments/{{WP_ENV}}.php file.
 */

use Roots\WPConfig\Config;
use function Env\env;

// Enable all options for Env\Env (USE_ENV_ARRAY + CONVERT_* + STRIP_QUOTES)
Env\Env::$options = 31;

/**
 * Directory containing all of the site's files
 */
$root_dir = dirname(__DIR__);

/**
 * Document Root
 */
$webroot_dir = $root_dir . '/public';

/**
 * Cloudron Health Check
 * Respond to the health check request from Cloudron
 */
if ($_SERVER["REMOTE_ADDR"] === '172.18.0.1') {
    echo "Cloudron healthcheck response";
    exit;
}

/**
 * Handle Reverse Proxy (HTTPS detection and host forwarding)
 * This ensures that WordPress properly handles requests behind a reverse proxy,
 * where HTTPS is proxied to HTTP.
 */
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'];
    
    if ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $_SERVER['HTTPS'] = 'on';
    }
}

/**
 * Use Dotenv to set environment variables
 */
$env_files = file_exists($root_dir . '/.env.local')
    ? ['.env', '.env.local']
    : ['.env'];

$dotenv = Dotenv\Dotenv::createImmutable($root_dir, $env_files, false);
$dotenv->load();

// Require essential variables
$dotenv->required(['WP_HOME', 'WP_SITEURL']);
if (!env('DATABASE_URL')) {
    $dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD']);
}

/**
 * Set up global environment constants
 */
define('WP_ENV', env('WP_ENV') ?: 'production');

// Infer WP_ENVIRONMENT_TYPE from WP_ENV
if (!env('WP_ENVIRONMENT_TYPE') && in_array(WP_ENV, ['production', 'staging', 'development', 'local'])) {
    Config::define('WP_ENVIRONMENT_TYPE', WP_ENV);
}

/**
 * Language
 */
Config::define('WPLANG', 'en_GB');

/**
 * Memory Limits
 */
Config::define('WP_MEMORY_LIMIT', '1024M');
Config::define('WP_MAX_MEMORY_LIMIT', '2048M');

/**
 * URLs
 */
Config::define('WP_HOME', env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL'));

/**
 * Custom Content Directory
 */
Config::define('CONTENT_DIR', '/app');
Config::define('WP_CONTENT_DIR', $webroot_dir . Config::get('CONTENT_DIR'));
Config::define('WP_CONTENT_URL', Config::get('WP_HOME') . Config::get('CONTENT_DIR'));

/**
 * Database Settings
 */
if (env('DB_SSL')) {
    Config::define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
}

Config::define('DB_NAME', getenv('CLOUDRON_MYSQL_DATABASE') ?: env('DB_NAME'));
Config::define('DB_USER', getenv('CLOUDRON_MYSQL_USERNAME') ?: env('DB_USER'));
Config::define('DB_PASSWORD', getenv('CLOUDRON_MYSQL_PASSWORD') ?: env('DB_PASSWORD'));
Config::define('DB_HOST', getenv('CLOUDRON_MYSQL_HOST') ?: env('DB_HOST') ?: 'localhost');
Config::define('DB_PORT', getenv('CLOUDRON_MYSQL_PORT') ?: env('DB_PORT') ?: 3306);
Config::define('DB_CHARSET', 'utf8mb4');
Config::define('DB_COLLATE', 'utf8mb4_general_ci');
$table_prefix = env('DB_PREFIX') ?: 'wp_';

/**
 * Authentication Keys and Salts
 */
Config::define('AUTH_KEY', env('AUTH_KEY'));
Config::define('SECURE_AUTH_KEY', env('SECURE_AUTH_KEY'));
Config::define('LOGGED_IN_KEY', env('LOGGED_IN_KEY'));
Config::define('NONCE_KEY', env('NONCE_KEY'));
Config::define('AUTH_SALT', env('AUTH_SALT'));
Config::define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
Config::define('LOGGED_IN_SALT', env('LOGGED_IN_SALT'));
Config::define('NONCE_SALT', env('NONCE_SALT'));

/**
 * SSL settings
 */
Config::define('FORCE_SSL_LOGIN', env('FORCE_SSL_LOGIN') ?: true);
Config::define('FORCE_SSL_ADMIN', env('FORCE_SSL_ADMIN') ?: true);

/**
 * Redis Settings
 */
if (env('WP_ENV') === 'production') {
    Config::define('WP_REDIS_HOST', getenv('CLOUDRON_REDIS_HOST') ?: env('WP_REDIS_HOST'));
    Config::define('WP_REDIS_PORT', getenv('CLOUDRON_REDIS_PORT') ?: env('WP_REDIS_PORT') ?: 6379);
    Config::define('WP_REDIS_PASSWORD', getenv('CLOUDRON_REDIS_PASSWORD') ?: env('WP_REDIS_PASSWORD'));
    Config::define('WP_CACHE', true);
} else {
    Config::define('WP_CACHE', false); // Disable Redis caching in non-production environments
}

/**
 * Cloudflare
 */
Config::define('CLOUDFLARE_GLOBAL_API_KEY', env('CLOUDFLARE_GLOBAL_API_KEY'));
Config::define('CLOUDFLARE_API_TOKEN', env('CLOUDFLARE_API_TOKEN'));
Config::define('CLOUDFLARE_ZONE_ID', env('CLOUDFLARE_ZONE_ID'));
Config::define('CLOUDFLARE_EMAIL', env('CLOUDFLARE_EMAIL'));
// Turnstile
Config::define('CLOUDFLARE_TURNSTILE_SECRET_KEY', env('CLOUDFLARE_TURNSTILE_SECRET_KEY'));
Config::define('CLOUDFLARE_TURNSTILE_SITE_KEY', env('CLOUDFLARE_TURNSTILE_SITE_KEY'));

/**
 * OpenAI
 */
Config::define('OPENAI_API_KEY', env('OPENAI_API_KEY'));

/**
 * Custom Settings
 */
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('DISABLE_WP_CRON', env('DISABLE_WP_CRON') ?: true);
Config::define('WP_DEFAULT_THEME', env('WP_DEFAULT_THEME') ?: 'twentytwentyfour');
Config::define('WP_POST_REVISIONS', env('WP_POST_REVISIONS') ?: true);

/**
 * Debugging Settings
 */
Config::define('WP_DEBUG_DISPLAY', env('WP_DEBUG_DISPLAY') ?: false);
Config::define('WP_DEBUG_LOG', env('WP_DEBUG_LOG') ?: false);
Config::define('SCRIPT_DEBUG', env('SCRIPT_DEBUG') ?: false);
ini_set('display_errors', env('DISPLAY_ERRORS') ?: '0');

/**
 * Load Environment-Specific Config
 */
$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';
if (file_exists($env_config)) {
    require_once $env_config;
}

/**
 * Apply Config and Bootstrap WordPress
 */
Config::apply();

if (!defined('ABSPATH')) {
    define('ABSPATH', $webroot_dir . '/wp/');
}