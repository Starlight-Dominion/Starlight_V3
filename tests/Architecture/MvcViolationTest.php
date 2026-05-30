<?php

declare(strict_types=1);

namespace tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MvcViolationTest extends TestCase
{
    private const SCAN_DIRS = [
        'src/Controllers',
        'src/Services'
    ];

    /**
     * Files that are currently exempted from the audit.
     * We will remove these as we refactor them.
     */
    private const EXEMPTIONS = [];
    public function testNoDirectDatabaseAccessInControllersAndServices(): void
    {
        $violations = [];
        $projectRoot = dirname(__DIR__, 2);

        foreach (self::SCAN_DIRS as $dir) {
            $fullPath = $projectRoot . '/' . $dir;
            if (!is_dir($fullPath)) continue;

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath));
            foreach ($iterator as $file) {
                if ($file->isDir() || $file->getExtension() !== 'php') continue;

                $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
                
                if (in_array($relativePath, self::EXEMPTIONS)) continue;

                $content = file_get_contents($file->getPathname());

                // Check for direct Capsule usage
                if (str_contains($content, 'Capsule::') || str_contains($content, 'use Illuminate\\Database\\Capsule\\Manager as Capsule;')) {
                    $violations[] = "$relativePath: Uses direct Capsule query builder.";
                }

                // Check for common Eloquent static methods being called directly (e.g., User::where, Dominion::find)
                // This is a bit naive but covers many cases. We exclude Model.php itself if it were in the path.
                if (preg_match('/[A-Z][a-zA-Z0-9]*::(where|find|with|query|create|update|all|count|increment|decrement|sum)\(/', $content)) {
                    $violations[] = "$relativePath: Uses direct Eloquent model query methods.";
                }
            }
        }

        $this->assertEmpty($violations, "MVC Database Violations Found:\n" . implode("\n", $violations));
    }

    public function testNoSuperglobalsInServices(): void
    {
        $violations = [];
        $projectRoot = dirname(__DIR__, 2);
        $dir = $projectRoot . '/src/Services';

        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isDir() || $file->getExtension() !== 'php') continue;

                $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
                if (in_array($relativePath, self::EXEMPTIONS)) continue;

                $content = file_get_contents($file->getPathname());
                if (preg_match('/\$(POST|GET|SESSION|REQUEST|SERVER|COOKIE)/', $content)) {
                    $violations[] = "$relativePath: Directly accesses PHP superglobals.";
                }
            }
        }

        $this->assertEmpty($violations, "Superglobal Violations in Services Found:\n" . implode("\n", $violations));
    }

    public function testNoHtmlInControllers(): void
    {
        $violations = [];
        $projectRoot = dirname(__DIR__, 2);
        $dir = $projectRoot . '/src/Controllers';

        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isDir() || $file->getExtension() !== 'php') continue;

                $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
                if (in_array($relativePath, self::EXEMPTIONS)) continue;

                $content = file_get_contents($file->getPathname());
                
                // Heuristic: looks for html tags that shouldn't be in a controller (e.g. <div>, <span>, <table>)
                if (preg_match('/<(div|span|table|tr|td|p|h[1-6]|ul|li|a\s+href)[^>]*>/i', $content)) {
                    $violations[] = "$relativePath: Contains HTML tags, suggesting presentation logic leak.";
                }
            }
        }

        $this->assertEmpty($violations, "HTML found in Controllers:\n" . implode("\n", $violations));
    }

    public function testNoDatabaseQueriesInViews(): void
    {
        $violations = [];
        $projectRoot = dirname(__DIR__, 2);
        $dir = $projectRoot . '/src/Views';

        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isDir() || $file->getExtension() !== 'php') continue;

                $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
                $content = file_get_contents($file->getPathname());
                
                // Heuristic: views should not have Capsule:: or eloquent method calls like ::where
                if (preg_match('/(Capsule::|[A-Z][a-zA-Z0-9]*::(?:where|find|with|query|create|update|all)\()/', $content)) {
                    $violations[] = "$relativePath: Contains direct database or Eloquent queries.";
                }
            }
        }

        $this->assertEmpty($violations, "Database queries found in Views:\n" . implode("\n", $violations));
    }

    public function testNoResponseHandlingInServices(): void
    {
        $violations = [];
        $projectRoot = dirname(__DIR__, 2);
        $dir = $projectRoot . '/src/Services';

        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isDir() || $file->getExtension() !== 'php') continue;

                $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
                if (in_array($relativePath, self::EXEMPTIONS)) continue;

                $content = file_get_contents($file->getPathname());
                
                // Heuristic: services shouldn't use header(), json_encode() for HTTP responses, echo, or http_response_code
                if (preg_match('/\b(header|http_response_code)\s*\(/i', $content)) {
                    $violations[] = "$relativePath: Manipulates HTTP headers or response codes directly.";
                }
                
                if (preg_match('/^\s*(echo|print)\s+/m', $content)) {
                     $violations[] = "$relativePath: Uses echo or print directly.";
                }
            }
        }

        $this->assertEmpty($violations, "Response handling found in Services:\n" . implode("\n", $violations));
    }

    public function testNoComplexBusinessLogicInModels(): void
    {
        $violations = [];
        $projectRoot = dirname(__DIR__, 2);
        $dir = $projectRoot . '/src/Models';

        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isDir() || $file->getExtension() !== 'php') continue;

                $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
                $content = file_get_contents($file->getPathname());
                
                // Heuristic: We shouldn't see direct DB query building inside models outside of relationships.
                if (str_contains($content, 'Capsule::')) {
                    $violations[] = "$relativePath: Model uses Capsule query builder directly.";
                }
                
                // Flag loop keywords as a business logic smell in active record models
                if (preg_match('/\b(foreach|while)\s*\(/', $content)) {
                     $violations[] = "$relativePath: Model contains loops (foreach/while), suggesting business logic.";
                }
                
                // Look for heavy math methods
                if (preg_match('/\b(floor|ceil|sqrt|pow|round)\s*\(/', $content)) {
                     $violations[] = "$relativePath: Model uses heavy math functions, suggesting business logic/calculations.";
                }
            }
        }

        $this->assertEmpty($violations, "Business logic found in Models:\n" . implode("\n", $violations));
    }
}
