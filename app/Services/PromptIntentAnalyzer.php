<?php

declare(strict_types=1);

final class PromptIntentAnalyzer
{
    public function analyze(string $prompt, string $selectedType = 'auto'): array
    {
        $text = app_strtolower($prompt);
        $type = $selectedType !== 'auto' ? $selectedType : $this->detectType($text);

        return [
            'type' => $type,
            'domain' => $this->detectDomain($text),
            'goal' => $this->detectGoal($text, $type),
            'platform_hint' => $this->detectPlatformHint($text),
            'needs_anti_ai' => in_array($type, ['image', 'video', 'ad', 'landing'], true),
            'intent_summary' => $this->intentSummary($type),
        ];
    }

    private function detectType(string $text): string
    {
        // Order matters: specific outputs should be detected before broad words like "design".
        $map = [
            'video' => ['video', 'scene', 'camera', 'cinematic', 'reel', 'film', 'short video', 'فيديو', 'مشهد', 'كاميرا', 'سينمائي', 'ريل', 'لقطة'],
            'code' => ['code', 'php', 'javascript', 'typescript', 'python', 'bug', 'error', 'api', 'database', 'dashboard code', 'كود', 'برمجة', 'سكربت', 'خطأ', 'قاعدة بيانات', 'لوحة تحكم برمجية', 'اصلاح خطأ', 'إصلاح خطأ'],
            'article' => ['article', 'seo', 'blog', 'guide', 'content', 'copywriting', 'مقال', 'محتوى', 'دليل', 'سيو', 'وصف منتج', 'كتابة'],
            'landing' => ['landing page', 'homepage', 'website', 'web site', 'hero section', 'site design', 'website design', 'ui design', 'ux design', 'موقع', 'تصميم موقع', 'صفحة هبوط', 'لاندنج', 'صفحة رئيسية', 'واجهة موقع', 'ويب سايت', 'واجهة ويب', 'تصميم واجهة'],
            'ad' => ['ad', 'advertisement', 'campaign', 'social media', 'facebook', 'instagram', 'tiktok ad', 'اعلان', 'إعلان', 'حملة', 'فيسبوك', 'انستغرام', 'ممولة', 'بوست اعلاني', 'إعلاني'],
            'image' => ['image', 'poster', 'logo', 'infographic', 'visual', 'thumbnail', 'cover', 'صورة', 'بوستر', 'شعار', 'انفوجراف', 'إنفوجراف', 'تصميم صورة', 'غلاف'],
            'planning' => ['plan', 'prd', 'roadmap', 'strategy', 'compare', 'comparison', 'research', 'study', 'feasibility', 'analysis', 'خطة', 'دراسة', 'مقارنة', 'استراتيجية', 'تحليل', 'بحث', 'جدوى', 'خطة مشروع', 'تخطيط'],
        ];

        foreach ($map as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $type;
                }
            }
        }

        return 'planning';
    }

    private function detectDomain(string $text): string
    {
        $domains = [
            'aluminum and windows' => ['aluminum', 'aluminium', 'window', 'windows', 'door', 'doors', 'sound insulation', 'double glass', 'المنيوم', 'ألمنيوم', 'الومنيوم', 'ألومنيوم', 'شبابيك', 'نوافذ', 'ابواب', 'أبواب', 'دبل جلاس', 'عازل'],
            'kitchen design' => ['kitchen', 'cabinet', 'countertop', 'interior', 'مطبخ', 'مطابخ', 'خزائن'],
            'real estate' => ['apartment', 'villa', 'property', 'real estate', 'عقار', 'شقة', 'فيلا'],
            'technology' => ['software', 'app', 'ai', 'dashboard', 'code', 'تطبيق', 'برنامج', 'ذكاء اصطناعي', 'لوحة تحكم'],
            'education' => ['course', 'lesson', 'learn', 'training', 'دورة', 'درس', 'تعليم', 'تعلم'],
            'health' => ['medical', 'health', 'clinic', 'doctor', 'صحة', 'طبي', 'عيادة', 'طبيب'],
            'business' => ['business', 'entrepreneur', 'company', 'brand', 'startup', 'شركة', 'براند', 'مشروع', 'اعمال', 'أعمال'],
        ];

        foreach ($domains as $domain => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $domain;
                }
            }
        }

        return 'general';
    }

    private function detectGoal(string $text, string $type): string
    {
        if (str_contains($text, 'sell') || str_contains($text, 'customers') || str_contains($text, 'مبيعات') || str_contains($text, 'عملاء') || $type === 'ad') {
            return 'persuade the target audience and create a clear reason to act';
        }

        return match ($type) {
            'code' => 'create a precise developer-ready implementation prompt',
            'article' => 'create clear, useful, structured content that satisfies the reader intent',
            'video' => 'create a visual scene prompt with camera, motion, and production direction',
            'image' => 'create a visual prompt with subject, composition, style, and constraints',
            'landing' => 'create a structured UI/website design prompt with sections and UX direction',
            'planning' => 'create a structured research, planning, or analysis prompt with deliverables',
            default => 'produce a specific, useful, ready-to-use output',
        };
    }

    private function detectPlatformHint(string $text): string
    {
        foreach (['midjourney', 'ideogram', 'veo', 'chatgpt', 'gemini', 'claude', 'grok', 'cursor', 'windsurf', 'figma'] as $platform) {
            if (str_contains($text, $platform)) {
                return $platform;
            }
        }

        return 'general';
    }

    private function intentSummary(string $type): string
    {
        return match ($type) {
            'ad' => 'advertising / campaign prompt',
            'image' => 'visual generation prompt',
            'video' => 'video generation prompt',
            'landing' => 'website or UI design prompt',
            'article' => 'content / SEO writing prompt',
            'code' => 'software development prompt',
            'planning' => 'research / study / planning prompt',
            default => 'general prompt enhancement',
        };
    }
}
