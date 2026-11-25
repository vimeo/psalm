# TaintedLlmResponse

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be given to an LLM as what the model itself said: an assistant turn of the conversation.

## Risk

A model trusts its own earlier replies: an assistant turn written by an attacker (in the conversation history a client sends back, a prefilled reply or few-shot examples built from user data) steers what the model answers next.

The reply downloaded from the provider is input too. Escaping only `llm_response` from it lets it into assistant turns, and it is still reported in a prompt ([TaintedLlmPrompt](TaintedLlmPrompt.md)) or in HTML.

## Example

```php
<?php

final class AssistantMessage {
    /** @psalm-taint-sink llm_response $content */
    public function __construct(public string $content) {}
}

/**
 * The model's reply in the body the provider answered.
 *
 * @psalm-taint-escape llm_response
 */
function replyText(string $body): string {
    $data = json_decode($body, true);

    return is_array($data) && is_string($data['text'] ?? null) ? $data['text'] : '';
}

$body = (string) curl_exec(curl_init('https://llm.example.com/chat'));
new AssistantMessage(replyText($body)); // no issue: the model's own reply

foreach ((array) ($_POST['history'] ?? []) as $turn) {
    if ('assistant' === ($turn['role'] ?? null)) {
        new AssistantMessage((string) ($turn['content'] ?? '')); // TaintedLlmResponse
    }
}
```

## Mitigations

- Keep the conversation on the server: rebuild the assistant turns from what the model replied, not from what the client sends.
- Never put user input in an assistant turn, including prefilled replies and few-shot examples.

## Further resources

- [OWASP LLM01: Prompt Injection](https://genai.owasp.org/llmrisk/llm01-prompt-injection/)
- [CWE-1427](https://cwe.mitre.org/data/definitions/1427.html)
