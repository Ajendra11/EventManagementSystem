/**
 * EventHub Chatbot
 * Dynamic/context-aware quick chips
 * File: assets/js/chatbot.js
 */
document.addEventListener('DOMContentLoaded', function () {
    var widget = document.getElementById('ehChatbot');

    if (!widget) {
        return;
    }

    var toggle = document.getElementById('ehChatbotToggle');
    var closeBtn = document.getElementById('ehChatbotClose');
    var panel = document.getElementById('ehChatbotPanel');
    var form = document.getElementById('ehChatbotForm');
    var input = document.getElementById('ehChatbotInput');
    var messages = document.getElementById('ehChatbotMessages');
    var suggestions = document.getElementById('ehChatbotSuggestions');

    if (!toggle || !closeBtn || !panel || !form || !input || !messages || !suggestions) {
        return;
    }

    var isSending = false;
    var starterUsedCount = 0;
    var lastSuggestionKey = 'starter';
    var appUrl = window.APP_URL || '';

    var suggestionGroups = {
        starter: [
            { label: 'Recommend events', message: 'Recommend events', type: 'send' },
            { label: 'Free events', message: 'Show me free events', type: 'send' },
            { label: 'Workshops', message: 'Show me workshops', type: 'send' }
        ],

        event: [
            { label: 'Recommend events', message: 'Recommend events', type: 'send' },
            { label: 'Free events', message: 'Show me free events', type: 'send' },
            { label: 'Workshops', message: 'Show me workshops', type: 'send' },
            { label: 'Tech events', message: 'Show me tech events', type: 'send' }
        ],

        where: [
            { label: 'Where are my bookings?', message: 'Where are my bookings?', type: 'send' },
            { label: 'Where are my reviews?', message: 'Where are my reviews?', type: 'send' },
            { label: 'Where is my account?', message: 'Where is my account?', type: 'send' },
            { label: 'Where are events?', message: 'Where are events?', type: 'send' }
        ],

        booking: [
            { label: 'My booking status', message: 'My booking status', type: 'send' },
            { label: 'Where are my bookings?', message: 'Where are my bookings?', type: 'send' },
            { label: 'QR ticket help', message: 'How can I download my QR ticket?', type: 'send' }
        ],

        account: [
            { label: 'Where is my account?', message: 'Where is my account?', type: 'send' },
            { label: 'Change password', message: 'Where can I change my password?', type: 'send' },
            { label: 'Delete account', message: 'Where can I delete my account?', type: 'send', danger: true }
        ],

        review: [
            { label: 'Where are my reviews?', message: 'Where are my reviews?', type: 'send' },
            { label: 'Write a review', message: 'How do I write a review?', type: 'send' }
        ],

        auth: [
            { label: 'Login', message: 'Where is login?', type: 'send' },
            { label: 'Create account', message: 'Where can I create an account?', type: 'send' }
        ],

        followEvent: [
            { label: 'Free events', message: 'Show me free events', type: 'send' },
            { label: 'Workshops', message: 'Show me workshops', type: 'send' },
            { label: 'Browse events', message: 'Where are events?', type: 'send' }
        ],

        followBooking: [
            { label: 'Where are my bookings?', message: 'Where are my bookings?', type: 'send' },
            { label: 'QR ticket help', message: 'How do I use my QR ticket?', type: 'send' },
            { label: 'Browse events', message: 'Where are events?', type: 'send' }
        ],

        followAccount: [
            { label: 'Manage account', message: 'Where is my account?', type: 'send' },
            { label: 'Change password', message: 'Where can I change my password?', type: 'send' },
            { label: 'My bookings', message: 'Where are my bookings?', type: 'send' }
        ]
    };

    function normalizeText(value) {
        return String(value || '').trim().toLowerCase();
    }

    function containsAny(text, words) {
        return words.some(function (word) {
            return text.indexOf(word) !== -1;
        });
    }

    function getSuggestionKey(text, allowStarter) {
        var value = normalizeText(text);

        if (!value) {
            return allowStarter && starterUsedCount < 2 ? 'starter' : '';
        }

        if (containsAny(value, ['where is', 'where are', 'where can', 'find', 'show my', 'how to find', 'link'])) {
            return 'where';
        }

        if (containsAny(value, ['booking', 'bookings', 'ticket', 'qr', 'payment', 'paid', 'khalti'])) {
            return 'booking';
        }

        if (containsAny(value, ['account', 'profile', 'password', 'delete', 'security', 'manage'])) {
            return 'account';
        }

        if (containsAny(value, ['review', 'reviews', 'rating', 'ratings'])) {
            return 'review';
        }

        if (containsAny(value, ['login', 'register', 'signup', 'sign up', 'sign in'])) {
            return 'auth';
        }

        if (containsAny(value, ['event', 'events', 'workshop', 'conference', 'summit', 'hackathon', 'tech', 'free', 'recommend'])) {
            return 'event';
        }

        return '';
    }

    function getFollowupKey(userMessage, botReply) {
        var combined = normalizeText(userMessage + ' ' + botReply);

        if (containsAny(combined, ['booking', 'ticket', 'qr', 'payment'])) {
            return 'followBooking';
        }

        if (containsAny(combined, ['account', 'profile', 'password', 'security'])) {
            return 'followAccount';
        }

        if (containsAny(combined, ['event', 'workshop', 'conference', 'summit', 'hackathon', 'free'])) {
            return 'followEvent';
        }

        return '';
    }

    function clearSuggestions() {
        suggestions.innerHTML = '';
        suggestions.classList.add('is-empty');
        lastSuggestionKey = '';
    }

    function renderSuggestions(key) {
        var chips = suggestionGroups[key] || [];

        suggestions.innerHTML = '';

        if (!chips.length) {
            suggestions.classList.add('is-empty');
            lastSuggestionKey = '';
            return;
        }

        chips.forEach(function (chip) {
            var button = document.createElement('button');

            button.type = 'button';
            button.className = 'eh-chatbot-chip';
            button.textContent = chip.label;

            if (chip.danger) {
                button.classList.add('danger');
            }

            button.addEventListener('click', function () {
                if (isSending) {
                    return;
                }

                if (key === 'starter') {
                    starterUsedCount += 1;
                }

                if (chip.type === 'send' && chip.message) {
                    clearSuggestions();
                    openPanel();
                    sendMessage(chip.message, true);
                }
            });

            suggestions.appendChild(button);
        });

        suggestions.classList.remove('is-empty');
        lastSuggestionKey = key;
    }

    function updateSuggestionsFromInput() {
        if (isSending) {
            clearSuggestions();
            return;
        }

        var key = getSuggestionKey(input.value, true);

        if (!key) {
            clearSuggestions();
            return;
        }

        if (key !== lastSuggestionKey) {
            renderSuggestions(key);
        }
    }

    function scrollToBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function labelForUrl(url) {
        if (url.indexOf('/bookings/my-bookings.php') !== -1) {
            return 'Open My Bookings';
        }

        if (url.indexOf('/reviews/my-reviews.php') !== -1) {
            return 'Open My Reviews';
        }

        if (url.indexOf('/auth/manage-profile.php') !== -1) {
            return 'Open Manage Account';
        }

        if (url.indexOf('/auth/password-security.php') !== -1) {
            return 'Open Password & Security';
        }

        if (url.indexOf('/auth/delete-account.php') !== -1) {
            return 'Open Delete Account';
        }

        if (url.indexOf('/events/index.php') !== -1) {
            return 'Browse Events';
        }

        if (url.indexOf('/events/show.php') !== -1) {
            return 'View Event';
        }

        if (url.indexOf('/auth/login.php') !== -1) {
            return 'Open Login';
        }

        if (url.indexOf('/auth/register.php') !== -1) {
            return 'Create Account';
        }

        return 'Open Link';
    }

    function appendTextWithLinks(container, text) {
        var value = String(text || '');
        var urlPattern = /(https?:\/\/[^\s]+)/g;
        var lastIndex = 0;
        var match;

        while ((match = urlPattern.exec(value)) !== null) {
            if (match.index > lastIndex) {
                container.appendChild(document.createTextNode(value.slice(lastIndex, match.index)));
            }

            var url = match[0];
            var link = document.createElement('a');

            link.href = url;
            link.textContent = labelForUrl(url);
            link.target = '_blank';
            link.rel = 'noopener noreferrer';

            container.appendChild(link);
            lastIndex = match.index + url.length;
        }

        if (lastIndex < value.length) {
            container.appendChild(document.createTextNode(value.slice(lastIndex)));
        }
    }

    function addMessage(text, who) {
        var div = document.createElement('div');

        div.className = 'eh-chatbot-msg ' + who;
        appendTextWithLinks(div, text);

        messages.appendChild(div);
        scrollToBottom();

        return div;
    }

    function showTyping() {
        var div = document.createElement('div');

        div.className = 'eh-chatbot-msg bot eh-chatbot-typing';
        div.setAttribute('aria-label', 'Assistant is typing');
        div.innerHTML = '<span class="eh-dot"></span><span class="eh-dot"></span><span class="eh-dot"></span>';

        messages.appendChild(div);
        scrollToBottom();

        return div;
    }

    function removeTyping(element) {
        if (element && element.parentNode) {
            element.parentNode.removeChild(element);
        }
    }

    function setInputDisabled(disabled) {
        var sendButton = form.querySelector('button[type="submit"]');

        input.disabled = disabled;

        if (sendButton) {
            sendButton.disabled = disabled;
        }
    }

    function openPanel() {
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');

        window.setTimeout(function () {
            input.focus();
            scrollToBottom();
            updateSuggestionsFromInput();
        }, 0);
    }

    function closePanel() {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    function sendMessage(message, fromChip) {
        var value = String(message || '').trim();

        if (!value || isSending) {
            return;
        }

        addMessage(value, 'user');
        input.value = '';

        isSending = true;
        clearSuggestions();
        setInputDisabled(true);

        var typingElement = showTyping();
        var apiUrl = appUrl + '/api/chatbot.php';

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                message: value
            }).toString()
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            })
            .then(function (data) {
                var reply = data && typeof data.reply === 'string' && data.reply.trim()
                    ? data.reply.trim()
                    : 'Sorry, I could not generate a response. Please try again.';

                removeTyping(typingElement);
                addMessage(reply, 'bot');

                var followupKey = getFollowupKey(value, reply);

                if (followupKey) {
                    renderSuggestions(followupKey);
                } else if (!fromChip) {
                    updateSuggestionsFromInput();
                }
            })
            .catch(function () {
                removeTyping(typingElement);
                addMessage(
                    'I am having trouble connecting right now. Please try again in a moment, or browse the Events page for the latest listings.',
                    'bot'
                );
            })
            .finally(function () {
                isSending = false;
                setInputDisabled(false);
                input.focus();
            });
    }

    toggle.addEventListener('click', function () {
        if (panel.hidden) {
            openPanel();
        } else {
            closePanel();
        }
    });

    closeBtn.addEventListener('click', closePanel);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            closePanel();
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        sendMessage(input.value, false);
    });

    input.addEventListener('input', updateSuggestionsFromInput);
    input.addEventListener('focus', updateSuggestionsFromInput);

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage(input.value, false);
        }
    });

    renderSuggestions('starter');
});