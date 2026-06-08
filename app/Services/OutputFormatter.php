<?php

declare(strict_types=1);

final class OutputFormatter
{
    public function format(array $input, array $intent, array $details, array $style, array $brand, array $constraints, string $negativePrompt, array $score, string $warning, array $provider = []): array
    {
        $originalPrompt = trim((string) $input['prompt']);
        $platform = trim((string)($input['platform'] ?? 'general')) ?: 'general';
        $outputGoal = trim((string)($input['output_goal'] ?? 'improve_prompt')) ?: 'improve_prompt';
        $outputStyle = trim((string)($input['output_style'] ?? 'structured_prompt')) ?: 'structured_prompt';
        $styleName = (string)($style['name'] ?? 'Auto Prompt Director');
        $mustInclude = array_values(array_filter(array_map('strval', $style['must_include'] ?? [])));
        $replaceWith = array_values(array_filter(array_map('strval', $constraints['replace_with'] ?? [])));
        $isArabic = strtolower((string)($details['language'] ?? '')) === 'arabic';
        $contextName = (string)($brand['brand'] ?? ($brand['name'] ?? 'General Project'));

        $improved = $isArabic
            ? $this->buildArabicPrompt($originalPrompt, $intent, $details, $styleName, $mustInclude, $replaceWith, $contextName, $platform, $negativePrompt)
            : $this->buildEnglishPrompt($originalPrompt, $intent, $details, $styleName, $mustInclude, $replaceWith, $contextName, $platform, $negativePrompt);

        $short = $isArabic
            ? $this->shortArabic($originalPrompt, $intent, $details)
            : $this->shortEnglish($originalPrompt, $intent, $details);

        $improved = $this->applyLocalOutputControls($improved, $short, $outputGoal, $outputStyle, $platform, $isArabic);

        return [
            'improved_prompt' => $improved,
            'short_prompt' => $short,
            'negative_prompt' => $negativePrompt,
            'english_prompt' => $isArabic ? $this->buildEnglishPrompt($originalPrompt, $intent, $details, $styleName, $mustInclude, $replaceWith, $contextName, $platform, $negativePrompt) : $improved,
            'why_stronger' => $isArabic
                ? "هذه النسخة أقوى لأنها تحوّل الطلب إلى أمر مباشر قابل للنسخ، وتحدد الهدف والجمهور والمخرجات والقيود حسب نوع الطلب ({$this->arabicLabel((string)$intent['type'])}) بدون افتراض بلد أو علامة تجارية غير مذكورة."
                : "This version is stronger because it turns the request into a direct paste-ready instruction with goal, audience, deliverables, constraints, and quality rules based on the request type ({$intent['type']}) without forcing any country or brand identity.",
            'score' => $score,
            'warning' => $warning,
            'provider' => $provider,
            'spell_correction' => $input['spell_correction'] ?? null,
            'settings' => [
                'prompt_type' => $intent['type'],
                'intent_summary' => $intent['intent_summary'] ?? $intent['type'],
                'domain' => $intent['domain'],
                'market' => $details['market'],
                'language' => $details['language'],
                'target_audience' => $details['audience'],
                'format' => $details['format'],
                'style' => $styleName,
                'platform' => $platform,
                'output_goal' => $outputGoal,
                'output_style' => $outputStyle,
                'project_context' => $contextName,
                'ai_provider' => $provider['name'] ?? 'Local Rule Engine',
            ],
        ];
    }

