<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/check-clover-threshold.php <clover.xml>\n");
    exit(2);
}

$cloverPath = $argv[1];
if (!is_file($cloverPath)) {
    fwrite(STDERR, "Coverage file not found: {$cloverPath}\n");
    exit(2);
}

$xml = simplexml_load_file($cloverPath);
if ($xml === false) {
    fwrite(STDERR, "Failed to parse Clover report: {$cloverPath}\n");
    exit(2);
}

$metrics = $xml->project?->metrics;
if ($metrics === null) {
    fwrite(STDERR, "Clover report does not contain project metrics.\n");
    exit(2);
}

function metricInt(SimpleXMLElement $metrics, string $name): int
{
    return isset($metrics[$name]) ? (int) $metrics[$name] : 0;
}

function percentage(int $covered, int $total): float
{
    if ($total <= 0) {
        return 100.0;
    }

    return ($covered / $total) * 100;
}

$statementPct = percentage(metricInt($metrics, 'coveredstatements'), metricInt($metrics, 'statements'));
$branchPct = percentage(metricInt($metrics, 'coveredconditionals'), metricInt($metrics, 'conditionals'));
$functionPct = percentage(metricInt($metrics, 'coveredmethods'), metricInt($metrics, 'methods'));
$linePct = percentage(metricInt($metrics, 'coveredelements'), metricInt($metrics, 'elements'));

$thresholds = [
    'statements' => (float) ($_ENV['PHP_COVERAGE_MIN_STATEMENTS'] ?? $_SERVER['PHP_COVERAGE_MIN_STATEMENTS'] ?? 70),
    'branches' => (float) ($_ENV['PHP_COVERAGE_MIN_BRANCHES'] ?? $_SERVER['PHP_COVERAGE_MIN_BRANCHES'] ?? 60),
    'functions' => (float) ($_ENV['PHP_COVERAGE_MIN_FUNCTIONS'] ?? $_SERVER['PHP_COVERAGE_MIN_FUNCTIONS'] ?? 70),
    'lines' => (float) ($_ENV['PHP_COVERAGE_MIN_LINES'] ?? $_SERVER['PHP_COVERAGE_MIN_LINES'] ?? 70),
];

$actuals = [
    'statements' => $statementPct,
    'branches' => $branchPct,
    'functions' => $functionPct,
    'lines' => $linePct,
];

fwrite(STDOUT, "Backend coverage summary (Clover)\n");
foreach ($actuals as $metric => $value) {
    fwrite(STDOUT, sprintf("- %s: %.2f%% (min %.2f%%)\n", $metric, $value, $thresholds[$metric]));
}

$failures = [];
foreach ($actuals as $metric => $value) {
    if ($value + 1e-9 < $thresholds[$metric]) {
        $failures[] = sprintf('%s %.2f%% < %.2f%%', $metric, $value, $thresholds[$metric]);
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Coverage threshold check failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, "- {$failure}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Coverage thresholds satisfied.\n");
