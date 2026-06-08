import { execSync } from 'child_process';

export async function promoteToAdmin(username: string): Promise<void> {
    // We run the command inside the docker container to ensure the environment is correct.
    const command = `docker exec sdo_app php -r "require 'vendor/autoload.php'; \\$dotenv = \\Dotenv\\Dotenv::createImmutable('/var/www/html'); \\$dotenv->load(); \\sdo\\Infrastructure\\Eloquent::boot(); \\sdo\\Models\\User::where('username', '${username}')->update(['is_admin' => 1]);"`;
    
    try {
        execSync(command, { stdio: 'inherit' });
    } catch (e) {
        console.error('Failed to promote user to admin via Docker:', e);
        // Fallback to local if docker fails (might be in CI with a different setup)
        try {
            const localCommand = `php -r "require 'vendor/autoload.php'; \\$dotenv = \\Dotenv\\Dotenv::createImmutable(getcwd()); \\$dotenv->safeLoad(); \\sdo\\Infrastructure\\Eloquent::boot(); \\sdo\\Models\\User::where('username', '${username}')->update(['is_admin' => 1]);"`;
            execSync(localCommand, { stdio: 'inherit' });
        } catch (e2) {
            console.error('Local promotion fallback also failed.');
            throw e2;
        }
    }
}