    private function buildEnglishPrompt(string $originalPrompt, array $intent, array $details, string $styleName, array $mustInclude, array $replaceWith, string $contextName, string $platform, string $negativePrompt): string
    {
        return match ((string)$intent['type']) {
            'ad' => $this->englishAd($originalPrompt, $details, $styleName, $mustInclude, $replaceWith, $negativePrompt),
            'image' => $this->englishImage($originalPrompt, $details, $styleName, $mustInclude, $replaceWith, $negativePrompt),
            'video' => $this->englishVideo($originalPrompt, $details, $styleName, $mustInclude, $replaceWith, $negativePrompt),
            'article' => $this->englishArticle($originalPrompt, $details),
            'code' => $this->englishCode($originalPrompt, $details),
            'landing' => $this->englishWebsite($originalPrompt, $details, $styleName, $mustInclude, $replaceWith, $negativePrompt),
            'planning' => $this->englishPlanning($originalPrompt, $details),
            default => $this->englishGeneral($originalPrompt, $details, $styleName, $negativePrompt),
        };
    }

    private function buildArabicPrompt(string $originalPrompt, array $intent, array $details, string $styleName, array $mustInclude, array $replaceWith, string $contextName, string $platform, string $negativePrompt): string
    {
        return match ((string)$intent['type']) {
            'ad' => $this->arabicAd($originalPrompt, $details, $styleName, $mustInclude, $replaceWith, $negativePrompt),
            'image' => $this->arabicImage($originalPrompt, $details, $styleName, $mustInclude, $replaceWith, $negativePrompt),
            'video' => $this->arabicVideo($originalPrompt, $details, $styleName, $mustInclude, $replaceWith, $negativePrompt),
            'article' => $this->arabicArticle($originalPrompt, $details),
            'code' => $this->arabicCode($originalPrompt, $details),
            'landing' => $this->arabicWebsite($originalPrompt, $details, $styleName, $mustInclude, $replaceWith, $negativePrompt),
            'planning' => $this->arabicPlanning($originalPrompt, $details),
            default => $this->arabicGeneral($originalPrompt, $details, $styleName, $negativePrompt),
        };
    }

    private function englishAd(string $originalPrompt, array $details, string $styleName, array $mustInclude, array $replaceWith, string $negativePrompt): string
    {
        return "Act as a senior advertising strategist and creative director. Create a complete advertising brief and final ad copy for: {$originalPrompt}\n\nDeliver:\n1. Campaign objective, target audience, customer pain point, and desired action.\n2. One strong core message with a main headline, supporting line, proof/trust element, and CTA.\n3. Three alternative headline angles.\n4. Visual or copy direction suitable for the selected platform.\n5. A concise final ad version ready to use.\n\nQuality rules: make it specific, practical, persuasive, and human-made. Do not assume a country, brand, price, offer, or claim not mentioned by the user.\n\nConstraints: {$this->cleanNegative($negativePrompt)}";
    }

    private function englishImage(string $originalPrompt, array $details, string $styleName, array $mustInclude, array $replaceWith, string $negativePrompt): string
    {
        return "Act as a senior visual art director. Create a final image-generation instruction for: {$originalPrompt}\n\nInclude:\n1. Main subject, setting, and realistic context.\n2. Composition, focal point, framing, depth, and visual hierarchy.\n3. Style direction: {$styleName}; use {$this->listForPrompt($mustInclude)} where relevant.\n4. Lighting, material texture, camera angle, mood, and realism level.\n5. Text/typography placement only if the image needs text.\n6. Output settings such as aspect ratio and final use.\n\nQuality rules: keep the direction specific, visual, and executable. Avoid empty adjectives and generic AI aesthetics.\n\nNegative prompt: {$this->cleanNegative($negativePrompt)}";
    }

    private function englishVideo(string $originalPrompt, array $details, string $styleName, array $mustInclude, array $replaceWith, string $negativePrompt): string
    {
        return "Act as a cinematic director. Create a final video-generation instruction for: {$originalPrompt}\n\nInclude:\n1. Scene concept and visual story beat.\n2. Shot sequence: opening, main action, detail shots, and closing.\n3. Camera movement, lens feel, angle, transitions, pacing, and duration.\n4. Lighting, environment, textures, realistic motion, and mood.\n5. Platform fit: aspect ratio, duration, and pacing.\n\nQuality rules: make the scene production-ready and avoid vague cinematic buzzwords.\n\nNegative prompt: {$this->cleanNegative($negativePrompt)}";
    }

