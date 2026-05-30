const token = document.querySelector('meta[name="csrf-token"]')?.content;

if (window.cyberLesson) {
    const log = document.querySelector('#chat-log');
    const form = document.querySelector('#chat-form');
    const input = document.querySelector('#chat-message');
    const quickReplies = document.querySelector('#quick-replies');
    let activeAgent = window.cyberLesson.agent;
    let history = [];
    let lastUserLanguage = 'en';

    const detectLanguage = (message) => /[\u0600-\u06FF]/.test(message) ? 'ar' : 'en';

    const escapeHtml = (value) => value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    const formatInline = (value) => value
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/`([^`]+)`/g, '<code class="rounded bg-slate-950/80 px-1 py-0.5 text-cyan-100">$1</code>');

    const resourceCard = (url, title = '') => {
        let domain = url;

        try {
            domain = new URL(url).hostname.replace(/^www\./, '');
        } catch {
            domain = url.replace(/^https?:\/\//, '').split('/')[0];
        }

        const label = title.trim().replace(/[:\-–—]+$/, '').trim() || domain;

        return `
            <a href="${url}" target="_blank" rel="noopener noreferrer" class="my-2 block max-w-full rounded-lg border border-cyan-300/20 bg-slate-950/70 p-3 text-left no-underline transition hover:border-cyan-300 hover:bg-slate-900">
                <span class="block truncate font-semibold text-cyan-100">${formatInline(label)}</span>
                <span class="mt-1 block truncate text-xs text-slate-400">${domain}</span>
            </a>
        `;
    };

    const formatRichLine = (value) => {
        const parts = [];
        const pattern = /https?:\/\/[^\s<]+/g;
        let cursor = 0;
        let match;

        while ((match = pattern.exec(value)) !== null) {
            const rawUrl = match[0];
            const cleanUrl = rawUrl.replace(/[),.;:!?]+$/, '');
            const trailing = rawUrl.slice(cleanUrl.length);
            const before = value.slice(cursor, match.index);
            const beforeText = before.trim().replace(/[:\-–—]+$/, '').trim();

            if (beforeText) {
                parts.push(`<p class="my-1">${formatInline(beforeText)}</p>`);
            }

            parts.push(resourceCard(cleanUrl, beforeText));

            if (trailing.trim()) {
                parts.push(`<span>${formatInline(trailing)}</span>`);
            }

            cursor = match.index + rawUrl.length;
        }

        const rest = value.slice(cursor).trim();
        if (rest) {
            parts.push(`<p class="my-1">${formatInline(rest)}</p>`);
        }

        return parts.length ? parts.join('') : formatInline(value);
    };

    const formatBotMessage = (message) => {
        const lines = escapeHtml(message).split('\n');
        const html = [];
        let listType = null;
        let inCode = false;
        let codeLines = [];

        const closeList = () => {
            if (!listType) return;
            html.push(`</${listType}>`);
            listType = null;
        };

        const closeCode = () => {
            if (!inCode) return;
            html.push(`<pre class="my-3 max-w-full overflow-x-auto rounded-lg bg-slate-950 p-3 text-xs text-cyan-100"><code>${codeLines.join('\n')}</code></pre>`);
            inCode = false;
            codeLines = [];
        };

        lines.forEach((line) => {
            const trimmed = line.trim();

            if (trimmed.startsWith('```')) {
                if (inCode) {
                    closeCode();
                } else {
                    closeList();
                    inCode = true;
                }
                return;
            }

            if (inCode) {
                codeLines.push(line);
                return;
            }

            if (!trimmed) {
                closeList();
                html.push('<div class="h-2"></div>');
                return;
            }

            const heading = trimmed.match(/^(#{1,3})\s+(.+)$/);
            if (heading) {
                closeList();
                html.push(`<p class="mt-3 font-semibold text-cyan-100">${formatInline(heading[2])}</p>`);
                return;
            }

            const unordered = trimmed.match(/^[-*•]\s+(.+)$/);
            if (unordered) {
                if (listType !== 'ul') {
                    closeList();
                    html.push('<ul class="my-2 list-disc space-y-1 ps-5">');
                    listType = 'ul';
                }
                html.push(`<li>${formatRichLine(unordered[1])}</li>`);
                return;
            }

            const ordered = trimmed.match(/^\d+[.)]\s+(.+)$/);
            if (ordered) {
                if (listType !== 'ol') {
                    closeList();
                    html.push('<ol class="my-2 list-decimal space-y-1 ps-5">');
                    listType = 'ol';
                }
                html.push(`<li>${formatRichLine(ordered[1])}</li>`);
                return;
            }

            closeList();
            html.push(`<div class="my-1">${formatRichLine(trimmed)}</div>`);
        });

        closeCode();
        closeList();

        return html.join('');
    };

    document.querySelectorAll('.agent-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            activeAgent = tab.dataset.agent;
            document.querySelectorAll('.agent-tab').forEach((item) => {
                item.className = 'agent-tab rounded-lg bg-slate-950/70 px-2 py-2 text-xs';
            });
            tab.className = 'agent-tab rounded-lg bg-cyan-400 px-2 py-2 text-xs font-semibold text-slate-950';
        });
    });

    const addMessage = (message, own = false) => {
        const bubble = document.createElement('div');
        const language = detectLanguage(message);

        bubble.className = own
            ? 'ml-8 rounded-lg bg-cyan-400 p-3 leading-relaxed text-slate-950'
            : 'mr-8 rounded-lg bg-white/10 p-3 leading-relaxed text-cyan-50';
        bubble.classList.add(language === 'ar' ? 'text-right' : 'text-left');
        bubble.dir = language === 'ar' ? 'rtl' : 'ltr';
        bubble.style.overflowWrap = 'anywhere';
        bubble.style.wordBreak = 'break-word';

        if (own) {
            bubble.textContent = message;
        } else {
            bubble.innerHTML = formatBotMessage(message);
        }
        log.appendChild(bubble);
        log.scrollTop = log.scrollHeight;
    };

    const shouldShowLevelPicker = (message) => {
        const text = message.toLowerCase();

        return (
            text.includes('beginner')
            && text.includes('intermediate')
            && text.includes('expert')
            && (
                text.includes('before i build your plan')
                || text.includes('choose your current level')
                || text.includes('which level are you')
                || text.includes('اختر مستواك')
                || text.includes('ما هو مستواك')
                || text.includes('قبل ما أبني')
                || text.includes('قبل أن أبني')
            )
        );
    };

    const closeLevelPicker = () => {
        document.querySelector('#level-picker-modal')?.remove();
    };

    const showLevelPicker = () => {
        closeLevelPicker();

        const modal = document.createElement('div');
        modal.id = 'level-picker-modal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4';
        modal.innerHTML = `
            <div class="w-full max-w-md rounded-lg border border-white/10 bg-slate-900 p-5 text-white shadow-2xl">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase text-cyan-200">Cyber Mentor</p>
                        <h3 class="mt-1 text-lg font-semibold">Choose your level</h3>
                    </div>
                    <button type="button" data-level-close class="rounded-md border border-white/10 px-2 py-1 text-sm text-slate-300 hover:bg-white/10">X</button>
                </div>
                <div class="space-y-2">
                    <button type="button" data-level="Beginner" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-left hover:border-cyan-300">
                        <span class="block font-semibold text-cyan-100">Beginner</span>
                        <span class="text-sm text-slate-300">New to cybersecurity or still learning the basics.</span>
                    </button>
                    <button type="button" data-level="Intermediate" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-left hover:border-cyan-300">
                        <span class="block font-semibold text-cyan-100">Intermediate</span>
                        <span class="text-sm text-slate-300">You know networking, Linux, or basic security concepts.</span>
                    </button>
                    <button type="button" data-level="Expert" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-left hover:border-cyan-300">
                        <span class="block font-semibold text-cyan-100">Expert</span>
                        <span class="text-sm text-slate-300">You have hands-on experience and want advanced specialization.</span>
                    </button>
                </div>
            </div>
        `;

        modal.querySelector('[data-level-close]').addEventListener('click', closeLevelPicker);
        modal.querySelectorAll('[data-level]').forEach((button) => {
            button.addEventListener('click', () => {
                const level = button.dataset.level;
                closeLevelPicker();
                const message = lastUserLanguage === 'ar'
                    ? `أنا ${level}. من فضلك ابنِ لي خطة لتعلم الأمن السيبراني مناسبة لمستواي.`
                    : `I am ${level}. Please build a cybersecurity learning plan for my level.`;

                sendMessage(message);
            });
        });

        document.body.appendChild(modal);
    };

    const sendMessage = async (message) => {
        lastUserLanguage = detectLanguage(message);
        quickReplies?.classList.add('hidden');
        addMessage(message, true);
        addMessage('Thinking...');

        const response = await fetch(window.cyberLesson.chatUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json'},
            body: JSON.stringify({
                message,
                history,
                lesson_id: window.cyberLesson.lessonId,
                agent_type: activeAgent,
                platform_version: window.cyberLesson.version,
            }),
        });
        const data = await response.json();
        log.lastElementChild.remove();
        const reply = data.message || 'The AI service is currently unavailable.';
        addMessage(reply);
        history = [
            ...history,
            {role: 'user', content: message},
            {role: 'assistant', content: reply},
        ].slice(-20);

        if (shouldShowLevelPicker(reply)) {
            showLevelPicker();
        }
    };

    window.addEventListener('load', () => {
        if (!log || log.children.length > 0) return;

        addMessage(
            'Hello!\n**I am Cyber Mentor**, your specialized cybersecurity study assistant.\n\n' +
            'I can help you build a personalized study plan, explain security concepts, and suggest practical exercises.\n' +
            'Tell me: what is your current experience level, and what is your cybersecurity goal?'
        );
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;
        input.value = '';
        sendMessage(message);
    });

    quickReplies?.querySelectorAll('.quick-reply').forEach((button) => {
        button.addEventListener('click', () => {
            const message = lastUserLanguage === 'ar' ? button.dataset.messageAr : button.dataset.messageEn;
            sendMessage(message);
        });
    });
}

if (window.quizSeconds) {
    let seconds = window.quizSeconds;
    const timer = document.querySelector('#quiz-timer');
    setInterval(() => {
        seconds = Math.max(0, seconds - 1);
        const min = String(Math.floor(seconds / 60)).padStart(2, '0');
        const sec = String(seconds % 60).padStart(2, '0');
        if (timer) timer.textContent = `${min}:${sec}`;
    }, 1000);
}
