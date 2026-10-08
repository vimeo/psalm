# TaintedLlmPrompt

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into an LLM prompt, risking prompt injection.

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
