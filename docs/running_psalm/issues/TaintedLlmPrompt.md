# TaintedLlmPrompt

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into an LLM prompt, risking prompt injection.

## Risk

Text from an attacker that is placed in a prompt can change the instructions the model follows (prompt injection), for instance to leak the rest of the prompt or to misuse tools the model can call.

## Example

```php
<?php

class LlmAgent {
    /** @psalm-taint-sink llm_prompt $prompt */
    public function prompt(string $prompt): string {
        return "";
    }
}

$agent = new LlmAgent();
$agent->prompt((string) $_GET["question"]);
```

## Mitigations

- Keep user input apart from instructions (separate messages for the system prompt and the user turn).
- Give the model only the data and tools the user may use, and treat its output as untrusted.

## Further resources

- [OWASP LLM01: Prompt Injection](https://genai.owasp.org/llmrisk/llm01-prompt-injection/)
