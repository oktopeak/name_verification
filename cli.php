#!/usr/bin/env php
<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once __DIR__ . '/autoload.php';
}

use App\Generator\NameGenerator;
use App\Verifier\NameVerifier;
use App\Storage\NameStorage;

// Load configuration
$config = require __DIR__ . '/config.php';

// Initialize components
$storage = new NameStorage($config['storage_path'] ?? 'storage/latest_name.json');

// Try to get API key from config first, then environment variable
$apiKey = $config['openai_api_key'] ?? getenv('OPENAI_API_KEY');
if ($apiKey) {
    echo colorize("✓ LLM mode enabled (using OpenAI API)\n", 'green');
} else {
    echo colorize("ℹ Using deterministic generation (no API key configured)\n", 'yellow');
}

$generator = new NameGenerator($storage, $apiKey);
$verifier = new NameVerifier($storage);

// CLI colors
function colorize($text, $color = 'white') {
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'cyan' => "\033[36m",
        'white' => "\033[37m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function printHeader() {
    echo colorize("\n=================================\n", 'cyan');
    echo colorize("  Name Verification Application  \n", 'cyan');
    echo colorize("=================================\n\n", 'cyan');
}

function printMenu() {
    echo colorize("\nOptions:\n", 'yellow');
    echo "  1. Generate a target name\n";
    echo "  2. Verify a candidate name\n";
    echo "  3. Show current target name\n";
    echo "  4. Clear target name\n";
    echo "  5. Run test suite\n";
    echo "  6. Exit\n\n";
    echo "Enter your choice (1-6): ";
}

function generateName($generator) {
    echo "\n" . colorize("Enter a prompt for name generation:\n", 'yellow');
    echo "> ";
    $prompt = trim(fgets(STDIN));

    if (empty($prompt)) {
        echo colorize("Error: Prompt cannot be empty.\n", 'red');
        return;
    }

    echo colorize("\nGenerating name...\n", 'cyan');
    $name = $generator->generate($prompt);

    echo colorize("\n✓ Target name generated: ", 'green');
    echo colorize($name, 'white') . "\n";
}

function verifyName($verifier) {
    echo "\n" . colorize("Enter a candidate name to verify:\n", 'yellow');
    echo "> ";
    $candidateName = trim(fgets(STDIN));

    if (empty($candidateName)) {
        echo colorize("Error: Candidate name cannot be empty.\n", 'red');
        return;
    }

    echo colorize("\nVerifying...\n", 'cyan');
    $result = $verifier->verify($candidateName);

    if (isset($result['error'])) {
        echo colorize("\n✗ Error: " . $result['message'] . "\n", 'red');
        return;
    }

    echo "\n" . colorize("Verification Result:\n", 'cyan');
    echo str_repeat("-", 40) . "\n";
    echo "Target Name:    " . colorize($result['target_name'], 'white') . "\n";
    echo "Candidate Name: " . colorize($result['candidate_name'], 'white') . "\n";
    echo "Match:          " . ($result['match'] ? colorize("YES", 'green') : colorize("NO", 'red')) . "\n";
    echo "Confidence:     " . colorize($result['confidence'] . "%", 'yellow') . "\n";
    echo "Reason:         " . $result['reason'] . "\n";
    echo str_repeat("-", 40) . "\n";
}

function showCurrentTarget($storage) {
    $targetName = $storage->getLatest();

    if ($targetName === null) {
        echo colorize("\nNo target name has been generated yet.\n", 'yellow');
    } else {
        echo colorize("\nCurrent target name: ", 'cyan');
        echo colorize($targetName, 'white') . "\n";
    }
}

function clearTarget($storage) {
    $storage->clear();
    echo colorize("\n✓ Target name cleared.\n", 'green');
}

function runTests() {
    echo colorize("\nRunning test suite...\n", 'cyan');
    echo "This will execute test.php with all 30 test cases.\n\n";

    // Execute test.php
    passthru('php test.php');
}

// Main loop
printHeader();

while (true) {
    printMenu();
    $choice = trim(fgets(STDIN));

    switch ($choice) {
        case '1':
            generateName($generator);
            break;

        case '2':
            verifyName($verifier);
            break;

        case '3':
            showCurrentTarget($storage);
            break;

        case '4':
            clearTarget($storage);
            break;

        case '5':
            runTests();
            break;

        case '6':
            echo colorize("\nGoodbye!\n", 'cyan');
            exit(0);

        default:
            echo colorize("\nInvalid choice. Please enter a number between 1 and 6.\n", 'red');
    }
}