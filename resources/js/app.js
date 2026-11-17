import './bootstrap';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;
window.Livewire = Livewire;

window.syncComparisonButtons = function () {
    if (typeof window.getComparisonIds !== 'function') {
        return;
    }

    window.dispatchEvent(new CustomEvent('comparison-updated', {
        detail: window.getComparisonIds(),
    }));
};

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

document.addEventListener('livewire:navigated', () => {
    window.initScrollReveal();
    window.syncComparisonButtons();
});

Livewire.hook('morph.updated', () => {
    window.requestAnimationFrame(() => {
        window.initScrollReveal();
        window.syncComparisonButtons();
    });
});

window.aiAssistantWidget = (config) => ({
    config,
    panelOpen: false,
    sideTabVisible: false,
    messages: [],
    input: '',
    loading: false,
    streaming: false,
    abortController: null,
    selectedImage: null,
    selectedImagePreview: null,
    selectedImageName: null,
    imageError: null,
    pageContextOverride: null,
    activePageContext: {
        product_slug: null,
        product_name: null,
    },
    revealTimer: null,
    stateKey: 'bmw_ai_assistant_state_v4',
    visitorKey: 'bmw_ai_visitor_id',
    syncChannelName: 'bmw_ai_assistant_sync',
    syncChannel: null,
    visitorId: null,
    legacyStateKeys: [
        'bmw_ai_assistant_state_v2',
        'bmw_ai_assistant_state_v3',
        'bmw_ai_assistant_position',
    ],
    maxStoredMessages: 16,

    get hasMessages() {
        return this.messages.length > 0;
    },

    init() {
        this.clearLegacyState();
        this.visitorId = this.ensureVisitorId();
        this.activePageContext = this.detectPageContext();
        this.attachVisitorIdToForms();
        this.restoreState();
        this.setupCrossTabSync();
        if (!this.messages.some((item) => item?.role === 'user')) {
            this.rehydrateFromServer();
        }
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

    setupCrossTabSync() {
        window.addEventListener('storage', (event) => {
            if (event.key === this.stateKey || event.key === this.visitorKey) {
                this.syncFromLocalStorage();
            }
        });

        if (typeof BroadcastChannel !== 'undefined') {
            try {
                this.syncChannel = new BroadcastChannel(this.syncChannelName);
                this.syncChannel.onmessage = (event) => {
                    if (event?.data?.type === 'sync') {
                        this.syncFromLocalStorage();
                    }
                };
            } catch (error) {
                this.syncChannel = null;
            }
        }
    },

    broadcastSync() {
        if (!this.syncChannel) {
            return;
        }

        try {
            this.syncChannel.postMessage({
                type: 'sync',
                visitorId: this.visitorId,
                updatedAt: Date.now(),
            });
        } catch (error) {
            void error;
        }
    },

    syncVisitorId(nextVisitorId) {
        if (!this.isValidVisitorId(nextVisitorId)) {
            return;
        }

        this.visitorId = nextVisitorId;
        try {
            localStorage.setItem(this.visitorKey, nextVisitorId);
        } catch (error) {
            void error;
        }
        this.attachVisitorIdToForms();
    },

    syncFromLocalStorage() {
        this.stopProgressiveReveal();

        try {
            const storedVisitor = localStorage.getItem(this.visitorKey);
            if (this.isValidVisitorId(storedVisitor)) {
                this.visitorId = storedVisitor;
                this.attachVisitorIdToForms();
            }
        } catch (error) {
            void error;
        }

        this.restoreState();
        this.scrollMessages();
    },

    async rehydrateFromServer() {
        const historyEndpoint = this.config.historyEndpoint || '/ai/showroom-assistant/history';
        if (!historyEndpoint || typeof fetch !== 'function') {
            return;
        }

        try {
            const url = new URL(historyEndpoint, window.location.origin);
            const currentVisitorId = this.ensureVisitorId();
            if (currentVisitorId) {
                url.searchParams.set('visitor_id', currentVisitorId);
            }

            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json().catch(() => null);
            if (!payload || payload.restored !== true || !Array.isArray(payload.messages) || payload.messages.length === 0) {
                return;
            }

            if (payload.visitor_id) {
                this.syncVisitorId(payload.visitor_id);
            }

            const restoredMessages = payload.messages
                .filter((msg) => ['user', 'assistant'].includes(msg?.role) && typeof msg?.content === 'string')
                .slice(-this.maxStoredMessages)
                .map((msg) => {
                    if (msg.role === 'user') {
                        return this.createUserMessage(msg.content);
                    }

                    const actions = Array.isArray(msg.actions) ? msg.actions : [];
                    return this.createAssistantMessage(msg.content, actions, false, actions);
                });

            if (restoredMessages.length > 0 && !this.messages.some((item) => item?.role === 'user')) {
                this.messages = restoredMessages;
                this.pruneMessages();
                this.persistState(false);
                this.scrollMessages();
            }
        } catch (error) {
            void error;
        }
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
        this.persistState(true);
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

    openImagePicker() {
        this.imageError = null;
        this.$refs.imageInput?.click();
    },

    handleImageSelect(event) {
        this.imageError = null;
        const file = event?.target?.files?.[0];

        if (!file) {
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            this.imageError = 'Chỉ hỗ trợ ảnh JPG, PNG hoặc WEBP.';
            this.clearSelectedImage();
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            this.imageError = 'Dung lượng ảnh tối đa 5MB.';
            this.clearSelectedImage();
            return;
        }

        if (this.selectedImagePreview) {
            URL.revokeObjectURL(this.selectedImagePreview);
        }

        this.selectedImage = file;
        this.selectedImageName = file.name;
        this.selectedImagePreview = URL.createObjectURL(file);
    },

    clearSelectedImage() {
        if (this.selectedImagePreview) {
            URL.revokeObjectURL(this.selectedImagePreview);
        }

        this.selectedImage = null;
        this.selectedImagePreview = null;
        this.selectedImageName = null;

        if (this.$refs.imageInput) {
            this.$refs.imageInput.value = '';
        }
    },

    abortStreaming() {
        if (this.abortController) {
            this.abortController.abort();
        }
    },

    buildRequestOptions(message, imageFile, pageContext, acceptHeader = 'application/json') {
        const visitorId = this.ensureVisitorId();

        if (imageFile) {
            const formData = new FormData();
            formData.append('message', message);
            formData.append('image', imageFile);
            if (visitorId) formData.append('visitor_id', visitorId);
            formData.append('page_url', window.location.href);
            if (document.referrer) formData.append('referrer', document.referrer);
            if (pageContext.product_slug) formData.append('product_slug', pageContext.product_slug);
            if (pageContext.product_name) formData.append('product_name', pageContext.product_name);

            return {
                method: 'POST',
                headers: {
                    Accept: acceptHeader,
                    'X-CSRF-TOKEN': config.csrf,
                },
                body: formData,
            };
        }

        return {
            method: 'POST',
            headers: {
                Accept: acceptHeader,
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrf,
            },
            body: JSON.stringify({
                message,
                visitor_id: visitorId,
                page_url: window.location.href,
                referrer: document.referrer || null,
                product_slug: pageContext.product_slug,
                product_name: pageContext.product_name,
            }),
        };
    },

    updateStreamingAssistantMessage(messageId, text, suggestions = [], finalize = false) {
        const target = this.messages.find((item) => item.id === messageId);
        if (!target) {
            return;
        }

        const normalizedSuggestions = this.normalizeSuggestionList(suggestions);
        const formatted = this.formatAssistantMessage(text, normalizedSuggestions, null);
        const offsetState = this.assignPartOffsets(formatted.blocks);

        target.text = String(text || '');
        target.blocks = offsetState.blocks;
        target.totalChars = offsetState.totalChars;
        target.revealedChars = offsetState.totalChars;

        if (finalize) {
            target.suggestions = normalizedSuggestions;
            target.actions = formatted.actions;
        }
    },

    async send() {
        const rawMessage = this.input.trim();
        const imageFile = this.selectedImage;
        const previewUrl = this.selectedImagePreview;
        const imageName = this.selectedImageName;

        if ((!rawMessage && !imageFile) || this.loading || this.streaming) {
            return;
        }

        const displayMessage = rawMessage || 'Nhận diện hình ảnh BMW';
        this.syncFromLocalStorage();
        const pageContext = this.detectPageContext();
        this.activePageContext = pageContext;
        this.stopProgressiveReveal();
        this.imageError = null;

        this.messages.push(this.createUserMessage(displayMessage, previewUrl, imageName));
        this.input = '';
        this.selectedImage = null;
        this.selectedImagePreview = null;
        this.selectedImageName = null;
        if (this.$refs.imageInput) {
            this.$refs.imageInput.value = '';
        }

        this.loading = true;
        this.streaming = false;
        this.persistState(true);
        this.scrollMessages();

        let streamAssistantId = null;
        let streamedText = '';

        try {
            if (config.streamEndpoint) {
                this.abortController = new AbortController();
                const streamOptions = this.buildRequestOptions(rawMessage, imageFile, pageContext, 'text/event-stream');
                streamOptions.signal = this.abortController.signal;

                const streamResponse = await fetch(config.streamEndpoint, streamOptions);
                const contentType = streamResponse.headers.get('content-type') || '';

                if (streamResponse.ok && contentType.includes('text/event-stream') && streamResponse.body) {
                    this.streaming = true;
                    const reader = streamResponse.body.getReader();
                    const decoder = new TextDecoder('utf-8');
                    let buffer = '';
                    let receivedDone = false;

                    const processFrame = (frame) => {
                        const lines = frame.split(/\r?\n/);
                        let eventName = 'message';
                        const dataLines = [];

                        for (const line of lines) {
                            if (line.startsWith('event:')) {
                                eventName = line.slice(6).trim();
                            } else if (line.startsWith('data:')) {
                                dataLines.push(line.slice(5).trim());
                            }
                        }

                        if (!dataLines.length) {
                            return;
                        }

                        let data = {};
                        try {
                            data = JSON.parse(dataLines.join('\n'));
                        } catch (e) {
                            return;
                        }

                        if (eventName === 'chunk' && typeof data.delta === 'string') {
                            if (!streamAssistantId) {
                                const placeholder = this.createAssistantMessage('', [], false);
                                this.messages.push(placeholder);
                                streamAssistantId = placeholder.id;
                                this.loading = false;
                            }
                            streamedText += data.delta;
                            this.updateStreamingAssistantMessage(streamAssistantId, streamedText, [], false);
                            this.scrollMessages();
                        } else if (eventName === 'done') {
                            receivedDone = true;
                            if (data.visitor_id) {
                                this.syncVisitorId(data.visitor_id);
                            }
                            const finalAnswer = data.answer || streamedText || config.fallback;
                            const finalSuggestions = Array.isArray(data.suggestions) ? data.suggestions : [];

                            if (!streamAssistantId) {
                                this.appendAssistantMessage(finalAnswer, finalSuggestions, false);
                            } else {
                                this.updateStreamingAssistantMessage(streamAssistantId, finalAnswer, finalSuggestions, true);
                            }

                            this.loading = false;
                            this.streaming = false;
                            this.pruneMessages();
                            this.persistState(true);
                            this.scrollMessages();
                        }
                    };

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) {
                            break;
                        }
                        buffer += decoder.decode(value, { stream: true });
                        const frames = buffer.split(/\r?\n\r?\n/);
                        buffer = frames.pop() || '';
                        frames.forEach(processFrame);
                    }

                    if (buffer.trim()) {
                        processFrame(buffer.trim());
                    }

                    if (receivedDone || streamAssistantId) {
                        return;
                    }
                }
            }

            const response = await fetch(
                config.endpoint,
                this.buildRequestOptions(rawMessage, imageFile, pageContext, 'application/json'),
            );

            const payload = await response.json().catch(() => ({}));

            if (payload.visitor_id) {
                this.syncVisitorId(payload.visitor_id);
            }

            this.appendAssistantMessage(
                payload.answer || config.fallback,
                Array.isArray(payload.suggestions) ? payload.suggestions : [],
                true,
            );
            this.pruneMessages();
            this.persistState();
        } catch (error) {
            if (error?.name === 'AbortError') {
                if (streamAssistantId && streamedText.trim()) {
                    this.updateStreamingAssistantMessage(streamAssistantId, streamedText, [], true);
                }
            } else {
                this.appendAssistantMessage(config.fallback, [], true);
            }
        } finally {
            this.abortController = null;
            this.streaming = false;
            this.loading = false;
            this.pruneMessages();
            this.persistState(true);
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

    createUserMessage(text, imagePreviewUrl = null, imageName = null) {
        return {
            id: this.messageId(),
            role: 'user',
            text: String(text || ''),
            imagePreviewUrl: imagePreviewUrl || null,
            imageName: imageName || null,
            blocks: [],
            actions: [],
        };
    },

    createAssistantMessage(text, suggestions = [], animate = false, restoredActions = null) {
        const normalizedSuggestions = this.normalizeSuggestionList(suggestions);
        const normalizedRestoredActions = this.normalizeActionList(restoredActions);
        const formatted = this.formatAssistantMessage(text, normalizedSuggestions, normalizedRestoredActions);
        const offsetState = this.assignPartOffsets(formatted.blocks);
        const shouldAnimate = animate && !this.prefersReducedMotion() && offsetState.totalChars > 0;

        return {
            id: this.messageId(),
            role: 'assistant',
            text: String(text || ''),
            suggestions: normalizedSuggestions,
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

    formatAssistantMessage(text, suggestions = [], restoredActions = []) {
        const extracted = this.extractActions(String(text || ''), suggestions, restoredActions);
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

            const headingMatch = trimmedLine.match(/^#{1,4}\s+(.+)$/);

            if (headingMatch) {
                flushParagraph();
                flushList();
                const headingText = headingMatch[1].replace(/\*{1,2}/g, '').trim();

                if (headingText) {
                    blocks.push({
                        type: 'paragraph',
                        parts: [{ type: 'strong', text: headingText }],
                    });
                }

                return;
            }

            const listMatch = trimmedLine.match(/^([-*\u2022]|\d+[.)])\s+(.+)$/);

            if (listMatch) {
                flushParagraph();
                const itemText = listMatch[2].trim();

                if (itemText) {
                    listItems.push(itemText);
                }

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
        const pattern = /(\*\*([^*]+)\*\*)|(__([^_]+)__)|(\[([^\]]{1,120})\]\(\s*((?:[^()\n]|\([^()\n]*\))*)\s*\))/g;
        let cursor = 0;
        let match;

        while ((match = pattern.exec(text)) !== null) {
            if (match.index > cursor) {
                parts.push({ type: 'text', text: text.slice(cursor, match.index) });
            }

            if (match[2] || match[4]) {
                parts.push({ type: 'strong', text: match[2] || match[4] });
            } else if (match[6] !== undefined) {
                const label = match[6].replace(/\*{1,2}/g, '').trim();
                const rawHref = String(match[7] || '').trim();
                const url = rawHref ? this.internalLink(rawHref) : null;

                if (label && url) {
                    parts.push({ type: 'link', text: label, url });
                } else if (label) {
                    parts.push({ type: 'strong', text: label });
                }
            }

            cursor = pattern.lastIndex;
        }

        if (cursor < text.length) {
            parts.push({ type: 'text', text: text.slice(cursor) });
        }

        return parts.length ? parts : [{ type: 'text', text }];
    },

    extractActions(text, suggestions = [], restoredActions = []) {
        const actions = [];
        let cleanedText = String(text || '');

        const addAction = (href, fallbackLabel = '', overrides = null, preferOverrides = false) => {
            const url = this.internalLink(href);

            if (!url) {
                return null;
            }

            const canon = this.canonicalActionUrl(url);
            const meta = this.actionMeta(url, fallbackLabel, overrides);
            const existingIndex = actions.findIndex((action) => action.canon === canon);

            if (existingIndex !== -1) {
                if (preferOverrides) {
                    actions[existingIndex] = {
                        canon,
                        label: meta.label,
                        subtitle: meta.subtitle,
                        kind: meta.kind,
                        url,
                    };
                }

                return url;
            }

            actions.push({
                canon,
                label: meta.label,
                subtitle: meta.subtitle,
                kind: meta.kind,
                url,
            });

            return url;
        };

        if (Array.isArray(restoredActions)) {
            restoredActions.forEach((action) => {
                addAction(action?.url, action?.label || '', action, true);
            });
        }

        cleanedText = cleanedText.replace(/\[([^\]]{1,120})\]\(\s*((?:[^()\n]|\([^()\n]*\))*)\s*\)/g, (match, rawLabel, rawHref) => {
            const label = String(rawLabel || '').replace(/\*{1,2}/g, '').trim();

            if (!label) {
                return '';
            }

            const validUrl = addAction(rawHref, label);

            return validUrl ? `[${label}](${validUrl})` : `**${label}**`;
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
            addAction(suggestion?.url, suggestion?.label, suggestion, true);
        });

        const prioritizedActions = this.prioritizeAndLimitActions(
            actions.map(({ label, subtitle, kind, url }) => ({ label, subtitle, kind, url })),
            4,
        );

        if (prioritizedActions.length > 0) {
            cleanedText = cleanedText
                .replace(
                    /(?:Quý khách có thể|Bạn có thể|Có thể)?\s*(?:tìm hiểu thêm|xem thêm chi tiết|xem chi tiết|tham khảo thêm|truy cập)\s*(?:tại đây|ở đây|tại liên kết|qua liên kết|bên dưới)?\s*[:\-–]+\s*(?=\n|$)/gim,
                    '',
                )
                .replace(/[:\-–]\s*[.,]?\s*$/gm, '.');
        }

        return {
            text: cleanedText
                .replace(/[ \t]+\n/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .replace(/[ \t]{2,}/g, ' ')
                .trim(),
            actions: prioritizedActions,
        };
    },

    actionMeta(url, fallbackLabel = '', overrides = null) {
        const value = String(url || '');
        const label = this.actionLabel(value, fallbackLabel);
        const customSubtitle = String(overrides?.subtitle || '').trim();
        const customKind = String(overrides?.kind || '').trim();

        const make = (defaultSubtitle, defaultKind) => ({
            label,
            subtitle: customSubtitle || defaultSubtitle,
            kind: customKind || defaultKind,
            url: value,
        });

        if (value.startsWith('/accessories/') && value.endsWith('/order')) {
            return make('PHỤ KIỆN CHÍNH HÃNG', 'accessory');
        }

        if (value.startsWith('/booking')) {
            const type = new URL(value, window.location.origin).searchParams.get('type');

            if (type === 'test_drive' || type === 'test') {
                return make('Trải nghiệm thực tế', 'booking');
            }

            if (type === 'quote') {
                return make('Tư vấn tài chính', 'quote');
            }

            return make('Showroom BMW', 'booking');
        }

        if (value.startsWith('/compare')) {
            return make('Bảng đối chiếu thông số', 'compare');
        }

        if (value.startsWith('/tim-hieu-them/')) {
            return make('BÀI VIẾT & ƯU ĐÃI', 'article');
        }

        if (value.startsWith('/tim-hieu-them') || value.startsWith('/offers')) {
            return make('Chương trình ưu đãi', 'offer');
        }

        if (value.startsWith('/catalog?type=motorbike')) {
            return make('BMW Motorrad', 'catalog');
        }

        if (value.startsWith('/catalog/')) {
            return make('HỒ SƠ SẢN PHẨM', 'product');
        }

        if (value.startsWith('/accessories')) {
            return make('Phụ kiện chính hãng', 'accessory');
        }

        if (value.startsWith('/catalog')) {
            return make('Danh mục Showroom', 'catalog');
        }

        return make('Showroom BMW', 'link');
    },

    actionLabel(url, fallbackLabel = '') {
        const value = String(url || '');
        const fallback = String(fallbackLabel || '').replace(/\s+/g, ' ').trim();
        const genericLabels = [
            'tại đây',
            'tai day',
            'ở đây',
            'o day',
            'xem chi tiết',
            'xem chi tiet',
            'chi tiết',
            'chi tiet',
            'đọc bài viết',
            'doc bai viet',
            'tìm hiểu thêm',
            'tim hieu them',
            'liên kết',
            'lien ket',
            'mở liên kết',
            'mo lien ket',
        ];
        const isConcrete = (text) => Boolean(text) && !genericLabels.includes(text.toLowerCase());

        if (value.startsWith('/accessories/') && value.endsWith('/order')) {
            const cleanAccessory = fallback.replace(/^Đặt\s+phụ\s+kiện\s+/i, '').trim();

            return isConcrete(cleanAccessory) && cleanAccessory.toLowerCase() !== 'đặt phụ kiện'
                ? cleanAccessory
                : 'Đặt phụ kiện';
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
            return isConcrete(fallback) && fallback.toLowerCase() !== 'mở so sánh xe'
                ? fallback
                : 'So sánh xe';
        }

        if (value.startsWith('/tim-hieu-them/')) {
            return isConcrete(fallback) ? fallback : 'Đọc bài viết';
        }

        if (value.startsWith('/tim-hieu-them')) {
            return 'Xem ưu đãi';
        }

        if (value.startsWith('/catalog?type=motorbike')) {
            return 'Xem BMW Motorrad';
        }

        if (value.startsWith('/catalog/')) {
            const cleanVehicle = fallback.replace(/^Xem\s+/i, '').trim();

            return isConcrete(cleanVehicle) ? cleanVehicle : 'Xem chi tiết';
        }

        if (value.startsWith('/accessories')) {
            return 'Xem phụ kiện';
        }

        if (value.startsWith('/catalog')) {
            return 'Xem danh mục';
        }

        return fallback || 'Mở liên kết';
    },

    canonicalActionUrl(href) {
        const value = String(href || '').trim();

        if (!value || value === '/') {
            return '/';
        }

        try {
            const parsed = new URL(value, window.location.origin);
            const cleanPath = parsed.pathname.replace(/\/+$/, '') || '/';

            return `${cleanPath}${parsed.search}`;
        } catch (error) {
            return value.replace(/\/+$/, '') || '/';
        }
    },

    internalLink(href) {
        const value = String(href || '').trim();
        const lowered = value.toLowerCase();

        if (
            !value
            || value === '#'
            || value.startsWith('#')
            || value.startsWith('//')
            || value.includes('\n')
            || lowered.startsWith('javascript:')
            || lowered.startsWith('data:')
            || lowered.startsWith('vbscript:')
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

    actionPriority(action) {
        const url = String(action?.url || '');

        if (url.startsWith('/catalog/') || (url.startsWith('/accessories/') && url.endsWith('/order'))) {
            return 1;
        }

        if (url.startsWith('/compare')) {
            return 2;
        }

        if (url.startsWith('/booking') && url.includes('type=test_drive')) {
            return 3;
        }

        if (url.startsWith('/booking') && url.includes('type=quote')) {
            return 4;
        }

        if (url.startsWith('/tim-hieu-them/')) {
            return 5;
        }

        if (url.startsWith('/offers') || url.startsWith('/tim-hieu-them')) {
            return 6;
        }

        if (url.startsWith('/booking')) {
            return 7;
        }

        return 8;
    },

    prioritizeAndLimitActions(actions = [], limit = 4) {
        return actions
            .map((action, index) => ({ action, index, priority: this.actionPriority(action) }))
            .sort((a, b) => (a.priority - b.priority) || (a.index - b.index))
            .slice(0, limit)
            .map(({ action }) => action);
    },

    normalizeSuggestionList(items = []) {
        if (!Array.isArray(items)) {
            return [];
        }

        const unique = [];

        items.forEach((item) => {
            const url = this.internalLink(item?.url);
            const canon = url ? this.canonicalActionUrl(url) : null;

            if (!url || unique.some((entry) => this.canonicalActionUrl(entry.url) === canon)) {
                return;
            }

            const normalized = {
                label: String(item?.label || this.actionLabel(url)).trim(),
                url,
            };

            if (item?.subtitle) {
                normalized.subtitle = String(item.subtitle).trim();
            }

            if (item?.kind) {
                normalized.kind = String(item.kind).trim();
            }

            unique.push(normalized);
        });

        return unique.slice(0, 6);
    },

    normalizeActionList(items = []) {
        if (!Array.isArray(items)) {
            return [];
        }

        const unique = [];

        items.forEach((item) => {
            const url = this.internalLink(item?.url);
            const canon = url ? this.canonicalActionUrl(url) : null;

            if (!url || unique.some((entry) => this.canonicalActionUrl(entry.url) === canon)) {
                return;
            }

            const meta = this.actionMeta(url, item?.label || '', item);
            unique.push({
                label: meta.label,
                subtitle: String(item?.subtitle || meta.subtitle || 'BMW Showroom').trim(),
                kind: String(item?.kind || meta.kind || 'link').trim(),
                url,
            });
        });

        return this.prioritizeAndLimitActions(unique, 4);
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
                    .map((message) => {
                        if (message.role === 'user') {
                            return this.createUserMessage(message.text, null, message.imageName || null);
                        }

                        const savedSuggestions = this.normalizeSuggestionList(message.suggestions || []);
                        const savedActions = this.normalizeActionList(message.actions || []);

                        return this.createAssistantMessage(
                            message.text,
                            savedSuggestions,
                            false,
                            savedActions,
                        );
                    });
            }
        } catch (error) {
            this.panelOpen = false;
            this.sideTabVisible = false;
            this.messages = [];
        }
    },

    persistState(broadcast = true) {
        try {
            localStorage.setItem(this.stateKey, JSON.stringify({
                mode: this.sideTabVisible ? 'hidden' : (this.panelOpen ? 'panel' : 'launcher'),
                messages: this.messages.slice(-this.maxStoredMessages).map((message) => {
                    if (message.role === 'user') {
                        return {
                            role: 'user',
                            text: this.safeStoredText(message.text),
                            imageName: message.imageName ? String(message.imageName).slice(0, 80) : null,
                        };
                    }

                    return {
                        role: 'assistant',
                        text: this.safeStoredText(message.text),
                        suggestions: this.normalizeSuggestionList(message.suggestions || []),
                        actions: this.normalizeActionList(message.actions || []),
                    };
                }),
            }));

            if (broadcast) {
                this.broadcastSync();
            }
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

Livewire.start();
