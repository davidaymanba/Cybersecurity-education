const token = document.querySelector('meta[name="csrf-token"]')?.content;

if (window.cyberLesson) {
    const log = document.querySelector('#chat-log');
    const form = document.querySelector('#chat-form');
    const input = document.querySelector('#chat-message');
    const quickReplies = document.querySelector('#quick-replies');
    let activeAgent = window.cyberLesson.agent;
    const studentName = String(window.cyberLesson.userName || '').trim();
    const studentLevel = String(window.cyberLesson.userLevel || '').trim();
    const helloLine = studentName ? `Hello ${studentName}!` : 'Hello!';
    const levelLine = studentLevel ? `\nYour saved level: **${studentLevel}**.` : '';
    const agentProfiles = {
        single_tutor: {
            welcome: `${helloLine}\n**I am Cyber Mentor**, your specialized cybersecurity study assistant.${levelLine}\n\nI can help you build a personalized study plan, explain security concepts, recommend approved lesson videos, and suggest safe practice.\nTell me what you want to work on.`,
            placeholder: 'Ask Cyber Mentor...',
            quickReplies: [
                {label: 'Create plan', en: 'I need a cybersecurity learning plan.', ar: 'أحتاج خطة لتعلم الأمن السيبراني.'},
                {label: 'Explain topic', en: 'Explain a cybersecurity topic in simple terms.', ar: 'اشرح لي موضوعاً في الأمن السيبراني بطريقة بسيطة.'},
                {label: 'Give resources', en: 'Share useful cybersecurity learning resources with links.', ar: 'اعطني مصادر مفيدة لتعلم الأمن السيبراني مع روابط.'},
                {label: 'Quiz me', en: 'Quiz me with safe beginner cybersecurity questions.', ar: 'اختبرني بأسئلة آمنة للمبتدئين في الأمن السيبراني.'},
            ],
        },
        navigation: {
            welcome: `${helloLine}\n**Guide** is active.${levelLine}\n\nI only help with study plans, roadmaps, schedules, goals, and choosing the next lesson.\nTell me your goal or ask for a plan.`,
            placeholder: 'Ask for a plan, roadmap, or next lesson...',
            quickReplies: [
                {label: 'Create plan', en: 'I need a cybersecurity learning plan.', ar: 'أحتاج خطة لتعلم الأمن السيبراني.'},
                {label: 'Pick next lesson', en: 'What should I learn next in this course?', ar: 'ما الدرس التالي الذي يجب أن أتعلمه في هذا المسار؟'},
                {label: 'Weekly schedule', en: 'Build me a weekly cybersecurity study schedule.', ar: 'ابنِ لي جدولاً أسبوعياً لتعلم الأمن السيبراني.'},
                {label: 'Beginner roadmap', en: 'Create a beginner cybersecurity roadmap.', ar: 'اعمل لي خارطة طريق للمبتدئين في الأمن السيبراني.'},
            ],
        },
        explanation: {
            welcome: `${helloLine}\n**Tutor** is active.${levelLine}\n\nI only explain cybersecurity concepts, definitions, comparisons, and safe defensive examples.\nAsk me about a concept from the lesson.`,
            placeholder: 'Ask about a cybersecurity concept...',
            quickReplies: [
                {label: 'Explain topic', en: 'Explain this lesson topic in simple terms.', ar: 'اشرح موضوع هذا الدرس بطريقة بسيطة.'},
                {label: 'Give example', en: 'Give me a safe defensive example for this concept.', ar: 'اعطني مثالاً دفاعياً آمناً لهذا المفهوم.'},
                {label: 'Compare concepts', en: 'Compare two cybersecurity concepts for me.', ar: 'قارن لي بين مفهومين في الأمن السيبراني.'},
                {label: 'Check understanding', en: 'Check my understanding of this concept.', ar: 'اختبر فهمي لهذا المفهوم.'},
            ],
        },
        video: {
            welcome: `${helloLine}\n**Video** is active.${levelLine}\n\nI only recommend approved videos embedded in this lesson.\nAsk what to watch or what to focus on while watching.`,
            placeholder: 'Ask for approved lesson videos...',
            quickReplies: [
                {label: 'Recommend videos', en: 'Recommend the approved videos for this lesson.', ar: 'رشح لي الفيديوهات المعتمدة لهذا الدرس.'},
                {label: 'What to watch', en: 'Which approved video should I watch first?', ar: 'أي فيديو معتمد يجب أن أشاهده أولاً؟'},
                {label: 'Focus notes', en: 'What should I focus on while watching the lesson video?', ar: 'على ماذا أركز أثناء مشاهدة فيديو الدرس؟'},
                {label: 'After video', en: 'What should I do after watching the approved video?', ar: 'ماذا أفعل بعد مشاهدة الفيديو المعتمد؟'},
            ],
        },
    };
    const histories = {
        single_tutor: [],
        navigation: [],
        explanation: [],
        video: [],
    };
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

    const currentProfile = () => agentProfiles[activeAgent] || agentProfiles.single_tutor;

    const setTabStyles = () => {
        document.querySelectorAll('.agent-tab').forEach((item) => {
            item.className = item.dataset.agent === activeAgent
                ? 'agent-tab rounded-lg bg-cyan-400 px-2 py-2 text-xs font-semibold text-slate-950'
                : 'agent-tab rounded-lg bg-slate-950/70 px-2 py-2 text-xs';
        });
    };

    const renderQuickReplies = () => {
        if (!quickReplies) return;

        quickReplies.innerHTML = '';
        currentProfile().quickReplies.forEach((reply) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'quick-reply rounded-lg border border-white/10 bg-slate-950/70 px-3 py-2 text-left text-xs text-cyan-50 hover:border-cyan-300';
            button.textContent = reply.label;
            button.addEventListener('click', () => {
                sendMessage(lastUserLanguage === 'ar' ? reply.ar : reply.en);
            });
            quickReplies.appendChild(button);
        });

        quickReplies.classList.remove('hidden');
    };

    document.querySelectorAll('.agent-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            activeAgent = tab.dataset.agent;
            setTabStyles();
            renderActiveAgent();
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

        return bubble;
    };

    const renderActiveAgent = () => {
        if (!log) return;

        log.innerHTML = '';
        if (input) {
            input.placeholder = currentProfile().placeholder;
        }
        addMessage(currentProfile().welcome);
        histories[activeAgent].forEach((item) => {
            addMessage(item.content, item.role === 'user');
        });
        renderQuickReplies();
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
        const agentAtSend = activeAgent;
        const agentHistory = histories[agentAtSend] || [];
        lastUserLanguage = detectLanguage(message);
        quickReplies?.classList.add('hidden');
        addMessage(message, true);
        const thinkingBubble = addMessage('Thinking...');

        let reply = lastUserLanguage === 'ar'
            ? 'تعذر الاتصال بخدمة الذكاء الاصطناعي حالياً. حاول مرة أخرى بعد لحظات.'
            : 'The AI service is currently unavailable. Please try again in a moment.';

        try {
            const response = await fetch(window.cyberLesson.chatUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json'},
                body: JSON.stringify({
                    message,
                    history: agentHistory,
                    lesson_id: window.cyberLesson.lessonId,
                    agent_type: agentAtSend,
                    platform_version: window.cyberLesson.version,
                }),
            });
            const data = await response.json();
            reply = data.message || reply;
        } catch {
            reply = lastUserLanguage === 'ar'
                ? 'تعذر الاتصال بخدمة الذكاء الاصطناعي حالياً. حاول مرة أخرى بعد لحظات.'
                : 'The AI service is currently unavailable. Please try again in a moment.';
        }

        thinkingBubble.remove();
        histories[agentAtSend] = [
            ...agentHistory,
            {role: 'user', content: message},
            {role: 'assistant', content: reply},
        ].slice(-20);

        if (activeAgent === agentAtSend) {
            addMessage(reply);
            renderQuickReplies();
        }

        if (activeAgent === agentAtSend && shouldShowLevelPicker(reply)) {
            showLevelPicker();
        }
    };

    window.addEventListener('load', () => {
        setTabStyles();
        renderActiveAgent();
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;
        input.value = '';
        sendMessage(message);
    });

    setTabStyles();
    renderActiveAgent();
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
