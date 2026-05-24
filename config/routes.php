<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    // Public Routes
    $r->addRoute('GET', '/', [\sdo\Controllers\HomeController::class, 'index']);
    $r->addRoute('GET', '/about', [\sdo\Controllers\PageController::class, 'about']);
    $r->addRoute('GET', '/rules', [\sdo\Controllers\PageController::class, 'rules']);
    $r->addRoute('GET', '/terms', [\sdo\Controllers\PageController::class, 'terms']);
    $r->addRoute('GET', '/contact', [\sdo\Controllers\PageController::class, 'contact']);

    // Auth Routes
    $r->addRoute('GET', '/login', [\sdo\Controllers\AuthController::class, 'showLogin']);
    $r->addRoute('POST', '/login', [\sdo\Controllers\AuthController::class, 'login']);
    $r->addRoute('GET', '/register', [\sdo\Controllers\AuthController::class, 'showRegister']);
    $r->addRoute('POST', '/register', [\sdo\Controllers\AuthController::class, 'register']);
    $r->addRoute('GET', '/logout', [\sdo\Controllers\AuthController::class, 'logout']);

    // Protected Routes
    $r->addRoute('GET', '/dashboard', [\sdo\Controllers\DashboardController::class, 'index']);
    $r->addRoute('POST', '/settings/profile', [\sdo\Controllers\SettingsController::class, 'updateProfile']);
    $r->addRoute('GET', '/structures', [\sdo\Controllers\StructureController::class, 'index']);
    $r->addRoute('GET', '/structures/foundation', [\sdo\Controllers\FoundationController::class, 'index']);
    $r->addRoute('POST', '/structures/foundation/upgrade', [\sdo\Controllers\FoundationController::class, 'upgrade']);
    $r->addRoute('POST', '/structures/foundation/purchase-upgrade', [\sdo\Controllers\FoundationController::class, 'purchaseUpgrade']);
    $r->addRoute('GET', '/structures/armory', [\sdo\Controllers\ArmoryController::class, 'index']);
    $r->addRoute('GET', '/structures/stable', [\sdo\Controllers\StableController::class, 'index']);
    $r->addRoute('POST', '/structures/stable/stable-unit', [\sdo\Controllers\StableController::class, 'stableUnit']);
    $r->addRoute('POST', '/structures/stable/upgrade', [\sdo\Controllers\StableController::class, 'upgrade']);
    $r->addRoute('GET', '/structures/upgrades', [\sdo\Controllers\UpgradesController::class, 'index']);
    $r->addRoute('POST', '/structures/upgrades/housing', [\sdo\Controllers\UpgradesController::class, 'upgradeHousing']);
    $r->addRoute('POST', '/structures/upgrades/mercenary-market', [\sdo\Controllers\UpgradesController::class, 'upgradeMercenaryMarket']);
    $r->addRoute('GET', '/combat/battlefield', [\sdo\Controllers\BattlefieldController::class, 'index']);
    $r->addRoute('POST', '/combat/battlefield/attack', [\sdo\Controllers\BattlefieldController::class, 'attack']);
    $r->addRoute('GET', '/combat/battlefield/report/{id:\d+}', [\sdo\Controllers\BattlefieldController::class, 'report']);
    $r->addRoute('GET', '/spy', [\sdo\Controllers\SpyController::class, 'index']); 
    $r->addRoute('POST', '/spy/reconnaissance/init', [\sdo\Controllers\SpyController::class, 'initReconnaissance']);
    $r->addRoute('POST', '/spy/reconnaissance/execute', [\sdo\Controllers\SpyController::class, 'executeReconnaissance']);
    $r->addRoute('GET', '/combat/army', [\sdo\Controllers\ArmoryController::class, 'index']);
    $r->addRoute('GET', '/clan/home', [\sdo\Controllers\ClanController::class, 'home']);
    $r->addRoute('GET', '/clan/bank', [\sdo\Controllers\ClanController::class, 'bank']);

    // Bank Routes
    $r->addRoute('GET', '/bank', [\sdo\Controllers\BankController::class, 'index']);
    $r->addRoute('POST', '/bank/deposit', [\sdo\Controllers\BankController::class, 'deposit']);
    $r->addRoute('POST', '/bank/withdraw', [\sdo\Controllers\BankController::class, 'withdraw']);

    // Training Routes (Military Units Only)
    $r->addRoute('GET', '/combat/training', [\sdo\Controllers\TrainingController::class, 'index']);
    $r->addRoute('POST', '/combat/train', [\sdo\Controllers\TrainingController::class, 'train']);

    // Recruitment Routes
    $r->addRoute('GET', '/combat/recruit', [\sdo\Controllers\RecruitmentController::class, 'index']);
    $r->addRoute('POST', '/combat/recruit/start', [\sdo\Controllers\RecruitmentController::class, 'start']);
    $r->addRoute('POST', '/combat/recruit/click', [\sdo\Controllers\RecruitmentController::class, 'click']);

    // Admin Command Center
    $r->addRoute('GET', '/admin', [\sdo\Controllers\AdminController::class, 'index']);
    $r->addRoute('GET', '/admin/search', [\sdo\Controllers\AdminController::class, 'searchDominions']);
    $r->addRoute('GET', '/admin/kingdoms', [\sdo\Controllers\AdminController::class, 'getAllDominions']);
    $r->addRoute('POST', '/admin/update-kingdom', [\sdo\Controllers\AdminController::class, 'updateDominion']);
    $r->addRoute('GET', '/admin/units', [\sdo\Controllers\AdminController::class, 'getUnits']);
    $r->addRoute('POST', '/admin/update-unit', [\sdo\Controllers\AdminController::class, 'updateUnit']);
    $r->addRoute('POST', '/admin/add-unit', [\sdo\Controllers\AdminController::class, 'addUnit']);
    $r->addRoute('POST', '/admin/delete-unit', [\sdo\Controllers\AdminController::class, 'deleteUnit']);
    $r->addRoute('GET', '/admin/structures', [\sdo\Controllers\AdminController::class, 'getStructures']);
    $r->addRoute('POST', '/admin/add-structure', [\sdo\Controllers\AdminController::class, 'addStructure']);
    $r->addRoute('POST', '/admin/update-structure', [\sdo\Controllers\AdminController::class, 'updateStructure']);
    $r->addRoute('POST', '/admin/delete-structure', [\sdo\Controllers\AdminController::class, 'deleteStructure']);
    $r->addRoute('POST', '/admin/add-structure-level', [\sdo\Controllers\AdminController::class, 'addStructureLevel']);
    $r->addRoute('POST', '/admin/update-structure-level', [\sdo\Controllers\AdminController::class, 'updateStructureLevel']);
    $r->addRoute('GET', '/admin/armory-items', [\sdo\Controllers\AdminController::class, 'getArmoryItems']);
    $r->addRoute('POST', '/admin/update-armory-item', [\sdo\Controllers\AdminController::class, 'updateArmoryItem']);
    $r->addRoute('POST', '/admin/add-armory-item', [\sdo\Controllers\AdminController::class, 'addArmoryItem']);
    $r->addRoute('POST', '/admin/delete-armory-item', [\sdo\Controllers\AdminController::class, 'deleteArmoryItem']);
    $r->addRoute('GET', '/admin/battle-logs', [\sdo\Controllers\AdminController::class, 'getBattleLogs']);
    
    // API Management
    $r->addRoute('GET', '/admin/api/keys', [\sdo\Controllers\AdminController::class, 'getApiKeys']);
    $r->addRoute('GET', '/admin/api/applications', [\sdo\Controllers\AdminController::class, 'getApiApplications']);
    $r->addRoute('POST', '/admin/api/process-app', [\sdo\Controllers\AdminController::class, 'processApiApplication']);
    $r->addRoute('POST', '/admin/api/issue', [\sdo\Controllers\AdminController::class, 'issueApiKey']);
    $r->addRoute('POST', '/admin/api/update', [\sdo\Controllers\AdminController::class, 'updateApiKey']);
    $r->addRoute('POST', '/admin/api/delete', [\sdo\Controllers\AdminController::class, 'deleteApiKey']);
    $r->addRoute('GET', '/admin/api/logs', [\sdo\Controllers\AdminController::class, 'getApiLogs']);

    $r->addRoute('GET', '/admin/settings', [\sdo\Controllers\AdminController::class, 'getSettings']);
    $r->addRoute('POST', '/admin/update-setting', [\sdo\Controllers\AdminController::class, 'updateSetting']);

    // Open API v1
    $r->addRoute('GET', '/api/v1/ping', [\sdo\Controllers\ApiController::class, 'ping']);
    $r->addRoute('GET', '/api/v1/sector/status', [\sdo\Controllers\ApiController::class, 'sectorStatus']);
    $r->addRoute('GET', '/api/v1/sector/manpower', [\sdo\Controllers\ApiController::class, 'sectorManpower']);
    $r->addRoute('GET', '/api/v1/sector/structures', [\sdo\Controllers\ApiController::class, 'sectorStructures']);
    $r->addRoute('GET', '/api/v1/battlefield', [\sdo\Controllers\ApiController::class, 'battlefield']);
    $r->addRoute('GET', '/api/v1/discord/link-status', [\sdo\Controllers\ApiController::class, 'discordLinkStatus']);

    // Mines Routes (Miner Assignment/Untraining & Upgrades)
    $r->addRoute('GET', '/structures/mines', [\sdo\Controllers\MinesController::class, 'index']);
    $r->addRoute('POST', '/structures/mines/assign', [\sdo\Controllers\MinesController::class, 'assign']);
    $r->addRoute('POST', '/structures/mines/unassign', [\sdo\Controllers\MinesController::class, 'unassign']);
    $r->addRoute('POST', '/structures/mines/upgrade-current', [\sdo\Controllers\MinesController::class, 'upgradeCurrentMine']);
    $r->addRoute('POST', '/structures/mines/upgrade-tier', [\sdo\Controllers\MinesController::class, 'upgradeMineTier']);

    //Armory Actions
    $r->addRoute('POST', '/structures/armory/buy', [\sdo\Controllers\ArmoryController::class, 'buy']);
    $r->addRoute('POST', '/structures/armory/sell', [\sdo\Controllers\ArmoryController::class, 'sell']);
    $r->addRoute('POST', '/structures/armory/upgrade', [\sdo\Controllers\ArmoryController::class, 'upgrade']);
    $r->addRoute('POST', '/structures/armory/equip', [\sdo\Controllers\ArmoryController::class, 'toggleEquip']);
    $r->addRoute('POST', '/structures/armory/upgrade-item', [\sdo\Controllers\ArmoryController::class, 'upgradeItem']);

    // Settings Routes
    $r->addRoute('GET', '/settings', [\sdo\Controllers\SettingsController::class, 'index']);
    $r->addRoute('POST', '/settings/identity', [\sdo\Controllers\SettingsController::class, 'updateIdentity']);
    $r->addRoute('POST', '/settings/cipher', [\sdo\Controllers\SettingsController::class, 'updateCipher']);
    $r->addRoute('POST', '/settings/stasis', [\sdo\Controllers\SettingsController::class, 'toggleStasis']);
    $r->addRoute('POST', '/settings/avatar', [\sdo\Controllers\SettingsController::class, 'updateAvatar']);
    $r->addRoute('POST', '/settings/api/apply', [\sdo\Controllers\SettingsController::class, 'applyForApi']);
    $r->addRoute('POST', '/settings/discord/link-code', [\sdo\Controllers\SettingsController::class, 'createDiscordLinkCode']);

    
};