    private function englishArticle(string $originalPrompt, array $details): string
    {
        return "Act as an expert content strategist and writer. Write a complete content plan and article-writing instruction for: {$originalPrompt}\n\nDeliver:\n1. Reader intent and target audience: {$details['audience']}.\n2. Clear angle and useful promise to the reader.\n3. Suggested H1 and full H2/H3 outline.\n4. A direct answer early in the article.\n5. Practical examples, steps, comparisons, warnings, and FAQs where useful.\n6. SEO elements: meta title, meta description, internal-link ideas, and schema suggestions when relevant.\n\nQuality rules: no unsupported claims, no filler, no fake data, and flag facts that may require verification.";
    }

    private function englishCode(string $originalPrompt, array $details): string
    {
        return "Act as a senior software architect and implementation engineer. Turn this request into a developer-ready task specification: {$originalPrompt}\n\nDeliver:\n1. Objective and expected behavior.\n2. Required files/modules and suggested architecture.\n3. Data model or API contract if needed.\n4. UI/UX requirements if there is an interface.\n5. Security requirements: validation, escaping, CSRF/auth, secret handling, and error handling.\n6. Edge cases and failure states.\n7. Acceptance criteria and test checklist.\n\nQuality rules: do not hard-code secrets, do not mix unrelated responsibilities, and make the implementation steps clear.";
    }

    private function englishWebsite(string $originalPrompt, array $details, string $styleName, array $mustInclude, array $replaceWith, string $negativePrompt): string
    {
        return "Act as a world-class UI/UX designer and web art director. Design a complete website concept for an aluminum company that needs modern, eye-comfortable colors and multiple useful sections.\n\nDeliver:\n1. Website goal and conversion goal.\n2. Recommended section structure, such as hero, value proposition, products/services, proof or portfolio, process, reviews, FAQ, and contact/CTA; adapt the sections to the business type.\n3. Visual direction with a calm modern color system, clear contrast, one primary CTA color, readable typography, generous spacing, and a clean responsive layout.\n4. UX direction for desktop, tablet, and mobile: navigation, content hierarchy, CTA visibility, scanning flow, and accessibility.\n5. Content expectations: real section headings, short useful copy, trust signals, and practical product/service details.\n6. Design quality constraints: avoid template-looking layouts, excessive decoration, random gradients, fake stock imagery, and over-polished AI aesthetics; use real-world context, believable product imagery, balanced asymmetry when useful, and restrained visual effects.\n7. Final delivery format: a structured website design brief suitable for Figma, Webflow, Framer, or a web developer.\n\nDo not assume a country, local market, company history, awards, prices, or claims that were not provided.";
    }

    private function englishPlanning(string $originalPrompt, array $details): string
    {
        return "Act as a professional research and planning consultant. Produce a structured study or analysis for: {$originalPrompt}\n\nDeliver:\n1. Objective and decision the study should support.\n2. Background, assumptions, and missing details inferred cautiously.\n3. Key questions that must be answered.\n4. Methodology: what to compare, what data is needed, and how to evaluate it.\n5. Sections: executive summary, options, pros/cons, risks, cost/time factors, implementation steps, and recommendation.\n6. Final output format: executive summary, detailed analysis, action plan, and checklist.\n\nQuality rules: separate facts from assumptions, state uncertainty, and do not invent unsupported claims.";
    }

    private function englishGeneral(string $originalPrompt, array $details, string $styleName, string $negativePrompt): string
    {
        return "Act as the most suitable expert for this request. Produce the final usable output for: {$originalPrompt}\n\nInclude:\n1. Objective.\n2. Audience.\n3. Context and assumptions.\n4. Required deliverables.\n5. Constraints and quality rules.\n6. Examples or structure where useful.\n7. Final delivery format.\n\nKeep it specific, useful, and non-generic. Do not assume a country or brand identity that was not provided.\n\nConstraints: {$this->cleanNegative($negativePrompt)}";
    }

