const statusBox = document.querySelector('#vaultStatus');
const setupNotice = document.querySelector('#setupNotice');
const unlockPanel = document.querySelector('#unlockPanel');
const vaultApp = document.querySelector('#vaultApp');
const providersList = document.querySelector('#providersList');
const providerForm = document.querySelector('#providerForm');

const providerDefaults = {
  openrouter: {
    key: 'openrouter',
    name: 'OpenRouter',
    type: 'openrouter',
    base_url: 'https://openrouter.ai/api/v1',
    capabilities: 'text, code, vision',
    model_placeholder: 'Example: openai/gpt-4o-mini, anthropic/claude-3.5-sonnet'
  },
  openai: {
    key: 'openai',
    name: 'OpenAI',
    type: 'openai',
    base_url: 'https://api.openai.com/v1',
    capabilities: 'text, code, vision',
    model_placeholder: 'Example: gpt-4o-mini or gpt-4.1-mini'
  },
  gemini: {
    key: 'gemini',
    name: 'Gemini',
    type: 'gemini',
    base_url: 'https://generativelanguage.googleapis.com/v1beta',
    capabilities: 'text, code, vision',
    model_placeholder: 'Example: gemini-1.5-flash or gemini-2.0-flash'
  },
  claude: {
    key: 'claude',
    name: 'Claude',
    type: 'claude',
    base_url: 'https://api.anthropic.com/v1',
    capabilities: 'text, code, vision',
    model_placeholder: 'Example: claude-3-5-sonnet-latest'
  },
  grok: {
    key: 'grok',
    name: 'Grok / xAI',
    type: 'grok',
    base_url: 'https://api.x.ai/v1',
    capabilities: 'text, code',
    model_placeholder: 'Example: grok-beta or grok-2-latest'
  },

  deepseek: {
    key: 'deepseek',
    name: 'DeepSeek',
    type: 'openai_compatible',
    base_url: 'https://api.deepseek.com',
    capabilities: 'text, code',
    model_placeholder: 'Example: deepseek-chat or deepseek-reasoner'
  },
  kimi: {
    key: 'kimi',
    name: 'Kimi / Moonshot AI',
    type: 'openai_compatible',
    base_url: 'https://api.moonshot.ai/v1',
    capabilities: 'text, code, vision',
    model_placeholder: 'Example: kimi-latest or moonshot-v1-auto'
  },
  deepinfra: {
    key: 'deepinfra',
    name: 'DeepInfra',
    type: 'openai_compatible',
    base_url: 'https://api.deepinfra.com/v1/openai',
    capabilities: 'text, code, vision, embedding, image, audio',
    model_placeholder: 'Example: meta-llama/Meta-Llama-3.1-8B-Instruct or deepseek-ai/DeepSeek-V3'
  },
  together: {
    key: 'together',
    name: 'Together AI',
    type: 'openai_compatible',
    base_url: 'https://api.together.ai/v1',
    capabilities: 'text, code, vision, image, embedding, audio',
    model_placeholder: 'Example: meta-llama/Llama-4-Maverick-17B-128E-Instruct-FP8 or openai/gpt-oss-20b'
  },
  groq: {
    key: 'groq',
    name: 'Groq',
    type: 'openai_compatible',
    base_url: 'https://api.groq.com/openai/v1',
    capabilities: 'text, code',
    model_placeholder: 'Example: llama-3.1-8b-instant or moonshotai/kimi-k2-instruct'
  },
  mistral: {
    key: 'mistral',
    name: 'Mistral AI',
    type: 'openai_compatible',
    base_url: 'https://api.mistral.ai/v1',
    capabilities: 'text, code, vision, embedding, audio',
    model_placeholder: 'Example: mistral-large-latest or mistral-small-latest'
  },
  perplexity: {
    key: 'perplexity',
    name: 'Perplexity',
    type: 'openai_compatible',
    base_url: 'https://api.perplexity.ai',
    capabilities: 'text, code',
    model_placeholder: 'Example: sonar-pro or sonar-reasoning-pro'
  },
  novita: {
    key: 'novita',
    name: 'Novita AI',
    type: 'openai_compatible',
    base_url: 'https://api.novita.ai/openai',
    capabilities: 'text, code, vision, image',
    model_placeholder: 'Example: meta-llama/llama-3.1-8b-instruct or your Novita model ID'
  },
  fireworks: {
    key: 'fireworks',
    name: 'Fireworks AI',
    type: 'openai_compatible',
    base_url: 'https://api.fireworks.ai/inference/v1',
    capabilities: 'text, code, vision, image',
    model_placeholder: 'Example: accounts/fireworks/models/llama-v3p1-8b-instruct'
  },
  cerebras: {
    key: 'cerebras',
    name: 'Cerebras',
    type: 'openai_compatible',
    base_url: 'https://api.cerebras.ai/v1',
    capabilities: 'text, code',
    model_placeholder: 'Example: llama3.1-8b or qwen-3-coder-480b'
  },
  custom: {
    key: 'custom_provider',
    name: 'Custom Provider',
    type: 'custom',
    base_url: '',
    capabilities: 'text, code',
    model_placeholder: 'Enter your custom model name'
  }
};

function csrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

function setStatus(message, isError = false) {
  if (!statusBox) return;
  statusBox.textContent = message;
  statusBox.classList.toggle('is-error', isError);
}

async function api(action, payload = {}) {
  const response = await fetch('provider-api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken()
    },
    body: JSON.stringify({ action, ...payload })
  });
  const data = await response.json();
  if (!data.ok) throw new Error(data.error || 'Request failed.');
  return data.data;
}

function applyPreset(presetName, keepModel = false) {
  const preset = providerDefaults[presetName] || providerDefaults.custom;
  document.querySelector('#providerKey').value = preset.key;
  document.querySelector('#providerName').value = preset.name;
  document.querySelector('#providerType').value = preset.type;
  document.querySelector('#providerBaseUrl').value = preset.base_url;
  document.querySelector('#providerCapabilities').value = preset.capabilities;
  document.querySelector('#providerModel').placeholder = preset.model_placeholder || 'Enter model name';
  if (!keepModel) document.querySelector('#providerModel').value = '';
}

function formData() {
  return {
    key: document.querySelector('#providerKey').value.trim(),
    name: document.querySelector('#providerName').value.trim(),
    type: document.querySelector('#providerType').value.trim(),
    base_url: document.querySelector('#providerBaseUrl').value.trim(),
    default_model: document.querySelector('#providerModel').value.trim(),
    capabilities: document.querySelector('#providerCapabilities').value,
    priority: document.querySelector('#providerPriority').value,
    rate_limit_per_minute: document.querySelector('#providerRateLimit').value,
    cost_guard_daily_usd: document.querySelector('#providerCostGuard').value,
    api_key: document.querySelector('#providerApiKey').value,
    enabled: document.querySelector('#providerEnabled').checked,
    make_default: document.querySelector('#providerDefault').checked
  };
}

function providerTypeToPreset(type, key) {
  if (key && providerDefaults[key]) return key;
  if (type === 'openrouter') return 'openrouter';
  if (type === 'openai') return 'openai';
  if (type === 'gemini') return 'gemini';
  if (type === 'claude') return 'claude';
  if (type === 'grok') return 'grok';
  if (type === 'openai_compatible' && key && providerDefaults[key]) return key;
  return 'custom';
}

function fillForm(provider = {}) {
  const presetName = providerTypeToPreset(provider.type, provider.key);
  document.querySelector('#providerPreset').value = presetName;
  applyPreset(presetName, true);

  if (provider.key) document.querySelector('#providerKey').value = provider.key;
  if (provider.name) document.querySelector('#providerName').value = provider.name;
  if (provider.type) document.querySelector('#providerType').value = provider.type;
  if (provider.base_url !== undefined) document.querySelector('#providerBaseUrl').value = provider.base_url || '';

  document.querySelector('#providerModel').value = provider.default_model || '';
  document.querySelector('#providerCapabilities').value = (provider.capabilities || ['text', 'code']).join(', ');
  document.querySelector('#providerPriority').value = provider.priority ?? 100;
  document.querySelector('#providerRateLimit').value = provider.rate_limit_per_minute ?? 30;
  document.querySelector('#providerCostGuard').value = provider.cost_guard_daily_usd ?? 3;
  document.querySelector('#providerApiKey').value = '';
  document.querySelector('#providerEnabled').checked = provider.enabled ?? true;
  document.querySelector('#providerDefault').checked = true;
}

