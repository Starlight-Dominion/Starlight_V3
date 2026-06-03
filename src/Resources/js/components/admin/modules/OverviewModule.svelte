<script>
    let { stats = {} } = $props();
</script>

<div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl relative overflow-hidden group">
            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[4px] block mb-2">Total Sovereigns</span>
            <span class="text-4xl font-title font-black text-white">{stats.total_users || 0}</span>
            <div class="absolute -bottom-4 -right-4 text-white/5 text-6xl font-black italic select-none group-hover:text-cyan-500/10 transition-colors">USR</div>
        </div>
        <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl relative overflow-hidden group">
            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[4px] block mb-2">Active Sectors</span>
            <span class="text-4xl font-title font-black text-white">{stats.total_kingdoms || 0}</span>
            <div class="absolute -bottom-4 -right-4 text-white/5 text-6xl font-black italic select-none group-hover:text-red-500/10 transition-colors">DOM</div>
        </div>
        <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl relative overflow-hidden group">
            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[4px] block mb-2">Total Wealth</span>
            <span class="text-4xl font-title font-black text-emerald-500">{((stats.total_credits || 0) / 1000000).toFixed(1)}M</span>
            <div class="absolute -bottom-4 -right-4 text-white/5 text-6xl font-black italic select-none group-hover:text-emerald-500/10 transition-colors">CP</div>
        </div>
        <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl relative overflow-hidden group">
            <span class="text-[8px] font-black text-gray-600 uppercase tracking-[4px] block mb-2">Total Population</span>
            <span class="text-4xl font-title font-black text-cyan-400">{((stats.total_citizens || 0) / 1000).toFixed(1)}K</span>
            <div class="absolute -bottom-4 -right-4 text-white/5 text-6xl font-black italic select-none group-hover:text-cyan-400/10 transition-colors">POP</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-dark-translucent border border-white/5 p-8 rounded-3xl space-y-4">
            <h3 class="text-[10px] font-black text-gray-600 uppercase tracking-[4px]">System Status</h3>
            <div class="space-y-2 font-mono">
                <div class="flex justify-between items-center py-2 border-b border-white/5"><span class="text-[10px] uppercase text-gray-500">Database</span><span class="text-[10px] font-black text-cyan-400 uppercase">Connected</span></div>
                <div class="flex justify-between items-center py-2 border-b border-white/5"><span class="text-[10px] uppercase text-gray-500">Heartbeat</span><span class="text-[10px] font-black text-cyan-400 uppercase">Synchronized</span></div>
                <div class="flex justify-between items-center py-2 border-b border-white/5"><span class="text-[10px] uppercase text-gray-500">Total Manpower</span><span class="text-[10px] font-black text-white uppercase">{((stats.total_manpower || 0) / 1000).toFixed(1)}K Units</span></div>
            </div>
        </div>
        <div class="bg-red-950/10 border border-red-900/20 p-8 rounded-3xl space-y-4">
            <h3 class="text-[10px] font-black text-red-500 uppercase tracking-[4px]">Global Warning</h3>
            <p class="text-gray-400 text-[10px] leading-relaxed italic">Changes made within this terminal affect the live production database immediately. Unauthorized modifications constitute sector-level treason.</p>
        </div>
    </div>
</div>
