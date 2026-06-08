<script>
    import { game } from '../stores/gameStore.svelte.js';
    import { fade } from 'svelte/transition';

    let { active = 'hub', payload = {} } = $props();
</script>

<div in:fade class="bg-gray-900/60 border border-white/5 rounded-lg overflow-hidden shadow-2xl backdrop-blur-md">
    <header class="bg-[#030712]/60 px-4 py-2 border-b border-white/5 flex justify-between items-center">
        <h2 class="text-cyan-500 font-title text-[9px] font-black uppercase tracking-[3px]">Sector Group</h2>
    </header>
    <div class="p-2 space-y-1">
        <a href="/alliance" class="block w-full text-left px-3 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-cyan-500/10 hover:text-white transition-all {active === 'hub' ? 'text-cyan-400 bg-cyan-500/5' : 'text-gray-500'}">Hub</a>
        <a href="/alliance/treasury" class="block w-full text-left px-3 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-cyan-500/10 hover:text-white transition-all {active === 'bank' ? 'text-cyan-400 bg-cyan-500/5' : 'text-gray-500'}">Treasury</a>
        <a href="/alliance/structures" class="block w-full text-left px-3 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-cyan-500/10 hover:text-white transition-all {active === 'structures' ? 'text-cyan-400 bg-cyan-500/5' : 'text-gray-500'}">Structures</a>
        <a href="/alliance/forum" class="block w-full text-left px-3 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-cyan-500/10 hover:text-white transition-all {active === 'forum' ? 'text-cyan-400 bg-cyan-500/5' : 'text-gray-500'}">Forum</a>
        
        {#if payload.is_leader || payload.my_role?.can_invite || payload.my_role?.can_kick || payload.can_manage}
            <a href="/alliance/command" class="block w-full text-left px-3 py-2 text-[10px] font-bold uppercase tracking-widest hover:bg-purple-500/10 hover:text-white transition-all {active === 'management' ? 'text-purple-400 bg-purple-500/5' : 'text-gray-500'}">Command</a>
        {/if}
    </div>
</div>

<style>
    .font-title { font-family: 'Orbitron', sans-serif; }
</style>
