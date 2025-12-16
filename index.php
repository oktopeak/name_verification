<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once __DIR__ . '/autoload.php';
}

use App\Generator\NameGenerator;
use App\Verifier\NameVerifier;
use App\Storage\NameStorage;

// Initialize components
$storage = new NameStorage();
$apiKey = getenv('OPENAI_API_KEY');
$generator = new NameGenerator($storage, $apiKey);
$verifier = new NameVerifier($storage);

// Handle form submissions
$message = '';
$messageType = '';
$verificationResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate'])) {
        $prompt = trim($_POST['prompt'] ?? '');
        if (!empty($prompt)) {
            $generatedName = $generator->generate($prompt);
            $message = "Target name generated: <strong>$generatedName</strong>";
            $messageType = 'success';
        } else {
            $message = "Please enter a prompt for name generation.";
            $messageType = 'error';
        }
    } elseif (isset($_POST['verify'])) {
        $candidateName = trim($_POST['candidate'] ?? '');
        if (!empty($candidateName)) {
            $verificationResult = $verifier->verify($candidateName);
            if (isset($verificationResult['error'])) {
                $message = $verificationResult['message'];
                $messageType = 'error';
                $verificationResult = null;
            }
        } else {
            $message = "Please enter a candidate name to verify.";
            $messageType = 'error';
        }
    } elseif (isset($_POST['clear'])) {
        $storage->clear();
        $message = "Target name cleared.";
        $messageType = 'success';
    }
}

$currentTarget = $storage->getLatest();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Name Verification Application</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
            padding: 20px;
        }

        header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        header p {
            font-size: 1.1em;
            opacity: 0.95;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .current-target {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .current-target strong {
            color: #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, transform 0.1s;
        }

        button:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        button:active {
            transform: translateY(0);
        }

        button.secondary {
            background: #e0e0e0;
            color: #333;
        }

        button.secondary:hover {
            background: #d0d0d0;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .verification-result {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .verification-result h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .result-item {
            display: flex;
            margin-bottom: 12px;
            align-items: center;
        }

        .result-label {
            font-weight: 600;
            color: #555;
            min-width: 150px;
        }

        .result-value {
            flex: 1;
        }

        .match-yes {
            color: #28a745;
            font-weight: bold;
        }

        .match-no {
            color: #dc3545;
            font-weight: bold;
        }

        .confidence {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        .test-link {
            text-align: center;
            margin-top: 30px;
        }

        .test-link a {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .test-link a:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Name Verification Application</h1>
            <p>Generate target names and verify candidate matches</p>
        </header>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Current Target Name</h2>
            <?php if ($currentTarget): ?>
                <div class="current-target">
                    <strong>Target:</strong> <?php echo htmlspecialchars($currentTarget); ?>
                </div>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="clear" class="secondary">Clear Target Name</button>
                </form>
            <?php else: ?>
                <p style="color: #666;">No target name generated yet. Generate one below.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Generate Target Name</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="prompt">Enter a prompt for name generation:</label>
                    <textarea name="prompt" id="prompt" placeholder="e.g., Generate a random Arabic sounding name with an Al and ibn both involved. The name shouldn't be longer than 5 words."><?php echo htmlspecialchars($_POST['prompt'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="generate">Generate Name</button>
            </form>
        </div>

        <div class="card">
            <h2>Verify Candidate Name</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="candidate">Enter a candidate name to verify:</label>
                    <input type="text" name="candidate" id="candidate"
                           placeholder="e.g., John Smith"
                           value="<?php echo htmlspecialchars($_POST['candidate'] ?? ''); ?>">
                </div>
                <button type="submit" name="verify">Verify Name</button>
            </form>

            <?php if ($verificationResult): ?>
                <div class="verification-result">
                    <h3>Verification Result</h3>
                    <div class="result-item">
                        <span class="result-label">Target Name:</span>
                        <span class="result-value"><?php echo htmlspecialchars($verificationResult['target_name']); ?></span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Candidate Name:</span>
                        <span class="result-value"><?php echo htmlspecialchars($verificationResult['candidate_name']); ?></span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Match:</span>
                        <span class="result-value <?php echo $verificationResult['match'] ? 'match-yes' : 'match-no'; ?>">
                            <?php echo $verificationResult['match'] ? 'YES' : 'NO'; ?>
                        </span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Confidence:</span>
                        <span class="result-value">
                            <span class="confidence"><?php echo $verificationResult['confidence']; ?>%</span>
                        </span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Reason:</span>
                        <span class="result-value"><?php echo htmlspecialchars($verificationResult['reason']); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="test-link">
            <a href="test.php">Run Test Suite (30 test cases)</a>
        </div>
    </div>
</body>
</html>