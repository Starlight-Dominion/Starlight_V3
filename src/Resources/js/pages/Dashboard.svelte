<script>
  import { onMount } from 'svelte';
  import { fade, slide } from 'svelte/transition';
  import { game } from '../stores/gameStore.svelte.js';
  import AiAdvisor from '../components/AiAdvisor.svelte';
  
  let { 
    user = {}, 
    economy = {}, 
    military = {}, 
    alliance = {}, 
    advisor = {} 
  } = $props();

  let panels = $state({
    eco: true,
    mil: true,
    pop: true,
    fleet: true,
    esp: true,
    structure: true,
    sec: true
  });

  function formatNumber(num) {
    if (num === undefined || num === null) return '0';
    return new Intl.NumberFormat().format(num);
  }

  onMount(() => {
    const interval = setInterval(() => {
      if (advisor && advisor.seconds_until_next_turn > 0) {
        advisor.seconds_until_next_turn -= 1;
      }
    }, 1000);

    return () => clearInterval(interval);
  });
</script>

<div in:fade class="grid grid-cols-1 lg:grid-cols-4 gap-4">
    <!-- SIDEBAR ADVISOR -->
    <aside class="lg:col-span-1 space-y-4">
        <AiAdvisor {advisor} />

        {#if game.user?.is_admin}
            <div in:fade class="bg-red-950/20 border border-red-900/30 rounded-lg overflow-hidden shadow-2xl backdrop-blur-md">
                <header class="bg-red-900/20 px-4 py-2 border-b border-red-900/20 flex justify-between items-center">
                    <h2 class="text-red-500 font-title text-[9px] font-black uppercase tracking-[3px]">High Command</h2>
                    <span class="w-1.5 h-1.5 bg-red-600 rounded-full animate-pulse shadow-[0_0_8px_#ff0000]"></span>
                </header>
                <div class="p-3">
                    <a href="/admin" class="block w-full bg-red-900/20 hover:bg-red-900/40 border border-red-900/30 text-red-500 text-center py-2 rounded font-title font-black text-[9px] uppercase tracking-[3px] transition-all">
                        Access Command Center
                    </a>
                </div>
            </div>
        {/if}
    </aside>

    <!-- MAIN DASHBOARD CONTENT -->
    <main class="lg:col-span-3 space-y-4">
        
        <!-- Hero Profile Card (Legacy Aesthetic) -->
        <div class="bg-gray-900/60 border border-white/5 rounded-lg p-6 backdrop-blur-md relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                <span class="text-7xl font-title font-black text-white uppercase tracking-tighter">SIGIL_B</span>
            </div>
            
            <div class="flex flex-col md:flex-row items-center gap-6 relative z-10">
                <div class="relative group">
                    <div class="w-24 h-24 rounded-full border-2 border-gray-700 overflow-hidden bg-black flex items-center justify-center group-hover:border-cyan-500 transition-all duration-500">
                        {#if user.avatar_path}
                            <img src={user.avatar_path} alt="Avatar" class="w-full h-full object-cover" />
                        {:else}
                            <span class="text-gray-700 font-title font-black text-3xl uppercase">{user.character_name?.charAt(0)}</span>
                        {/if}
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-cyan-500 rounded-full border-2 border-gray-900 flex items-center justify-center text-[10px] text-white font-bold">
                        {user.level}
                    </div>
                </div>

                <div class="text-center md:text-left space-y-1">
                    <h2 class="font-title text-3xl text-white uppercase tracking-tighter text-shadow-glow">
                        {user.character_name}
                    </h2>
                    <div class="flex flex-wrap justify-center md:justify-start gap-4 items-center">
                        <span class="text-cyan-400 font-bold text-xs uppercase tracking-widest">Level {user.level} {user.race} {user.class}</span>
                        {#if alliance && alliance.name !== 'None'}
                            <span class="w-1.5 h-1.5 bg-gray-700 rounded-full"></span>
                            <span class="text-gray-400 text-xs font-bold uppercase tracking-widest">
                                Alliance: <span class="text-white">[{alliance.tag}] {alliance.name}</span>
                            </span>
                        {/if}
                    </div>
                    <div class="mt-2 flex gap-4">
                        <div class="text-[9px] uppercase font-black tracking-widest text-gray-500">
                            Net Worth: <span class="text-emerald-500 font-mono">{formatNumber(user.net_worth)} CP</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Economic Overview -->
            <div class="bg-gray-900/40 border border-white/5 rounded-lg p-4 space-y-3 backdrop-blur-sm transition-all hover:bg-gray-900/60">
                <div class="flex items-center justify-between border-b border-white/5 pb-2 mb-2">
                    <h3 class="font-title text-cyan-500 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                        <span class="w-1.5 h-1.5 bg-cyan-500 rounded-full mr-2 shadow-[0_0_5px_#00ffff]"></span>
                        Economic Ledger
                    </h3>
                    <button class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest" onclick={() => panels.eco = !panels.eco}>
                        {panels.eco ? 'Minimize' : 'Expand'}
                    </button>
                </div>
                {#if panels.eco}
                    <div in:slide class="space-y-1.5 font-mono text-[11px]">
                        <div class="flex justify-between text-gray-400"><span>Credits on Hand</span> <span class="text-white font-bold">{formatNumber(user.credits)}</span></div>
                        <div class="flex justify-between text-gray-400"><span>Secure Bank</span> <span class="text-cyan-600 font-bold">{formatNumber(user.banked_credits)}</span></div>
                        <div class="flex justify-between text-gray-400 border-t border-white/5 pt-1.5 mt-1.5">
                            <span class="text-gray-500">Income / Turn</span> 
                            <span class="text-emerald-500 font-bold">+{formatNumber(economy.income_per_turn)} <span class="text-[8px]">CP</span></span>
                        </div>
                    </div>
                {/if}
            </div>

            <!-- Military Command -->
            <div class="bg-gray-900/40 border border-white/5 rounded-lg p-4 space-y-3 backdrop-blur-sm transition-all hover:bg-gray-900/60">
                <div class="flex items-center justify-between border-b border-white/5 pb-2 mb-2">
                    <h3 class="font-title text-red-500 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-2 shadow-[0_0_5px_#ff0000]"></span>
                        Military Command
                    </h3>
                    <button class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest" onclick={() => panels.mil = !panels.mil}>
                        {panels.mil ? 'Minimize' : 'Expand'}
                    </button>
                </div>
                {#if panels.mil}
                    <div in:slide class="space-y-1.5 font-mono text-[11px]">
                        <div class="flex justify-between text-gray-400"><span>Offense Power</span> <span class="text-white font-bold">{formatNumber(military.offense_power)}</span></div>
                        <div class="flex justify-between text-gray-400"><span>Defense Rating</span> <span class="text-white font-bold">{formatNumber(military.defense_rating)}</span></div>
                        <div class="flex justify-between text-gray-400"><span>Tactical Turns</span> <span class="text-cyan-400 font-bold">{formatNumber(user.attack_turns)}</span></div>
                        <div class="flex justify-between text-gray-400 border-t border-white/5 pt-1.5 mt-1.5">
                            <span class="text-gray-500">Combat Record</span> 
                            <span class="text-white font-bold">
                                <span class="text-emerald-500">{military.wins}</span> / <span class="text-red-500">{military.losses}</span>
                            </span>
                        </div>
                    </div>
                {/if}
            </div>

            <!-- Population Census -->
            <div class="bg-gray-900/40 border border-white/5 rounded-lg p-4 space-y-3 backdrop-blur-sm transition-all hover:bg-gray-900/60">
                <div class="flex items-center justify-between border-b border-white/5 pb-2 mb-2">
                    <h3 class="font-title text-emerald-500 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2 shadow-[0_0_5px_#00ff00]"></span>
                        Population Census
                    </h3>
                    <button class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest" onclick={() => panels.pop = !panels.pop}>
                        {panels.pop ? 'Minimize' : 'Expand'}
                    </button>
                </div>
                {#if panels.pop}
                    <div in:slide class="space-y-1.5 font-mono text-[11px]">
                        <div class="flex justify-between text-gray-400"><span>Total Population</span> <span class="text-white font-bold">{formatNumber(economy.total_population)}</span></div>
                        <div class="flex justify-between text-gray-400"><span>Untrained Citizens</span> <span class="text-white font-bold">{formatNumber(user.untrained_citizens)}</span></div>
                        <div class="flex justify-between text-gray-400"><span>Active Workers</span> <span class="text-emerald-600 font-bold">{formatNumber(user.workers)}</span></div>
                        <div class="flex justify-between text-gray-400 border-t border-white/5 pt-1.5 mt-1.5">
                            <span class="text-gray-500">Growth Rate</span> 
                            <span class="text-emerald-500 font-bold">+{formatNumber(economy.citizens_per_turn)} <span class="text-[8px]">CIT / CYCLE</span></span>
                        </div>
                    </div>
                {/if}
            </div>

            <!-- Fleet Composition -->
            <div class="bg-gray-900/40 border border-white/5 rounded-lg p-4 space-y-3 backdrop-blur-sm transition-all hover:bg-gray-900/60">
                <div class="flex items-center justify-between border-b border-white/5 pb-2 mb-2">
                    <h3 class="font-title text-orange-500 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                        <span class="w-1.5 h-1.5 bg-orange-500 rounded-full mr-2 shadow-[0_0_5px_#ff8800]"></span>
                        Fleet Roster
                    </h3>
                    <button class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest" onclick={() => panels.fleet = !panels.fleet}>
                        {panels.fleet ? 'Minimize' : 'Expand'}
                    </button>
                </div>
                {#if panels.fleet}
                    <div in:slide class="space-y-1.5 font-mono text-[11px]">
                        <div class="flex justify-between text-gray-400"><span>Total Military</span> <span class="text-white font-bold">{formatNumber(economy.total_military)}</span></div>
                        <div class="flex justify-between text-gray-400"><span>Frontline Soldiers</span> <span class="text-red-400 font-bold">{formatNumber(user.soldiers)}</span></div>
                        <div class="flex justify-between text-gray-400"><span>Security Guards</span> <span class="text-blue-400 font-bold">{formatNumber(user.guards)}</span></div>
                        <div class="flex justify-between text-gray-400"><span>Detection Sentries</span> <span class="text-orange-400 font-bold">{formatNumber(user.sentries)}</span></div>
                        <div class="flex justify-between text-gray-400"><span>Espionage Agents</span> <span class="text-purple-400 font-bold">{formatNumber(user.spies)}</span></div>
                    </div>
                {/if}
            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Espionage Overview -->
            <div class="bg-gray-900/40 border border-white/5 rounded-lg p-4 space-y-3 backdrop-blur-sm transition-all hover:bg-gray-900/60">
                <div class="flex items-center justify-between border-b border-white/5 pb-2 mb-2">
                    <h3 class="font-title text-purple-500 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                        <span class="w-1.5 h-1.5 bg-purple-500 rounded-full mr-2 shadow-[0_0_5px_#8800ff]"></span>
                        Espionage Intel
                    </h3>
                    <button class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest" onclick={() => panels.esp = !panels.esp}>
                        {panels.esp ? 'Minimize' : 'Expand'}
                    </button>
                </div>
                {#if panels.esp}
                    <div in:slide class="space-y-1.5 font-mono text-[11px]">
                        <div class="flex justify-between text-gray-400"><span>Infiltration Power</span> <span class="text-purple-400 font-bold">{formatNumber(military.spy_offense)}</span></div>
                        <div class="flex justify-between text-gray-400"><span>Counter-Intel Rating</span> <span class="text-orange-400 font-bold">{formatNumber(military.sentry_defense)}</span></div>
                    </div>
                {/if}
            </div>

            <!-- Structure Status -->
            <div class="bg-gray-900/40 border border-white/5 rounded-lg p-4 space-y-3 backdrop-blur-sm transition-all hover:bg-gray-900/60">
                <div class="flex items-center justify-between border-b border-white/5 pb-2 mb-2">
                    <h3 class="font-title text-emerald-600 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                        <span class="w-1.5 h-1.5 bg-emerald-600 rounded-full mr-2 shadow-[0_0_5px_#00aa00]"></span>
                        Foundation Status
                    </h3>
                    <button class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest" onclick={() => panels.structure = !panels.structure}>
                        {panels.structure ? 'Minimize' : 'Expand'}
                    </button>
                </div>
                {#if panels.structure}
                    <div in:slide class="space-y-3">
                        <div class="flex justify-between items-center text-[10px] font-mono text-gray-400 uppercase tracking-widest">
                            <span>Integrity</span>
                            <span class="text-white font-bold">{formatNumber(game.user?.dominion?.foundation_hp || 0)} / {formatNumber(game.user?.dominion?.foundation_max_hp || 0)}</span>
                        </div>
                        <div class="w-full bg-black/60 rounded-full h-2 border border-white/5 overflow-hidden">
                            <div 
                                class="bg-emerald-500 h-full rounded-full transition-all duration-1000 shadow-[0_0_10px_#00ff00]" 
                                style="width: {(game.user?.dominion?.foundation_hp / game.user?.dominion?.foundation_max_hp) * 100}%"
                            ></div>
                        </div>
                        <p class="text-[9px] text-gray-500 italic uppercase tracking-tighter">Planetary core remains stable.</p>
                    </div>
                {/if}
            </div>

        </div>

        <!-- Security Information -->
        <div class="bg-black/40 border border-white/5 rounded-lg p-4 space-y-3 backdrop-blur-sm transition-all hover:bg-black/60">
            <div class="flex items-center justify-between border-b border-white/5 pb-2 mb-2">
                <h3 class="font-title text-gray-400 flex items-center uppercase text-[10px] font-black tracking-[2px]">
                    <span class="w-1.5 h-1.5 bg-gray-600 rounded-full mr-2"></span>
                    Terminal Access Logs
                </h3>
                <button class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest" onclick={() => panels.sec = !panels.sec}>
                    {panels.sec ? 'Minimize' : 'Expand'}
                </button>
            </div>
            {#if panels.sec}
                <div in:slide class="grid grid-cols-1 md:grid-cols-2 gap-4 font-mono text-[10px]">
                    <div class="flex justify-between text-gray-600"><span>Uplink Protocol</span> <span class="text-white font-bold">ST-SEC-V4</span></div>
                    <div class="flex justify-between text-gray-600"><span>Terminal IP</span> <span class="text-white font-bold">{user.previous_login_ip}</span></div>
                    <div class="flex justify-between text-gray-600"><span>Last Signal</span> <span class="text-white font-bold">{user.previous_login_at}</span></div>
                    <div class="flex justify-between text-gray-600"><span>Neural Sync</span> <span class="text-emerald-500 font-bold">100% STABLE</span></div>
                </div>
            {/if}
        </div>

    </main>
</div>

<style>
  .font-title { font-family: 'Orbitron', sans-serif; }
  .text-shadow-glow {
    text-shadow: 0 0 10px rgba(6, 182, 212, 0.5);
  }
</style>
