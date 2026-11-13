import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;

window.initScrollReveal = function (root = document) {
    if (!('IntersectionObserver' in window)) {
        root.querySelectorAll('.reveal-on-scroll').forEach((el) => el.classList.add('is-revealed'));
        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });

    root.querySelectorAll('.reveal-on-scroll:not(.is-revealed)').forEach((el) => observer.observe(el));
};

document.addEventListener('DOMContentLoaded', () => {
    window.initScrollReveal();
});

window.aiAssistantWidget = (config) => ({
    config,
    panelOpen: false,
    sideTabVisible: false,
    messages: [],
    input: '',
    loading: false,
    pageContextOverride: null,
    activePageContext: {
        product_slug: null,
        product_name: null,
    },
    revealTimer: null,
    stateKey: 'bmw_ai_assistant_state_v4',
    visitorKey: 'bmw_ai_visitor_id',
    visitorId: null,
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
        this.visitorId = this.ensureVisitorId();
        this.activePageContext = this.detectPageContext();
        this.attachVisitorIdToForms();
        this.restoreState();
        this.scrollMessages();

        window.addEventListener('resize', () => {
            if (window.innerWidth < 640 && this.panelOpen) {
                this.scrollMessages();
            }
        });

        window.addEventListener('bmw-ai:ask', (event) => {
            this.handleContextualAsk(event?.detail || {});
        });

        document.addEventListener('submit', () => this.attachVisitorIdToForms(), true);
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
        this.stopProgressiveReveal();
        this.messages = [];
        this.persistState();
        this.scrollMessages();

        const resetEndpoint = this.config.resetEndpoint || '/ai/showroom-assistant/reset';

        fetch(resetEndpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.config.csrf,
            },
            body: JSON.stringify({
                visitor_id: this.ensureVisitorId(),
            }),
            keepalive: true,
        }).catch(() => {});
    },

    handleContextualAsk(detail = {}) {
        if (detail?.context && typeof detail.context === 'object') {
            this.pageContextOverride = {
                product_slug: detail.context.product_slug || null,
                product_name: detail.context.product_name || null,
            };
        }

        this.activePageContext = this.detectPageContext();
        this.openPanel();

        const prompt = typeof detail?.prompt === 'string' ? detail.prompt.trim() : '';

        if (prompt && !this.loading) {
            this.input = prompt;
            this.send();
        }
    },

    detectPageContext() {
        if (this.pageContextOverride?.product_slug) {
            return {
                product_slug: this.pageContextOverride.product_slug,
                product_name: this.pageContextOverride.product_name || null,
            };
        }

        const contextEl = document.querySelector('[data-ai-page-context]');

        if (contextEl?.dataset?.productSlug) {
            return {
                product_slug: contextEl.dataset.productSlug,
                product_name: contextEl.dataset.productName || null,
            };
        }

        const pathMatch = window.location.pathname.match(/^\/catalog\/([a-z0-9-]+)$/i);

        if (pathMatch) {
            return {
                product_slug: pathMatch[1],
                product_name: null,
            };
        }

        return {
            product_slug: null,
            product_name: null,
        };
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

        const pageContext = this.detectPageContext();
        this.activePageContext = pageContext;
        this.stopProgressiveReveal();

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
                body: JSON.stringify({
                    message,
                    visitor_id: this.ensureVisitorId(),
                    page_url: window.location.href,
                    referrer: document.referrer || null,
                    product_slug: pageContext.product_slug,
                    product_name: pageContext.product_name,
                }),
            });

            const payload = await response.json().catch(() => ({}));

            this.appendAssistantMessage(
                payload.answer || config.fallback,
                Array.isArray(payload.suggestions) ? payload.suggestions : [],
                true,
            );
        } catch (error) {
            this.appendAssistantMessage(config.fallback, [], true);
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

    prefersReducedMotion() {
        return typeof window.matchMedia === 'function'
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    },

    stopProgressiveReveal() {
        if (this.revealTimer) {
            clearInterval(this.revealTimer);
            this.revealTimer = null;
        }

        this.messages.forEach((item) => {
            if (item.role === 'assistant' && Number.isFinite(item.totalChars)) {
                item.revealedChars = item.totalChars;
            }
        });
    },

    appendAssistantMessage(text, suggestions = [], animate = true) {
        const assistantMessage = this.createAssistantMessage(text, suggestions, animate);
        this.messages.push(assistantMessage);

        if (animate && !this.prefersReducedMotion() && assistantMessage.totalChars > 0) {
            this.startProgressiveReveal(assistantMessage.id, assistantMessage.totalChars);
        }
    },

    startProgressiveReveal(messageId, totalChars) {
        const step = Math.max(4, Math.ceil(totalChars / 36));

        this.revealTimer = setInterval(() => {
            const target = this.messages.find((item) => item.id === messageId);

            if (!target) {
                this.stopProgressiveReveal();
                return;
            }

            target.revealedChars = Math.min(totalChars, (target.revealedChars ?? 0) + step);
            this.scrollMessages();

            if (target.revealedChars >= totalChars) {
                this.stopProgressiveReveal();
            }
        }, 18);
    },

    visiblePartText(part, revealedChars) {
        const text = String(part?.text || '');
        const limit = Number.isFinite(revealedChars) ? revealedChars : text.length;
        const start = Number(part?.start ?? 0);

        if (limit <= start) {
            return '';
        }

        return text.slice(0, Math.max(0, limit - start));
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

    createAssistantMessage(text, suggestions = [], animate = false) {
        const formatted = this.formatAssistantMessage(text, suggestions);
        const offsetState = this.assignPartOffsets(formatted.blocks);
        const shouldAnimate = animate && !this.prefersReducedMotion() && offsetState.totalChars > 0;

        return {
            id: this.messageId(),
            role: 'assistant',
            text: String(text || ''),
            blocks: offsetState.blocks,
            actions: formatted.actions,
            totalChars: offsetState.totalChars,
            revealedChars: shouldAnimate ? 0 : offsetState.totalChars,
        };
    },

    assignPartOffsets(blocks) {
        let offset = 0;

        const mappedBlocks = blocks.map((block) => {
            if (block.type === 'list') {
                return {
                    ...block,
                    items: (block.items || []).map((item) => ({
                        ...item,
                        parts: (item.parts || []).map((part) => {
                            const text = String(part.text || '');
                            const mapped = { ...part, start: offset, end: offset + text.length };
                            offset += text.length;
                            return mapped;
                        }),
                    })),
                };
            }

            return {
                ...block,
                parts: (block.parts || []).map((part) => {
                    const text = String(part.text || '');
                    const mapped = { ...part, start: offset, end: offset + text.length };
                    offset += text.length;
                    return mapped;
                }),
            };
        });

        return {
            blocks: mappedBlocks,
            totalChars: offset,
        };
    },

    messageId() {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        return `msg-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    },

    ensureVisitorId() {
        try {
            const stored = localStorage.getItem(this.visitorKey);

            if (this.isValidVisitorId(stored)) {
                return stored;
            }

            const generated = window.crypto?.randomUUID
                ? window.crypto.randomUUID()
                : `visitor-${Date.now()}-${Math.random().toString(16).slice(2)}`;

            localStorage.setItem(this.visitorKey, generated);

            return generated;
        } catch (error) {
            return `volatile-${Date.now()}-${Math.random().toString(16).slice(2)}`;
        }
    },

    isValidVisitorId(value) {
        return typeof value === 'string'
            && value.length > 0
            && value.length <= 80
            && /^[A-Za-z0-9._:-]+$/.test(value);
    },

    attachVisitorIdToForms() {
        const visitorId = this.ensureVisitorId();
        const forms = document.querySelectorAll(
            'form[action*="/booking"], form[action*="/accessories/"][action*="/order"]',
        );

        forms.forEach((form) => {
            let input = form.querySelector('input[name="ai_visitor_id"]');

            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ai_visitor_id';
                input.setAttribute('data-ai-visitor-field', 'true');
                form.appendChild(input);
            }

            input.value = visitorId;
        });
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
        const pattern = /(\*\*([^*]+)\*\*)|(__([^_]+)__)|(\[([^\]]{1,100})\]\(([^)]+)\))/g;
        let cursor = 0;
        let match;

        while ((match = pattern.exec(text)) !== null) {
            if (match.index > cursor) {
                parts.push({ type: 'text', text: text.slice(cursor, match.index) });
            }

            if (match[2] || match[4]) {
                parts.push({ type: 'strong', text: match[2] || match[4] });
            } else if (match[6] && match[7]) {
                const url = this.internalLink(match[7]) || match[7];
                parts.push({ type: 'link', text: match[6], url: url });
            }
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

        cleanedText.replace(/\[([^\]]{1,100})]\(([^)]+)\)/g, (match, label, href) => {
            addAction(href, label);
            return match;
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
            actions: actions.map(({ label, url }) => this.actionMeta(url, label)),
        };
    },

    actionMeta(url, fallbackLabel = '') {
        const value = String(url || '');
        const label = this.actionLabel(value, fallbackLabel);

        if (value.startsWith('/accessories/') && value.endsWith('/order')) {
            return { label, subtitle: 'Đặt hàng chính hãng', kind: 'accessory', url: value };
        }

        if (value.startsWith('/booking')) {
            const type = new URL(value, window.location.origin).searchParams.get('type');

            if (type === 'test_drive' || type === 'test') {
                return { label, subtitle: 'Trải nghiệm thực tế', kind: 'booking', url: value };
            }

            if (type === 'quote') {
                return { label, subtitle: 'Tư vấn tài chính', kind: 'quote', url: value };
            }

            return { label, subtitle: 'Showroom BMW', kind: 'booking', url: value };
        }

        if (value.startsWith('/compare')) {
            return { label, subtitle: 'Bảng đối chiếu thông số', kind: 'compare', url: value };
        }

        if (value.startsWith('/tim-hieu-them/')) {
            return { label, subtitle: 'Chuyên mục Showroom', kind: 'article', url: value };
        }

        if (value.startsWith('/tim-hieu-them') || value.startsWith('/offers')) {
            return { label, subtitle: 'Chương trình ưu đãi', kind: 'offer', url: value };
        }

        if (value.startsWith('/catalog?type=motorbike')) {
            return { label, subtitle: 'BMW Motorrad', kind: 'catalog', url: value };
        }

        if (value.startsWith('/catalog/')) {
            return { label, subtitle: 'Hồ sơ sản phẩm', kind: 'product', url: value };
        }

        if (value.startsWith('/accessories')) {
            return { label, subtitle: 'Phụ kiện chính hãng', kind: 'accessory', url: value };
        }

        if (value.startsWith('/catalog')) {
            return { label, subtitle: 'Danh mục Showroom', kind: 'catalog', url: value };
        }

        return { label, subtitle: 'Showroom BMW', kind: 'link', url: value };
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
