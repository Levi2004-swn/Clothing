<?php
/**
 * Language Switch AJAX Endpoint
 * 
 * This file handles language switching requests via AJAX
 * Place this file as: ajax/switch-language.php
 * 
 * USAGE:
 * POST/GET to this file with 'lang' parameter
 * 
 * Example:
 * $.post('ajax/switch-language.php', {lang: 'es'}, function(response) {
 *     console.log(response);
 * });
 */

// Start session
session_start();

// Set JSON header
header('Content-Type: application/json');

// Enable CORS if needed (for testing)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// ============================================
// CONFIGURATION
// ============================================

// List of supported languages
$validLanguages = [
    'en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 
    'zh-CN', 'zh-TW', 'ar', 'hi', 'bn', 'pa', 'te', 'mr', 
    'ta', 'ur', 'vi', 'th', 'tr', 'pl', 'uk', 'ro', 'nl', 
    'el', 'cs', 'sv', 'hu', 'fi', 'da', 'no', 'id', 'ms', 'tl'
];

// Language names for response
$languageNames = [
    'en' => 'English',
    'es' => 'Spanish (Español)',
    'fr' => 'French (Français)',
    'de' => 'German (Deutsch)',
    'it' => 'Italian (Italiano)',
    'pt' => 'Portuguese (Português)',
    'ru' => 'Russian (Русский)',
    'ja' => 'Japanese (日本語)',
    'ko' => 'Korean (한국어)',
    'zh-CN' => 'Chinese Simplified (简体中文)',
    'zh-TW' => 'Chinese Traditional (繁體中文)',
    'ar' => 'Arabic (العربية)',
    'hi' => 'Hindi (हिन्दी)',
    'bn' => 'Bengali (বাংলা)',
    'pa' => 'Punjabi (ਪੰਜਾਬੀ)',
    'te' => 'Telugu (తెలుగు)',
    'mr' => 'Marathi (मराठी)',
    'ta' => 'Tamil (தமிழ்)',
    'ur' => 'Urdu (اردو)',
    'vi' => 'Vietnamese (Tiếng Việt)',
    'th' => 'Thai (ไทย)',
    'tr' => 'Turkish (Türkçe)',
    'pl' => 'Polish (Polski)',
    'uk' => 'Ukrainian (Українська)',
    'ro' => 'Romanian (Română)',
    'nl' => 'Dutch (Nederlands)',
    'el' => 'Greek (Ελληνικά)',
    'cs' => 'Czech (Čeština)',
    'sv' => 'Swedish (Svenska)',
    'hu' => 'Hungarian (Magyar)',
    'fi' => 'Finnish (Suomi)',
    'da' => 'Danish (Dansk)',
    'no' => 'Norwegian (Norsk)',
    'id' => 'Indonesian (Bahasa Indonesia)',
    'ms' => 'Malay (Bahasa Melayu)',
    'tl' => 'Filipino (Tagalog)'
];

// ============================================
// GET REQUEST PARAMETERS
// ============================================

// Get language code from POST or GET
$langCode = $_POST['lang'] ?? $_GET['lang'] ?? '';

// Clean and validate language code
$langCode = trim($langCode);
$langCode = strtolower($langCode);

// Special case for Chinese
if ($langCode === 'zh-cn' || $langCode === 'zh_cn') {
    $langCode = 'zh-CN';
} elseif ($langCode === 'zh-tw' || $langCode === 'zh_tw') {
    $langCode = 'zh-TW';
}

// ============================================
// VALIDATION
// ============================================

// Check if language code is provided
if (empty($langCode)) {
    echo json_encode([
        'success' => false,
        'error' => 'NO_LANGUAGE_CODE',
        'message' => 'Language code is required',
        'current_language' => $_SESSION['site_language'] ?? 'en'
    ]);
    exit;
}

// Check if language code is valid
if (!in_array($langCode, $validLanguages)) {
    echo json_encode([
        'success' => false,
        'error' => 'INVALID_LANGUAGE',
        'message' => 'Invalid language code: ' . $langCode,
        'valid_languages' => $validLanguages,
        'current_language' => $_SESSION['site_language'] ?? 'en'
    ]);
    exit;
}

// ============================================
// SET LANGUAGE
// ============================================

try {
    // Set in session
    $_SESSION['site_language'] = $langCode;
    
    // Set in cookie (expires in 1 year)
    $cookieSet = setcookie(
        'site_language', 
        $langCode, 
        time() + (86400 * 365), // 1 year
        '/', // Path
        '', // Domain (current domain)
        false, // Secure (set to true if using HTTPS)
        true // HttpOnly
    );
    
    // Log language change (optional)
    if (function_exists('error_log')) {
        error_log("Language changed to: $langCode by IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    // ============================================
    // SUCCESS RESPONSE
    // ============================================
    
    echo json_encode([
        'success' => true,
        'language' => $langCode,
        'language_name' => $languageNames[$langCode] ?? $langCode,
        'message' => 'Language changed successfully to ' . ($languageNames[$langCode] ?? $langCode),
        'cookie_set' => $cookieSet,
        'session_set' => isset($_SESSION['site_language']),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // ============================================
    // ERROR RESPONSE
    // ============================================
    
    echo json_encode([
        'success' => false,
        'error' => 'SET_LANGUAGE_FAILED',
        'message' => 'Failed to set language: ' . $e->getMessage(),
        'current_language' => $_SESSION['site_language'] ?? 'en'
    ]);
}