<?php

declare(strict_types=1);

final class StyleDirector
{
    public function choose(array $intent, string $selectedStyle, array $presets): array
    {
        if ($selectedStyle !== 'auto' && isset($presets[$selectedStyle])) {
            return $presets[$selectedStyle];
        }

        $type = $intent['type'];
        $domain = $intent['domain'];

        $styleKey = match (true) {
            $domain === 'aluminum and windows' && $type !== 'landing' => 'documentary_product_direction',
            $domain === 'kitchen design' => 'high_end_interior_editorial',
            $type === 'image' => 'editorial_collage',
            $type === 'video' => 'cinematic_product_direction',
            $type === 'landing' => 'minimal_editorial_web',
            $type === 'code' => 'structured_developer_brief',
            default => 'auto_prompt_director',
        };

        return $presets[$styleKey] ?? reset($presets);
    }
}
