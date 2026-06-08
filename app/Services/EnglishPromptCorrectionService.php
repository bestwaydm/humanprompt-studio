<?php

declare(strict_types=1);

final class EnglishPromptCorrectionService
{
    /**
     * Lightweight local correction before the request is sent to the live model.
     * It intentionally fixes common spelling/grammar issues without changing the user's intent.
     */
    public function correct(string $prompt, bool $enabled = true): array
    {
        $original = trim($prompt);
        if (!$enabled || $original === '' || !$this->looksMostlyEnglish($original)) {
            return [
                'enabled' => $enabled,
                'applied' => false,
                'original' => $original,
                'corrected' => $original,
                'changes' => [],
                'note' => $enabled ? 'No English spelling correction was needed or the prompt is not mostly English.' : 'English spelling correction is disabled.',
            ];
        }

        $corrected = $original;
        $changes = [];

        $replacements = [
            '/\bbussiness\b/i' => 'business',
            '/\bbuisness\b/i' => 'business',
            '/\bbusinesss\b/i' => 'business',
            '/\bnusring\b/i' => 'nursing',
            '/\bnusing\b/i' => 'nursing',
            '/\bnurseing\b/i' => 'nursing',
            '/\bintervetions\b/i' => 'interventions',
            '/\bintervensions\b/i' => 'interventions',
            '/\bintervations\b/i' => 'interventions',
            '/\bintervetion\b/i' => 'intervention',
            '/\bfacebook\b/i' => 'Facebook',
            '/\bnusr\b/i' => 'nurse',
            '/\bregistred\b/i' => 'registered',
            '/\blicenced\b/i' => 'licensed',
            '/\bproffesional\b/i' => 'professional',
            '/\bprofesional\b/i' => 'professional',
            '/\bafordable\b/i' => 'affordable',
            '/\bcompasionate\b/i' => 'compassionate',
            '/\bquailty\b/i' => 'quality',
            '/\bqualtiy\b/i' => 'quality',
            '/\bservcie\b/i' => 'service',
            '/\bservcies\b/i' => 'services',
            '/\bdesgin\b/i' => 'design',
            '/\bcontant\b/i' => 'content',
            '/\baudiencee\b/i' => 'audience',
            '/\bmarkting\b/i' => 'marketing',
            '/\bcampain\b/i' => 'campaign',
            '/\bcampagin\b/i' => 'campaign',
            '/\binterseted\b/i' => 'interested',
            '/\bconsern\b/i' => 'concern',
            '/\bconcernes\b/i' => 'concerns',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $before = $corrected;
            $corrected = preg_replace($pattern, $replacement, $corrected) ?? $corrected;
            if ($before !== $corrected) {
                $changes[] = trim($pattern, '/i') . ' → ' . $replacement;
            }
        }

        $grammarReplacements = [
            '/\bmake an ad for Facebook post\b/i' => 'make a Facebook post ad',
            '/\bfor my business\s+Registered Nurse\b/i' => 'for my registered nurse business',
            '/\bfor all Home Care Nursing Interventions\b/i' => 'for home care nursing interventions',
            '/\bWith Good Price and High Concern\b/i' => 'with affordable pricing and compassionate care',
            '/\bWith Low Price and High Concern\b/i' => 'with affordable pricing and compassionate care',
            '/\bCall me now\b/i' => 'Call me now',
        ];

        foreach ($grammarReplacements as $pattern => $replacement) {
            $before = $corrected;
            $corrected = preg_replace($pattern, $replacement, $corrected) ?? $corrected;
            if ($before !== $corrected) {
                $changes[] = trim($pattern, '/i') . ' → ' . $replacement;
            }
        }

        $corrected = preg_replace('/[ \t]+/', ' ', $corrected) ?? $corrected;
        $corrected = preg_replace('/\s+\n/', "\n", $corrected) ?? $corrected;
        $corrected = trim($corrected);

        $applied = $corrected !== $original;

        return [
            'enabled' => true,
            'applied' => $applied,
            'original' => $original,
            'corrected' => $corrected,
            'changes' => array_values(array_unique($changes)),
            'note' => $applied ? 'English spelling/grammar correction was applied before enhancement.' : 'English spelling/grammar correction was enabled, but no obvious errors were detected.',
        ];
    }

    private function looksMostlyEnglish(string $text): bool
    {
        $letters = preg_match_all('/[A-Za-z]/', $text) ?: 0;
        $arabic = preg_match_all('/\p{Arabic}/u', $text) ?: 0;
        return $letters >= 8 && $letters >= $arabic;
    }
}
