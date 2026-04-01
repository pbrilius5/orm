<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Environment configuration service that supports both .env and .env.yaml formats
 */
class EnvironmentConfig
{
    private array $envVars = [];

    public function __construct(string $environmentFile = null)
    {
        $this->loadEnvironment($environmentFile ?? getcwd() . '/.env');
    }

    private function loadEnvironment(string $filePath): void
    {
        if (!file_exists($filePath)) {
            // Try with .env.dist as fallback
            $filePath = getcwd() . '/.env.dist';
            if (!file_exists($filePath)) {
                throw new \RuntimeException('Environment file not found: ' . $filePath);
            }
        }

        // Load traditional .env file first
        $dotenv = new Dotenv();
        $dotenv->bootEnv($filePath);

        // Then try to load YAML format if file has .yaml extension or contains YAML
        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'yaml' || pathinfo($filePath, PATHINFO_EXTENSION) === 'yml') {
            $yamlContent = Yaml::parseFile($filePath);
            if (is_array($yamlContent)) {
                $this->envVars = array_merge($this->envVars, $yamlContent);
            }
        } else {
            // Check if the file contains YAML frontmatter or mixed format
            $fileContent = file_get_contents($filePath);
            if (strpos($fileContent, '---') === 0 && strpos($fileContent, '...') !== false) {
                // Extract YAML between --- markers
                $parts = explode('---', $fileContent);
                if (count($parts) >= 3) {
                    $yamlContent = Yaml::parse($parts[1]);
                    if (is_array($yamlContent)) {
                        $this->envVars = array_merge($this->envVars, $yamlContent);
                    }
                }
            }
        }

        // Always populate from $_ENV as fallback (Dotenv already did this)
        $this->envVars = array_merge($this->envVars, $_ENV);
    }

    public function get(string $key, $default = null)
    {
        return $this->envVars[$key] ?? $_ENV[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->envVars[$key]) || isset($_ENV[$key]);
    }

    public function all(): array
    {
        return array_merge($this->envVars, $_ENV);
    }

    public function getDatabaseParams(): array
    {
        return [
            'driver' => 'pdo_mysql',
            'host' => $this->get('DB_HOST', 'localhost'),
            'port' => $this->get('DB_PORT', '3306'),
            'dbname' => $this->get('DB_NAME', 'app'),
            'user' => $this->get('DB_USER', 'root'),
            'password' => $this->get('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
        ];
    }

    public function getDatabaseParamsForTesting(): array
    {
        return [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
    }

    public function isDebug(): bool
    {
        $debug = $this->get('APP_DEBUG', false);
        return is_string($debug) ? ($debug === 'true' || $debug === '1') : (bool) $debug;
    }

    public function getAppEnv(): string
    {
        return $this->get('APP_ENV', 'dev');
    }
}