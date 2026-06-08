<?php

declare(strict_types=1);

final class MissingDetailsDetector
{
    public function fill(array $intent, array $input): array
    {
        $language = $this->normalizeLanguage(trim((string)($input['language'] ?? '')), (string)($input['prompt'] ?? ''));
        $isArabic = strtolower($language) === 'arabic';
        $type = (string)($intent['type'] ?? 'planning');
        $domain = (string)($intent['domain'] ?? 'general');

        return [
            'market' => trim((string)($input['market'] ?? '')) ?: ($isArabic ? 'عالمي / غير محدد' : 'Global / not specified'),
            'language' => $language,
            'audience' => trim((string)($input['audience'] ?? '')) ?: $this->defaultAudience($domain, $type, $isArabic),
            'tone' => trim((string)($input['tone'] ?? '')) ?: $this->defaultTone($type, $isArabic),
            'format' => trim((string)($input['format'] ?? '')) ?: $this->defaultFormat($type, $isArabic),
            'detail_level' => trim((string)($input['detail_level'] ?? 'professional')) ?: 'professional',
        ];
    }

    private function normalizeLanguage(string $language, string $prompt): string
    {
        if ($language !== '' && strtolower($language) !== 'auto') {
            return $language;
        }

        return preg_match('/[\x{0600}-\x{06FF}]/u', $prompt) ? 'Arabic' : 'English';
    }

    private function defaultAudience(string $domain, string $type, bool $isArabic): string
    {
        if ($isArabic) {
            if ($type === 'planning') {
                return 'صاحب قرار أو فريق عمل يحتاج دراسة منظمة وخطوات قابلة للتنفيذ';
            }
            if ($type === 'code') {
                return 'مطور أو Agent برمجي يحتاج تعليمات واضحة قابلة للتنفيذ';
            }
            if ($type === 'article') {
                return 'قراء يبحثون عن إجابة مفيدة ومنظمة وسهلة الفهم';
            }

            return match ($domain) {
                'aluminum and windows' => 'أصحاب المنازل والشقق والفلل والعملاء المهتمون بالتشطيب أو التجديد',
                'kitchen design' => 'أصحاب المنازل والعائلات والعملاء المهتمون بتجديد المطابخ والتصميم الداخلي',
                'technology' => 'أصحاب المشاريع، المطورون، مدراء المنتجات، وفرق العمل التقنية',
                'education' => 'متعلمون يحتاجون شرحًا واضحًا وتدرجًا عمليًا',
                default => 'الجمهور المناسب حسب طبيعة الطلب، بدون افتراض دولة أو هوية محلية',
            };
        }

        if ($type === 'planning') {
            return 'decision-makers or project teams who need a structured study with actionable steps';
        }
        if ($type === 'code') {
            return 'developers or coding agents who need implementation-ready instructions';
        }
        if ($type === 'article') {
            return 'readers looking for a useful, structured, easy-to-understand answer';
        }

        return match ($domain) {
            'aluminum and windows' => 'homeowners, renovation clients, contractors, and property owners',
            'kitchen design' => 'homeowners, families, interior design customers, and renovation clients',
            'technology' => 'founders, developers, product owners, and business teams',
            'education' => 'learners who need clear explanations and practical progression',
            default => 'the relevant audience based on the request, without forcing a country or local identity',
        };
    }

    private function defaultTone(string $type, bool $isArabic): string
    {
        if ($isArabic) {
            return match ($type) {
                'code' => 'دقيقة، منظمة، تقنية، وجاهزة للتنفيذ',
                'article' => 'واضحة، مفيدة، بشرية، ومناسبة للقارئ ومحركات البحث عند الحاجة',
                'video' => 'بصرية، محددة، ومرتبطة بمشهد قابل للتنفيذ',
                'ad' => 'مقنعة، واضحة، عملية، وموجهة للفعل',
                'planning' => 'تحليلية، منظمة، محايدة، وقابلة للتنفيذ',
                default => 'واضحة، عملية، احترافية، وبعيدة عن الكلام العام',
            };
        }

        return match ($type) {
            'code' => 'precise, structured, technical, and implementation-ready',
            'article' => 'clear, useful, human, and reader-first',
            'video' => 'visual, specific, and production-ready',
            'ad' => 'persuasive, clear, practical, and action-oriented',
            'planning' => 'analytical, structured, neutral, and actionable',
            default => 'clear, practical, professional, and non-generic',
        };
    }

    private function defaultFormat(string $type, bool $isArabic): string
    {
        if ($isArabic) {
            return match ($type) {
                'image' => 'برومبت تصميم بصري يتضمن الموضوع، التكوين، الأسلوب، الإضاءة، القيود، والمخرجات',
                'ad' => 'برومبت إعلان يتضمن الجمهور، العرض، الرسالة، CTA، الأسلوب البصري أو النصي، والقيود',
                'video' => 'برومبت فيديو يتضمن المشهد، اللقطات، حركة الكاميرا، الإضاءة، الإيقاع، والقيود',
                'article' => 'برومبت كتابة محتوى يتضمن الهدف، الجمهور، الهيكل، العناوين، النبرة، ومعايير الجودة',
                'code' => 'برومبت مهمة برمجية يتضمن المتطلبات، الملفات، المعمارية، الأمان، الحالات الطرفية، ومعايير القبول',
                'landing' => 'برومبت تصميم موقع أو واجهة يتضمن الأقسام، UX، الألوان، التجاوب، المحتوى، والقيود',
                'planning' => 'برومبت دراسة أو تحليل يتضمن الأسئلة الأساسية، المنهجية، المحاور، المخاطر، والمخرجات النهائية',
                default => 'برومبت منظم قابل للاستخدام في أي أداة ذكاء اصطناعي',
            };
        }

        return match ($type) {
            'image' => 'visual prompt with subject, composition, style, lighting, constraints, and output settings',
            'ad' => 'advertising prompt with audience, offer, message, CTA, visual/copy direction, and constraints',
            'video' => 'video prompt with scene, shots, camera movement, lighting, pacing, and constraints',
            'article' => 'content-writing prompt with goal, audience, structure, headings, tone, and quality rules',
            'code' => 'developer task prompt with requirements, files, architecture, security, edge cases, and acceptance criteria',
            'landing' => 'website/UI design prompt with sections, UX, colors, responsive behavior, content, and constraints',
            'planning' => 'research/planning prompt with key questions, methodology, sections, risks, and deliverables',
            default => 'structured prompt usable in any AI tool',
        };
    }
}
