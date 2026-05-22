<script>
    import { fade } from 'svelte/transition';
    let { content = '' } = $props();
</script>

<div in:fade class="max-w-4xl mx-auto px-6 py-20 space-y-12 min-h-screen">
    <header class="border-b border-cyan-500/20 pb-8">
        <h1 class="text-5xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Official Rules</h1>
        <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[6px] mt-4 italic">Neural Conduct & Sector Protocols</p>
    </header>

    <div class="bg-dark-translucent border border-white/5 p-12 rounded-3xl shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
             <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
        </div>

        <div class="prose prose-invert prose-cyan max-w-none relative z-10">
            <div class="text-gray-300 leading-relaxed font-light whitespace-pre-wrap">
                {content}
            </div>
        </div>
    </div>

    <footer class="text-center pt-8">
        <p class="text-[9px] text-gray-700 uppercase tracking-widest font-black italic">
            Failure to comply with sector protocols results in immediate neural detachment.
        </p>
    </footer>
</div>

<style>
    /* Custom prose-like styles for the rules content */
    :global(.prose h3) {
        color: #22d3ee;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 900;
        margin-top: 2rem;
        margin-bottom: 1rem;
        font-family: var(--font-title);
    }
</style>
