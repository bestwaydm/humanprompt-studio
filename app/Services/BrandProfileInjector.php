<?php

declare(strict_types=1);

final class BrandProfileInjector
{
    public function getProfile(string $profileKey, array $profiles): array
    {
        if ($profileKey && isset($profiles[$profileKey])) {
            return $profiles[$profileKey];
        }

        return $profiles['general_project'] ?? [];
    }

    public function summarize(array $profile): string
    {
        if (!$profile) {
            return 'No project context selected.';
        }

        $parts = [];
        $parts[] = 'Project context: ' . ($profile['brand'] ?? ($profile['name'] ?? 'General Project'));
        $parts[] = 'Market: ' . ($profile['market'] ?? 'Global / not specified');
        $parts[] = 'Tone: ' . ($profile['tone'] ?? 'Clear and practical');

        if (!empty($profile['preferred_terms'])) {
            $parts[] = 'Preferred terms: ' . implode(', ', $profile['preferred_terms']);
        }

        if (!empty($profile['avoid'])) {
            $parts[] = 'Context must avoid: ' . implode(', ', $profile['avoid']);
        }

        return implode("\n", $parts);
    }
}
