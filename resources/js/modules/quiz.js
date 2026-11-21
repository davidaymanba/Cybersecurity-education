/** Quiz countdown: the server owns the clock (remainingSeconds); at 0 the form auto-submits. */
export function initQuiz() {
    let seconds = window.quizSeconds;
    const timer = document.querySelector('#quiz-timer');
    const quizForm = document.querySelector('#quiz-form');
    const interval = setInterval(() => {
        seconds = Math.max(0, seconds - 1);
        const min = String(Math.floor(seconds / 60)).padStart(2, '0');
        const sec = String(seconds % 60).padStart(2, '0');
        if (timer) timer.textContent = `${min}:${sec}`;

        // Time is up: submit whatever has been answered (form.submit() skips `required`).
        if (seconds === 0) {
            clearInterval(interval);
            quizForm?.submit();
        }
    }, 1000);
}
