# HumanPrompt Studio — Agent Notes

## Product rules

- The tool is global and multilingual by default.
- Do not force Jordan, local-market language, brand profiles, prices, awards, history, or country-specific claims unless explicitly provided.
- Keep Provider Vault, encrypted API keys, and `.env` behavior intact.
- English spelling/grammar correction should stay enabled by default.

## Output controls

The interface supports:

### Output Goal
- Improve as Prompt
- Generate Final Content
- Create Brief + Prompt
- Analyze Request
- Create Variations

### Output Style
- Direct Prompt
- Structured Prompt
- Short Prompt
- Deep Professional Prompt
- Tool-Specific Prompt

The model must respect these fields when generating output.

## Direct output contract

The improved prompt should be paste-ready. Avoid meta phrases such as:

- Convert this request into a prompt
- Prompt:
- Final output: ready-to-use prompt
- Original request:

Start directly with the expert role/task.
