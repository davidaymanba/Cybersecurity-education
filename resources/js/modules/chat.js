/**
 * Lesson chat widget.
 *
 * Behaviour that must not change (the server depends on it):
 * - SSE streaming from cyberLesson.streamUrl, JSON fallback to cyberLesson.chatUrl
 * - one history per agent, sent back exactly as received (the server verifies assistant turns)
 * - quick replies follow the language the student last typed in (starting with the page locale)
 * - redirect hints when an agent says another tab fits better, and the level picker
 */
import { gsap, prefersReducedMotion } from '../ui/motion';

export function initChat() {
    const config = window.cyberLesson;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const log = document.querySelector('#chat-log');
    const form = document.querySelector('#chat-form');
    const input = document.querySelector('#chat-message');
    const quickReplies = document.querySelector('#quick-replies');
    const tabs = [...document.querySelectorAll('.agent-tab')];
    const indicator = document.querySelector('#agent-indicator');
    const tabList = document.querySelector('#agent-tabs');
    if (!log || !form || !input) return;

    let activeAgent = config.agent;
    const studentName = String(config.userName || '').trim();
    const studentLevel = String(config.userLevel || '').trim();
    // The chat starts in the page's language; after that replies follow what the student types.
    const uiArabic = config.locale === 'ar';
    const t = (en, ar) => (uiArabic ? ar : en);
    const helloLine = studentName ? t(`Hello ${studentName}!`, `أهلاً ${studentName}!`) : t('Hello!', 'أهلاً!');
    const levelLine = studentLevel ? t(`\nYour saved level: **${studentLevel}**.`, `\nمستواك المحفوظ: **${studentLevel}**.`) : '';
    const reply = (en, ar, labelEn, labelAr) => ({ label: t(labelEn, labelAr), en, ar });

    const agentProfiles = {
        single_tutor: {
            welcome: t(
                `${helloLine}\n**I am Cyber Mentor**, your specialized cybersecurity study assistant.${levelLine}\n\nI can help you build a personalized study plan, explain security concepts, recommend approved lesson videos, and suggest safe practice.\nTell me what you want to work on.`,
                `${helloLine}\n**أنا Cyber Mentor**، مساعدك المتخصص في دراسة الأمن السيبراني.${levelLine}\n\nأقدر أبني لك خطة مذاكرة تناسبك، وأشرح لك المفاهيم الأمنية، وأرشّح لك فيديوهات الدرس المعتمدة، وأقترح عليك تدريبات آمنة.\nوش تبغى نشتغل عليه؟`,
            ),
            placeholder: t('Ask Cyber Mentor...', 'اسأل Cyber Mentor...'),
            quickReplies: [
                reply('I need a cybersecurity learning plan.', 'أحتاج خطة لتعلم الأمن السيبراني.', 'Create plan', 'خطة مذاكرة'),
                reply('Explain a cybersecurity topic in simple terms.', 'اشرح لي موضوعاً في الأمن السيبراني بطريقة بسيطة.', 'Explain topic', 'اشرح موضوع'),
                reply('Share useful cybersecurity learning resources with links.', 'اعطني مصادر مفيدة لتعلم الأمن السيبراني مع روابط.', 'Give resources', 'مصادر تعلم'),
                reply('Quiz me with safe beginner cybersecurity questions.', 'اختبرني بأسئلة آمنة للمبتدئين في الأمن السيبراني.', 'Quiz me', 'اختبرني'),
            ],
        },
        navigation: {
            welcome: t(
                `${helloLine}\n**Guide** is active.${levelLine}\n\nI only help with study plans, roadmaps, schedules, goals, and choosing the next lesson.\nTell me your goal or ask for a plan.`,
                `${helloLine}\n**المرشد** مفعّل.${levelLine}\n\nأساعدك في خطط المذاكرة، والمسارات، والجداول، والأهداف، واختيار الدرس الجاي.\nقل لي هدفك أو اطلب خطة.`,
            ),
            placeholder: t('Ask for a plan, roadmap, or next lesson...', 'اطلب خطة أو مسار أو الدرس الجاي...'),
            quickReplies: [
                reply('I need a cybersecurity learning plan.', 'أحتاج خطة لتعلم الأمن السيبراني.', 'Create plan', 'خطة مذاكرة'),
                reply('What should I learn next in this course?', 'وش الدرس اللي أدرسه بعد هذا؟', 'Pick next lesson', 'الدرس الجاي'),
                reply('Build me a weekly cybersecurity study schedule.', 'ابنِ لي جدولاً أسبوعياً لتعلم الأمن السيبراني.', 'Weekly schedule', 'جدول أسبوعي'),
                reply('Create a beginner cybersecurity roadmap.', 'اعمل لي خارطة طريق للمبتدئين في الأمن السيبراني.', 'Beginner roadmap', 'مسار للمبتدئين'),
            ],
        },
        explanation: {
            welcome: t(
                `${helloLine}\n**Tutor** is active.${levelLine}\n\nI only explain cybersecurity concepts, definitions, comparisons, and safe defensive examples.\nAsk me about a concept from the lesson.`,
                `${helloLine}\n**الشارح** مفعّل.${levelLine}\n\nأشرح لك مفاهيم الأمن السيبراني والتعريفات والمقارنات مع أمثلة دفاعية آمنة.\nاسألني عن أي مفهوم في الدرس.`,
            ),
            placeholder: t('Ask about a cybersecurity concept...', 'اسأل عن مفهوم في الأمن السيبراني...'),
            quickReplies: [
                reply('Explain this lesson topic in simple terms.', 'اشرح موضوع هذا الدرس بطريقة بسيطة.', 'Explain topic', 'اشرح الدرس'),
                reply('Give me a safe defensive example for this concept.', 'اعطني مثالاً دفاعياً آمناً لهذا المفهوم.', 'Give example', 'أعطني مثال'),
                reply('Compare two cybersecurity concepts for me.', 'قارن لي بين مفهومين في الأمن السيبراني.', 'Compare concepts', 'قارن مفهومين'),
                reply('Check my understanding of this concept.', 'اختبر فهمي لهذا المفهوم.', 'Check understanding', 'اختبر فهمي'),
            ],
        },
        video: {
            welcome: t(
                `${helloLine}\n**Video** is active.${levelLine}\n\nI only recommend approved videos embedded in this lesson.\nAsk what to watch or what to focus on while watching.`,
                `${helloLine}\n**الفيديو** مفعّل.${levelLine}\n\nأرشّح لك الفيديوهات المعتمدة في هذا الدرس فقط.\nاسألني وش تشاهد أو على وش تركّز وأنت تشاهد.`,
            ),
            placeholder: t('Ask for approved lesson videos...', 'اطلب فيديوهات الدرس المعتمدة...'),
            quickReplies: [
                reply('Recommend the approved videos for this lesson.', 'رشح لي الفيديوهات المعتمدة لهذا الدرس.', 'Recommend videos', 'رشّح فيديوهات'),
                reply('Which approved video should I watch first?', 'أي فيديو معتمد أشاهده أول؟', 'What to watch', 'وش أشاهد أول؟'),
                reply('What should I focus on while watching the lesson video?', 'على وش أركز وأنا أشاهد فيديو الدرس؟', 'Focus notes', 'على وش أركز؟'),
                reply('What should I do after watching the approved video?', 'وش أسوي بعد ما أشاهد الفيديو المعتمد؟', 'After video', 'بعد الفيديو'),
            ],
        },
    };
    const histories = { single_tutor: [], navigation: [], explanation: [], video: [] };
    const agentLabels = {
        single_tutor: 'Cyber Mentor',
        navigation: t('Guide', 'المرشد'),
        explanation: t('Tutor', 'الشارح'),
        video: t('Video', 'الفيديو'),
    };
    let lastUserLanguage = uiArabic ? 'ar' : 'en';
    const animate = !prefersReducedMotion();

    const detectLanguage = (message) => (/[؀-ۿ]/.test(message) ? 'ar' : 'en');

    const escapeHtml = (value) => value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const formatInline = (value) => value
        .replace(/\*\*(.*?)\*\*/g, '<strong class="font-semibold text-white">$1</strong>')
        .replace(/`([^`]+)`/g, '<code class="rounded bg-void/80 px-1 py-0.5 font-mono text-[0.85em] text-cyan-100" dir="ltr">$1</code>');

    const resourceCard = (url, title = '') => {
        let domain = url;
        try {
            domain = new URL(url).hostname.replace(/^www\./, '');
        } catch {
            domain = url.replace(/^https?:\/\//, '').split('/')[0];
        }
        const label = title.trim().replace(/[:\-–—]+$/, '').trim() || domain;

        return `
            <a href="${url}" target="_blank" rel="noopener noreferrer" class="my-2 flex max-w-full items-center gap-3 rounded-xl bg-void/70 p-3 no-underline ring-1 ring-signal/20 transition hover:ring-signal/60">
                <span class="grid h-8 w-8 flex-none place-items-center rounded-lg bg-signal/10 text-signal"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg></span>
                <span class="min-w-0"><span class="block truncate font-semibold text-cyan-100">${formatInline(label)}</span><span class="mt-0.5 block truncate text-xs text-slate-400" dir="ltr">${domain}</span></span>
            </a>`;
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

            if (beforeText) parts.push(`<p class="my-1">${formatInline(beforeText)}</p>`);
            parts.push(resourceCard(cleanUrl, beforeText));
            if (trailing.trim()) parts.push(`<span>${formatInline(trailing)}</span>`);
            cursor = match.index + rawUrl.length;
        }

        const rest = value.slice(cursor).trim();
        if (rest) parts.push(`<p class="my-1">${formatInline(rest)}</p>`);

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
            html.push(`<pre class="my-3 max-w-full overflow-x-auto rounded-lg bg-void p-3 font-mono text-xs text-cyan-100 ring-1 ring-white/10" dir="ltr"><code>${codeLines.join('\n')}</code></pre>`);
            inCode = false;
            codeLines = [];
        };

        lines.forEach((line) => {
            const trimmed = line.trim();

            if (trimmed.startsWith('```')) {
                if (inCode) closeCode();
                else { closeList(); inCode = true; }
                return;
            }
            if (inCode) { codeLines.push(line); return; }
            if (!trimmed) { closeList(); html.push('<div class="h-2"></div>'); return; }

            const heading = trimmed.match(/^(#{1,3})\s+(.+)$/);
            if (heading) {
                closeList();
                html.push(`<p class="mt-3 font-semibold text-cyan-100">${formatInline(heading[2])}</p>`);
                return;
            }
            const unordered = trimmed.match(/^[-*•]\s+(.+)$/);
            if (unordered) {
                if (listType !== 'ul') { closeList(); html.push('<ul class="my-2 list-disc space-y-1 ps-5 marker:text-signal">'); listType = 'ul'; }
                html.push(`<li>${formatRichLine(unordered[1])}</li>`);
                return;
            }
            const ordered = trimmed.match(/^\d+[.)]\s+(.+)$/);
            if (ordered) {
                if (listType !== 'ol') { closeList(); html.push('<ol class="my-2 list-decimal space-y-1 ps-5 marker:text-signal">'); listType = 'ol'; }
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
    const scrollToEnd = () => { log.scrollTop = log.scrollHeight; };
    const enter = (el) => {
        if (animate) gsap.from(el, { opacity: 0, y: 10, duration: 0.45, ease: 'expo.out', clearProps: 'all' });
    };

    // ---------------------------------------------------------------- tabs
    const moveIndicator = (instant = false) => {
        if (!indicator || !tabList) return;
        const active = tabs.find((tab) => tab.dataset.agent === activeAgent);
        if (!active) return;
        // offsetLeft is measured from the physical left edge in both LTR and RTL, so the indicator uses `left`.
        gsap.to(indicator, { x: active.offsetLeft, width: active.offsetWidth, duration: instant || !animate ? 0 : 0.55, ease: 'expo.out' });
    };

    const setTabStyles = (instant = false) => {
        tabs.forEach((tab) => {
            const selected = tab.dataset.agent === activeAgent;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        moveIndicator(instant);
    };

    const selectAgent = (agent) => {
        if (agent === activeAgent) return;
        activeAgent = agent;
        setTabStyles();
        renderActiveAgent();
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectAgent(tab.dataset.agent));
        // Arrow keys move between tabs (mirrored in RTL).
        tab.addEventListener('keydown', (event) => {
            const rtl = document.documentElement.dir === 'rtl';
            const step = { ArrowRight: rtl ? -1 : 1, ArrowLeft: rtl ? 1 : -1 }[event.key];
            if (!step) return;
            event.preventDefault();
            const next = tabs[(index + step + tabs.length) % tabs.length];
            next.focus();
            selectAgent(next.dataset.agent);
        });
    });
    if (tabList) new ResizeObserver(() => moveIndicator(true)).observe(tabList);

    // ---------------------------------------------------------------- messages
    const avatar = (agent) => `<span class="mb-1 flex items-center gap-1.5 text-[11px] font-medium text-signal"><span class="h-1.5 w-1.5 rounded-full bg-signal"></span>${agentLabels[agent] || 'Cyber Mentor'}</span>`;

    const addMessage = (message, own = false, { animateIn = true, agent = activeAgent } = {}) => {
        const wrap = document.createElement('div');
        const language = detectLanguage(message);
        wrap.className = own ? 'flex justify-end' : 'flex flex-col items-start';
        const bubble = document.createElement('div');
        bubble.className = own
            ? 'max-w-[85%] rounded-2xl rounded-ee-md bg-signal px-3.5 py-2.5 leading-relaxed text-void'
            : 'max-w-[92%] rounded-2xl rounded-ss-md bg-white/[0.05] px-3.5 py-2.5 leading-relaxed text-slate-200 ring-1 ring-white/[0.07]';
        bubble.dir = language === 'ar' ? 'rtl' : 'ltr';
        bubble.style.textAlign = 'start';
        bubble.style.overflowWrap = 'anywhere';

        if (own) {
            bubble.textContent = message;
        } else {
            wrap.insertAdjacentHTML('afterbegin', avatar(agent));
            bubble.innerHTML = formatBotMessage(message);
        }
        wrap.appendChild(bubble);
        log.appendChild(wrap);
        if (animateIn) enter(wrap);
        scrollToEnd();

        return bubble;
    };

    const addTyping = () => {
        const wrap = document.createElement('div');
        wrap.className = 'flex flex-col items-start';
        wrap.innerHTML = `${avatar(activeAgent)}<div class="flex items-center gap-1 rounded-2xl rounded-ss-md bg-white/[0.05] px-4 py-3.5 ring-1 ring-white/[0.07]" role="status" aria-label="${lastUserLanguage === 'ar' ? 'جاري التفكير' : 'Thinking'}"><span class="typing-dot h-1.5 w-1.5 rounded-full bg-signal"></span><span class="typing-dot h-1.5 w-1.5 rounded-full bg-signal"></span><span class="typing-dot h-1.5 w-1.5 rounded-full bg-signal"></span></div>`;
        log.appendChild(wrap);
        enter(wrap);
        scrollToEnd();
        return wrap;
    };

    const renderQuickReplies = () => {
        if (!quickReplies) return;
        quickReplies.innerHTML = '';
        currentProfile().quickReplies.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rounded-full bg-white/[0.04] px-3 py-1.5 text-xs text-cyan-50 ring-1 ring-white/10 transition hover:bg-signal/10 hover:ring-signal/50';
            button.textContent = item.label;
            button.addEventListener('click', () => sendMessage(lastUserLanguage === 'ar' ? item.ar : item.en));
            quickReplies.appendChild(button);
        });
        quickReplies.classList.remove('hidden');
        if (animate) gsap.from(quickReplies.children, { opacity: 0, y: 6, duration: 0.35, stagger: 0.04, ease: 'power2.out', clearProps: 'all' });
    };

    const showAgentRedirectHint = (meta, originalMessage) => {
        if (!meta?.redirect || !meta.target_agent || !agentProfiles[meta.target_agent]) return;

        const arabic = lastUserLanguage === 'ar';
        const targetLabel = escapeHtml(String(arabic
            ? (meta.target_label_ar || agentLabels[meta.target_agent])
            : (meta.target_label || agentLabels[meta.target_agent])));
        const hint = document.createElement('div');
        hint.className = 'rounded-xl border border-pulse/30 bg-pulse/[0.07] p-3 text-sm';
        hint.dir = arabic ? 'rtl' : 'ltr';
        hint.style.textAlign = 'start';
        hint.innerHTML = `
            <p class="font-semibold text-violet-100">${arabic ? 'التبويب المناسب لهذا الطلب' : 'Best tab for this request'}</p>
            <p class="mt-1 text-slate-300">${arabic ? `افتح ${targetLabel} وأعد إرسال السؤال هناك.` : `Open ${targetLabel} and send this question there.`}</p>
            <button type="button" class="btn btn-sm mt-3 bg-pulse text-void hover:bg-violet-300">${arabic ? `افتح ${targetLabel}` : `Open ${targetLabel}`}</button>`;

        hint.querySelector('button').addEventListener('click', () => {
            selectAgent(meta.target_agent);
            input.value = originalMessage;
            input.focus();
        });

        log.appendChild(hint);
        enter(hint);
        scrollToEnd();
    };

    const renderActiveAgent = () => {
        log.innerHTML = '';
        input.placeholder = currentProfile().placeholder;
        addMessage(currentProfile().welcome);
        histories[activeAgent].forEach((item) => addMessage(item.content, item.role === 'user', { animateIn: false }));
        renderQuickReplies();
    };

    // ---------------------------------------------------------------- level picker
    const shouldShowLevelPicker = (message) => {
        const text = message.toLowerCase();
        const hasEnglishLevels = text.includes('beginner') && text.includes('intermediate') && text.includes('expert');
        const hasArabicLevels = (
            (text.includes('مبتدئ') || text.includes('مبتدي') || text.includes('مبتدئة'))
            && (text.includes('متوسط') || text.includes('متوسطة'))
            && (text.includes('محترف') || text.includes('خبير') || text.includes('متقدم') || text.includes('متقدمة'))
        );
        const asksForLevel = (
            text.includes('before i build your plan')
            || text.includes('choose your current level')
            || text.includes('which level are you')
            || text.includes('اختر مستواك')
            || text.includes('اختَر مستواك')
            || text.includes('مستواك الحالي')
            || text.includes('ما هو مستواك')
            || text.includes('ما مستواك')
            || text.includes('قبل ما أبني')
            || text.includes('قبل أن أبني')
        );
        return asksForLevel && (hasEnglishLevels || hasArabicLevels);
    };

    const showLevelPicker = () => {
        const dialog = document.querySelector('#level-picker');
        if (!dialog?.showModal) return;
        const arabic = lastUserLanguage === 'ar';
        const levels = [
            ['Beginner', arabic ? 'مبتدئ' : 'Beginner', arabic ? 'جديد في الأمن السيبراني أو ما زلت تتعلم الأساسيات.' : 'New to cybersecurity or still learning the basics.'],
            ['Intermediate', arabic ? 'متوسط' : 'Intermediate', arabic ? 'تفهم أساسيات الشبكات أو Linux أو مفاهيم الأمن الأساسية.' : 'You know networking, Linux, or basic security concepts.'],
            ['Expert', arabic ? 'محترف' : 'Expert', arabic ? 'لديك خبرة عملية وتريد مساراً متقدماً أو تخصصاً أدق.' : 'You have hands-on experience and want advanced specialization.'],
        ];
        dialog.dir = arabic ? 'rtl' : 'ltr';
        dialog.querySelector('[data-level-title]').textContent = arabic ? 'اختر مستواك' : 'Choose your level';
        const options = dialog.querySelector('[data-level-options]');
        options.innerHTML = '';
        levels.forEach(([value, label, description]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'choice w-full text-start';
            button.innerHTML = '<span><span data-l class="block font-semibold text-cyan-100"></span><span data-d class="mt-0.5 block text-sm text-slate-400"></span></span>';
            button.querySelector('[data-l]').textContent = label;
            button.querySelector('[data-d]').textContent = description;
            button.addEventListener('click', () => {
                dialog.close();
                sendMessage(arabic
                    ? `أنا مستواي ${value}. من فضلك ابنِ لي خطة لتعلم الأمن السيبراني مناسبة لمستواي.`
                    : `I am ${value}. Please build a cybersecurity learning plan for my level.`);
            });
            options.appendChild(button);
        });
        dialog.showModal();
    };

    // ---------------------------------------------------------------- transport
    const streamChat = async (payload, onChunk) => {
        const response = await fetch(config.streamUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'text/event-stream' },
            body: JSON.stringify(payload),
        });
        if (!response.ok || !response.body) throw new Error('Streaming response failed.');

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let text = '';
        let meta = {};

        const handleEvent = (rawEvent) => {
            const dataLine = rawEvent.split('\n').find((line) => line.startsWith('data: '));
            if (!dataLine) return;
            const eventLine = rawEvent.split('\n').find((line) => line.startsWith('event: '));
            const eventName = eventLine ? eventLine.slice(7).trim() : 'message';
            const data = JSON.parse(dataLine.slice(6));

            if (eventName === 'chunk') {
                text += data.text || '';
                onChunk(text);
            }
            if (eventName === 'done') {
                text = data.message || text;
                meta = data.meta || {};
            }
            if (eventName === 'error') throw new Error(data.message || 'Streaming response failed.');
        };

        while (true) {
            const { value, done } = await reader.read();
            buffer += decoder.decode(value || new Uint8Array(), { stream: !done });
            let boundary;
            while ((boundary = buffer.indexOf('\n\n')) !== -1) {
                const rawEvent = buffer.slice(0, boundary).trim();
                buffer = buffer.slice(boundary + 2);
                if (rawEvent) handleEvent(rawEvent);
            }
            if (done) break;
        }
        if (buffer.trim()) handleEvent(buffer.trim());

        return { message: text, meta };
    };

    const sendJsonChat = async (payload) => {
        const response = await fetch(config.chatUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            body: JSON.stringify(payload),
        });
        return response.json();
    };

    const unavailable = () => (lastUserLanguage === 'ar'
        ? 'تعذر الاتصال بخدمة الذكاء الاصطناعي حالياً. حاول مرة أخرى بعد لحظات.'
        : 'The AI service is currently unavailable. Please try again in a moment.');

    const sendMessage = async (message) => {
        const agentAtSend = activeAgent;
        const agentHistory = histories[agentAtSend] || [];
        lastUserLanguage = detectLanguage(message);
        quickReplies?.classList.add('hidden');
        addMessage(message, true);

        // Typing indicator until the first chunk arrives, then a bubble with a streaming caret.
        let typing = addTyping();
        let bubble = null;
        const showText = (text) => {
            if (activeAgent !== agentAtSend) return;
            if (!bubble) {
                typing?.remove();
                typing = null;
                bubble = addMessage('', false, { agent: agentAtSend });
            }
            if (!bubble.isConnected) return;
            bubble.dir = detectLanguage(text) === 'ar' ? 'rtl' : 'ltr';
            bubble.innerHTML = `${formatBotMessage(text)}<span class="caret text-signal" aria-hidden="true"></span>`;
            scrollToEnd();
        };

        let answer = unavailable();
        let meta = {};
        const payload = {
            message,
            history: agentHistory,
            lesson_id: config.lessonId,
            agent_type: agentAtSend,
            platform_version: config.version,
        };

        try {
            const data = config.streamUrl ? await streamChat(payload, showText) : await sendJsonChat(payload);
            answer = data.message || answer;
            meta = data.meta || {};
        } catch {
            try {
                const data = await sendJsonChat(payload);
                answer = data.message || answer;
                meta = data.meta || {};
            } catch {
                answer = unavailable();
            }
        }

        typing?.remove();
        histories[agentAtSend] = [
            ...agentHistory,
            { role: 'user', content: message },
            { role: 'assistant', content: answer },
        ].slice(-20);

        if (activeAgent !== agentAtSend) return;
        if (bubble?.isConnected) {
            bubble.dir = detectLanguage(answer) === 'ar' ? 'rtl' : 'ltr';
            bubble.innerHTML = formatBotMessage(answer);
        } else {
            addMessage(answer, false, { agent: agentAtSend });
        }
        renderQuickReplies();
        showAgentRedirectHint(meta, message);
        if (shouldShowLevelPicker(answer)) showLevelPicker();
    };

    // ---------------------------------------------------------------- input
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;
        input.value = '';
        sendMessage(message);
    });
    // Enter sends, Shift+Enter adds a new line (IME composition is left alone).
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    setTabStyles(true);
    renderActiveAgent();
    document.fonts?.ready.then(() => moveIndicator(true));
}
