#!/usr/bin/env php
<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once __DIR__ . '/autoload.php';
}

use App\Storage\NameStorage;
use App\Verifier\NameVerifier;

// Test cases from the PDF
$testCases = [
    // Expected Matches (1-18)
    ['Tyler Bliha', 'Tlyer Bilha', true, 'Minor transposition and misspelling'],
    ['Al-Hilal', 'alhilal', true, 'Hyphen and casing differences only'],
    ['Dargulov', 'Darguloff', true, 'Common phonetic suffix variation (v vs ff)'],
    ['Bob Ellensworth', 'Robert Ellensworth', true, 'Common nickname vs formal name'],
    ['Mohammed Al Fayed', 'Muhammad Alfayed', true, 'Spacing and transliteration variance'],
    ['Sarah O\'Connor', 'Sara Oconnor', true, 'Apostrophe removal and vowel simplification'],
    ['Jonathon Smith', 'Jonathan Smith', true, 'Common spelling variant of first name'],
    ['Abdul Rahman ibn Saleh', 'Abdulrahman ibn Saleh', true, 'Spacing variation within compound name'],
    ['Al Hassan Al Saud', 'Al-Hasan Al Saud', true, 'Minor consonant simplification and hyphenation'],
    ['Katherine McDonald', 'Catherine Macdonald', true, 'Phonetic first name and common Mc/Mac variation'],
    ['Yusuf Al Qasim', 'Youssef Alkasim', true, 'Transliteration differences in Arabic-derived names'],
    ['Steven Johnson', 'Stephen Jonson', true, 'Phonetic spelling differences in both names'],
    ['Alexander Petrov', 'Aleksandr Petrof', true, 'Slavic transliteration and phonetic variation'],
    ['Jean-Luc Picard', 'Jean Luc Picard', true, 'Hyphen removal'],
    ['Mikhail Gorbachov', 'Mikhail Gorbachev', true, 'Alternate transliteration endings'],
    ['Elizabeth Turner', 'Liz Turner', true, 'Common nickname shortening'],
    ['Omar ibn Al Khattab', 'Omar Ibn Alkhattab', true, 'Case, spacing, and compound-name variance'],
    ['Sean O\'Brien', 'Shawn Obrien', true, 'Phonetic first name and punctuation removal'],

    // Expected Non-matches (19-30)
    ['Emanuel Oscar', 'Belinda Oscar', false, 'Same last name but entirely different first name'],
    ['Michael Thompson', 'Michelle Thompson', false, 'Similar-looking but distinct first names'],
    ['Ali Hassan', 'Hassan Ali', false, 'Token order swap changes identity'],
    ['John Smith', 'James Smith', false, 'Different common first names'],
    ['Abdullah ibn Omar', 'Omar ibn Abdullah', false, 'Reversal of patronymic meaning'],
    ['Maria Gonzalez', 'Mario Gonzalez', false, 'Gendered name difference'],
    ['Christopher Nolan', 'Christian Nolan', false, 'Similar prefix but distinct names'],
    ['Ahmed Al Rashid', 'Ahmed Al Rashidi', false, 'Different surname root'],
    ['Samantha Lee', 'Samuel Lee', false, 'Different first name despite shared root'],
    ['Ivan Petrov', 'Ilya Petrov', false, 'Distinct given names in same cultural group'],
    ['Fatima Zahra', 'Zahra Fatima', false, 'Name order inversion changes identity'],
    ['William Carter', 'Liam Carter', false, 'Nickname not universally equivalent without explicit mapping'],
];

