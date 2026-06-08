<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Helpers/helpers.php';

session_start();
$csrfToken = ensure_csrf_token();
$config = require dirname(__DIR__) . '/config/app.php';
?>
<!doctype html>
<html lang="en" dir="ltr" data-mode="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h($csrfToken) ?>">
    <title><?= h($config['name']) ?> — MVP</title>
    <meta name="description" content="A prompt director with an anti-generic AI style engine.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <main class="shell">
        <section class="hero card">
            <div>
                <p class="eyebrow">Auto Prompt Director • Any Task • Any Language</p>
                <h1>HumanPrompt Studio</h1>
                <p class="lead">Turn any simple request into a final paste-ready instruction for ads, images, videos, websites, code, articles, studies, plans, and analysis — without forcing local assumptions.</p>
            </div>
            <div class="hero-panel">
                <span class="score-pill">MVP v1.0</span>
                <span class="score-pill">Output Styles + Spellcheck + Live AI</span>
                <a class="score-pill link-pill" href="providers.php">Manage Providers</a>
            </div>
        </section>

        <section class="grid">
            <form id="promptForm" class="card form-card">
                <label for="prompt">Original prompt</label>
                <textarea id="prompt" name="prompt" rows="7" placeholder="Write anything: an ad, study, website, article, code task, video, image prompt, or analysis request."></textarea>

                <div class="form-grid">
                    <div>
                        <label for="output_goal">Output goal</label>
                        <select id="output_goal" name="output_goal">
                            <option value="improve_prompt">Improve as Prompt</option>
                            <option value="generate_final_content">Generate Final Content</option>
                            <option value="brief_plus_prompt">Create Brief + Prompt</option>
                            <option value="analyze_request">Analyze Request</option>
                            <option value="create_variations">Create Variations</option>
                        </select>
                        <p class="field-hint">Choose whether you want a better prompt, the final content itself, or a deeper brief.</p>
                    </div>
                    <div>
                        <label for="output_style">Output style</label>
                        <select id="output_style" name="output_style">
                            <option value="structured_prompt">Structured Prompt</option>
                            <option value="direct_prompt">Direct Prompt</option>
                            <option value="short_prompt">Short Prompt</option>
                            <option value="deep_professional_prompt">Deep Professional Prompt</option>
                            <option value="tool_specific_prompt">Tool-Specific Prompt</option>
                        </select>
                        <p class="field-hint">Controls the depth and shape of the generated output.</p>
                    </div>
                    <div>
                        <label for="type">Prompt type</label>
                        <select id="type" name="type">
                            <option value="auto">Auto</option>
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                            <option value="ad">Ad</option>
                            <option value="article">Article</option>
                            <option value="code">Code</option>
                            <option value="landing">Landing Page</option>
                            <option value="planning">Study / Planning / Analysis</option>
                        </select>
                    </div>
                    <div>
                        <label for="style">Style preset</label>
                        <select id="style" name="style">
                            <option value="auto">Auto</option>
                            <option value="documentary_product_direction">Documentary Product Direction</option>
                            <option value="editorial_collage">Editorial Collage</option>
                            <option value="high_end_interior_editorial">High-End Interior Editorial</option>
                            <option value="cinematic_product_direction">Cinematic Product Direction</option>
                            <option value="minimal_editorial_web">Minimal Editorial Web</option>
                            <option value="structured_developer_brief">Structured Developer Brief</option>
                            <option value="human_made_social_ad">Human-Made Social Ad</option>
                            <option value="auto_prompt_director">Auto Prompt Director</option>
                        </select>
                    </div>
                    <div>
                        <label for="platform">Target tool</label>
                        <select id="platform" name="platform">
                            <option value="general">General</option>
                            <option value="chatgpt">ChatGPT</option>
                            <option value="claude">Claude</option>
                            <option value="gemini">Gemini</option>
                            <option value="grok">Grok</option>
                            <option value="facebook">Facebook Post / Ad</option>
                            <option value="instagram">Instagram</option>
                            <option value="canva">Canva</option>
                            <option value="figma">Figma</option>
                            <option value="webflow">Webflow</option>
                            <option value="framer">Framer</option>
                            <option value="midjourney">Midjourney</option>
                            <option value="ideogram">Ideogram</option>
                            <option value="veo">Veo</option>
                        </select>
                    </div>
                    <div>
                        <label for="brand_profile">Project context</label>
                        <select id="brand_profile" name="brand_profile">
                            <option value="general_project">General / No specific brand</option>
                            <option value="business_website">Business Website</option>
                            <option value="ecommerce_project">E-commerce Project</option>
                            <option value="saas_product">SaaS / Software Product</option>
                            <option value="creative_campaign">Creative Campaign</option>
                            <option value="technical_project">Technical / Developer Project</option>
                        </select>
                    </div>
                    <div>
                        <label for="market">Market / country</label>
                        <input id="market" name="market" value="" placeholder="Global / Auto / optional">
                    </div>
                    <div>
                        <label for="ai_provider">AI provider</label>
                        <select id="ai_provider" name="ai_provider">
                            <option value="default">Default Provider</option>
                            <option value="local_rule_engine">Local Rule Engine</option>
                        </select>
                    </div>
                    <div>
                        <label for="language">Language</label>
                        <select id="language" name="language">
                            <option value="Auto">Auto - follow user prompt</option>
                            <option value="English">English</option>
                            <option value="Arabic">Arabic</option>
                            <option value="Bilingual">Bilingual</option>
                            <option value="French">French</option>
                            <option value="Spanish">Spanish</option>
                            <option value="German">German</option>
                            <option value="Turkish">Turkish</option>
                        </select>
                    </div>
                </div>

                <label class="check-row">
                    <input type="checkbox" id="anti_ai" name="anti_ai" checked>
                    <span>Apply anti-generic AI style engine</span>
                </label>

                <label class="check-row">
                    <input type="checkbox" id="fix_english" name="fix_english" checked>
                    <span>Fix English spelling & grammar before enhancing</span>
                </label>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">Transform Prompt</button>
                    <button class="btn btn-secondary" type="button" id="exampleBtn">Load Example</button>
                    <button class="btn btn-ghost" type="reset">Clear</button>
                </div>
            </form>

            <section class="card result-card">
                <div class="result-header">
                    <div>
                        <p class="eyebrow">Generated output</p>
                        <h2>Final Direct Prompt</h2>
                    </div>
                    <button class="btn btn-secondary" type="button" id="copyBtn">Copy Final</button>
                        <button class="btn btn-secondary" type="button" id="copyAllBtn">Copy All</button>
                </div>

                <div id="status" class="status">Ready.</div>

                <article class="output-block">
                    <div class="output-title-row"><h3>Final Prompt</h3><button class="mini-copy" type="button" data-copy-target="improvedPrompt">Copy</button></div>
                    <pre id="improvedPrompt"></pre>
                </article>
                <article class="output-block">
                    <div class="output-title-row"><h3>Short Version</h3><button class="mini-copy" type="button" data-copy-target="shortPrompt">Copy</button></div>
                    <pre id="shortPrompt"></pre>
                </article>
                <article class="output-block">
                    <div class="output-title-row"><h3>Negative Prompt</h3><button class="mini-copy" type="button" data-copy-target="negativePrompt">Copy</button></div>
                    <pre id="negativePrompt"></pre>
                </article>
                <article class="output-block">
                    <h3>Processing Note</h3>
                    <pre id="processingNote">Spelling correction and provider status will appear here.</pre>
                </article>
                <article class="output-block">
                    <h3>Prompt Score</h3>
                    <div id="scoreGrid" class="score-grid"></div>
                </article>
                <article class="output-block">
                    <div class="output-title-row"><h3>Why this is stronger</h3><button class="mini-copy" type="button" data-copy-target="whyStronger">Copy</button></div>
                    <pre id="whyStronger"></pre>
                </article>
            </section>
        </section>
    </main>

    <script src="assets/js/app.js"></script>
</body>
</html>
