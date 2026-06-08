<script>
    import { fade } from 'svelte/transition';
    import { game } from '../../stores/gameStore.svelte.js';
    import AiAdvisor from '../../components/AiAdvisor.svelte';
    import AllianceSidebar from '../../components/AllianceSidebar.svelte';

    let { payload = {} } = $props();
    let showCreate = $state(false);
    let newThreadTitle = $state('');
    let newThreadContent = $state('');
    let processing = $state(false);

    async function createThread() {
        if (!newThreadTitle || !newThreadContent) return;
        processing = true;
        try {
            const formData = new FormData();
            formData.append('title', newThreadTitle);
            formData.append('content', newThreadContent);
            formData.append('_csrf', game.csrf);
            
            const res = await fetch('/api/alliance/forum/thread/create', { method: 'POST', body: formData });
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
            <div>
                <h1 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Encrypted Comm-Link</h1>
                <p class="text-purple-500/60 text-[10px] font-bold uppercase tracking-[4px] mt-1 italic">Authorized internal collective communications.</p>
            </div>
            <button 
                onclick={() => showCreate = !showCreate}
                class="bg-purple-500/20 border border-purple-500/50 px-6 py-2 text-[10px] font-black text-purple-400 uppercase tracking-widest hover:bg-purple-500/40 transition-all"
            >
                {showCreate ? 'ABORT' : 'NEW BROADCAST'}
            </button>
        </header>

        {#if showCreate}
            <div in:fade class="bg-gray-900/40 border border-purple-500/30 p-6 rounded-lg backdrop-blur-sm space-y-4">
                <input 
                    bind:value={newThreadTitle}
                    type="text" 
                    placeholder="BROADCAST FREQUENCY TITLE..." 
                    class="w-full bg-black/40 border border-white/10 px-4 py-3 text-xs font-mono text-purple-400 focus:outline-none focus:border-purple-500/50 transition-all uppercase"
                />
                <textarea 
                    bind:value={newThreadContent}
                    placeholder="TRANSMISSION DATA..." 
                    rows="6"
                    class="w-full bg-black/40 border border-white/10 px-4 py-3 text-xs font-mono text-gray-400 focus:outline-none focus:border-purple-500/50 transition-all uppercase"
                ></textarea>
                <div class="flex justify-end">
                    <button 
                        onclick={createThread}
                        disabled={processing}
                        class="bg-purple-600/20 border border-purple-500/50 px-8 py-3 text-[10px] font-black text-purple-400 uppercase tracking-widest hover:bg-purple-500/40 transition-all"
                    >
                        {processing ? 'TRANSMITTING...' : 'INITIALIZE BROADCAST'}
                    </button>
                </div>
            </div>
        {/if}

        <div class="bg-gray-900/40 border border-white/5 rounded-lg overflow-hidden backdrop-blur-sm shadow-2xl">
            <header class="bg-black/40 px-6 py-3 border-b border-white/5 flex justify-between items-center text-[10px] font-black text-gray-500 uppercase tracking-[4px]">
                <span>Active Frequencies</span>
                <span class="font-mono opacity-50">Authorized Only</span>
            </header>
            
            <div class="divide-y divide-white/5">
                {#each payload.threads || [] as thread}
                    <a 
                        href={`/alliance/forum/thread/${thread.id}`}
                        class="w-full text-left px-6 py-5 hover:bg-purple-500/5 transition-all group flex justify-between items-center"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                {#if thread.is_stickied}
                                    <span class="bg-purple-500/20 text-purple-400 border border-purple-500/50 text-[7px] px-1 rounded font-black uppercase">PRIORITY</span>
                                {/if}
                                {#if thread.is_locked}
                                    <span class="text-red-500/50 text-[8px] uppercase font-black">LOCKED</span>
                                {/if}
                                <h3 class="text-sm font-title font-black text-white uppercase group-hover:text-purple-400 transition-colors">{thread.title}</h3>
                            </div>
                            <div class="flex gap-4 text-[9px] text-gray-600 font-bold uppercase tracking-widest">
                                <span>Origin: {thread.user?.username}</span>
                                <span>Sync: {new Date(thread.updated_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[8px] font-black text-gray-600 uppercase block mb-1">Replies</span>
                            <span class="text-lg font-mono text-purple-400 font-bold">{thread.posts_count - 1 || 0}</span>
                        </div>
                    </a>
                {:else}
                    <div class="py-24 text-center opacity-20 italic uppercase tracking-widest">
                        No active frequencies detected.
                    </div>
                {/each}
            </div>
        </div>
    </main>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
