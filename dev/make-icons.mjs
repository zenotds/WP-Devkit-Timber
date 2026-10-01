// Genera assets/icons/ dal pacchetto Font Awesome Pro copiato in dev/fontawesome/ (svgs/ e css/): gli SVG per stile e la mappa degli alias
//
// Uso: npm run make:icons [-- stili...]   (default: regular brands)

import fs from "node:fs";
import path from "node:path";

const SOURCE = "./dev/fontawesome";
const OUT = "./assets/icons";
const requested = process.argv.slice(2);
const styles = requested.length ? requested : ["regular", "brands"];

if (!fs.existsSync(path.join(SOURCE, "svgs"))) {
	console.error(`⚠️  Copia il pacchetto Font Awesome Pro in ${SOURCE}/ (servono svgs/<stile>/ e css/fontawesome.css + css/brands.css)`);
	process.exit(1);
}

for (const style of styles) {
	if (!fs.existsSync(path.join(SOURCE, "svgs", style))) {
		console.error(`⚠️  Stile non presente nel pacchetto: ${style}`);
		process.exit(1);
	}
}

// Svuota le generazioni precedenti, .gitkeep escluso
fs.mkdirSync(OUT, { recursive: true });
for (const entry of fs.readdirSync(OUT, { withFileTypes: true })) {
	if (entry.name !== ".gitkeep") fs.rmSync(path.join(OUT, entry.name), { recursive: true, force: true });
}

// Commento di licenza tolto dai file, una copia in LICENSE.txt
let license = "";

for (const style of styles) {
	const from = path.join(SOURCE, "svgs", style);
	const to = path.join(OUT, style);
	fs.mkdirSync(to, { recursive: true });

	const files = fs.readdirSync(from).filter((f) => f.endsWith(".svg"));
	for (const file of files) {
		const svg = fs.readFileSync(path.join(from, file), "utf8");
		license ||= (/<!--!?\s*([\s\S]*?)\s*-->/.exec(svg) || [])[1] || "";
		fs.writeFileSync(path.join(to, file), svg.replace(/<!--[\s\S]*?-->/g, "").trim());
	}
	console.log(`   ${style}: ${files.length} icone`);
}

// Alias → nome del file, raggruppando le classi per valore di --fa: vale sia per le regole condivise (.fa-close,.fa-times,.fa-xmark{--fa:...}) sia per una regola per alias (FA 7.2+). Gli stili classici condividono i nomi, i brand hanno una mappa a parte. Valore tra virgolette doppie o singole (.fa-ditto{--fa:'"'}), altre dichiarazioni dopo ammesse
function aliases(cssFile, dir) {
	const map = {};
	if (!fs.existsSync(cssFile) || !fs.existsSync(dir)) return map;
	const css = fs.readFileSync(cssFile, "utf8");
	const groups = new Map();
	for (const [, selectors, value] of css.matchAll(/([^{}]+)\{\s*--fa:\s*("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')[^}]*\}/g)) {
		const names = [...selectors.matchAll(/\.fa-([a-z0-9-]+)/g)].map((m) => m[1]);
		groups.set(value, [...(groups.get(value) || []), ...names]);
	}
	for (const names of groups.values()) {
		const canonical = names.find((name) => fs.existsSync(path.join(dir, `${name}.svg`)));
		if (!canonical) continue;
		for (const name of names) if (name !== canonical) map[name] = canonical;
	}
	return map;
}

const classic = styles.find((s) => s !== "brands");
const map = {
	classic: classic ? aliases(path.join(SOURCE, "css/fontawesome.css"), path.join(OUT, classic)) : {},
	brands: styles.includes("brands") ? aliases(path.join(SOURCE, "css/brands.css"), path.join(OUT, "brands")) : {},
};

const php = (value) => `'${value.replace(/\\/g, "\\\\").replace(/'/g, "\\'")}'`;
const entries = (obj) =>
	Object.keys(obj)
		.sort()
		.map((k) => `\t\t${php(k)} => ${php(obj[k])},`)
		.join("\n");

fs.writeFileSync(
	path.join(OUT, "aliases.php"),
	`<?php\n// Generato da dev/make-icons.mjs: non modificare\nreturn [\n\t'classic' => [\n${entries(map.classic)}\n\t],\n\t'brands' => [\n${entries(map.brands)}\n\t],\n];\n`,
);
fs.writeFileSync(path.join(OUT, "LICENSE.txt"), `${license}\n`);

console.log(`   alias: ${Object.keys(map.classic).length} classic, ${Object.keys(map.brands).length} brands`);
console.log(`✅ Icone in ${OUT}`);