function renderProviders(vault) {
  providersList.textContent = '';
  const providers = vault.providers || {};
  const keys = Object.keys(providers);

  if (keys.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'empty-state';
    empty.textContent = 'No external providers saved yet. Choose a provider, paste the API key, write the model name, and save.';
    providersList.appendChild(empty);
    return;
  }

  keys.forEach((key) => {
    const provider = providers[key];
    const card = document.createElement('article');
    card.className = 'provider-card';

    const title = document.createElement('div');
    title.className = 'provider-title';
    title.textContent = `${provider.name || key} ${vault.default_provider === key ? '• Default' : ''}`;

    const meta = document.createElement('div');
    meta.className = 'provider-meta';
    meta.textContent = `${provider.type || 'custom'} • model: ${provider.default_model || 'missing'} • ${provider.enabled ? 'enabled' : 'disabled'} • API key: ${provider.api_key_hint || 'missing'}`;

    const actions = document.createElement('div');
    actions.className = 'provider-actions';

    const editBtn = document.createElement('button');
    editBtn.className = 'btn btn-secondary';
    editBtn.type = 'button';
    editBtn.textContent = 'Edit';
    editBtn.addEventListener('click', () => fillForm(provider));

    const testBtn = document.createElement('button');
    testBtn.className = 'btn btn-secondary';
    testBtn.type = 'button';
    testBtn.textContent = 'Test';
    testBtn.addEventListener('click', async () => {
      try {
        const result = await api('test', { key });
        setStatus(`${result.status}: ${result.message}`, result.status === 'error');
      } catch (error) {
        setStatus(error.message, true);
      }
    });

    const defaultBtn = document.createElement('button');
    defaultBtn.className = 'btn btn-secondary';
    defaultBtn.type = 'button';
    defaultBtn.textContent = 'Set Default';
    defaultBtn.addEventListener('click', async () => {
      try {
        await api('set_default', { key });
        setStatus('Default provider updated.');
        await loadProviders();
      } catch (error) {
        setStatus(error.message, true);
      }
    });

    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn btn-ghost danger-text';
    deleteBtn.type = 'button';
    deleteBtn.textContent = 'Delete';
    deleteBtn.addEventListener('click', async () => {
      if (!confirm(`Delete provider "${key}"?`)) return;
      try {
        await api('delete', { key });
        setStatus('Provider deleted.');
        await loadProviders();
      } catch (error) {
        setStatus(error.message, true);
      }
    });

    actions.append(editBtn, testBtn, defaultBtn, deleteBtn);
    card.append(title, meta, actions);
    providersList.appendChild(card);
  });
}

async function loadProviders() {
  const vault = await api('list');
  renderProviders(vault);
}

async function checkStatus() {
  try {
    const data = await api('status');
    setupNotice.hidden = !data.needs_setup;
    unlockPanel.hidden = data.needs_setup || data.unlocked;
    vaultApp.hidden = !data.unlocked;
    if (data.unlocked) {
      await loadProviders();
      fillForm({});
    }
  } catch (error) {
    console.error(error);
  }
}

const setupBtn = document.querySelector('#setupVaultBtn');
if (setupBtn) {
  setupBtn.addEventListener('click', async () => {
    try {
      const password = document.querySelector('#setupPassword').value;
      const secret = document.querySelector('#setupSecret').value;
      const result = await api('setup', { password, secret });
      document.querySelector('#setupPassword').value = '';
      document.querySelector('#setupSecret').value = '';
      alert(result.message || 'Vault configured.');
      await checkStatus();
    } catch (error) {
      alert(error.message);
    }
  });
}

document.querySelector('#unlockBtn').addEventListener('click', async () => {
  try {
    const password = document.querySelector('#vaultPassword').value;
    await api('unlock', { password });
    document.querySelector('#vaultPassword').value = '';
    await checkStatus();
  } catch (error) {
    alert(error.message);
  }
});

document.querySelector('#lockBtn').addEventListener('click', async () => {
  try {
    await api('logout');
    await checkStatus();
  } catch (error) {
    alert(error.message);
  }
});

document.querySelector('#providerPreset').addEventListener('change', (event) => {
  applyPreset(event.target.value);
});

document.querySelector('#refreshProvidersBtn').addEventListener('click', loadProviders);
document.querySelector('#newProviderBtn').addEventListener('click', () => fillForm({}));

providerForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  try {
    const payload = formData();
    if (!payload.default_model) {
      setStatus('Model name is required.', true);
      return;
    }
    await api('save', { provider: payload });
    fillForm({});
    setStatus('Provider saved securely.');
    await loadProviders();
  } catch (error) {
    setStatus(error.message, true);
  }
});

fillForm({});
checkStatus();
