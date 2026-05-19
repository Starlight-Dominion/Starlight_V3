<?php

declare(strict_types=1);

/**
 * Shadow Reign - Armory Loadouts Configuration
 * Theme: Medieval Fantasy / Arcane Military
 */

return [
    'sell_loss_multiplier' => 0.5, // 50% refund on sale
    'upgrade_costs' => [
        1 => 500000,
        2 => 1000000,
        3 => 2500000,
        4 => 5000000,
        5 => 10000000,
        6 => 25000000,
        7 => 50000000,
        8 => 100000000,
        9 => 250000000,
        10 => 500000000,
    ],
    'loadouts' => [
        'soldiers' => [
            'title' => 'Vanguard Offensive Loadout',
            'categories' => [
                'main_weapon' => [
                    'title' => 'Heavy Martial Weapons',
                    'slots' => 1,
                    'items' => [
                        'iron_claymore' => ['name' => 'Iron Claymore', 'attack' => 40, 'cost' => 800, 'notes' => 'Basic, reliable steel.'],
                        'tempered_greatsword' => ['name' => 'Tempered Greatsword', 'attack' => 60, 'cost' => 1200, 'notes' => 'High penetration, slower swing.', 'requires' => 'iron_claymore', 'armory_level_req' => 1],
                        'runic_warhammer' => ['name' => 'Runic Warhammer', 'attack' => 75, 'cost' => 1700, 'notes' => 'Crushing power, slightly unwieldy.', 'requires' => 'tempered_greatsword', 'armory_level_req' => 2],
                        'dragonbone_cleaver' => ['name' => 'Dragonbone Cleaver', 'attack' => 90, 'cost' => 2200, 'notes' => 'Strikes with ancient fury.', 'requires' => 'runic_warhammer', 'armory_level_req' => 3],
                        'mythic_soul_blade' => ['name' => 'Mythic Soul Blade', 'attack' => 160, 'cost' => 3000, 'notes' => 'Extremely strong, high cost artifact.', 'requires' => 'dragonbone_cleaver', 'armory_level_req' => 4],
                    ]
                ],
                'sidearm' => [
                    'title' => 'Off-Hand Weapons',
                    'slots' => 1,
                    'items' => [
                        'steel_shortsword' => ['name' => 'Steel Shortsword', 'attack' => 25, 'cost' => 300, 'notes' => 'Basic secondary sidearm.'],
                        'parrying_dagger' => ['name' => 'Parrying Dagger', 'attack' => 30, 'cost' => 400, 'notes' => 'Weak but bypasses shields briefly.', 'requires' => 'steel_shortsword', 'armory_level_req' => 1],
                        'serrated_kukri' => ['name' => 'Serrated Kukri', 'attack' => 35, 'cost' => 500, 'notes' => 'Bleeding rounds, bonus vs. light armor.', 'requires' => 'parrying_dagger', 'armory_level_req' => 2],
                        'hand_crossbow' => ['name' => 'Hand Crossbow', 'attack' => 45, 'cost' => 700, 'notes' => 'Burst damage, close range.', 'requires' => 'serrated_kukri', 'armory_level_req' => 3],
                        'phoenix_revolver' => ['name' => 'Phoenix Revolver', 'attack' => 75, 'cost' => 900, 'notes' => 'High crit chance, slower reload.', 'requires' => 'hand_crossbow', 'armory_level_req' => 4],
                    ]
                ],
                'melee' => [
                    'title' => 'Melee Weapons',
                    'slots' => 1,
                    'items' => [
                        'combat_knife' => ['name' => 'Combat Knife', 'attack' => 10, 'cost' => 100, 'notes' => 'Quick, cheap.'],
                        'shock_baton' => ['name' => 'Shock Baton', 'attack' => 20, 'cost' => 250, 'notes' => 'Stuns briefly, low raw damage.', 'requires' => 'combat_knife', 'armory_level_req' => 1],
                        'energy_blade' => ['name' => 'Energy Blade', 'attack' => 30, 'cost' => 400, 'notes' => 'Ignores armor.', 'requires' => 'shock_baton', 'armory_level_req' => 2],
                        'vibro_axe' => ['name' => 'Vibro Axe', 'attack' => 40, 'cost' => 600, 'notes' => 'Heavy, great vs. fortifications.', 'requires' => 'energy_blade', 'armory_level_req' => 3],
                        'plasma_sword' => ['name' => 'Plasma Sword', 'attack' => 70, 'cost' => 800, 'notes' => 'High damage, rare.', 'requires' => 'vibro_axe', 'armory_level_req' => 4],
                    ]
                ],
                'headgear' => [
                    'title' => 'Martial Head Gear',
                    'slots' => 1,
                    'items' => [
                        'leather_cowl' => ['name' => 'Leather Cowl', 'attack' => 5, 'cost' => 150, 'notes' => 'Accuracy boost.'],
                        'scout_visor' => ['name' => 'Scout Visor', 'attack' => 10, 'cost' => 300, 'notes' => 'Detects stealth.', 'requires' => 'leather_cowl', 'armory_level_req' => 1],
                        'knights_great_helm' => ['name' => 'Knights Great Helm', 'attack' => 15, 'cost' => 500, 'notes' => 'Defense bonus, slight weight penalty.', 'requires' => 'scout_visor', 'armory_level_req' => 2],
                        'neural_circlet' => ['name' => 'Neural Circlet', 'attack' => 20, 'cost' => 700, 'notes' => 'Faster reactions, boosts all attacks.', 'requires' => 'knights_great_helm', 'armory_level_req' => 3],
                        'wraith_hood' => ['name' => 'Wraith Hood', 'attack' => 50, 'cost' => 1000, 'notes' => 'Stealth advantage, minimal armor.', 'requires' => 'neural_circlet', 'armory_level_req' => 4],
                    ]
                ],
                'explosives' => [
                    'title' => 'Alchemical Arts',
                    'slots' => 1,
                    'items' => [
                        'pitch_pot' => ['name' => 'Pitch Pot', 'attack' => 30, 'cost' => 200, 'notes' => 'Basic flammable mixture.'],
                        'hellfire_urn' => ['name' => 'Hellfire Urn', 'attack' => 45, 'cost' => 400, 'notes' => 'Sticky flames that burn hotter.', 'requires' => 'pitch_pot', 'armory_level_req' => 1],
                        'lightning_vial' => ['name' => 'Lightning Vial', 'attack' => 50, 'cost' => 600, 'notes' => 'Releases electrical arc on impact.', 'requires' => 'hellfire_urn', 'armory_level_req' => 2],
                        'swarm_jar' => ['name' => 'Swarm Jar', 'attack' => 70, 'cost' => 900, 'notes' => 'Aggressive hornets shred troops.', 'requires' => 'lightning_vial', 'armory_level_req' => 3],
                        'void_catalyst' => ['name' => 'Void Catalyst', 'attack' => 150, 'cost' => 1400, 'notes' => 'Creates a gravitational collapse.', 'requires' => 'swarm_jar', 'armory_level_req' => 4],
                    ]
                ]
            ]
        ],
        'guards' => [
            'title' => 'Bulwark Defensive Loadout',
            'categories' => [
                'armor_suit' => [
                    'title' => 'Heavy Defensive Armor',
                    'slots' => 1,
                    'items' => [
                        'padded_gambeson' => ['name' => 'Padded Gambeson', 'defense' => 40, 'cost' => 800, 'notes' => 'Basic protection, minimal weight.'],
                        'reinforced_chainmail' => ['name' => 'Reinforced Chainmail', 'defense' => 80, 'cost' => 1200, 'notes' => 'Strong against slashing blades.', 'requires' => 'padded_gambeson', 'armory_level_req' => 1],
                        'knights_plate' => ['name' => 'Knights Plate', 'defense' => 115, 'cost' => 1700, 'notes' => 'Reduces magical damage, high defense.', 'requires' => 'reinforced_chainmail', 'armory_level_req' => 2],
                        'mithril_cuirass' => ['name' => 'Mithril Cuirass', 'defense' => 140, 'cost' => 2200, 'notes' => 'Lightweight, extreme damage reduction.', 'requires' => 'knights_plate', 'armory_level_req' => 3],
                        'aegis_god_armor' => ['name' => 'Aegis God Armor', 'defense' => 300, 'cost' => 3000, 'notes' => 'Divine shield generator, top-tier defense.', 'requires' => 'mithril_cuirass', 'armory_level_req' => 4],
                    ]
                ],
                'secondary_defense' => [
                    'title' => 'Arcane Wards',
                    'slots' => 1,
                    'items' => [
                        'iron_amulet' => ['name' => 'Iron Amulet', 'defense' => 15, 'cost' => 300, 'notes' => 'Reduces physical impact damage.'],
                        'blessed_rosary' => ['name' => 'Blessed Rosary', 'defense' => 20, 'cost' => 400, 'notes' => 'Lowers magical damage.', 'requires' => 'iron_amulet', 'armory_level_req' => 1],
                        'deflector_relic' => ['name' => 'Deflector Relic', 'defense' => 25, 'cost' => 500, 'notes' => 'Partial barrier that recharges slowly.', 'requires' => 'blessed_rosary', 'armory_level_req' => 2],
                        'guardian_idol' => ['name' => 'Guardian Idol', 'defense' => 35, 'cost' => 700, 'notes' => 'Assists defense, counters attackers.', 'requires' => 'deflector_relic', 'armory_level_req' => 3],
                        'life_bloom_gem' => ['name' => 'Life-Bloom Gem', 'defense' => 75, 'cost' => 900, 'notes' => 'Heals user periodically during battle.', 'requires' => 'guardian_idol', 'armory_level_req' => 4],
                    ]
                ],
                'melee_counter' => [
                    'title' => 'Melee Countermeasures',
                    'slots' => 1,
                    'items' => [
                        'parrying_dagger_def' => ['name' => 'Parrying Dagger', 'defense' => 10, 'cost' => 100, 'notes' => 'Minimal, last-ditch block.'],
                        'spiked_buckler' => ['name' => 'Spiked Buckler', 'defense' => 20, 'cost' => 250, 'notes' => 'Electrocutes melee attackers.', 'requires' => 'parrying_dagger_def'],
                        'guard_sword_kit' => ['name' => 'Guard Sword Kit', 'defense' => 30, 'cost' => 400, 'notes' => 'Defensive melee stance.', 'requires' => 'spiked_buckler'],
                        'energy_shield' => ['name' => 'Energy Shield', 'defense' => 40, 'cost' => 600, 'notes' => 'Small but strong energy shield.', 'requires' => 'guard_sword_kit'],
                        'photon_barrier' => ['name' => 'Photon Barrier', 'defense' => 70, 'cost' => 800, 'notes' => 'Creates a light shield, blocks most hits.', 'requires' => 'energy_shield'],
                    ]
                ],
                'defensive_headgear' => [
                    'title' => 'Defensive Greathelms',
                    'slots' => 1,
                    'items' => [
                        'soldier_helmet' => ['name' => 'Soldier Helmet', 'defense' => 5, 'cost' => 150, 'notes' => 'Basic head protection.'],
                        'armored_visor' => ['name' => 'Armored Visor', 'defense' => 10, 'cost' => 300, 'notes' => 'Lightweight and strong.', 'requires' => 'soldier_helmet'],
                        'reinforced_greathelm' => ['name' => 'Reinforced Greathelm', 'defense' => 15, 'cost' => 500, 'notes' => 'Excellent impact resistance.', 'requires' => 'armored_visor'],
                        'neural_guard_mask' => ['name' => 'Neural Guard Mask', 'defense' => 20, 'cost' => 700, 'notes' => 'Protects against psychic effects.', 'requires' => 'reinforced_greathelm'],
                        'divine_aegis_helm' => ['name' => 'Divine Aegis Helm', 'defense' => 45, 'cost' => 1000, 'notes' => 'High-tier divine head defense.', 'requires' => 'neural_guard_mask'],
                    ]
                ],
                'defensive_deployable' => [
                    'title' => 'Defensive Deployables',
                    'slots' => 1,
                    'items' => [
                        'portable_barricade' => ['name' => 'Portable Barricade', 'defense' => 30, 'cost' => 200, 'notes' => 'Small personal barrier.'],
                        'arcane_wall_scroll' => ['name' => 'Arcane Wall Scroll', 'defense' => 45, 'cost' => 400, 'notes' => 'Deployable energy wall.', 'requires' => 'portable_barricade'],
                        'mana_scrambler' => ['name' => 'Mana Scrambler', 'defense' => 50, 'cost' => 600, 'notes' => 'Nullifies enemy spells.', 'requires' => 'arcane_wall_scroll'],
                        'repair_pylon' => ['name' => 'Repair Pylon', 'defense' => 70, 'cost' => 900, 'notes' => 'Repairs nearby allies.', 'requires' => 'mana_scrambler'],
                        'fortress_dome_relic' => ['name' => 'Fortress Dome Relic', 'defense' => 150, 'cost' => 1400, 'notes' => 'Creates a temporary invulnerable dome.', 'requires' => 'repair_pylon'],
                    ]
                ]
            ]
        ],
        'sentries' => [
            'title' => 'Garrison Defensive Loadout',
            'categories' => [
                'shields' => [
                    'title' => 'Tower Shields',
                    'slots' => 1,
                    'items' => [
                        'iron_shield' => ['name' => 'Iron Shield', 'defense' => 50, 'cost' => 900, 'notes' => 'Standard issue shield.'],
                        'great_tower_shield' => ['name' => 'Great Tower Shield', 'defense' => 70, 'cost' => 1300, 'notes' => 'Heavy, but provides excellent cover.', 'requires' => 'iron_shield', 'armory_level_req' => 1],
                        'crusader_shield' => ['name' => 'Crusader Shield', 'defense' => 85, 'cost' => 1800, 'notes' => 'Wider, better for holding a line.', 'requires' => 'great_tower_shield', 'armory_level_req' => 2],
                        'garrison_shield' => ['name' => 'Garrison Shield', 'defense' => 100, 'cost' => 2300, 'notes' => 'Can be deployed as temporary cover.', 'requires' => 'crusader_shield', 'armory_level_req' => 3],
                        'bulwark_shield' => ['name' => 'Bulwark Shield', 'defense' => 130, 'cost' => 3100, 'notes' => 'Nearly impenetrable frontal defense.', 'requires' => 'garrison_shield', 'armory_level_req' => 4],
                    ]
                ],
                'secondary_defensive_systems' => [
                    'title' => 'Secondary Bastion Systems',
                    'slots' => 1,
                    'items' => [
                        'arrow_catcher' => ['name' => 'Arrow Catcher', 'defense' => 20, 'cost' => 350, 'notes' => 'Intercepts incoming projectiles.'],
                        'aura_relic' => ['name' => 'Aura Relic', 'defense' => 25, 'cost' => 450, 'notes' => 'Provides damage shield to nearby allies.', 'requires' => 'arrow_catcher', 'armory_level_req' => 1],
                        'guardian_oath' => ['name' => 'Guardian Oath', 'defense' => 30, 'cost' => 550, 'notes' => 'Automatically diverts power to shields.', 'requires' => 'aura_relic', 'armory_level_req' => 2],
                        'bastion_stance' => ['name' => 'Bastion Stance', 'defense' => 40, 'cost' => 750, 'notes' => 'Greatly increases defense when stationary.', 'requires' => 'guardian_oath', 'armory_level_req' => 3],
                        'fortress_protocol' => ['name' => 'Fortress Protocol', 'defense' => 50, 'cost' => 950, 'notes' => 'Links with other sentries.', 'requires' => 'bastion_stance', 'armory_level_req' => 4],
                    ]
                ],
                'shield_bash' => [
                    'title' => 'Shield Bash Arts',
                    'slots' => 1,
                    'items' => [
                        'concussive_shove' => ['name' => 'Concussive Shove', 'defense' => 15, 'cost' => 150, 'notes' => 'Knocks back melee attackers.'],
                        'kinetic_ram' => ['name' => 'Kinetic Ram', 'defense' => 25, 'cost' => 300, 'notes' => 'A powerful forward shield bash.', 'requires' => 'concussive_shove', 'armory_level_req' => 1],
                        'repulsor_wave' => ['name' => 'Repulsor Wave', 'defense' => 35, 'cost' => 450, 'notes' => 'Pushes away all nearby enemies.', 'requires' => 'kinetic_ram', 'armory_level_req' => 2],
                        'overcharge_burst' => ['name' => 'Overcharge Burst', 'defense' => 45, 'cost' => 650, 'notes' => 'Releases a blast on shield break.', 'requires' => 'repulsor_wave', 'armory_level_req' => 3],
                        'sentinels_wrath' => ['name' => 'Sentinel\'s Wrath', 'defense' => 55, 'cost' => 850, 'notes' => 'Devastating slam that stuns enemies.', 'requires' => 'overcharge_burst', 'armory_level_req' => 4],
                    ]
                ],
                'helmets' => [
                    'title' => 'Heavy Helmets',
                    'slots' => 1,
                    'items' => [
                        'sentry_helmet' => ['name' => 'Sentry Helmet', 'defense' => 10, 'cost' => 200, 'notes' => 'Standard issue helmet.'],
                        'reinforced_visor' => ['name' => 'Reinforced Visor', 'defense' => 15, 'cost' => 350, 'notes' => 'Extra protection against headshots.', 'requires' => 'sentry_helmet', 'armory_level_req' => 1],
                        'commanders_helm' => ['name' => 'Commander\'s Helm', 'defense' => 20, 'cost' => 550, 'notes' => 'Increases effectiveness of nearby units.', 'requires' => 'reinforced_visor', 'armory_level_req' => 2],
                        'juggernaut_helm' => ['name' => 'Juggernaut Helm', 'defense' => 25, 'cost' => 750, 'notes' => 'Unmatched heavy protection.', 'requires' => 'commanders_helm', 'armory_level_req' => 3],
                        'praetorian_helm' => ['name' => 'Praetorian Helm', 'defense' => 30, 'cost' => 1050, 'notes' => 'The ultimate in defensive headgear.', 'requires' => 'juggernaut_helm', 'armory_level_req' => 4],
                    ]
                ],
                'fortifications' => [
                    'title' => 'Static Defenses',
                    'slots' => 1,
                    'items' => [
                        'wooden_palisade' => ['name' => 'Wooden Palisade', 'defense' => 35, 'cost' => 250, 'notes' => 'Creates a small piece of cover.'],
                        'stone_barricade' => ['name' => 'Stone Barricade', 'defense' => 50, 'cost' => 450, 'notes' => 'Larger, more durable cover.', 'requires' => 'wooden_palisade', 'armory_level_req' => 1],
                        'guard_tower' => ['name' => 'Guard Tower', 'defense' => 55, 'cost' => 650, 'notes' => 'Provides better vantage point.', 'requires' => 'stone_barricade', 'armory_level_req' => 2],
                        'iron_bunker' => ['name' => 'Iron Bunker', 'defense' => 75, 'cost' => 950, 'notes' => 'Heavily fortified structure.', 'requires' => 'guard_tower', 'armory_level_req' => 3],
                        'citadel_fortress' => ['name' => 'Citadel Fortress', 'defense' => 105, 'cost' => 1450, 'notes' => 'Massive, nearly indestructible fortification.', 'requires' => 'iron_bunker', 'armory_level_req' => 4],
                    ]
                ]
            ]
        ],
        'spies' => [
            'title' => 'Shadow Infiltration Loadout',
            'categories' => [
                'silenced_projectors' => [
                    'title' => 'Silent Projectors',
                    'slots' => 1,
                    'items' => [
                        'throwing_knives' => ['name' => 'Throwing Knives', 'attack' => 30, 'cost' => 700, 'notes' => 'Standard issue spy tool.'],
                        'blowgun' => ['name' => 'Blowgun', 'attack' => 50, 'cost' => 1100, 'notes' => 'Fires silent, poisoned darts.', 'requires' => 'throwing_knives', 'armory_level_req' => 1],
                        'hand_crossbow_spy' => ['name' => 'Hand Crossbow', 'attack' => 65, 'cost' => 1600, 'notes' => 'Can disable enemy electronics.', 'requires' => 'blowgun', 'armory_level_req' => 2],
                        'phantom_bow' => ['name' => 'Phantom Bow', 'attack' => 80, 'cost' => 2100, 'notes' => 'Fires arrows that phase through cover.', 'requires' => 'hand_crossbow_spy', 'armory_level_req' => 3],
                        'void_stalker_bow' => ['name' => 'Void Stalker Bow', 'attack' => 110, 'cost' => 2900, 'notes' => 'The ultimate stealth weapon.', 'requires' => 'phantom_bow', 'armory_level_req' => 4],
                    ]
                ],
                'cloaking_disruption' => [
                    'title' => 'Invisibility & Deception',
                    'slots' => 1,
                    'items' => [
                        'shadow_field' => ['name' => 'Shadow Field', 'attack' => 10, 'cost' => 250, 'notes' => 'Makes the user harder to detect.'],
                        'chameleon_cloak' => ['name' => 'Chameleon Cloak', 'attack' => 15, 'cost' => 350, 'notes' => 'Matches the environment.', 'requires' => 'shadow_field', 'armory_level_req' => 1],
                        'illusion_scroll' => ['name' => 'Illusion Scroll', 'defense' => 20, 'cost' => 450, 'notes' => 'Creates a duplicate to confuse enemies.', 'requires' => 'chameleon_cloak', 'armory_level_req' => 2],
                        'phase_shift_relic' => ['name' => 'Phase Shift Relic', 'attack' => 25, 'cost' => 650, 'notes' => 'Allows temporary phasing through objects.', 'requires' => 'illusion_scroll', 'armory_level_req' => 3],
                        'void_shroud' => ['name' => 'Void Shroud', 'attack' => 30, 'cost' => 850, 'notes' => 'Renders the user nearly invisible.', 'requires' => 'phase_shift_relic', 'armory_level_req' => 4],
                    ]
                ],
                'concealed_blades' => [
                    'title' => 'Assassination Blades',
                    'slots' => 1,
                    'items' => [
                        'hidden_dagger' => ['name' => 'Hidden Dagger', 'attack' => 15, 'cost' => 120, 'notes' => 'Small, concealed blade.'],
                        'poisoned_blade' => ['name' => 'Poisoned Blade', 'attack' => 25, 'cost' => 270, 'notes' => 'Deals damage over time.', 'requires' => 'hidden_dagger', 'armory_level_req' => 1],
                        'obsidian_edge' => ['name' => 'Obsidian Edge', 'attack' => 35, 'cost' => 420, 'notes' => 'Can cut through most armor.', 'requires' => 'poisoned_blade', 'armory_level_req' => 2],
                        'shadow_blade' => ['name' => 'Shadow Blade', 'attack' => 45, 'cost' => 620, 'notes' => 'A blade made of pure darkness.', 'requires' => 'obsidian_edge', 'armory_level_req' => 3],
                        'abyssal_razor' => ['name' => 'Abyssal Razor', 'attack' => 55, 'cost' => 820, 'notes' => 'Can cut through reality itself.', 'requires' => 'shadow_blade', 'armory_level_req' => 4],
                    ]
                ],
                'intel_suite' => [
                    'title' => 'Intel & Vision Arts',
                    'slots' => 1,
                    'items' => [
                        'scout_goggles' => ['name' => 'Scout Goggles', 'defense' => 5, 'cost' => 170, 'notes' => 'Basic enemy positions intel.'],
                        'threat_sigil' => ['name' => 'Threat Sigil', 'defense' => 10, 'cost' => 320, 'notes' => 'Highlights nearby threats.', 'requires' => 'scout_goggles', 'armory_level_req' => 1],
                        'neural_link' => ['name' => 'Neural Link', 'defense' => 15, 'cost' => 520, 'notes' => 'Allows hacking enemy systems.', 'requires' => 'threat_sigil', 'armory_level_req' => 2],
                        'mind_scry' => ['name' => 'Mind Scry', 'defense' => 20, 'cost' => 720, 'notes' => 'Can read nearby enemy thoughts.', 'requires' => 'neural_link', 'armory_level_req' => 3],
                        'oracle_eye' => ['name' => 'Oracle Eye', 'defense' => 25, 'cost' => 1020, 'notes' => 'Can predict enemy movements.', 'requires' => 'mind_scry', 'armory_level_req' => 4],
                    ]
                ],
                'infiltration_gadgets' => [
                    'title' => 'Infiltration Tools',
                    'slots' => 1,
                    'items' => [
                        'grappling_chain' => ['name' => 'Grappling Chain', 'attack' => 5, 'cost' => 220, 'notes' => 'Reach high places.'],
                        'smoke_bomb' => ['name' => 'Smoke Bomb', 'attack' => 10, 'cost' => 420, 'notes' => 'Cloud of smoke to obscure vision.', 'requires' => 'grappling_chain', 'armory_level_req' => 1],
                        'mana_burst' => ['name' => 'Mana Burst', 'attack' => 15, 'cost' => 620, 'notes' => 'Disables enemy magic items.', 'requires' => 'smoke_bomb', 'armory_level_req' => 2],
                        'mirror_decoy' => ['name' => 'Mirror Decoy', 'attack' => 20, 'cost' => 920, 'notes' => 'Holographic decoy to distract.', 'requires' => 'mana_burst', 'armory_level_req' => 3],
                        'blink_stone' => ['name' => 'Blink Stone', 'attack' => 25, 'cost' => 1420, 'notes' => 'Teleport short distances.', 'requires' => 'mirror_decoy', 'armory_level_req' => 4],
                    ]
                ]
            ]
        ]
    ]
];