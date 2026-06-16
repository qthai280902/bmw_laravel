import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.aiAssistantWidget = (config) => ({
    config,
    panelOpen: false,
    sideTabVisible: false,
    messages: [],
    input: '',
    loading: false,
    stateKey: 'bmw_ai_assistant_state_v4',
    legacyStateKeys: [
        'bmw_ai_assistant_state_v2',
        'bmw_ai_assistant_state_v3',
        'bmw_ai_assistant_position',
    ],
    maxStoredMessages: 24,

    get hasMessages() {
        return this.messages.length > 0;
    },

    init() {
        this.clearLegacyState();
        this.restoreState();
        this.scrollMessages();

        window.addEventListener('resize', () => {
            if (window.innerWidth < 640 && this.panelOpen) {
                this.scrollMessages();
            }
        });
    },

    openPanel() {
        this.panelOpen = true;
        this.sideTabVisible = false;
        this.persistState();
        this.scrollMessages();
    },

    minimize() {
        this.panelOpen = false;
        this.sideTabVisible = false;
        this.persistState();
    },

    hideToSide() {
        this.panelOpen = false;
        this.sideTabVisible = true;
        this.persistState();
    },

    clearConversation() {
        this.messages = [];
        this.persistState();
        this.scrollMessages();
    },

    sendSuggestion(prompt) {
        this.input = prompt;
        this.openPanel();
        this.send();
    },

    async send() {
        const message = this.input.trim();

        if (!message || this.loading) {
            return;
        }

        this.messages.push(this.createUserMessage(message));
        this.input = '';
        this.loading = true;
        this.persistState();
        this.scrollMessages();

        try {
            const response = await fetch(config.endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                },
                body: JSON.stringify({ message }),
            });

            const payload = await response.json().catch(() => ({}));

            this.messages.push(this.createAssistantMessage(
                payload.answer || config.fallback,
                Array.isArray(payload.suggestions) ? payload.suggestions : [],
            ));
        } catch (error) {
            this.messages.push(this.createAssistantMessage(config.fallback, []));
        } finally {
            this.pruneMessages();
            this.loading = false;
            this.persistState();
            this.scrollMessages();
        }
    },

    scrollMessages() {
        this.$nextTick(() => {
            if (this.$refs.messages) {
                this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
            }
        });
    },

    createUserMessage(text) {
        return {
            id: this.messageId(),
            role: 'user',
            text: String(text || ''),
            blocks: [],
            actions: [],
        };
    },

    createAssistantMessage(text, suggestions = []) {
        const formatted = this.formatAssistantMessage(text, suggestions);

        return {
            id: this.messageId(),
            role: 'assistant',
            text: String(text || ''),
            blocks: formatted.blocks,
            actions: formatted.actions,
        };
    },

    messageId() {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        return `msg-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    },

    formatAssistantMessage(text, suggestions = []) {
        const extracted = this.extractActions(String(text || ''), suggestions);
        const blocks = this.buildBlocks(extracted.text);

        return {
            blocks: blocks.length ? blocks : [{
                type: 'paragraph',
                parts: [{ type: 'text', text: 'Tôi đã chuẩn bị một số thao tác phù hợp cho bạn.' }],
            }],
            actions: extracted.actions,
        };
    },

    buildBlocks(text) {
        const lines = String(text || '')
            .replace(/\r\n/g, '\n')
            .replace(/\r/g, '\n')
            .split('\n');
        const blocks = [];
        let paragraphLines = [];
        let listItems = [];

        const flushParagraph = () => {
            const paragraph = paragraphLines.join(' ').replace(/\s{2,}/g, ' ').trim();

            if (paragraph) {
                blocks.push({
                    type: 'paragraph',
                    parts: this.parseTextParts(paragraph),
                });
            }

            paragraphLines = [];
        };

        const flushList = () => {
            if (listItems.length) {
                blocks.push({
                    type: 'list',
                    items: listItems.map((item) => ({
                        parts: this.parseTextParts(item),
                    })),
                });
            }

            listItems = [];
        };

        lines.forEach((line) => {
            const trimmedLine = line.trim();

            if (!trimmedLine) {
                flushParagraph();
                flushList();
                return;
            }

            const listMatch = trimmedLine.match(/^([-*\u2022]|\d+[.)])\s+(.+)$/);

            if (listMatch) {
                flushParagraph();
                listItems.push(listMatch[2]);
                return;
            }

            flushList();
            paragraphLines.push(trimmedLine);
        });

        flushParagraph();
        flushList();

        return blocks;
    },

    parseTextParts(text) {
        const parts = [];
        const pattern = /(\*\*([^*]+)\*\*)|(__([^_]+)__)/g;
        let cursor = 0;
        let match;

        while ((match = pattern.exec(text)) !== null) {
            if (match.index > cursor) {
                parts.push({ type: 'text', text: text.slice(cursor, match.index) });
            }

            parts.push({ type: 'strong', text: match[2] || match[4] });
            cursor = pattern.lastIndex;
        }

        if (cursor < text.length) {
            parts.push({ type: 'text', text: text.slice(cursor) });
        }

        return parts.length ? parts : [{ type: 'text', text }];
    },

    extractActions(text, suggestions = []) {
        const actions = [];
        let cleanedText = String(text || '');

        const addAction = (href, fallbackLabel = '') => {
            const url = this.internalLink(href);

            if (!url) {
                return null;
            }

            const label = this.actionLabel(url, fallbackLabel);
            const key = `${label}|${url}`;

            if (!actions.some((action) => action.key === key || action.url === url)) {
                actions.push({ key, label, url });
            }

            return url;
        };

        cleanedText = cleanedText.replace(/\[([^\]]{1,100})]\(([^)]+)\)/g, (match, label, href) => {
            return addAction(href, label) ? label : label;
        });

        cleanedText = cleanedText.replace(/(^|[\s("'\[])(\/(?!\/)[^\s<>()]+)/g, (match, prefix, rawHref) => {
            const trailing = rawHref.match(/[.,;:!?]+$/)?.[0] || '';
            const cleanHref = trailing ? rawHref.slice(0, -trailing.length) : rawHref;

            if (addAction(cleanHref)) {
                return prefix.trimEnd() ? prefix : '';
            }

            return `${prefix}${cleanHref}${trailing}`;
        });

        suggestions.forEach((suggestion) => {
            addAction(suggestion?.url, suggestion?.label);
        });

        return {
            text: cleanedText
                .replace(/[ \t]+\n/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .replace(/[ \t]{2,}/g, ' ')
                .trim(),
            actions: actions.map(({ label, url }) => ({ label, url })),
        };
    },

    actionLabel(url, fallbackLabel = '') {
        const value = String(url || '');
        const fallback = String(fallbackLabel || '').trim();

        if (value.startsWith('/accessories/') && value.endsWith('/order')) {
            return 'Đặt phụ kiện';
        }

        if (value.startsWith('/booking')) {
            const type = new URL(value, window.location.origin).searchParams.get('type');

            if (type === 'test_drive' || type === 'test') {
                return 'Đặt lịch lái thử';
            }

            if (type === 'quote') {
                return 'Nhận báo giá';
            }

            return 'Đặt lịch tư vấn';
        }

        if (value.startsWith('/compare')) {
            return 'So sánh xe';
        }

        if (value.startsWith('/tim-hieu-them/')) {
            return 'Đọc bài viết';
        }

        if (value.startsWith('/tim-hieu-them')) {
            return 'Xem ưu đãi';
        }

        if (value.startsWith('/catalog?type=motorbike')) {
            return 'Xem BMW Motorrad';
        }

        if (value.startsWith('/catalog/')) {
            return 'Xem chi tiết';
        }

        if (value.startsWith('/accessories')) {
            return 'Xem phụ kiện';
        }

        if (value.startsWith('/catalog')) {
            return 'Xem danh mục';
        }

        return fallback || 'Mở liên kết';
    },

    internalLink(href) {
        const value = String(href || '').trim();
        const lowered = value.toLowerCase();

        if (
            !value
            || value.startsWith('//')
            || value.includes('\n')
            || lowered.startsWith('javascript:')
            || lowered.startsWith('data:')
        ) {
            return null;
        }

        if (value.startsWith('/') && !value.startsWith('/\\')) {
            return value;
        }

        try {
            const url = new URL(value, window.location.origin);

            return url.origin === window.location.origin
                ? `${url.pathname}${url.search}${url.hash}`
                : null;
        } catch (error) {
            return null;
        }
    },

    restoreState() {
        try {
            const saved = JSON.parse(localStorage.getItem(this.stateKey) || 'null');

            if (!saved || typeof saved !== 'object') {
                return;
            }

            this.panelOpen = saved.mode === 'panel';
            this.sideTabVisible = saved.mode === 'hidden';

            if (Array.isArray(saved.messages)) {
                this.messages = saved.messages
                    .filter((message) => ['assistant', 'user'].includes(message?.role) && typeof message?.text === 'string')
                    .slice(-this.maxStoredMessages)
                    .map((message) => message.role === 'user'
                        ? this.createUserMessage(message.text)
                        : this.createAssistantMessage(message.text, []));
            }
        } catch (error) {
            this.panelOpen = false;
            this.sideTabVisible = false;
            this.messages = [];
        }
    },

    persistState() {
        try {
            localStorage.setItem(this.stateKey, JSON.stringify({
                mode: this.sideTabVisible ? 'hidden' : (this.panelOpen ? 'panel' : 'launcher'),
                messages: this.messages.slice(-this.maxStoredMessages).map((message) => ({
                    role: message.role === 'user' ? 'user' : 'assistant',
                    text: this.safeStoredText(message.text),
                })),
            }));
        } catch (error) {
            void error;
        }
    },

    clearLegacyState() {
        try {
            this.legacyStateKeys.forEach((key) => localStorage.removeItem(key));
        } catch (error) {
            void error;
        }
    },

    pruneMessages() {
        if (this.messages.length <= this.maxStoredMessages) {
            return;
        }

        this.messages = this.messages.slice(-this.maxStoredMessages);
    },

    safeStoredText(text) {
        return String(text || '')
            .replace(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi, '[email hidden]')
            .replace(/(?:\+?84|0)(?:[\s.-]?\d){8,10}/g, '[contact hidden]')
            .slice(0, 1400);
    },
});

Alpine.start();
