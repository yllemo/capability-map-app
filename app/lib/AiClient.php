<?php
declare(strict_types=1);

namespace App;

final class AiClient {
  public static function generateMarkdownEdit(
    string $systemPrompt,
    string $userInstruction,
    string $currentMarkdown
  ): array {
    $cfg = cfg('ai');
    $provider = $cfg['provider'] ?? 'openai';
    if ($provider !== 'openai') {
      return ['ok' => false, 'error' => 'Only openai provider is currently supported'];
    }

    $openai = $cfg['openai'] ?? [];
    $apiKey = (string)($openai['api_key'] ?? '');
    $model = (string)($openai['model'] ?? 'gpt-4.1-mini');
    $baseUrl = rtrim((string)($openai['base_url'] ?? 'https://api.openai.com/v1'), '/');
    $timeout = (int)($openai['timeout_seconds'] ?? 60);

    if ($apiKey === '') {
      return ['ok' => false, 'error' => 'OPENAI_API_KEY saknas i miljovariabler'];
    }

    $url = $baseUrl . '/chat/completions';
    $payload = [
      'model' => $model,
      'temperature' => 0.2,
      'response_format' => ['type' => 'json_object'],
      'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        [
          'role' => 'user',
          'content' => "INSTRUKTION:\n" . $userInstruction . "\n\n" .
            "NUVARANDE MARKDOWN:\n" . $currentMarkdown . "\n\n" .
            "Svara med JSON-format:\n" .
            "{\n" .
            "  \"updated_markdown\": \"...\",\n" .
            "  \"notes\": \"kort sammanfattning\"\n" .
            "}\n",
        ],
      ],
    ];

    $ch = curl_init($url);
    if ($ch === false) {
      return ['ok' => false, 'error' => 'Kunde inte initiera HTTP-klient'];
    }

    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
      ],
      CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
      CURLOPT_TIMEOUT => $timeout,
    ]);

    $raw = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $curlErr !== '') {
      return ['ok' => false, 'error' => 'OpenAI-anrop misslyckades: ' . $curlErr];
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
      return ['ok' => false, 'error' => 'Ogiltigt svar fran OpenAI'];
    }
    if ($httpCode >= 400) {
      $msg = $json['error']['message'] ?? ('HTTP ' . $httpCode);
      return ['ok' => false, 'error' => 'OpenAI fel: ' . $msg];
    }

    $content = (string)($json['choices'][0]['message']['content'] ?? '');
    $parsed = json_decode($content, true);
    if (!is_array($parsed)) {
      return ['ok' => false, 'error' => 'AI-svaret kunde inte tolkas som JSON'];
    }

    $updated = (string)($parsed['updated_markdown'] ?? '');
    $notes = (string)($parsed['notes'] ?? '');
    if ($updated === '') {
      return ['ok' => false, 'error' => 'AI-svaret saknade updated_markdown'];
    }

    return ['ok' => true, 'updated_markdown' => $updated, 'notes' => $notes];
  }
}