    private function arabicAd(string $originalPrompt, array $details, string $styleName, array $mustInclude, array $replaceWith, string $negativePrompt): string
    {
        return "تصرّف كاستراتيجي إعلانات ومدير إبداعي محترف. أنشئ Brief إعلانيًا ونسخة إعلان نهائية للطلب: {$originalPrompt}\n\nقدّم:\n1. هدف الحملة، الجمهور، مشكلة العميل، والفعل المطلوب.\n2. رسالة أساسية واضحة مع عنوان رئيسي، سطر داعم، عنصر ثقة، وCTA.\n3. ثلاثة اتجاهات بديلة للعناوين.\n4. اتجاه بصري أو نصي مناسب للمنصة المختارة.\n5. نسخة إعلان مختصرة جاهزة للاستخدام.\n\nقواعد الجودة: اجعلها محددة، عملية، مقنعة، وبشرية. لا تفترض بلدًا أو علامة تجارية أو سعرًا أو عرضًا لم يذكره المستخدم.\n\nقيود: {$this->cleanNegative($negativePrompt)}";
    }

    private function arabicImage(string $originalPrompt, array $details, string $styleName, array $mustInclude, array $replaceWith, string $negativePrompt): string
    {
        return "تصرّف كمدير فني بصري محترف. أنشئ تعليمات توليد صورة نهائية للطلب: {$originalPrompt}\n\nضمّن:\n1. الموضوع الرئيسي، البيئة، والسياق الواقعي.\n2. التكوين، نقطة التركيز، التأطير، العمق، وتسلسل النظر.\n3. الاتجاه الفني: {$this->arabicLabel($styleName)}؛ استخدم {$this->arabicList($mustInclude)} عندما يناسب الطلب.\n4. الإضاءة، الخامات، زاوية الكاميرا، المزاج، ودرجة الواقعية.\n5. موضع النص أو التايبوجرافي فقط إذا كانت الصورة تحتاج نصًا.\n6. إعدادات المخرج مثل النسبة والاستخدام النهائي.\n\nقواعد الجودة: اجعل التعليمات بصرية، محددة، وقابلة للتنفيذ. تجنب الأوصاف العامة وشكل الذكاء الاصطناعي المكرر.\n\nNegative Prompt: {$this->cleanNegative($negativePrompt)}";
    }

    private function arabicVideo(string $originalPrompt, array $details, string $styleName, array $mustInclude, array $replaceWith, string $negativePrompt): string
    {
        return "تصرّف كمخرج سينمائي محترف. أنشئ تعليمات توليد فيديو نهائية للطلب: {$originalPrompt}\n\nضمّن:\n1. فكرة المشهد والحبكة البصرية.\n2. تسلسل اللقطات: افتتاحية، الحدث الرئيسي، لقطات تفاصيل، وخاتمة.\n3. حركة الكاميرا، إحساس العدسة، الزاوية، الانتقالات، الإيقاع، والمدة.\n4. الإضاءة، البيئة، الخامات، الحركة الواقعية، والمزاج العام.\n5. ملاءمة المنصة: النسبة، المدة، والإيقاع.\n\nقواعد الجودة: اجعل المشهد قابلًا للإنتاج ولا تعتمد على كلمات سينمائية عامة.\n\nNegative Prompt: {$this->cleanNegative($negativePrompt)}";
    }

