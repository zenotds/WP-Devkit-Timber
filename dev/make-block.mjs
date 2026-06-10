// Scaffolds a new ACF block from the "section" demo block.
// Usage: npm run make:block -- <slug> "<Title>"

import fs from "node:fs";
import path from "node:path";

const [slug, title] = process.argv.slice(2);

if (!slug || !/^[a-z][a-z0-9-]*$/.test(slug)) {
	console.error('Usage: npm run make:block -- <slug> "<Title>"');
	console.error("The slug must be kebab-case (e.g. hero, image-text).");
	process.exit(1);
}

const blockTitle = title || slug.charAt(0).toUpperCase() + slug.slice(1);
const source = path.resolve("blocks/section");
const target = path.resolve(`blocks/${slug}`);

if (!fs.existsSync(source)) {
	console.error(`Demo block not found: ${source}`);
	process.exit(1);
}

if (fs.existsSync(target)) {
	console.error(`Block already exists: ${target}`);
	process.exit(1);
}

fs.mkdirSync(target, { recursive: true });

// block.json — rename block and reset demo-specific values
const blockJson = JSON.parse(
	fs.readFileSync(path.join(source, "block.json"), "utf8"),
);
blockJson.name = `bizen/${slug}`;
blockJson.title = blockTitle;
blockJson.description = "";
blockJson.keywords = [slug];
fs.writeFileSync(
	path.join(target, "block.json"),
	`${JSON.stringify(blockJson, null, "\t")}\n`,
);

// Twig template — rename and update the block class
const twig = fs
	.readFileSync(path.join(source, "section.twig"), "utf8")
	.replaceAll("block-section", `block-${slug}`)
	.replace(/^\{#[\s\S]*?#\}\n/, "");
fs.writeFileSync(path.join(target, `${slug}.twig`), twig);

// Per-block CSS
const css = fs
	.readFileSync(path.join(source, "style.css"), "utf8")
	.replaceAll("block-section", `block-${slug}`);
fs.writeFileSync(path.join(target, "style.css"), css);

// fields.json and preview.png are intentionally NOT copied:
// duplicate ACF keys conflict, and the preview must be a real screenshot.

console.log(`✅ Block created: blocks/${slug}/`);
console.log("Next steps:");
console.log(`  1. Create the ACF field group (location: Block == ${blockTitle});`);
console.log(`     ACF saves it automatically as blocks/${slug}/fields.json`);
console.log(`  2. Add a real screenshot as blocks/${slug}/preview.png`);
