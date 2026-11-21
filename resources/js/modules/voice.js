/** Voice assistant: browser speech recognition → /voice/ai → reply (spoken with the browser's TTS). */
export function initVoice() {
    const root = document.querySelector('[data-voice]');
    const s = window.__voiceStrings || {};
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const mic = root.querySelector('[data-voice-mic]');
    const status = root.querySelector('[data-voice-status]');
    const transcriptEl = root.querySelector('[data-voice-transcript]');
    const replyEl = root.querySelector('[data-voice-reply]');
    const speak = root.querySelector('[data-voice-speak]');
    const form = root.querySelector('[data-voice-form]');
    const text = root.querySelector('#voice-text');
    const lang = root.dataset.lang;

    const post = (url, body) => fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json' },
        body: JSON.stringify(body),
    });

    const setListening = (on) => {
        mic.setAttribute('aria-pressed', String(on));
        mic.setAttribute('aria-label', on ? s.stop : s.start);
        status.textContent = on ? s.listening : s.idle;
    };

    async function sendText(value) {
        transcriptEl.textContent = value;
        replyEl.textContent = s.thinking;
        status.textContent = s.thinking;
        try {
            const res = await post(root.dataset.respondUrl, { text: value });
            if (!res.ok) throw new Error(res.statusText);
            const data = await res.json();
            replyEl.textContent = data.message || '';
            status.textContent = s.idle;
            if (speak?.checked && 'speechSynthesis' in window && data.message) {
                const utterance = new SpeechSynthesisUtterance(data.message);
                utterance.lang = /[؀-ۿ]/.test(data.message) ? 'ar-SA' : 'en-US';
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utterance);
            }
        } catch {
            replyEl.textContent = s.error;
            status.textContent = s.idle;
        }
    }

    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;
    if (Recognition) {
        recognition = new Recognition();
        recognition.lang = lang;
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;
        recognition.onresult = (event) => sendText(event.results[0][0].transcript);
        recognition.onend = () => setListening(false);
        recognition.onerror = () => setListening(false);
    }

    mic.addEventListener('click', () => {
        if (!recognition) { status.textContent = s.unsupported; text.focus(); return; }
        if (mic.getAttribute('aria-pressed') === 'true') { recognition.stop(); return; }
        window.speechSynthesis?.cancel();
        recognition.start();
        setListening(true);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const value = text.value.trim();
        if (!value) return;
        text.value = '';
        sendText(value);
    });

    root.querySelector('[data-voice-server-tts]')?.addEventListener('click', async () => {
        const value = replyEl.textContent.trim() && replyEl.textContent !== '—' ? replyEl.textContent : (text.value || transcriptEl.textContent);
        status.textContent = s.ttsMaking;
        try {
            const res = await post(root.dataset.ttsUrl, { text: value });
            const json = await res.json();
            if (!json.ok) throw new Error(json.error);
            status.textContent = s.idle;
            new Audio(json.url).play();
        } catch {
            status.textContent = s.ttsFailed;
        }
    });
}
