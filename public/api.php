<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/app/Helpers/helpers.php';
require_file('app/Repositories/JsonRepository.php');
require_file('app/Repositories/ProviderVaultRepository.php');
require_file('app/Services/EncryptionService.php');
require_file('app/Services/AiProviderService.php');
require_file('app/Services/PromptIntentAnalyzer.php');
require_file('app/Services/MissingDetailsDetector.php');
require_file('app/Services/StyleDirector.php');
require_file('app/Services/AntiAiStyleEngine.php');
require_file('app/Services/BrandProfileInjector.php');
require_file('app/Services/PromptScoreService.php');
require_file('app/Services/OutputFormatter.php');
require_file('app/Services/EnglishPromptCorrectionService.php');

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; connect-src \'self\'; base-uri \'self\'; form-action \'self\'; frame-ancestors \'self\'');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

verify_csrf_header();
rate_limit_or_fail();

$raw = file_get_contents('php://input') ?: '';
$input = json_decode($raw, true);

if (!is_array($input)) {
    json_response(['ok' => false, 'error' => 'Invalid JSON payload.'], 422);
}

$prompt = trim((string)($input['prompt'] ?? ''));
if ($prompt === '' || app_strlen($prompt) < 5) {
    json_response(['ok' => false, 'error' => 'Please enter a prompt with at least 5 characters.'], 422);
}

if (app_strlen($prompt) > 3000) {
    json_response(['ok' => false, 'error' => 'Prompt is too long for this MVP. Max length is 3000 characters.'], 422);
}

$spellCorrection = (new EnglishPromptCorrectionService())->correct($prompt, (bool)($input['fix_english'] ?? true));
$workingPrompt = (string)($spellCorrection['corrected'] ?? $prompt);
$input['original_prompt_raw'] = $prompt;
$input['prompt'] = $workingPrompt;
$input['spell_correction'] = $spellCorrection;

$repo = new JsonRepository();
$presets = $repo->read('style_presets.json', []);
$patterns = $repo->read('negative_patterns.json', []);
$profiles = $repo->read('brand_profiles.json', []);
$config = require dirname(__DIR__) . '/config/app.php';

$encryption = new EncryptionService((string)($config['provider_vault']['encryption_secret'] ?? ''));
$vault = new ProviderVaultRepository($repo, $encryption);
$providerService = new AiProviderService($vault);
$provider = $providerService->resolve((string)($input['ai_provider'] ?? 'default'));

$intent = (new PromptIntentAnalyzer())->analyze($workingPrompt, (string)($input['type'] ?? 'auto'));
$details = (new MissingDetailsDetector())->fill($intent, $input);
$style = (new StyleDirector())->choose($intent, (string)($input['style'] ?? 'auto'), $presets);
$antiAiEnabled = (bool)($input['anti_ai'] ?? true);
$constraints = (new AntiAiStyleEngine())->buildConstraints($patterns, $antiAiEnabled);
$negativePrompt = (new AntiAiStyleEngine())->negativePrompt($constraints, $style);
$brand = (new BrandProfileInjector())->getProfile((string)($input['brand_profile'] ?? 'general_project'), $profiles);
$scoreService = new PromptScoreService();
$score = $scoreService->score($intent, $details, $style, $antiAiEnabled);
$warning = $scoreService->warning($input);

$output = (new OutputFormatter())->format($input, $intent, $details, $style, $brand, $constraints, $negativePrompt, $score, $warning, $provider);
$liveResult = $providerService->generate((string)($input['ai_provider'] ?? 'default'), $input, $output);
$output = $liveResult['output'] ?? $output;

$repo->prependHistory([
    'id' => bin2hex(random_bytes(8)),
    'created_at' => gmdate('c'),
    'original_prompt' => $prompt,
    'corrected_prompt' => $workingPrompt,
    'type' => $intent['type'],
    'style' => $style['name'] ?? 'Unknown',
    'platform' => $input['platform'] ?? 'general',
    'ai_provider' => $provider['key'] ?? 'local_rule_engine',
    'output' => $output,
], (int)($config['history_limit'] ?? 200));

json_response(['ok' => true, 'data' => $output]);
