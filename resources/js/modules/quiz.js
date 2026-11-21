/**
 * Quiz countdown. The server owns the clock (remainingSeconds); at 0 the form auto-submits.
 * The ring and the answered counter are progressive enhancements on top of the text timer.
 */
export function initQuiz() {
    let seconds = Math.max(0, Number(window.quizSeconds) || 0);
    const total = Math.max(1, Number(window.quizTotalSeconds) || seconds || 1);
    const timer = document.querySelector('#quiz-timer');
    const ring = document.querySelector('[data-quiz-ring]');
    const clock = document.querySelector('[data-quiz-clock]');
    const quizForm = document.querySelector('#quiz-form');
    const announcer = document.querySelector('[data-quiz-announce]');
    const answered = document.querySelector('[data-answered]');
    const questions = quizForm ? [...quizForm.querySelectorAll('fieldset[data-question]')] : [];

    const render = () => {
        const min = String(Math.floor(seconds / 60)).padStart(2, '0');
        const sec = String(seconds % 60).padStart(2, '0');
        if (timer) timer.textContent = `${min}:${sec}`;
        if (ring) ring.style.strokeDashoffset = String(100 - (seconds / total) * 100);
        // Last 30 seconds: the clock turns red.
        clock?.toggleAttribute('data-urgent', seconds <= 30);
        if (announcer && (seconds === 60 || seconds === 30 || seconds === 10)) {
            announcer.textContent = announcer.dataset.template.replace(':seconds', String(seconds));
        }
    };

    const countAnswered = () => {
        const done = questions.filter((q) => q.querySelector('input:checked')).length;
        if (answered) answered.textContent = String(done);
        questions.forEach((q) => q.toggleAttribute('data-answered', !!q.querySelector('input:checked')));
    };
    quizForm?.addEventListener('change', countAnswered);
    countAnswered();
    render();

    const interval = setInterval(() => {
        seconds = Math.max(0, seconds - 1);
        render();

        // Time is up: submit whatever has been answered (form.submit() skips `required`).
        if (seconds === 0) {
            clearInterval(interval);
            quizForm?.submit();
        }
    }, 1000);
}
