const token = document.querySelector('meta[name="csrf-token"]')?.content;

if (window.cyberLesson) {
    const log = document.querySelector('#chat-log');
    const form = document.querySelector('#chat-form');
    const input = document.querySelector('#chat-message');
    let activeAgent = window.cyberLesson.agent;

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
        bubble.className = own
            ? 'ml-8 rounded-lg bg-cyan-400 p-3 text-slate-950'
            : 'mr-8 rounded-lg bg-white/10 p-3 text-cyan-50';
        bubble.textContent = message;
        log.appendChild(bubble);
        log.scrollTop = log.scrollHeight;
    };

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;
        input.value = '';
        addMessage(message, true);
        addMessage('Thinking...');

        const response = await fetch(window.cyberLesson.chatUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json'},
            body: JSON.stringify({
                message,
                lesson_id: window.cyberLesson.lessonId,
                agent_type: activeAgent,
                platform_version: window.cyberLesson.version,
            }),
        });
        const data = await response.json();
        log.lastElementChild.remove();
        addMessage(data.message || 'The AI service is currently unavailable.');
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
