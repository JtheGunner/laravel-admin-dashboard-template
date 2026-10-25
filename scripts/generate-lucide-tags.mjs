/**
 * Fetches icon tags from the Lucide GitHub repo and writes
 * resources/js/data/lucide-tags.json.
 *
 * Run once (or after a Lucide upgrade): node scripts/generate-lucide-tags.mjs
 */

import { writeFileSync } from 'fs';
import * as LucideIcons from 'lucide-vue-next';

function toKebab(s) {
    return s.replace(/([A-Z])/g, (_, c, i) => (i ? '-' : '') + c.toLowerCase());
}

const iconNames = Object.keys(LucideIcons)
    .filter((k) => {
        if (k.startsWith('Lucide') || k.endsWith('Icon')) {
            return false;
        }

        return /^[A-Z]/.test(k);
    })
    .map(toKebab);

console.log(`Fetching tags for ${iconNames.length} icons …`);

const CONCURRENCY = 25;
const BASE = 'https://raw.githubusercontent.com/lucide-icons/lucide/main/icons';
const tags = {};

async function fetchIcon(name) {
    try {
        const res = await fetch(`${BASE}/${name}.json`);

        if (!res.ok) {
return;
}

        const data = await res.json();
        const combined = [...(data.tags ?? []), ...(data.categories ?? [])];

        if (combined.length) {
tags[name] = combined;
}
    } catch {
        // silent — icon simply gets no tags
    }
}

for (let i = 0; i < iconNames.length; i += CONCURRENCY) {
    const batch = iconNames.slice(i, i + CONCURRENCY);
    await Promise.all(batch.map(fetchIcon));
    process.stdout.write(`\r  ${Math.min(i + CONCURRENCY, iconNames.length)}/${iconNames.length}`);
}

const outPath = 'resources/js/data/lucide-tags.json';
writeFileSync(outPath, JSON.stringify(tags));
console.log(`\nDone — wrote ${outPath} (${Object.keys(tags).length} tagged icons)`);
