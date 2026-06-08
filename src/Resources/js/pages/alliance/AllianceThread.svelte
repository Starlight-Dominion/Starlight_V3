<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';
    import AiAdvisor from '../../components/AiAdvisor.svelte';
    import AllianceSidebar from '../../components/AllianceSidebar.svelte';

    let { payload = {} } = $props();
    let thread = $derived(payload.thread || {});
    let replyContent = $state('');
    let processing = $state(false);

    async function postReply() {
        if (!replyContent) return;
        processing = true;
        try {
            const formData = new FormData();
            formData.append('content', replyContent);
            formData.append('_csrf', game.csrf);
            
            const res = await fetch(`/api/alliance/forum/thread/${thread.id}/reply`, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            }
        } finally {
            processing = false;
        }
    }
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-6 pb-24">
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor advisor={payload?.advisor} />
        <AllianceSidebar active="forum" {payload} />
    </aside>

    <main class="lg:col-span-3 space-y-6">
        <header class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md flex justify-between items-center">
            <div class="space-y-1">
                <a href="/alliance/forum" class="text-[8px] font-black text-purple-500 uppercase tracking-widest hover:text-white transition-all mb-2 block">&lt; RETURN TO FREQUENCIES</a>
                <h1 class="text-2xl font-title font-black text-white uppercase tracking-tighter">{thread.title}</h1>
                <div class="flex gap-4 text-[9px] text-gray-600 font-bold uppercase tracking-widest">
                    <span>Origin: {thread.user?.username}</span>
                    <span>ID: {thread.id}</span>
                </div>
            </div>
        </header>

        <div class="space-y-4">
            {#each thread.posts || [] as post}
                <div class="bg-gray-900/40 border border-white/5 rounded-lg overflow-hidden backdrop-blur-sm shadow-xl flex">
                    <aside class="w-32 bg-black/40 p-4 border-r border-white/5 flex flex-col items-center gap-2">
                        <div class="w-12 h-12 bg-white/5 border border-white/10 rounded flex items-center justify-center">
                            <span class="text-[10px] font-black text-gray-500 uppercase">{post.user?.username?.substring(0, 2)}</span>
                        </div>
                        <span class="text-[9px] font-black text-cyan-500 uppercase text-center break-all">{post.user?.username}</span>
                        <span class="text-[7px] font-bold text-gray-600 uppercase text-center">{post.user?.alliance_role?.name || 'Ensign'}</span>
                    </aside>
                    <main class="flex-grow p-6 space-y-4">
                        <header class="flex justify-between items-center border-b border-white/5 pb-2">
                            <span class="text-[8px] font-mono text-gray-600 uppercase tracking-widest">{new Date(post.created_at).toLocaleString()}</span>
                            <span class="text-[8px] font-mono text-gray-800 uppercase tracking-widest"># {post.id}</span>
                        </header>
                        <div class="text-[11px] text-gray-300 font-mono leading-relaxed uppercase whitespace-pre-wrap">
                            {post.content}
                        </div>
                    </main>
                </div>
            {/each}
        </div>

        {#if !thread.is_locked}
            <div class="bg-gray-900/40 border border-purple-500/30 p-6 rounded-lg backdrop-blur-sm space-y-4">
                <header class="border-b border-white/5 pb-2">
                    <h3 class="text-[10px] font-black text-purple-400 uppercase tracking-[2px]">Transmit Response</h3>
                </header>
                <textarea 
                    bind:value={replyContent}
                    placeholder="INPUT DATA..." 
                    rows="4"
                    class="w-full bg-black/40 border border-white/10 px-4 py-3 text-xs font-mono text-gray-400 focus:outline-none focus:border-purple-500/50 transition-all uppercase"
                ></textarea>
                <div class="flex justify-end">
                    <button 
                        onclick={postReply}
                        disabled={processing || !replyContent}
                        class="bg-purple-600/20 border border-purple-500/50 px-8 py-3 text-[10px] font-black text-purple-400 uppercase tracking-widest hover:bg-purple-500/40 transition-all disabled:opacity-50"
                    >
                        {processing ? 'TRANSMITTING...' : 'INITIALIZE RESPONSE'}
                    </button>
                </div>
            </div>
        {:else}
            <div class="bg-red-500/10 border border-red-500/50 p-8 rounded-lg text-center">
                <span class="text-xs font-black text-red-500 uppercase tracking-[5px]">Transmission Frequency Terminated (Locked)</span>
            </div>
        {/if}
    </main>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
