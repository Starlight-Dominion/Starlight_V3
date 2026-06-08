<script>
    import { fade } from 'svelte/transition';
    import AdminLogsViewer from './AdminLogsViewer.svelte';
    import RecruitmentLogsViewer from './RecruitmentLogsViewer.svelte';

    let { game } = $props();
    let activeTab = $state('admin'); // 'admin', 'recruitment'

    const tabs = [
        { id: 'admin', name: 'Administrative Directives', icon: '⚡' },
        { id: 'recruitment', name: 'Recruitment Telemetry', icon: '👥' }
    ];
</script>

<div class="space-y-8">
    <!-- Sub-Navigation -->
    <nav class="flex gap-4 p-2 bg-dark-translucent border border-white/5 rounded-2xl w-fit">
        {#each tabs as tab}
            <button 
                onclick={() => activeTab = tab.id}
                class="px-8 py-4 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-3 {activeTab === tab.id ? 'bg-cyan-600 text-white shadow-[0_0_20px_rgba(6,182,212,0.3)]' : 'text-gray-500 hover:text-gray-300'}"
            >
                <span class="text-xs">{tab.icon}</span>
                {tab.name}
            </button>
        {/each}
    </nav>

    {#if activeTab === 'admin'}
        <div in:fade={{ duration: 200 }}>
            <AdminLogsViewer {game} />
        </div>
    {:else if activeTab === 'recruitment'}
        <div in:fade={{ duration: 200 }}>
            <RecruitmentLogsViewer {game} />
        </div>
    {/if}
</div>
