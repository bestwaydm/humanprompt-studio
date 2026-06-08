const form = document.querySelector('#promptForm');
const statusBox = document.querySelector('#status');
const improvedPrompt = document.querySelector('#improvedPrompt');
const shortPrompt = document.querySelector('#shortPrompt');
const negativePrompt = document.querySelector('#negativePrompt');
const whyStronger = document.querySelector('#whyStronger');
const scoreGrid = document.querySelector('#scoreGrid');
const copyBtn = document.querySelector('#copyBtn');
const copyAllBtn = document.querySelector('#copyAllBtn');
const exampleBtn = document.querySelector('#exampleBtn');
const providerSelect = document.querySelector('#ai_provider');
const providerNote = document.querySelector('#processingNote');

function csrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

function setStatus(message, isError = false) {
  statusBox.textContent = message;
  statusBox.classList.toggle('is-error', isError);
}

function formPayload() {
  return {
    prompt: document.querySelector('#prompt').value.trim(),
    output_goal: document.querySelector('#output_goal').value,
    output_style: document.querySelector('#output_style').value,
    fix_english: document.querySelector('#fix_english').checked,
    type: document.querySelector('#type').value,
    style: document.querySelector('#style').value,
    platform: document.querySelector('#platform').value,
    brand_profile: document.querySelector('#brand_profile').value,
    market: document.querySelector('#market').value.trim(),
    language: document.querySelector('#language').value,
    anti_ai: document.querySelector('#anti_ai').checked,
    ai_provider: document.querySelector('#ai_provider').value,
    detail_level: 'professional'
  };
}

function renderScore(score) {
  scoreGrid.textContent = '';
  Object.entries(score || {}).forEach(([key, value]) => {
    const item = document.createElement('div');
    item.className = 'score-item';

    const label = document.createElement('span');
    label.textContent = key.replaceAll('_', ' ');

    const strong = document.createElement('strong');
    strong.textContent = `${value}/10`;

    item.append(label, strong);
    scoreGrid.appendChild(item);
  });
}

function renderOutput(data) {
  improvedPrompt.textContent = data.improved_prompt || '';
  shortPrompt.textContent = data.short_prompt || '';
  negativePrompt.textContent = data.negative_prompt || '';
  whyStronger.textContent = `${data.why_stronger || ''}\n\nWarning: ${data.warning || ''}`;
  const correction = data.spell_correction?.note ? `Spellcheck: ${data.spell_correction.note}` : 'Spellcheck: not reported.';
  const provider = data.provider?.note ? `Provider: ${data.provider.note}` : 'Provider: Local provider note will appear here.';
  providerNote.textContent = `${correction}\n${provider}`;
  renderScore(data.score);
}

async function loadProviderOptions() {
  try {
    const response = await fetch('provider-options.php');
    const payload = await response.json();
    if (!payload.ok) return;

    providerSelect.textContent = '';
    const defaultOption = document.createElement('option');
    defaultOption.value = 'default';
    defaultOption.textContent = 'Default Provider';
    providerSelect.appendChild(defaultOption);

    (payload.data.options || []).forEach((option) => {
      const item = document.createElement('option');
      item.value = option.key;
      item.textContent = `${option.name}${option.is_default ? ' • default' : ''}`;
      providerSelect.appendChild(item);
    });

    providerNote.textContent = payload.data.vault_ready
      ? 'Provider Vault is configured. You can choose a saved provider or use the local engine.'
      : 'Provider Vault is not configured yet. The local rule engine will be used.';
  } catch (error) {
    providerNote.textContent = 'Could not load provider options. Local engine will be used.';
  }
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  setStatus('Generating with selected provider...');
  const submitButton = form.querySelector('button[type="submit"]');
  if (submitButton) submitButton.disabled = true;

  try {
    const response = await fetch('api.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken()
      },
      body: JSON.stringify(formPayload())
    });

    const payload = await response.json();

    if (!payload.ok) {
      setStatus(payload.error || 'Something went wrong.', true);
      return;
    }

    renderOutput(payload.data);
    setStatus('Prompt generated successfully.');
  } catch (error) {
    setStatus('Request failed. Please check your local server.', true);
  } finally {
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) submitButton.disabled = false;
  }
});


async function copyText(text, successMessage = 'Copied.') {
  const clean = (text || '').trim();
  if (!clean) {
    setStatus('Nothing to copy yet.', true);
    return;
  }

  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(clean);
    } else {
      const temp = document.createElement('textarea');
      temp.value = clean;
      temp.setAttribute('readonly', 'readonly');
      temp.style.position = 'fixed';
      temp.style.left = '-9999px';
      document.body.appendChild(temp);
      temp.select();
      document.execCommand('copy');
      temp.remove();
    }
    setStatus(successMessage);
  } catch (error) {
    setStatus('Copy failed. Select the text manually and copy it.', true);
  }
}

function fullOutputText() {
  return [
    'Final Prompt:\n' + improvedPrompt.textContent.trim(),
    'Short Version:\n' + shortPrompt.textContent.trim(),
    'Negative Prompt:\n' + negativePrompt.textContent.trim(),
    'Why this is stronger:\n' + whyStronger.textContent.trim()
  ].filter(Boolean).join('\n\n---\n\n');
}

copyBtn.addEventListener('click', async () => {
  await copyText(improvedPrompt.textContent, 'Final prompt copied.');
});

if (copyAllBtn) {
  copyAllBtn.addEventListener('click', async () => {
    await copyText(fullOutputText(), 'All output copied.');
  });
}

document.querySelectorAll('[data-copy-target]').forEach((button) => {
  button.addEventListener('click', async () => {
    const target = document.querySelector('#' + button.dataset.copyTarget);
    await copyText(target ? target.textContent : '', 'Section copied.');
  });
});

exampleBtn.addEventListener('click', () => {
  document.querySelector('#prompt').value = 'Prepare a feasibility study for launching a small online education platform, including risks, costs, audience, and first steps.';
  document.querySelector('#output_goal').value = 'improve_prompt';
  document.querySelector('#output_style').value = 'deep_professional_prompt';
  document.querySelector('#type').value = 'auto';
  document.querySelector('#style').value = 'auto_prompt_director';
  document.querySelector('#platform').value = 'chatgpt';
  document.querySelector('#brand_profile').value = 'general_project';
  document.querySelector('#market').value = '';
  document.querySelector('#language').value = 'Auto';
  document.querySelector('#anti_ai').checked = true;
  document.querySelector('#fix_english').checked = true;
  setStatus('Example loaded. Click Transform Prompt.');
});

loadProviderOptions();
