// src/Resources/js/app.js
import { mount } from 'svelte';
import App from './App.svelte';
import { updateGame, startHeartbeat } from './stores/gameStore.svelte.js';
import '../css/app.css';

// 1. Ingest State
try {
    if (window.__INITIAL_STATE__) {
        updateGame(window.__INITIAL_STATE__);
        if (window.__INITIAL_STATE__.user) {
            startHeartbeat();
        }
    }
} catch (e) {
    console.error("Starlight Dominion Init Error:", e);
}

// 2. Navigation Logic
window.navigate = async (url, push = true) => {
    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const newState = await response.json();
        updateGame(newState);
        if (push) window.history.pushState({ url }, '', url);
        window.scrollTo(0, 0);
    } catch (e) {
        window.location.href = url;
    }
};

// 3. Mount
const target = document.getElementById('app');
if (target) {
    try {
        mount(App, { target });
    } catch (e) {
        console.error("Svelte Mount Failure:", e);
        // If mount fails, show a basic error instead of a white screen
        target.innerHTML = `<div style="color:white; padding:20px; font-family:sans-serif;">
            <h1 style="color:#c5a059">Interface Error</h1>
            <p>The tactical overlay failed to load. Please refresh.</p>
        </div>`;
    }
}

// 4. Listeners
document.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (!link || link.target === '_blank' || link.host !== window.location.host) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || link.pathname === '/logout') return;
    e.preventDefault();
    window.navigate(link.pathname + link.search);
});

window.addEventListener('popstate', () => window.navigate(window.location.pathname, false));