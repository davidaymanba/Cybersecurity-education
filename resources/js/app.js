import { initMotion } from './ui/motion';
import { initShell } from './ui/shell';

initShell();
initMotion();

// Page modules are split into their own chunks and only loaded where the page needs them.
if (window.cyberLesson) import('./modules/chat').then((m) => m.initChat());
if (window.quizSeconds !== undefined) import('./modules/quiz').then((m) => m.initQuiz());
