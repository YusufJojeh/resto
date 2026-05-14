import { execSync } from 'node:child_process';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

export default async function globalSetup() {
    const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');

    execSync('npm run build', {
        cwd: root,
        stdio: 'inherit',
    });

    const hotFile = path.join(root, 'public', 'hot');
    if (fs.existsSync(hotFile)) {
        fs.rmSync(hotFile, { force: true });
    }

    execSync('composer dump-autoload', {
        cwd: root,
        stdio: 'inherit',
    });

    execSync(
        'php artisan migrate:fresh --seed --force && php artisan db:seed --class=Database\\Seeders\\VisualRegressionSeeder --force',
        {
            cwd: root,
            stdio: 'inherit',
        },
    );
}
