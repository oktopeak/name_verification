# Name Verification Application

A PHP application that generates target names from user prompts and verifies candidate names against them using fuzzy matching algorithms.

## Features

- **Target Name Generator**: Generate names from free-form prompts (with optional LLM support)
- **Name Verifier**: Verify candidate names against the latest generated target with confidence scoring
- **Fuzzy Matching**: Handles typos, nicknames, transliterations, and common variations
- **Multiple Interfaces**: CLI and Web interfaces
- **Comprehensive Test Suite**: 30 pre-defined test cases

## Requirements

- PHP >= 7.4
- Composer
- (Optional) OpenAI API key for LLM-based name generation

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd name-verification
```

2. Install dependencies:
```bash
composer install
```

3. (Optional) Set OpenAI API key for LLM generation:
```bash
export OPENAI_API_KEY="your-api-key-here"
```

## Usage

### Web Interface

Start the built-in PHP server:
```bash
composer serve
# or
php -S localhost:8000 index.php
```

Then open your browser and navigate to `http://localhost:8000`

### CLI Interface

Run the interactive CLI:
```bash
composer cli
# or
php cli.php
```

CLI Options:
1. Generate a target name
2. Verify a candidate name
3. Show current target name
4. Clear target name
5. Run test suite
6. Exit

### Running Tests

Execute all 30 test cases:
```bash
composer test
# or
php test.php
```

The test suite includes:
- 18 expected matches (nicknames, typos, transliterations)
- 12 expected non-matches (different names, order swaps)

## Architecture

### Key Components

1. **NameStorage** (`src/Storage/NameStorage.php`)
   - Stores the latest generated target name
   - Simple JSON file-based persistence

2. **NameGenerator** (`src/Generator/NameGenerator.php`)
   - Generates names from prompts
   - Supports LLM (OpenAI) or deterministic generation
   - Automatically saves to storage

3. **NameVerifier** (`src/Verifier/NameVerifier.php`)
   - Verifies candidates against stored target
   - Uses multiple matching algorithms:
     - Levenshtein distance for typos
     - Soundex for phonetic matching
     - Nickname mappings
     - Transliteration handling
   - Returns match (boolean), confidence (0-100), and reason

### Black Box Design

The verifier treats the generator as a black box:
- Only accesses the stored target name string
- No access to generator context or history
- Cannot call back into the generator
- Architecturally isolated components

## Matching Algorithm

The verifier uses a weighted combination of:

1. **Token-based similarity**: Compares individual name parts
2. **Phonetic similarity**: Uses soundex for similar-sounding names
3. **String distance**: Levenshtein distance for character-level differences
4. **Nickname detection**: Maps common nicknames (Bob→Robert, Liz→Elizabeth)
5. **Variation handling**:
   - Mc/Mac variations
   - Arabic transliterations (Mohammed→Muhammad)
   - Slavic endings (ov/off)
   - Punctuation normalization

**Match Threshold**: 75% confidence

## Test Cases

The application includes 30 test cases from the technical assessment:

### Expected Matches (Examples)
- `Tyler Bliha` → `Tlyer Bilha` (typos)
- `Bob Ellensworth` → `Robert Ellensworth` (nickname)
- `Mohammed Al Fayed` → `Muhammad Alfayed` (transliteration)

### Expected Non-Matches (Examples)
- `Ali Hassan` → `Hassan Ali` (order swap)
- `John Smith` → `James Smith` (different first name)
- `William Carter` → `Liam Carter` (no explicit nickname mapping)

## File Structure

```
name-verification/
├── src/
│   ├── Storage/
│   │   └── NameStorage.php
│   ├── Generator/
│   │   └── NameGenerator.php
│   └── Verifier/
│       └── NameVerifier.php
├── storage/
│   └── latest_name.json (auto-created)
├── vendor/ (after composer install)
├── cli.php
├── index.php
├── test.php
├── composer.json
├── .gitignore
└── README.md
```

## Configuration

### Using LLM for Generation

Set the `OPENAI_API_KEY` environment variable:
```bash
export OPENAI_API_KEY="sk-..."
```

Without an API key, the generator uses deterministic patterns based on prompt keywords.

### Storage Location

The default storage location is `storage/latest_name.json`. You can modify this in the NameStorage constructor.

## Development

### Adding New Nickname Mappings

Edit `src/Verifier/NameVerifier.php` and update the `initializeNicknames()` method:

```php
$this->nicknames = [
    'new_nickname' => 'formal_name',
    // ...
];
```

### Adjusting Match Threshold

In `src/Verifier/NameVerifier.php`, modify the threshold in the `verify()` method:

```php
$match = $confidence >= 0.75; // Adjust this value (0.0 to 1.0)
```

## Troubleshooting

### Tests Failing

If tests are failing, check:
1. The match threshold (may need adjustment)
2. Nickname mappings (ensure all test case nicknames are covered)
3. Transliteration rules (Arabic/Slavic name variations)

### No Composer

If you don't have Composer installed, you can create a simple autoloader:

```php
// autoload.php
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
```

Then replace `require_once __DIR__ . '/vendor/autoload.php';` with `require_once __DIR__ . '/autoload.php';` in all files.

## License

This is a technical assessment project for Abode Money.

## Support

For issues or questions, please refer to the technical assessment documentation or contact the assessment coordinator.