# Name Verification Application

Technical assessment solution for Abode Money - A name verification system with fuzzy matching capabilities.

## Quick Start

### Option 1: Using PHP's Built-in Server (Recommended)

```bash
# Clone the repository
git clone git@github.com:oktopeak/name_verification.git
cd name_verification

# Start the web server
php -S localhost:8000 index.php
```

Open your browser and navigate to: **http://localhost:8000**

### Option 2: Command Line Interface

```bash
# Run the interactive CLI
php cli.php
```

## Testing the Application

### Run the Full Test Suite

```bash
php test.php
```

**Expected Result:** All 30 test cases should pass (100% success rate)

### Manual Testing Examples

#### 1. Generate a Target Name
**Web Interface:**
- Enter prompt: "Generate a random Arabic sounding name with an Al and ibn both involved"
- Click "Generate Name"

**CLI:**
- Select option 1
- Enter the same prompt

#### 2. Test Matching Cases (Should Match ✅)

Try these candidate names against generated targets:

| Target Name | Test Candidate | Expected Result |
|-------------|---------------|-----------------|
| Tyler Bliha | Tlyer Bilha | ✅ Match (typos) |
| Bob Ellensworth | Robert Ellensworth | ✅ Match (nickname) |
| Mohammed Al Fayed | Muhammad Alfayed | ✅ Match (transliteration) |
| Sarah O'Connor | Sara Oconnor | ✅ Match (punctuation) |

#### 3. Test Non-Matching Cases (Should NOT Match ❌)

| Target Name | Test Candidate | Expected Result |
|-------------|---------------|-----------------|
| Ali Hassan | Hassan Ali | ❌ No Match (order swap) |
| John Smith | James Smith | ❌ No Match (different name) |
| Michael Thompson | Michelle Thompson | ❌ No Match (gender difference) |
| William Carter | Liam Carter | ❌ No Match (no nickname mapping) |

## Features Demonstrated

### ✅ Fuzzy Matching Capabilities
- **Typos & Misspellings**: Tyler → Tlyer
- **Nicknames**: Bob → Robert, Liz → Elizabeth
- **Transliterations**: Mohammed → Muhammad
- **Case Insensitive**: JOHN → john
- **Punctuation Handling**: O'Connor → Oconnor
- **Hyphenation**: Al-Hassan → Al Hassan

### ✅ Correct Rejections
- Name order swaps (Ali Hassan ≠ Hassan Ali)
- Gender-specific variations (Michael ≠ Michelle)
- Different names with same surname
- Similar prefixes but distinct names (Christopher ≠ Christian)

## Architecture Highlights

### Black Box Design ✅
The verifier component treats the generator as a **black box**:
- Only accesses the stored target name string
- No access to generator context or history
- Cannot call back into the generator
- Architecturally isolated components

### Storage
- Simple JSON file storage in `storage/latest_name.json`
- Maintains only the latest generated target name

## System Requirements

- **PHP**: Version 7.4 or higher
- **No external dependencies required** (custom autoloader included)
- **Optional**: Composer for dependency management
- **Optional**: OpenAI API key for LLM-based generation (falls back to deterministic generation)

## Adding OpenAI API Key (Optional)

The application works without an API key using deterministic generation. To enable LLM-powered generation:

### Method 1: Edit config.php (Easiest)
```php
// Open config.php and add your key:
'openai_api_key' => 'sk-proj-your-actual-api-key-here',
```

### Method 2: Environment Variable
```bash
# Linux/Mac
export OPENAI_API_KEY="sk-proj-your-actual-api-key-here"

# Windows
set OPENAI_API_KEY=sk-proj-your-actual-api-key-here
```

### Method 3: .env File
```bash
# Copy the example file
cp .env.example .env

# Edit .env and add your key
OPENAI_API_KEY=sk-proj-your-actual-api-key-here
```

**Get your API key from:** https://platform.openai.com/api-keys

⚠️ **Security Note:** Never commit your actual API key to version control!

## Installation Options

### Without Composer (Simplest)
```bash
# Just clone and run - no installation needed!
git clone git@github.com:oktopeak/name_verification.git
cd name_verification
php -S localhost:8000 index.php
```

### With Composer (Optional)
```bash
git clone git@github.com:oktopeak/name_verification.git
cd name_verification
composer install
composer serve  # Starts web server
```

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