<script>
    import { fade } from 'svelte/transition';
</script>

<div in:fade class="max-w-4xl mx-auto py-32 px-6 space-y-16 pb-40">
    <header class="border-b border-cyan-500/20 pb-12 text-center md:text-left">
        <h1 class="text-5xl md:text-7xl font-title font-black text-white uppercase tracking-tighter text-shadow-glow">Sector Laws</h1>
        <p class="text-cyan-500/60 text-[10px] font-bold uppercase tracking-[8px] mt-4 italic">Operational Protocols & Neural Conduct Agreement</p>
        <div class="mt-6 bg-black/30 border border-cyan-500/15 rounded-xl p-4 md:p-5 text-left">
            <p class="text-[10px] font-black uppercase tracking-[3px] text-cyan-500/80 mb-2">Defined Terms</p>
            <div class="text-[11px] text-gray-500 leading-relaxed space-y-1">
                <p><strong>Dominion</strong>: A Sovereign's kingdom or sector identity (including its designation/name and in-game state).</p>
                <p><strong>High Command</strong>: The governance and moderation team designated to administer Game Services.</p>
                <p><strong>Account</strong>: The registered player identity and credentials used to access Game Services.</p>
                <p><strong>Commander</strong>: Any player account holder using Game Services.</p>
                <p><strong>Sovereign</strong>: The in-game account identity operated by a Commander.</p>
                <p><strong>Game Services</strong>: The Starlight Dominion game, website, and official API platform.</p>
                <p><strong>API Key</strong>: A credential issued through the in-game application flow authorizing API access.</p>
                <p><strong>User Content</strong>: Any names, text, messages, uploads, or other content submitted through Game Services.</p>
                <p><strong>Personal Information</strong>: Information that identifies or can reasonably identify a real person.</p>
                <p><strong>Prohibited Conduct</strong>: Any conduct disallowed under these Terms or official Community Guidelines.</p>
                <p><strong>Automation</strong>: Scripts, bots, macros, or workflows performing gameplay or API actions without direct human initiation for each action.</p>
                <p><strong>Exploit</strong>: Intentional use of bugs, unintended mechanics, or security weaknesses for unfair advantage.</p>
                <p><strong>Circumvention</strong>: Attempts to bypass restrictions, rate limits, bans, suspensions, or safety controls.</p>
                <p><strong>Enforcement Action</strong>: Warning, content removal, timeout, key suspension or revocation, account suspension, or permanent ban.</p>
                <p><strong>Third-Party Platform</strong>: External services used with Game Services, including Discord.</p>
                <p><strong>Appeal</strong>: A request for High Command review of an Enforcement Action, when review is permitted.</p>
            </div>
        </div>
    </header>

    <div class="space-y-8">
        <!-- 01. SOVEREIGN IDENTITY -->
        <div class="bg-dark-translucent border border-white/5 rounded-3xl p-10 shadow-2xl relative overflow-hidden group hover:border-cyan-500/30 transition-all">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                IDENTITY
            </div>
            <section class="space-y-6 relative z-10">
                <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl flex items-center gap-4">
                    <span class="text-cyan-500 text-sm">01.</span> Sovereign Identity
                </h2>
                <div class="text-gray-400 text-sm leading-relaxed space-y-4 italic">
                    <p>Each Commander may operate one Sovereign (Account). Multi-accounting, account sharing, and other attempts to bypass account boundaries are material violations of these Terms.</p>
                    <p>Account names and profile changes must be used in good faith. Name changes intended for Circumvention, deception, or group-targeted punishment are Prohibited Conduct.</p>
                    <p>Confirmed attempts to weaponize name changes for group punishment or accountability evasion will result in a permanent game ban.</p>
                </div>
            </section>
        </div>

        <!-- 02. NEURAL API PROTOCOLS -->
        <div class="bg-dark-translucent border border-cyan-500/10 rounded-3xl p-10 shadow-2xl relative overflow-hidden group hover:border-cyan-500/30 transition-all">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                PROTOCOLS
            </div>
            <section class="space-y-6 relative z-10">
                <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl flex items-center gap-4">
                    <span class="text-cyan-400 text-sm">02.</span> Neural API Protocols
                </h2>
                <div class="text-gray-300 text-sm leading-relaxed space-y-4">
                    <p>High Command permits third-party tools and integrations only through the official API and only with a valid approved API Key tied to your Account.</p>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-3">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 2.A: API Key Application and Use</span>
                        <p class="text-xs text-gray-500 italic">API Keys are requested via the in-game application flow and may be approved, denied, suspended, or revoked at High Command discretion to protect service stability and fair play.</p>
                        <p class="text-xs text-gray-500 italic">Do not share, sell, lease, or transfer API Keys. The Commander operating the Account is responsible for all activity performed with keys issued to that Account.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-3">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 2.B: Rate Limits and Throughput Controls</span>
                        <p class="text-xs text-gray-500 italic">Rate limits are enforced server-side and are intentionally not published in fixed detail. Limits may vary by endpoint, application, and Game Services health conditions.</p>
                        <p class="text-xs text-gray-500 italic">Repeated over-limit traffic or Circumvention attempts may result in throttling, API Key suspension, or API Key revocation based on severity and repetition.</p>
                    </div>
                    <div class="bg-red-950/20 border border-red-900/30 p-6 rounded-xl space-y-3">
                        <span class="text-[10px] font-black text-red-500 uppercase tracking-widest">Article 2.C: Prohibited Automation and Abuse</span>
                        <p class="text-xs text-red-400 font-bold">Automation of offensive gameplay actions, Exploit development, credential abuse, and intentional service degradation are Prohibited Conduct.</p>
                        <p class="text-[11px] text-gray-500 italic">Standard Enforcement Action may include warning, temporary suspension, then permanent ban. Severe abuse may trigger immediate API Key revocation and Account-level action without prior warning.</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- 03. HIGH COMMAND ENFORCEMENT -->
        <div class="bg-dark-translucent border border-white/5 rounded-3xl p-10 shadow-2xl relative overflow-hidden group hover:border-red-900/20 transition-all">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                LIABILITY
            </div>
            <section class="space-y-6 relative z-10">
                <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl flex items-center gap-4">
                    <span class="text-red-900 text-sm">03.</span> High Command Enforcement
                </h2>
                <div class="text-gray-400 text-sm leading-relaxed space-y-4">
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-3">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 3.A: Core Community Conduct</span>
                        <p class="text-xs text-gray-500 italic">Respect other Commanders. Harassment, hate speech, bullying, discrimination, threats, illegal content, and doxxing are Prohibited Conduct.</p>
                        <p class="text-xs text-gray-500 italic">User Content must not impersonate staff or other members with intent to deceive and must not disclose Personal Information without clear consent.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-3">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 3.B: External Platform Compliance</span>
                        <p class="text-xs text-gray-500 italic">When using Game Services through a Third-Party Platform (including Discord integrations), you must also comply with that platform's terms and community policies.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-3">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 3.C: Community Guidelines Reference</span>
                        <p class="text-xs text-gray-500 italic">Official Community Guidelines are incorporated by reference into these Terms. Violations of those Guidelines constitute Prohibited Conduct and may trigger Enforcement Action.</p>
                    </div>
                    <div class="bg-red-950/20 border border-red-900/30 p-6 rounded-xl space-y-3">
                        <span class="text-[10px] font-black text-red-500 uppercase tracking-widest">Article 3.D: Enforcement Standard</span>
                        <p class="text-xs text-red-400 font-bold">High Command may investigate conduct, request clarifying information, and take proportionate action where violations are confirmed.</p>
                        <p class="text-[11px] text-gray-500 italic">Where practical, Enforcement Action follows an escalating model. Severe violations may result in immediate suspension or permanent removal to protect the community and service integrity. Appeal review, when permitted, is at High Command discretion.</p>
                    </div>
                    <p class="text-[11px] text-gray-500 italic">These Terms are formal warning and notice of enforcement standards. Continued use of Game Services by a Commander or Sovereign constitutes acceptance of this policy framework.</p>
                </div>
            </section>
        </div>

        <!-- 04. SERVICE AND LEGAL FRAMEWORK -->
        <div class="bg-dark-translucent border border-white/5 rounded-3xl p-10 shadow-2xl relative overflow-hidden group hover:border-cyan-500/20 transition-all">
            <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none font-title font-black text-white italic text-4xl uppercase">
                FRAMEWORK
            </div>
            <section class="space-y-6 relative z-10">
                <h2 class="text-white font-title font-black uppercase tracking-[3px] text-xl flex items-center gap-4">
                    <span class="text-cyan-500 text-sm">04.</span> Dominion Service Framework
                </h2>
                <div class="text-gray-400 text-sm leading-relaxed space-y-4">
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.A: Age and Eligibility</span>
                        <p class="text-xs text-gray-500 italic">You must be at least 13 years old (or the minimum age required by your jurisdiction, whichever is higher) and legally capable of accepting these Terms.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.B: Account Security</span>
                        <p class="text-xs text-gray-500 italic">Each Commander is responsible for securing Account credentials and for all activity performed through that Sovereign. Suspected Account compromise must be reported to High Command promptly.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.C: Service Changes and Availability</span>
                        <p class="text-xs text-gray-500 italic">Game Services, balancing systems, APIs, and features may be changed, limited, or discontinued at any time. Game Services are provided on an as-available basis and uninterrupted availability is not guaranteed.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.D: Virtual Assets</span>
                        <p class="text-xs text-gray-500 italic">In-game currency, items, rankings, and similar virtual assets are part of a revocable gameplay license within Game Services. They are not real-world property and are not redeemable for cash unless explicitly stated otherwise by High Command.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.E: Suspension and Termination</span>
                        <p class="text-xs text-gray-500 italic">High Command may apply Enforcement Action, including temporary suspension or permanent termination, for Prohibited Conduct, security risk, abuse, legal compliance, or to protect Game Services integrity.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.F: Appeals and Finality</span>
                        <p class="text-xs text-gray-500 italic">Where permitted, Appeals should be submitted within 14 days of notice of Enforcement Action. High Command decisions after review are final. Certain categories, including illegal content, credible threats, or severe doxxing, may be designated as no-appeal.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.G: Logging and Evidence Use</span>
                        <p class="text-xs text-gray-500 italic">High Command may log gameplay, API, moderation, and security events for abuse prevention, operations, investigations, and dispute resolution. Relevant logs and evidence may be used to support Enforcement Actions.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.H: Bug and Exploit Disclosure</span>
                        <p class="text-xs text-gray-500 italic">Security vulnerabilities and major Exploits must be reported privately to High Command without unreasonable delay. Public disclosure before remediation, or active Exploit abuse, is Prohibited Conduct.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.I: Third-Party Platforms and Services</span>
                        <p class="text-xs text-gray-500 italic">Game Services may integrate with Third-Party Platforms. High Command is not responsible for third-party availability, policy changes, outages, or content, and may restrict integrations where risk or incompatibility is detected.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.J: Warranty Disclaimer and Liability Limits</span>
                        <p class="text-xs text-gray-500 italic">To the maximum extent permitted by law, Game Services are provided as-is and as-available, without warranties of uninterrupted use, fitness, or non-infringement. High Command and operators are not liable for indirect, incidental, consequential, or punitive damages arising from use of Game Services.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.K: Governing Law and Venue</span>
                        <p class="text-xs text-gray-500 italic">These Terms are governed by applicable law in the operator's jurisdiction. Unless otherwise required by law, disputes will be resolved in the courts with proper venue in that jurisdiction.</p>
                    </div>
                    <div class="bg-black/40 border border-cyan-500/20 p-6 rounded-xl space-y-2">
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-widest">Article 4.L: Terms Updates and Notice</span>
                        <p class="text-xs text-gray-500 italic">High Command may update these Terms at any time. Material changes become effective upon posting or reasonable in-service notice. Continued use of Game Services after changes take effect constitutes acceptance of the updated Terms.</p>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <footer class="text-center space-y-6">
        <div class="h-px bg-gradient-to-r from-transparent via-cyan-500/20 to-transparent w-full"></div>
        <p class="text-[10px] text-gray-600 uppercase tracking-[4px] font-black">
            Neural Uplink Active &bull; Compliance is Mandatory &bull; Est. 2026
        </p>
    </footer>
</div>
