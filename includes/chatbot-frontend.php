<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$showChatbot = !is_admin() && !str_contains($path, '/admin/');

if (!$showChatbot) {
    return;
}
?>

<div class="eh-chatbot" id="ehChatbot" role="region" aria-label="EventHub AI Assistant">
    <button
        class="eh-chatbot-toggle"
        id="ehChatbotToggle"
        type="button"
        aria-expanded="false"
        aria-controls="ehChatbotPanel"
        title="Open AI Assistant"
    >
        💬 AI Help
    </button>

    <div
        class="eh-chatbot-panel"
        id="ehChatbotPanel"
        role="dialog"
        aria-label="EventHub Assistant chat"
        aria-modal="false"
        hidden
    >
        <div class="eh-chatbot-head">
            <div>
                <strong>EventHub Assistant</strong>
                <span class="eh-chatbot-subtitle">Powered by AI</span>
            </div>

            <button type="button" id="ehChatbotClose" aria-label="Close chat" title="Close chat">
                ×
            </button>
        </div>

        <div
            class="eh-chatbot-messages"
            id="ehChatbotMessages"
            role="log"
            aria-live="polite"
            aria-relevant="additions"
        >
            <div class="eh-chatbot-msg bot">Hi! Ask me about events, prices, seats, venues, or your booking status.</div>
        </div>

        <div class="eh-chatbot-bottom">
            <div
                class="eh-chatbot-suggestions"
                id="ehChatbotSuggestions"
                aria-label="Quick suggestions"
            ></div>

            <form class="eh-chatbot-form" id="ehChatbotForm" autocomplete="off" novalidate>
                <input
                    type="text"
                    id="ehChatbotInput"
                    name="message"
                    maxlength="500"
                    placeholder="Ask about events…"
                    autocomplete="off"
                    aria-label="Your message"
                    required
                >

                <button type="submit" aria-label="Send message">
                    Send
                </button>
            </form>
        </div>
    </div>
</div>