    private function arabicArticle(string $originalPrompt, array $details): string
    {
        return "تصرّف كخبير محتوى وكاتب محترف. أنشئ خطة كتابة واضحة ومواصفة مقال للطلب: {$originalPrompt}\n\nقدّم:\n1. نية القارئ والجمهور المستهدف: {$details['audience']}.\n2. زاوية المقال والوعد المفيد للقارئ.\n3. عنوان H1 مقترح وهيكل H2/H3 كامل.\n4. إجابة مباشرة ومفيدة في بداية المقال.\n5. أمثلة عملية، خطوات، مقارنات، تحذيرات، وأسئلة شائعة عند الحاجة.\n6. عناصر SEO: meta title، meta description، روابط داخلية مقترحة، وSchema عند اللزوم.\n\nقواعد الجودة: لا ادعاءات بلا دليل، لا حشو، لا أرقام مختلقة، ونبّه لأي معلومة تحتاج تحقق.";
    }

    private function arabicCode(string $originalPrompt, array $details): string
    {
        return "تصرّف كمهندس برمجيات ومعماري نظام محترف. حوّل الطلب إلى مواصفة تنفيذ جاهزة للمطور: {$originalPrompt}\n\nقدّم:\n1. الهدف والسلوك المتوقع.\n2. الملفات أو الموديولات المطلوبة والمعمارية المقترحة.\n3. نموذج البيانات أو عقد API عند الحاجة.\n4. متطلبات UI/UX إذا كانت هناك واجهة.\n5. متطلبات الأمان: التحقق، escaping، CSRF/auth، إدارة الأسرار، ومعالجة الأخطاء.\n6. الحالات الطرفية وحالات الفشل.\n7. معايير القبول وقائمة اختبار.\n\nقواعد الجودة: لا تضع أسرارًا داخل الكود، ولا تخلط مسؤوليات غير مرتبطة، واجعل خطوات التنفيذ واضحة.";
    }

    private function arabicWebsite(string $originalPrompt, array $details, string $styleName, array $mustInclude, array $replaceWith, string $negativePrompt): string
    {
        return "تصرّف كأفضل مصمم UI/UX ومدير فني للويب. صمّم تصورًا كاملًا لموقع إلكتروني لشركة ألمنيوم تحتاج ألوانًا عصرية مريحة للعين وأقسامًا متعددة مفيدة.\n\nقدّم:\n1. هدف الموقع والهدف التحويلي.\n2. هيكل الأقسام المقترح مثل: Hero، عرض القيمة، المنتجات/الخدمات، الإثبات أو الأعمال السابقة، آلية العمل، التقييمات، FAQ، والتواصل/CTA، مع تعديل الأقسام حسب طبيعة النشاط.\n3. اتجاه بصري بلوحة ألوان عصرية ومريحة، تباين واضح، لون CTA أساسي، خط مقروء، مساحات واسعة، وتسلسل بصري سهل.\n4. UX للأجهزة المكتبية والتابلت والموبايل: تنقل واضح، سهولة فحص المحتوى، بروز CTA، وتجربة خفيفة.\n5. توقعات المحتوى: عناوين أقسام حقيقية، نصوص قصيرة مفيدة، عناصر ثقة، وتفاصيل عملية عن المنتج أو الخدمة.\n6. قيود جودة التصميم: تجنب شكل القوالب الجاهزة، الزخرفة الزائدة، التدرجات العشوائية، صور stock المصطنعة، واللمعة المبالغ فيها؛ استخدم سياقًا واقعيًا، صورًا قابلة للتصديق، توازنًا غير متماثل عند الحاجة، وتأثيرات بصرية هادئة.\n7. شكل التسليم: brief تصميم منظم يصلح لـ Figma أو Webflow أو Framer أو مطور ويب.\n\nلا تفترض بلدًا، سوقًا محليًا، تاريخ شركة، جوائز، أسعارًا، أو ادعاءات لم يذكرها المستخدم.";
    }

