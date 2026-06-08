<?php

declare(strict_types=1);

final class PromptScoreService
{
    public function score(array $intent, array $details, array $style, bool $antiAiEnabled): array
    {
        return [
            'goal_clarity' => 9,
            'style_strength' => !empty($style['must_include']) ? 9 : 7,
            'anti_ai_strength' => $antiAiEnabled ? 10 : 5,
            'output_specificity' => $details['format'] ? 9 : 7,
            'execution_readiness' => in_array($intent['type'], ['code', 'article'], true) ? 9 : 8,
        ];
    }

    public function warning(array $input): string
    {
        if (empty($input['platform']) || ($input['platform'] ?? 'general') === 'general') {
            return 'The prompt is strong, but selecting a target platform can make it more specific.';
        }

        return 'No critical warning. The prompt is ready to use.';
    }
}
