<?php

declare(strict_types=1);

final class AntiAiStyleEngine
{
    public function buildConstraints(array $negativePatterns, bool $enabled): array
    {
        if (!$enabled) {
            return [
                'avoid' => [],
                'replace_with' => [],
            ];
        }

        return [
            'avoid' => $negativePatterns['visual_ai_patterns'] ?? [],
            'replace_with' => $negativePatterns['replacement_directions'] ?? [],
        ];
    }

    public function negativePrompt(array $constraints, array $style, string $language = 'English'): string
    {
        $avoid = array_merge($constraints['avoid'] ?? [], $style['avoid'] ?? []);
        $avoid = array_values(array_unique(array_filter(array_map('strval', $avoid))));

        if (!$avoid) {
            return $this->isArabic($language)
                ? 'تجنّب التوجيه الغامض، المخرج العام، ضعف التسلسل، وعدم وضوح المطلوب.'
                : 'Avoid vague direction, generic output, weak hierarchy, and unclear deliverables.';
        }

        // Keep the negative prompt readable. A very long blacklist makes the final result feel mechanical.
        $avoid = array_slice($avoid, 0, 10);

        if ($this->isArabic($language)) {
            return 'تجنّب: ' . implode('، ', array_map([$this, 'arabicTerm'], $avoid)) . '.';
        }

        return 'Avoid: ' . implode(', ', $avoid) . '.';
    }

    private function isArabic(string $language): bool
    {
        return app_strtolower($language) === 'arabic';
    }

    private function arabicTerm(string $term): string
    {
        return match ($term) {
            'centered hero layout' => 'hero متمركز بشكل قالب جاهز',
            'symmetric card grid' => 'شبكة بطاقات متناظرة ومكررة',
            'glossy blue dashboard' => 'ستايل داشبورد أزرق لامع',
            'neon gradient background' => 'خلفيات نيون أو تدرجات صارخة',
            'generic vector icons' => 'أيقونات vector عامة ومستهلكة',
            'glassmorphism' => 'glassmorphism زائد وغير مبرر',
            'floating hologram UI' => 'واجهات هولوجرام أو عناصر عائمة بلا سبب',
            'corporate AI infographic style' => 'شكل إنفوجراف corporate مولّد بالذكاء الاصطناعي',
            'perfect stock photo smile' => 'ابتسامات وصور stock مصطنعة',
            'over-polished artificial composition' => 'تركيب مصقول أكثر من اللازم ويبدو آليًا',
            'forced country-specific assumptions' => 'افتراض دولة أو سوق غير مذكور',
            'unrequested local-market claims' => 'ادعاءات محلية لم يطلبها المستخدم',
            'template-looking hero section' => 'hero section يشبه القوالب الجاهزة',
            'generic gradient blobs' => 'بقع تدرج عامة ومكررة',
            'too many floating cards' => 'بطاقات عائمة كثيرة بلا وظيفة',
            'country assumptions not requested by the user' => 'افتراض بلد لم يذكره المستخدم',
            default => $term,
        };
    }
}