    private function arabicPlanning(string $originalPrompt, array $details): string
    {
        return "تصرّف كمستشار أبحاث وتخطيط محترف. أنشئ دراسة أو تحليلًا منظمًا للطلب: {$originalPrompt}\n\nقدّم:\n1. الهدف والقرار الذي ستدعمه الدراسة.\n2. الخلفية والافتراضات والتفاصيل الناقصة التي يمكن استنتاجها بحذر.\n3. الأسئلة الأساسية التي يجب الإجابة عنها.\n4. المنهجية: ماذا نقارن، ما البيانات المطلوبة، وكيف يتم التقييم.\n5. الأقسام: ملخص تنفيذي، الخيارات، المزايا والعيوب، المخاطر، التكلفة/الوقت، خطوات التنفيذ، والتوصية.\n6. شكل التسليم: ملخص تنفيذي، تحليل تفصيلي، خطة عمل، وقائمة تحقق.\n\nقواعد الجودة: افصل الحقائق عن الافتراضات، اذكر عدم اليقين، ولا تقدم ادعاءات غير مدعومة.";
    }

    private function arabicGeneral(string $originalPrompt, array $details, string $styleName, string $negativePrompt): string
    {
        return "تصرّف كأفضل خبير مناسب لهذا الطلب. أنتج المخرج النهائي القابل للاستخدام مباشرة بناءً على: {$originalPrompt}\n\nضمّن:\n1. الهدف.\n2. الجمهور.\n3. السياق والافتراضات.\n4. المخرجات المطلوبة.\n5. القيود ومعايير الجودة.\n6. أمثلة أو هيكل عند الحاجة.\n7. شكل التسليم النهائي.\n\nاجعله محددًا ومفيدًا وغير عام. لا تفترض بلدًا أو هوية علامة تجارية غير مذكورة.\n\nقيود: {$this->cleanNegative($negativePrompt)}";
    }

    private function shortArabic(string $originalPrompt, array $intent, array $details): string
    {
        return "تصرّف كخبير مناسب للطلب التالي: {$originalPrompt}. أنتج مخرجًا مباشرًا ومنظمًا يحدد الهدف والجمهور والسياق والمخرجات والقيود ومعايير الجودة، بدون افتراض بلد أو علامة تجارية غير مذكورة.";
    }

    private function shortEnglish(string $originalPrompt, array $intent, array $details): string
    {
        return "Act as the right expert for this request: {$originalPrompt}. Produce a direct structured output with goal, audience, context, deliverables, constraints, and quality rules without forcing any country or brand identity not provided by the user.";
    }

    private function applyLocalOutputControls(string $improved, string $short, string $outputGoal, string $outputStyle, string $platform, bool $isArabic): string
    {
        if ($outputStyle === 'short_prompt') {
            return $short;
        }

        if ($outputStyle === 'direct_prompt') {
            return $improved;
        }

        if ($outputStyle === 'tool_specific_prompt' && $platform !== 'general') {
            $toolLine = $isArabic
                ? "\n\nاضبط المخرج خصيصًا لأداة/منصة: {$platform}."
                : "\n\nAdapt the output specifically for: {$platform}.";
            $improved .= $toolLine;
        }

        if ($outputGoal === 'generate_final_content') {
            $prefix = $isArabic
                ? "أنتج المحتوى النهائي مباشرة، وليس برومبتًا لاستخدامه لاحقًا. "
                : "Generate the final content directly, not a prompt for another tool. ";
            return $prefix . $improved;
        }

        if ($outputGoal === 'brief_plus_prompt') {
            $prefix = $isArabic
                ? "قدّم Brief مختصرًا ثم البرومبت النهائي القابل للنسخ. "
                : "Provide a short brief first, then the final paste-ready prompt. ";
            return $prefix . $improved;
        }

        if ($outputGoal === 'analyze_request') {
            $prefix = $isArabic
                ? "حلّل الطلب أولًا ثم اقترح أفضل صياغة تنفيذية له. "
                : "Analyze the request first, then propose the best execution-ready wording. ";
            return $prefix . $improved;
        }

        if ($outputGoal === 'create_variations') {
            $suffix = $isArabic
                ? "\n\nأضف 3 نسخ بديلة: قصيرة، متوسطة، واحترافية عميقة."
                : "\n\nAdd 3 alternative versions: short, medium, and deep professional.";
            return $improved . $suffix;
        }

        return $improved;
    }

