import { createRoot } from 'react-dom/client';
import 'lenis/dist/lenis.css';
import App from './App';

const data = {
    locale: 'ar',
    auth: false,
    routes: {},
    stats: { lessons: 0, quizzes: 0, videos: 0, agents: 4 },
    lessons: [],
    ...(window.__CYBERLEARN__ || {}),
};

createRoot(document.getElementById('preview-root')).render(<App data={data} />);
