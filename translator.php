<?php
/**
 * LibreTranslate API Integration (FREE & OPEN SOURCE)
 * 
 * LibreTranslate is a free and open-source translation API
 * No API key required for public instances!
 * 
 * Place this file as: includes/libre-translator.php
 * 
 * FEATURES:
 * ✅ 100% FREE
 * ✅ No API key required
 * ✅ Open source
 * ✅ Privacy-focused
 * ✅ Self-hostable
 * 
 * PUBLIC INSTANCES:
 * - https://libretranslate.com (official, free)
 * - https://translate.argosopentech.com (alternative)
 * - https://translate.terraprint.co (alternative)
 */

class LibreTranslator {
    
    // ============================================
    // CONFIGURATION
    // ============================================
    
    private $apiUrl = 'https://libretranslate.com/translate';
    private $apiKey = null; // Optional - leave null for free usage
    private $cacheEnabled = true;
    private $cacheDir = __DIR__ . '/../cache/translations/';
    private $timeout = 15; // API timeout in seconds
    
    /**
     * Constructor
     */
    public function __construct() {
        if ($this->cacheEnabled && !is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    // ============================================
    // PUBLIC METHODS
    // ============================================
    
    /**
     * Translate text
     * 
     * @param string $text Text to translate
     * @param string $targetLang Target language (es, fr, de, etc.)
     * @param string $sourceLang Source language (default: en)
     * @return string Translated text
     */
    public function translate($text, $targetLang, $sourceLang = 'en') {
        // Validation
        if (empty($text)) {
            return '';
        }
        
        // If same language, return original
        if ($targetLang === $sourceLang) {
            return $text;
        }
        
        // Check cache
        if ($this->cacheEnabled) {
            $cached = $this->getFromCache($text, $targetLang, $sourceLang);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // Make API request
        $translated = $this->makeTranslationRequest($text, $targetLang, $sourceLang);
        
        // Cache result
        if ($this->cacheEnabled && $translated !== null) {
            $this->saveToCache($text, $translated, $targetLang, $sourceLang);
        }
        
        return $translated ?? $text;
    }
    
    /**
     * Translate multiple texts (batch)
     * 
     * @param array $texts Array of texts
     * @param string $targetLang Target language
     * @param string $sourceLang Source language
     * @return array Translated texts
     */
    public function translateBatch($texts, $targetLang, $sourceLang = 'en') {
        $translated = [];
        
        foreach ($texts as $text) {
            $translated[] = $this->translate($text, $targetLang, $sourceLang);
        }
        
        return $translated;
    }
    
    /**
     * Detect language of text
     * 
     * @param string $text Text to detect
     * @return string|null Language code or null
     */
    public function detectLanguage($text) {
        if (empty($text)) {
            return null;
        }
        
        $url = 'https://libretranslate.com/detect';
        
        $data = ['q' => $text];
        if ($this->apiKey) {
            $data['api_key'] = $this->apiKey;
        }
        
        $response = $this->makeCurlRequest($url, $data);
        
        if ($response && isset($response[0]['language'])) {
            return $response[0]['language'];
        }
        
        return null;
    }
    
    /**
     * Get list of supported languages
     * 
     * @return array Language codes
     */
    public function getSupportedLanguages() {
        return [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'tr' => 'Turkish',
            'sv' => 'Swedish',
            'cs' => 'Czech',
            'uk' => 'Ukrainian',
            'fa' => 'Persian',
            'vi' => 'Vietnamese',
            'id' => 'Indonesian',
            'th' => 'Thai',
            'el' => 'Greek',
            'ro' => 'Romanian',
            'hu' => 'Hungarian',
            'da' => 'Danish',
            'fi' => 'Finnish',
            'no' => 'Norwegian',
            'sk' => 'Slovak',
            'bg' => 'Bulgarian',
            'ca' => 'Catalan',
            'hr' => 'Croatian',
            'lt' => 'Lithuanian',
            'sl' => 'Slovenian',
            'et' => 'Estonian',
            'lv' => 'Latvian'
        ];
    }
    
    // ============================================
    // PRIVATE METHODS
    // ============================================
    
    /**
     * Make translation API request
     * 
     * @param string $text Text to translate
     * @param string $targetLang Target language
     * @param string $sourceLang Source language
     * @return string|null Translated text or null
     */
    private function makeTranslationRequest($text, $targetLang, $sourceLang) {
        $data = [
            'q' => $text,
            'source' => $sourceLang,
            'target' => $targetLang,
            'format' => 'text'
        ];
        
        if ($this->apiKey) {
            $data['api_key'] = $this->apiKey;
        }
        
        $response = $this->makeCurlRequest($this->apiUrl, $data);
        
        if ($response && isset($response['translatedText'])) {
            return $response['translatedText'];
        }
        
        return null;
    }
    
    /**
     * Make cURL request
     * 
     * @param string $url API URL
     * @param array $data POST data
     * @return array|null Response data or null
     */
    private function makeCurlRequest($url, $data) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Handle errors
        if ($error) {
            error_log("LibreTranslate cURL error: " . $error);
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("LibreTranslate API error (HTTP $httpCode): " . $response);
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("LibreTranslate JSON decode error: " . json_last_error_msg());
            return null;
        }
        
        return $result;
    }
    
    // ============================================
    // CACHE METHODS
    // ============================================
    
    private function getFromCache($text, $targetLang, $sourceLang) {
        $cacheKey = $this->getCacheKey($text, $targetLang, $sourceLang);
        $cacheFile = $this->cacheDir . $cacheKey . '.txt';
        
        if (file_exists($cacheFile)) {
            return file_get_contents($cacheFile);
        }
        
        return null;
    }
    
    private function saveToCache($text, $translated, $targetLang, $sourceLang) {
        $cacheKey = $this->getCacheKey($text, $targetLang, $sourceLang);
        $cacheFile = $this->cacheDir . $cacheKey . '.txt';
        
        file_put_contents($cacheFile, $translated);
    }
    
    private function getCacheKey($text, $targetLang, $sourceLang) {
        return md5('libre|' . $text . '|' . $sourceLang . '|' . $targetLang);
    }
    
    /**
     * Clear cache
     */
    public function clearCache() {
        if (!is_dir($this->cacheDir)) {
            return;
        }
        
        $files = glob($this->cacheDir . '*.txt');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get LibreTranslator instance
 */
function getLibreTranslator() {
    static $translator = null;
    if ($translator === null) {
        $translator = new LibreTranslator();
    }
    return $translator;
}

/**
 * Quick translation helper
 * Usage: echo lt('Hello World');
 */
function lt($text) {
    $translator = getLibreTranslator();
    $currentLanguage = getCurrentLanguage();
    
    if ($currentLanguage === 'en') {
        return $text;
    }
    
    return $translator->translate($text, $currentLanguage);
}

/**
 * Get current language
 */
function getCurrentLanguage() {
    return $_COOKIE['site_language'] ?? $_SESSION['site_language'] ?? 'en';
}

/**
 * Set language
 */
function setLanguage($langCode) {
    $_SESSION['site_language'] = $langCode;
    setcookie('site_language', $langCode, time() + (86400 * 365), '/');
}