    private function listForPrompt(array $items): string
    {
        $items = array_slice(array_values(array_filter(array_map('strval', $items))), 0, 5);
        return $items ? implode(', ', $items) : 'specific details that fit the request';
    }

    private function cleanNegative(string $negativePrompt): string
    {
        $negativePrompt = trim($negativePrompt);
        $negativePrompt = preg_replace('/^Avoid:\s*/i', '', $negativePrompt) ?? $negativePrompt;
        $negativePrompt = preg_replace('/^تجنّب:\s*/u', '', $negativePrompt) ?? $negativePrompt;
        return trim($negativePrompt);
    }

    private function arabicList(array $items): string
    {
        $map = [
            'clear information hierarchy' => 'تسلسل معلومات واضح',
            'large readable headline' => 'عنوان كبير ومقروء',
            'editorial spacing' => 'مساحات تحريرية مريحة',
            'strong CTA' => 'زر دعوة لاتخاذ إجراء واضح',
            'calm color palette' => 'لوحة ألوان هادئة',
            'responsive section structure' => 'هيكل أقسام متجاوب',
            'editorial art direction' => 'إخراج تحريري',
            'asymmetric composition' => 'تكوين غير متماثل',
            'documentary realism' => 'واقعية وثائقية',
            'handmade collage' => 'كولاج بشري فقط إذا كان مناسبًا بصريًا',
            'print texture' => 'ملمس طباعي عند الحاجة',
            'imperfect alignment' => 'محاذاة غير مثالية بشكل مدروس',
            'cropped subject' => 'عنصر بصري مقصوص جزئيًا',
            'real-world shadows' => 'ظلال واقعية',
            'real-world context' => 'سياق واقعي',
            'bold typography' => 'تايبوجرافي واضح وجريء',
            'tactile paper layers' => 'طبقات ملموسة عند الحاجة',
            'globally adaptable wording' => 'صياغة قابلة للتكيّف عالميًا',
            'natural shadows' => 'ظلال طبيعية',
            'real context' => 'سياق حقيقي',
            'simple headline space' => 'مساحة عنوان بسيطة وواضحة',
            'real materials' => 'خامات واقعية',
            'authentic texture' => 'ملمس حقيقي',
            'specific visual subject' => 'موضوع بصري محدد',
        ];
        $items = array_slice(array_values(array_filter(array_map('strval', $items))), 0, 5);
        if (!$items) {
            return 'تفاصيل محددة تناسب الطلب';
        }
        $translated = array_map(static fn(string $item): string => $map[$item] ?? $item, $items);
        return implode('، ', $translated);
    }

    private function arabicLabel(string $value): string
    {
        return match ($value) {
            'landing' => 'تصميم موقع / واجهة',
            'ad' => 'إعلان / حملة',
            'image' => 'تصميم صورة',
            'video' => 'فيديو',
            'article' => 'مقال / محتوى',
            'code' => 'برمجة / كود',
            'planning' => 'دراسة / تحليل / تخطيط',
            'aluminum and windows' => 'ألمنيوم ونوافذ وأبواب',
            'general business', 'business' => 'أعمال عامة',
            'general' => 'عام',
            'kitchen design' => 'تصميم مطابخ',
            'technology' => 'تقنية وبرمجيات',
            'education' => 'تعليم',
            'health' => 'صحة',
            'General Project' => 'مشروع عام / بدون هوية محددة',
            'Minimal Editorial Web' => 'تصميم ويب تحريري بسيط وواضح',
            'Documentary Product Direction' => 'اتجاه واقعي للمنتج أو الخدمة',
            'Human-Made Social Ad' => 'إعلان بطابع بشري',
            'Auto Prompt Director' => 'موجه برومبت تلقائي',
            default => $value,
        };
    }
}
