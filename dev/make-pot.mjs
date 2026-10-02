// Genera lang/theme.pot con le stringhe del dominio `theme` da PHP e Twig: wp-cli col pacchetto timber/wp-i18n-twig, che estende `wp i18n make-pot` ai .twig
//
// Uso: npm run make:pot   (PHP diverso da quello nel PATH: WP_CLI_PHP=/percorso/php npm run make:pot)

import { execFileSync } from "node:child_process";
import fs from "node:fs";

const OUT = "lang/theme.pot";
const php = process.env.WP_CLI_PHP || "php";

const run = (cmd, args) => execFileSync(cmd, args, { encoding: "utf8", stdio: ["ignore", "pipe", "pipe"] }).trim();

let wp;
try {
	wp = run("sh", ["-c", "command -v wp"]);
} catch {
	console.error("⚠️  wp-cli non trovato nel PATH");
	process.exit(1);
}

// make-pot richiede mbstring con mbregex (mb_ereg): alcune build di PHP lo compilano senza
if (run(php, ["-r", "echo function_exists('mb_ereg') ? 1 : 0;"]) !== "1") {
	console.error(`⚠️  ${php} non ha mb_ereg (mbstring senza mbregex): indica un altro PHP con WP_CLI_PHP=/percorso/php`);
	process.exit(1);
}

// Senza il pacchetto make-pot salta i .twig in silenzio e il .pot esce quasi vuoto
if (!run(php, [wp, "package", "list", "--fields=name", "--format=csv"]).includes("timber/wp-i18n-twig")) {
	console.error("⚠️  Manca il supporto Twig: wp package install timber/wp-i18n-twig");
	process.exit(1);
}

fs.mkdirSync("lang", { recursive: true });
execFileSync(php, [wp, "i18n", "make-pot", ".", OUT, "--domain=theme", "--exclude=node_modules,vendor,assets,library,dev", "--skip-js", "--skip-block-json", "--skip-theme-json"], { stdio: "inherit" });

const count = (fs.readFileSync(OUT, "utf8").match(/^msgid "./gm) || []).length;
console.log(`   ${OUT}: ${count} stringhe`);