// CLI colors
function colorize($text, $color = 'white') {
    if (PHP_SAPI !== 'cli') {
        return $text;
    }
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

// For web display
if (PHP_SAPI !== 'cli') {
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Test Suite Results</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .pass { color: #28a745; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        .expected-yes { background: #d4edda; }
        .expected-no { background: #f8d7da; }
        .confidence {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            background: #667eea;
            color: white;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <h1>Name Verification Test Suite</h1>';
}

// Initialize components
$storage = new NameStorage();
$verifier = new NameVerifier($storage);

// Test results
$passed = 0;
$failed = 0;
$results = [];

echo PHP_SAPI === 'cli'
    ? colorize("\n=== NAME VERIFICATION TEST SUITE ===\n\n", 'cyan')
    : '<div class="summary"><h2>Running ' . count($testCases) . ' test cases...</h2></div>';

if (PHP_SAPI !== 'cli') {
    echo '<table><thead><tr>
        <th>#</th>
        <th>Target Name</th>
        <th>Candidate Name</th>
        <th>Expected</th>
        <th>Result</th>
        <th>Confidence</th>
        <th>Status</th>
        <th>Reason</th>
    </tr></thead><tbody>';
}

foreach ($testCases as $index => $test) {
    [$targetName, $candidateName, $expectedMatch, $description] = $test;
    $testNumber = $index + 1;

    // Set the target name
    $storage->save($targetName);

    // Verify the candidate
    $result = $verifier->verify($candidateName);

    // Check if the result matches expectation
    $testPassed = $result['match'] === $expectedMatch;

    if ($testPassed) {
        $passed++;
        $status = 'PASS';
        $statusColor = 'green';
    } else {
        $failed++;
        $status = 'FAIL';
        $statusColor = 'red';
    }

    $results[] = [
        'number' => $testNumber,
        'target' => $targetName,
        'candidate' => $candidateName,
        'expected' => $expectedMatch,
        'actual' => $result['match'],
        'confidence' => $result['confidence'],
        'status' => $status,
        'reason' => $result['reason'],
        'description' => $description
    ];

    // Display result
    if (PHP_SAPI === 'cli') {
        echo sprintf(
            "Test #%02d: %s\n  Target: %s\n  Candidate: %s\n  Expected: %s, Got: %s (Confidence: %d%%)\n  Status: %s\n  Reason: %s\n\n",
            $testNumber,
            colorize($status, $statusColor),
            $targetName,
            $candidateName,
            $expectedMatch ? 'MATCH' : 'NO MATCH',
            $result['match'] ? 'MATCH' : 'NO MATCH',
            $result['confidence'],
            colorize($status, $statusColor),
            $result['reason']
        );
    } else {
        $rowClass = $expectedMatch ? 'expected-yes' : 'expected-no';
        $statusClass = $testPassed ? 'pass' : 'fail';
        echo "<tr class='$rowClass'>
            <td>$testNumber</td>
            <td>$targetName</td>
            <td>$candidateName</td>
            <td>" . ($expectedMatch ? 'MATCH' : 'NO MATCH') . "</td>
            <td>" . ($result['match'] ? 'MATCH' : 'NO MATCH') . "</td>
            <td><span class='confidence'>{$result['confidence']}%</span></td>
            <td class='$statusClass'>$status</td>
            <td>{$result['reason']}</td>
        </tr>";
    }
}

if (PHP_SAPI !== 'cli') {
    echo '</tbody></table>';
}

// Summary
$total = count($testCases);
$successRate = round(($passed / $total) * 100, 1);

if (PHP_SAPI === 'cli') {
    echo colorize("\n=== TEST SUMMARY ===\n", 'cyan');
    echo sprintf(
        "Total Tests: %d\n%s: %d\n%s: %d\nSuccess Rate: %s%%\n\n",
        $total,
        colorize('Passed', 'green'),
        $passed,
        colorize('Failed', 'red'),
        $failed,
        $successRate >= 80 ? colorize($successRate, 'green') : colorize($successRate, 'red')
    );

    if ($failed > 0) {
        echo colorize("Failed Tests:\n", 'yellow');
        foreach ($results as $result) {
            if ($result['status'] === 'FAIL') {
                echo sprintf(
                    "  - Test #%d: %s vs %s (Expected: %s, Got: %s)\n",
                    $result['number'],
                    $result['target'],
                    $result['candidate'],
                    $result['expected'] ? 'MATCH' : 'NO MATCH',
                    $result['actual'] ? 'MATCH' : 'NO MATCH'
                );
            }
        }
    }

    echo $passed === $total
        ? colorize("\n✓ All tests passed!\n", 'green')
        : colorize("\n✗ Some tests failed. Please review the implementation.\n", 'red');
} else {
    $summaryClass = $successRate >= 80 ? 'pass' : 'fail';
    echo "<div class='summary'>
        <h2>Test Summary</h2>
        <p><strong>Total Tests:</strong> $total</p>
        <p><strong>Passed:</strong> <span class='pass'>$passed</span></p>
        <p><strong>Failed:</strong> <span class='fail'>$failed</span></p>
        <p><strong>Success Rate:</strong> <span class='$summaryClass'>{$successRate}%</span></p>";

    if ($passed === $total) {
        echo "<p style='color: #28a745; font-size: 1.2em; margin-top: 20px;'>✓ All tests passed!</p>";
    } else {
        echo "<p style='color: #dc3545; margin-top: 20px;'>✗ Some tests failed. Please review the implementation.</p>";
    }

    echo "</div></body></html>";
}

// Clean up storage after tests
$storage->clear();