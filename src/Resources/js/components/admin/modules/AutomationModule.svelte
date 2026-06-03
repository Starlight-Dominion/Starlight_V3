<script>
    import { fade } from 'svelte/transition';
    let { 
        botProfiles = [],
        onInspect,
        onDelete,
        onAdd
    } = $props();
</script>

<div in:fade class="space-y-12">
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-3xl font-title font-black text-white uppercase tracking-tighter">Automation Suite</h3>
            <p class="text-gray-500 font-bold uppercase tracking-[4px] text-[10px] mt-2 italic">Neural processing unit for automated sectors.</p>
        </div>
        <div class="flex gap-4">
            <button 
                onclick={onAdd}
                class="px-8 py-4 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-500 transition-all shadow-[0_0_20px_rgba(16,185,129,0.2)]"
            >
                Commission New Profile
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        {#each botProfiles as profile}
            <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl flex flex-col justify-between group hover:border-emerald-500/30 transition-all relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                    {profile.name.substring(0,3)}
                </div>
                <div class="space-y-6 relative z-10">
                    <div>
                        <h4 class="text-xl font-title font-black text-white uppercase tracking-tight leading-none">{profile.name}</h4>
                        <p class="text-[9px] font-bold text-gray-600 uppercase tracking-[3px] mt-2 italic">{profile.description || 'No directives defined.'}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-black/40 border border-white/5 p-4 rounded-xl">
                            <span class="block text-[7px] font-black text-emerald-900 uppercase tracking-widest mb-1">Frequency</span>
                            <span class="text-sm font-mono text-emerald-400 font-black uppercase">{profile.action_frequency_minutes}m</span>
                        </div>
                        <div class="bg-black/40 border border-white/5 p-4 rounded-xl">
                            <span class="block text-[7px] font-black text-gray-600 uppercase tracking-widest mb-1">Sectors</span>
                            <span class="text-sm font-mono text-white font-black uppercase">{profile.users_count || 0}</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between text-[8px] font-black uppercase tracking-widest">
                            <span class="text-red-500">Attack</span>
                            <span class="text-white">{profile.weight_attack}%</span>
                        </div>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-red-500" style="width: {profile.weight_attack}%"></div>
                        </div>

                        <div class="flex justify-between text-[8px] font-black uppercase tracking-widest">
                            <span class="text-blue-500">Build</span>
                            <span class="text-white">{profile.weight_build}%</span>
                        </div>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500" style="width: {profile.weight_build}%"></div>
                        </div>

                        <div class="flex justify-between text-[8px] font-black uppercase tracking-widest">
                            <span class="text-amber-500">Train</span>
                            <span class="text-white">{profile.weight_train}%</span>
                        </div>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-500" style="width: {profile.weight_train}%"></div>
                        </div>

                        <div class="flex justify-between text-[8px] font-black uppercase tracking-widest">
                            <span class="text-cyan-500">Explore</span>
                            <span class="text-white">{profile.weight_explore}%</span>
                        </div>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-cyan-500" style="width: {profile.weight_explore}%"></div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3 mt-8 relative z-10">
                    <button onclick={() => onDelete(profile.id)} class="w-12 h-12 rounded-xl bg-red-950/20 text-red-500 border border-red-900/30 flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500 hover:text-white transition-all">✕</button>
                    <button onclick={() => onInspect(profile)} class="flex-grow py-4 bg-emerald-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-[0_0_20px_rgba(16,185,129,0.2)]">Calibrate Profile</button>
                </div>
            </div>
        {/each}
    </div>
</div>
