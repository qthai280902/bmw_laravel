import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.aiAssistantWidget = (config) => ({
    panelOpen: true,
    greetingVisible: true,
    readyText: config.ready,
    prompts: config.prompts || [],
    responseSuggestions: [],
    messages: [
        {
            role: 'assistant',
            text: config.greeting,
        },
    ],
    input: '',
    loading: false,
    position: null,
    dragState: null,
    dragMoved: false,

    get positionStyle() {
        if (!this.position) {
            return 'right: 1rem; bottom: 1rem;';
        }

        return `left: ${this.position.x}px; top: ${this.position.y}px;`;
    },

    init() {
        this.restorePosition();
        if (window.innerWidth < 640) {
            this.panelOpen = false;
            this.greetingVisible = true;
        }

        window.addEventListener('resize', () => this.clampPosition());
    },

    openPanel() {
        if (this.dragMoved) {
            this.dragMoved = false;
            return;
        }

        this.panelOpen = true;
        this.greetingVisible = false;
        this.$nextTick(() => this.clampPosition());
    },

    minimize() {
        this.panelOpen = false;
        this.greetingVisible = true;
        this.$nextTick(() => this.clampPosition());
    },

    sendSuggestion(prompt) {
        this.input = prompt;
        this.send();
    },

    async send() {
        const message = this.input.trim();

        if (!message || this.loading) {
            return;
        }

        this.messages.push({ role: 'user', text: message });
        this.input = '';
        this.loading = true;
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

            this.messages.push({
                role: 'assistant',
                text: payload.answer || config.fallback,
            });
            this.responseSuggestions = Array.isArray(payload.suggestions) ? payload.suggestions : [];
        } catch (error) {
            this.messages.push({
                role: 'assistant',
                text: config.fallback,
            });
        } finally {
            this.loading = false;
            this.scrollMessages();
            this.$nextTick(() => this.clampPosition());
        }
    },

    scrollMessages() {
        this.$nextTick(() => {
            if (this.$refs.messages) {
                this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
            }
        });
    },

    startDrag(event) {
        if (event.target.closest('[data-ai-no-drag]')) {
            return;
        }

        if (event.button !== undefined && event.button !== 0) {
            return;
        }

        const rect = this.$root.getBoundingClientRect();
        this.position = {
            x: rect.left,
            y: rect.top,
        };
        this.dragMoved = false;
        this.dragState = {
            startX: event.clientX,
            startY: event.clientY,
            originX: rect.left,
            originY: rect.top,
            move: (moveEvent) => this.moveDrag(moveEvent),
            end: () => this.endDrag(),
        };

        window.addEventListener('pointermove', this.dragState.move);
        window.addEventListener('pointerup', this.dragState.end, { once: true });
        event.preventDefault();
    },

    moveDrag(event) {
        if (!this.dragState) {
            return;
        }

        const deltaX = event.clientX - this.dragState.startX;
        const deltaY = event.clientY - this.dragState.startY;

        if (Math.abs(deltaX) > 4 || Math.abs(deltaY) > 4) {
            this.dragMoved = true;
        }

        this.position = this.clampedPosition(
            this.dragState.originX + deltaX,
            this.dragState.originY + deltaY,
        );
    },

    endDrag() {
        if (!this.dragState) {
            return;
        }

        window.removeEventListener('pointermove', this.dragState.move);
        this.dragState = null;
        this.savePosition();
    },

    restorePosition() {
        try {
            const saved = JSON.parse(localStorage.getItem('bmw_ai_assistant_position') || 'null');
            if (saved && Number.isFinite(saved.x) && Number.isFinite(saved.y)) {
                this.position = this.clampedPosition(saved.x, saved.y);
            }
        } catch (error) {
            this.position = null;
        }
    },

    savePosition() {
        if (!this.position) {
            return;
        }

        try {
            localStorage.setItem('bmw_ai_assistant_position', JSON.stringify(this.position));
        } catch (error) {
            void error;
        }
    },

    clampPosition() {
        if (!this.position) {
            return;
        }

        this.position = this.clampedPosition(this.position.x, this.position.y);
        this.savePosition();
    },

    clampedPosition(x, y) {
        const margin = 12;
        const width = this.$root?.offsetWidth || 360;
        const height = this.$root?.offsetHeight || 120;
        const maxX = Math.max(margin, window.innerWidth - width - margin);
        const maxY = Math.max(margin, window.innerHeight - height - margin);

        return {
            x: Math.min(Math.max(margin, x), maxX),
            y: Math.min(Math.max(margin, y), maxY),
        };
    },
});

Alpine.start();
