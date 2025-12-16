<?php

/**
 * Configuration file for Name Verification Application
 *
 * IMPORTANT: Keep this file secure and never commit API keys to version control!
 */

return [
    /**
     * OpenAI API Configuration
     *
     * To use LLM-based name generation, add your OpenAI API key below.
     * Get your API key from: https://platform.openai.com/api-keys
     *
     * Options:
     * 1. Set directly here (not recommended for production)
     * 2. Use environment variable: getenv('OPENAI_API_KEY')
     * 3. Leave null to use deterministic generation (no API required)
     */
    'openai_api_key' => null, // Example: 'sk-proj-abc123xyz...'

    /**
     * Alternative: Use environment variable (recommended)
     * Uncomment the line below to read from environment
     */
    // 'openai_api_key' => getenv('OPENAI_API_KEY'),

    /**
     * Model Selection
     * Options: 'gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo'
     */
    'openai_model' => 'gpt-3.5-turbo',

    /**
     * Storage Configuration
     */
    'storage_path' => 'storage/latest_name.json',

    /**
     * Verifier Configuration
     */
    'match_threshold' => 0.75, // 75% confidence required for match

    /**
     * Debug Mode
     * Set to true to see detailed matching information
     */
    'debug' => false,
];