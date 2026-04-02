<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Environment configuration service with multi-format support.
 *
 * Loading priority (highest to lowest):
 *   1. System environment variables ($_ENV, $_SERVER)
 *   2. .env file (legacy KEY=VALUE format)
 *   3. .env.yaml (primary YAML configuration)
 *   4. .env.dist (template defaults)
 *
 * Variable substitution syntax:
 *   ${VAR}            - Reference another variable
 *   ${VAR:-default}   - Use default if VAR is not set
 *   ${VAR:?error}     - Throw error if VAR is not set
 */
class EnvironmentConfig
{
    private array $envVars = [];
    private string $projectRoot;
    private bool $loaded = false;

    public function __construct(?string $projectRoot = null)
    {
        $this->projectRoot = $projectRoot ?? $this->detectProjectRoot();
        $this->load();
    }

    private function detectProjectRoot(): string
    {
        $dir = __DIR__;
        while ($dir !== '/' && $dir !== '') {
            if (file_exists($dir . '/composer.json')) {
                return $dir;
            }
            $dir = dirname($dir);
        }

        return getcwd();
    }

    /**
     * Load environment configuration from all sources with proper priority.
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $vars = [];

        // 1. Load .env.dist as template defaults (lowest priority)
        $distPath = $this->projectRoot . '/.env.dist';
        if (file_exists($distPath)) {
            $vars = array_merge($vars, $this->parseEnvFile($distPath));
        }

        // 2. Load .env.yaml as primary configuration
        $yamlPath = $this->projectRoot . '/.env.yaml';
        if (file_exists($yamlPath)) {
            $yamlVars = $this->parseYamlFile($yamlPath);
            $vars = array_merge($vars, $yamlVars);
        }

        // 3. Load .env as legacy override
        $envPath = $this->projectRoot . '/.env';
        if (file_exists($envPath)) {
            $envVars = $this->parseEnvFile($envPath);
            $vars = array_merge($vars, $envVars);
        }

        // 4. Merge with system environment variables (highest priority)
        $vars = array_merge($vars, $_ENV, $_SERVER);

        // Resolve variable substitutions
        $this->envVars = $this->resolveVariables($vars);

        // Populate $_ENV for backward compatibility with legacy code
        foreach ($this->envVars as $key => $value) {
            if (is_scalar($value)) {
                $_ENV[$key] = $value;
            }
        }

        $this->loaded = true;
    }

    /**
     * Parse a traditional .env file (KEY=VALUE format).
     */
    private function parseEnvFile(string $filePath): array
    {
        $vars = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove surrounding quotes
                if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
                    || (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    /**
     * Parse a YAML configuration file.
     */
    private function parseYamlFile(string $filePath): array
    {
        $content = Yaml::parseFile($filePath, Yaml::PARSE_CONSTANT);

        if (!is_array($content)) {
            return [];
        }

        $vars = [];

        // Flatten nested structures into dot-notation keys
        $this->flattenArray($content, '', $vars);

        return $vars;
    }

    /**
     * Recursively flatten a nested array into dot-notation keys.
     */
    private function flattenArray(array $array, string $prefix, array &$result): void
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $this->flattenArray($value, $fullKey, $result);
            } else {
                $result[$fullKey] = $value;
            }
        }
    }

    /**
     * Resolve variable substitutions in all values.
     *
     * Supports:
     *   ${VAR}            - Direct reference
     *   ${VAR:-default}   - Default value if VAR is not set
     *   ${VAR:?error}     - Throw error if VAR is not set
     */
    private function resolveVariables(array $vars, int $depth = 0): array
    {
        if ($depth > 10) {
            throw new \RuntimeException('Circular variable reference detected in environment configuration');
        }

        $resolved = [];

        foreach ($vars as $key => $value) {
            if (is_string($value)) {
                $resolved[$key] = $this->resolveValue($value, $vars, $depth);
            } else {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    /**
     * Resolve a single value with variable substitution.
     */
    private function resolveValue(string $value, array $vars, int $depth): mixed
    {
        // Match ${...} patterns
        $pattern = '/\$\{([^}]+)\}/';

        return preg_replace_callback($pattern, function ($matches) use ($vars, $depth) {
            $expression = $matches[1];

            // Check for default value syntax: ${VAR:-default}
            if (strpos($expression, ':-') !== false) {
                [$varName, $default] = explode(':-', $expression, 2);
                return $this->getVarValue($varName, $vars, $default, $depth);
            }

            // Check for required syntax: ${VAR:?error}
            if (strpos($expression, ':?') !== false) {
                [$varName, $error] = explode(':?', $expression, 2);
                $value = $this->getVarValue($varName, $vars, null, $depth);
                if ($value === null || $value === '') {
                    throw new \RuntimeException("Environment variable '{$varName}' is required: {$error}");
                }
                return $value;
            }

            // Simple variable reference: ${VAR}
            return $this->getVarValue($expression, $vars, null, $depth);
        }, $value);
    }

    /**
     * Get the value of a variable, resolving it recursively.
     */
    private function getVarValue(string $varName, array $vars, mixed $default, int $depth): mixed
    {
        // Check system environment first
        if (isset($_ENV[$varName]) && $_ENV[$varName] !== '') {
            return $_ENV[$varName];
        }

        // Check loaded variables
        if (isset($vars[$varName])) {
            $value = $vars[$varName];

            // If the value itself contains variable references, resolve them
            if (is_string($value) && preg_match('/\$\{[^}]+\}/', $value)) {
                return $this->resolveValue($value, $vars, $depth + 1);
            }

            return $value;
        }

        // Return default if provided
        if ($default !== null) {
            // Check if default itself contains variable references
            if (is_string($default) && preg_match('/\$\{[^}]+\}/', $default)) {
                return $this->resolveValue($default, $vars, $depth + 1);
            }
            return $default;
        }

        return null;
    }

    /**
     * Get a configuration value by key.
     *
     * Supports both dot-notation (database.host) and flat keys (DB_HOST).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check exact key match
        if (array_key_exists($key, $this->envVars)) {
            return $this->envVars[$key];
        }

        // Check system environment
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        return $default;
    }

    /**
     * Check if a configuration key exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->envVars) || isset($_ENV[$key]);
    }

    /**
     * Get all configuration values.
     */
    public function all(): array
    {
        return $this->envVars;
    }

    /**
     * Get a required configuration value (throws if not set).
     */
    public function require(string $key, ?string $message = null): mixed
    {
        $value = $this->get($key);

        if ($value === null || $value === '') {
            throw new \RuntimeException($message ?? "Required environment variable '{$key}' is not set");
        }

        return $value;
    }

    /**
     * Get database connection parameters.
     */
    public function getDatabaseParams(): array
    {
        $path = $this->get('database.path', 'var/data/orm.db');
        if (!str_starts_with($path, '/')) {
            $path = $this->projectRoot . '/' . $path;
        }

        return [
            'driver' => $this->get('database.driver', 'pdo_sqlite'),
            'host' => $this->get('database.host', 'localhost'),
            'port' => $this->get('database.port', '3306'),
            'dbname' => $this->get('database.name', 'app'),
            'user' => $this->get('database.user', 'root'),
            'password' => $this->get('database.password', ''),
            'charset' => $this->get('database.charset', 'utf8mb4'),
            'path' => $path,
        ];
    }

    /**
     * Get database parameters for testing (SQLite in-memory).
     */
    public function getDatabaseParamsForTesting(): array
    {
        return [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
    }

    /**
     * Check if debug mode is enabled.
     */
    public function isDebug(): bool
    {
        $debug = $this->get('APP_DEBUG', false);
        return is_string($debug) ? ($debug === 'true' || $debug === '1') : (bool) $debug;
    }

    /**
     * Get the application environment.
     */
    public function getAppEnv(): string
    {
        return (string) $this->get('APP_ENV', 'dev');
    }

    /**
     * Get ORM proxy generation strategy.
     *
     * Returns the appropriate Doctrine ProxyFactory auto-generate constant
     * based on ORM_AUTO_GENERATE_PROXY setting or environment.
     */
    public function getOrmProxyAutoGenerate(): int
    {
        $explicit = $this->get('ORM_AUTO_GENERATE_PROXY');

        if ($explicit !== null && $explicit !== '') {
            return match ($explicit) {
                'true', '1', 'eval' => \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_EVAL,
                'false', '0', 'never' => \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_NEVER,
                'file_changed' => \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS,
                'always' => \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_ALWAYS,
                default => \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_EVAL,
            };
        }

        return match ($this->getAppEnv()) {
            'prod', 'production' => \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_NEVER,
            default => \Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_EVAL,
        };
    }

    /**
     * Get ORM proxy directory.
     */
    public function getOrmProxyDir(): string
    {
        return (string) $this->get('ORM_PROXY_DIR', sys_get_temp_dir() . '/orm/proxies');
    }

    /**
     * Get ORM proxy namespace.
     */
    public function getOrmProxyNamespace(): string
    {
        return (string) $this->get('ORM_PROXY_NAMESPACE', 'Oryx\ORM\Proxy');
    }

    /**
     * Get Memcached configuration for rate limiting.
     */
    public function getMemcachedConfig(): array
    {
        return [
            'host' => $this->get('MEMCACHED_HOST', 'localhost'),
            'port' => (int) $this->get('MEMCACHED_PORT', '11211'),
            'enabled' => $this->isMemcachedEnabled(),
        ];
    }

    /**
     * Check if Memcached is enabled.
     */
    public function isMemcachedEnabled(): bool
    {
        return extension_loaded('memcached')
            && $this->get('CACHE_DRIVER', 'array') === 'memcached';
    }

    /**
     * Get rate limiting configuration.
     */
    public function getRateLimitConfig(): array
    {
        return [
            'enabled' => (bool) $this->get('RATE_LIMIT_ENABLED', true),
            'max_requests' => (int) $this->get('RATE_LIMIT_MAX_REQUESTS', 60),
            'window_seconds' => (int) $this->get('RATE_LIMIT_WINDOW', 60),
        ];
    }

    /**
     * Get the project root path.
     */
    public function getProjectRoot(): string
    {
        return $this->projectRoot;
    }
}
