import { initMotion } from './ui/motion';
import { initShell } from './ui/shell';

initShell();
initMotion();

// Page modules are split into their own chunks and only loaded where the page needs them.
if (window.cyberLesson) import('./modules/chat').then((m) => m.initChat());
if (window.quizSeconds !== undefined) import('./modules/quiz').then((m) => m.initQuiz());

const bookmark = document.querySelector('#bookmark-btn');
if (bookmark) import('./modules/bookmark').then((m) => m.initBookmark(bookmark));

const celebration = document.querySelector('[data-celebrate]');
if (celebration) import('./modules/celebrate').then((m) => setTimeout(() => m.celebrate(celebration), 700));

if (document.querySelector('[data-voice]')) import('./modules/voice').then((m) => m.initVoice());
if (document.querySelector('[data-chart]')) import('./modules/charts').then((m) => m.initCharts());
