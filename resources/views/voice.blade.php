<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Voice Assistant</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; padding: 1.5rem; }
        #controls { margin-bottom: 1rem; }
        button { margin-right: .5rem; }
        #transcript, #reply { background:#f7f7f8; padding: .75rem; border-radius:6px; min-height:40px }
    </style>
</head>
<body>
    <h1>مساعد صوتي تفاعلي</h1>

    <div id="controls">
        <button id="startBtn">ابدأ الاستماع</button>
        <button id="stopBtn" disabled>إيقاف</button>
        <input id="manualInput" placeholder="أكتب رسالتك هنا" style="width:40%">
        <button id="sendBtn">إرسال</button>
    </div>

    <p><strong>النص الملتقط:</strong></p>
    <div id="transcript"></div>

    <p style="margin-top:1rem"><strong>رد الـ AI:</strong></p>
    <div id="reply"></div>

    <script>
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const transcriptEl = document.getElementById('transcript');
        const replyEl = document.getElementById('reply');
        let recognition = null;

        if (window.SpeechRecognition || window.webkitSpeechRecognition) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.lang = 'ar-SA';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            recognition.onresult = (e) => {
                const text = e.results[0][0].transcript;
                transcriptEl.textContent = text;
                sendText(text);
            };

            recognition.onend = () => {
                startBtn.disabled = false;
                stopBtn.disabled = true;
            };
        }

        startBtn.addEventListener('click', () => {
            if (!recognition) { alert('متصفحك لا يدعم ميزة التعرف على الصوت.'); return; }
            recognition.start();
            startBtn.disabled = true;
            stopBtn.disabled = false;
        });

        stopBtn.addEventListener('click', () => {
            if (recognition) recognition.stop();
        });

        document.getElementById('sendBtn').addEventListener('click', () => {
            const t = document.getElementById('manualInput').value.trim();
            if (t) { transcriptEl.textContent = t; sendText(t); }
        });

        async function sendText(text) {
            replyEl.textContent = 'جاري التفكير...';
            try {
                const res = await fetch('{{ route("voice.respond") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ text })
                });

                if (!res.ok) {
                    replyEl.textContent = 'خطأ: ' + res.statusText;
                    return;
                }

                const data = await res.json();
                replyEl.textContent = data.message || '';

                if ('speechSynthesis' in window && data.message) {
                    const ut = new SpeechSynthesisUtterance(data.message);
                    ut.lang = 'ar-SA';
                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(ut);
                }
            } catch (err) {
                replyEl.textContent = 'خطأ في الاتصال';
            }
        }
    </script>
</body>
</html>
