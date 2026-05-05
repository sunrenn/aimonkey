import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = dirname(fileURLToPath(import.meta.url));
const frontendDir = resolve(scriptDir, '..');
const distAssetsDir = resolve(frontendDir, 'dist', 'assets');
const rootAssetsDir = resolve(frontendDir, '..', 'assets');

if (!existsSync(distAssetsDir)) {
  throw new Error(`Missing dist assets directory: ${distAssetsDir}`);
}

rmSync(rootAssetsDir, { recursive: true, force: true });
mkdirSync(rootAssetsDir, { recursive: true });
cpSync(distAssetsDir, rootAssetsDir, { recursive: true });

console.log(`Copied frontend assets to ${rootAssetsDir}`);
