#!/usr/bin/env node

import { readFile, writeFile } from "node:fs/promises";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import readline from "node:readline/promises";
import { stdin as input, stdout as output } from "node:process";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(__dirname, "..");
const stylePath = path.join(projectRoot, "style.css");
const packagePath = path.join(projectRoot, "package.json");
const composerPath = path.join(projectRoot, "composer.json");
const configPath = path.join(projectRoot, "devkit.config.json");

function slugify(value) {
	return value
		.toString()
		.trim()
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, "-")
		.replace(/^-+|-+$/g, "")
		|| "theme";
}

function toBool(value, fallback) {
	if (typeof value === "boolean") {
		return value;
	}
	return fallback;
}

function getConfig() {
	if (!fs.existsSync(configPath)) {
		return {};
	}

	try {
		return JSON.parse(fs.readFileSync(configPath, "utf8")) ?? {};
	} catch {
		return {};
	}
}

function readJsonFile(filePath) {
	if (!fs.existsSync(filePath)) {
		return {};
	}

	try {
		return JSON.parse(fs.readFileSync(filePath, "utf8")) ?? {};
	} catch {
		return {};
	}
}

function readStyleMeta() {
	if (!fs.existsSync(stylePath)) {
		return {};
	}

	const content = fs.readFileSync(stylePath, "utf8");
	const metadata = {};
	const pattern = /^\s*\*\s+([^:]+):\s*(.+)$/gm;
	let match;

	while ((match = pattern.exec(content))) {
		const key = match[1].trim();
		const value = match[2].trim();
		metadata[key] = value;
	}

	return metadata;
}

function parseList(value) {
	if (!value) {
		return [];
	}

	return value
		.split(",")
		.map((item) => item.trim())
		.filter(Boolean);
}

function parseNumberList(value) {
	return parseList(value)
		.map((entry) => Number.parseInt(entry, 10))
		.filter(Number.isFinite);
}

async function ask(rl, question, defaultValue = "") {
	const suffix = defaultValue ? ` [${defaultValue}]` : "";
	const answer = (await rl.question(`${question}${suffix}: `)).trim();
	return answer || defaultValue;
}

async function confirm(rl, question, defaultValue = true) {
	const hint = defaultValue ? "Y/n" : "y/N";
	const answer = (await rl.question(`${question} (${hint}): `)).trim().toLowerCase();

	if (!answer) {
		return defaultValue;
	}

	if (["y", "yes"].includes(answer)) {
		return true;
	}

	if (["n", "no"].includes(answer)) {
		return false;
	}

	return defaultValue;
}

async function updateStyleMeta(meta) {
	let content = await readFile(stylePath, "utf8");

	const replacements = [
		{ key: "Theme Name", value: meta.name },
		{ key: "Description", value: meta.description },
		{ key: "Text Domain", value: meta.textDomain },
		{ key: "Author", value: meta.author },
		{ key: "Version", value: meta.version },
		{ key: "Release Date", value: meta.releaseDate },
	];

	replacements.forEach(({ key, value }) => {
		const pattern = new RegExp(`(\\*\\s+${key}:\\s*)(.*)`, "i");
		if (pattern.test(content)) {
			content = content.replace(pattern, `$1${value}`);
		} else if (key === "Text Domain") {
			content = content.replace(
				/(\\*\\s+Description:.*)/i,
				`$1\n * Text Domain: ${value}`,
			);
		}
	});

	await writeFile(stylePath, content, "utf8");
}

async function updatePackageJson(meta) {
	const pkg = JSON.parse(await readFile(packagePath, "utf8"));

	pkg.name = meta.slug;
	pkg.description = meta.description;
	pkg.version = meta.version;
	pkg.author = meta.author;
	pkg.scripts = pkg.scripts || {};
	pkg.scripts.setup = "node scripts/install.mjs";

	await writeFile(packagePath, `${JSON.stringify(pkg, null, "\t")}\n`, "utf8");
}

async function updateComposerJson(meta) {
	const composer = JSON.parse(await readFile(composerPath, "utf8"));

	composer.name = `${meta.slug}/theme`;
	composer.description = meta.description;
	composer.version = meta.version;

	const author = composer.authors?.[0] ?? {};
	author.name = meta.author;
	if (meta.authorEmail) {
		author.email = meta.authorEmail;
	}
	composer.authors = [author];

	await writeFile(composerPath, `${JSON.stringify(composer, null, "\t")}\n`, "utf8");
}

async function saveConfig(config) {
	await writeFile(configPath, `${JSON.stringify(config, null, "\t")}\n`, "utf8");
}

async function main() {
	const config = getConfig();
	const styleMeta = readStyleMeta();
	const pkgDefaults = readJsonFile(packagePath);
	const composerDefaults = readJsonFile(composerPath);
	const composerAuthor = Array.isArray(composerDefaults.authors)
		? composerDefaults.authors[0] ?? {}
		: {};
	const rl = readline.createInterface({ input, output });

	try {
		const currentTheme = config.theme ?? {};
		const currentBuild = config.build ?? {};
		const currentBrowserSync = currentBuild.browserSync ?? {};
		const currentGutenberg = config.gutenberg ?? {};
		const currentAcf = config.acf ?? {};
		const currentForms = config.forms ?? {};
		const currentMenus = config.menus ?? {};
		const currentContext = config.context ?? {};

		const defaultThemeName = currentTheme.name ?? styleMeta["Theme Name"] ?? "Theme name";
		const themeName = await ask(rl, "Theme name", defaultThemeName);
		const defaultSlug =
			currentTheme.slug ??
			pkgDefaults.name ??
			slugify(themeName);
		const themeSlugInput = await ask(rl, "Theme slug", defaultSlug);
		const themeSlug = themeSlugInput || defaultSlug;
		const themeDescription = await ask(
			rl,
			"Theme description",
			currentTheme.description ??
				styleMeta["Description"] ??
				pkgDefaults.description ??
				"Site Theme",
		);
		const themeAuthor = await ask(
			rl,
			"Author",
			currentTheme.author ??
				styleMeta["Author"] ??
				pkgDefaults.author ??
				composerAuthor.name ??
				"Francesco Selva",
		);
		const themeAuthorEmail = await ask(
			rl,
			"Author email (optional)",
			currentTheme.authorEmail ?? composerAuthor.email ?? "",
		);
		const themeVersion = await ask(
			rl,
			"Initial version",
			currentTheme.version ??
				styleMeta["Version"] ??
				pkgDefaults.version ??
				composerDefaults.version ??
				"1.0.0",
		);
		const textDomainDefault =
			currentTheme.textDomain ??
			styleMeta["Text Domain"] ??
			defaultSlug ??
			"theme";
		const textDomainInput = await ask(
			rl,
			"Text domain",
			textDomainDefault,
		);
		const textDomain = (textDomainInput || textDomainDefault || "theme").toLowerCase();

		const proxyUrl = await ask(
			rl,
			"BrowserSync proxy URL",
			currentBrowserSync.proxy ?? "https://your-site.test",
		);
		const proxyOpen = await confirm(
			rl,
			"Open browser automatically",
			toBool(currentBrowserSync.open, true),
		);
		const browserDefaults = (currentBrowserSync.browsers ?? ["firefox developer edition"]).join(", ");
		const browserInput = await ask(
			rl,
			"Preferred browsers (comma separated)",
			browserDefaults,
		);
		const browserList = parseList(browserInput);
		const proxyHttps = await confirm(
			rl,
			"Enable HTTPS for BrowserSync",
			toBool(currentBrowserSync.https, false),
		);
		const proxyPortInput = await ask(
			rl,
			"BrowserSync port",
			String(currentBrowserSync.port ?? 3000),
		);
		const proxyPort = Number.parseInt(proxyPortInput, 10) || 3000;

		const tailwindEnabled = await confirm(
			rl,
			"Use Tailwind CSS pipeline",
			toBool(currentBuild.useTailwind, true),
		);

		const gutenbergEnabled = await confirm(
			rl,
			"Enable Gutenberg editor",
			toBool(currentGutenberg.enabled, false),
		);

		let allowedSlugs = [];
		let allowedIds = [];
		let allowCoreBlocks = toBool(currentGutenberg.coreBlocks, true);
		let allowCustomBlocks = toBool(currentGutenberg.customBlocks, false);

		if (gutenbergEnabled) {
			allowedSlugs = parseList(
				await ask(
					rl,
					"Allowed slugs (comma separated, leave blank for all)",
					(currentGutenberg.allowedSlugs ?? []).join(", "),
				),
			);
			allowedIds = parseNumberList(
				await ask(
					rl,
					"Allowed IDs (comma separated, leave blank for all)",
					(currentGutenberg.allowedIds ?? []).join(", "),
				),
			);
			allowCoreBlocks = await confirm(
				rl,
				"Keep core Gutenberg blocks",
				toBool(currentGutenberg.coreBlocks, true),
			);
			allowCustomBlocks = await confirm(
				rl,
				"Enable custom Timber/ACF blocks",
				toBool(currentGutenberg.customBlocks, false),
			);
		}

		const enableUniqueIds = await confirm(
			rl,
			"Enable ACF unique ID enhancer",
			toBool(currentAcf.enableUniqueIds, true),
		);
		const enableWysiwyg = await confirm(
			rl,
			"Enable ACF WYSIWYG customizations",
			toBool(currentAcf.enableWysiwygTweaks, true),
		);
		const enableGoogleMap = await confirm(
			rl,
			"Use ACF Google Map field",
			toBool(currentAcf.enableGoogleMapField, false),
		);
		let googleMapKey = currentAcf.googleMapApiKey ?? "";
		if (enableGoogleMap) {
			googleMapKey = await ask(
				rl,
				"Google Maps API key",
				currentAcf.googleMapApiKey ?? "",
			);
		} else {
			googleMapKey = "";
		}

		const useCf7 = await confirm(
			rl,
			"Use Contact Form 7 overrides",
			toBool(currentForms.useContactForm7, true),
		);

		const menuPrompts = [
			["top_menu", "Top menu"],
			["main_menu", "Main menu"],
			["mobile_menu", "Mobile menu"],
			["footer_menu", "Footer menu"],
			["credits_menu", "Credits menu"],
		];

		const menus = {};
		for (const [key, label] of menuPrompts) {
			menus[key] = await confirm(
				rl,
				`Register ${label}?`,
				toBool(currentMenus[key], true),
			);
		}

		const preloadPosts = await confirm(
			rl,
			"Preload all posts into Timber context",
			toBool(currentContext.preloadPosts, true),
		);
		const preloadCategories = await confirm(
			rl,
			"Preload categories into Timber context",
			toBool(currentContext.preloadCategories, true),
		);

		config.theme = {
			name: themeName,
			slug: themeSlug,
			description: themeDescription,
			author: themeAuthor,
			authorEmail: themeAuthorEmail,
			version: themeVersion,
			textDomain,
		};

		config.build = {
			useTailwind: tailwindEnabled,
			browserSync: {
				proxy: proxyUrl,
				open: proxyOpen,
				https: proxyHttps,
				port: proxyPort,
				browsers: browserList.length ? browserList : ["firefox developer edition"],
			},
		};

		config.gutenberg = {
			enabled: gutenbergEnabled,
			allowedSlugs: gutenbergEnabled ? allowedSlugs : [],
			allowedIds: gutenbergEnabled ? allowedIds : [],
			coreBlocks: allowCoreBlocks,
			customBlocks: allowCustomBlocks,
		};

		config.acf = {
			enableUniqueIds,
			enableWysiwygTweaks: enableWysiwyg,
			enableGoogleMapField: enableGoogleMap,
			googleMapApiKey: googleMapKey,
		};

		config.forms = {
			useContactForm7: useCf7,
		};

		config.menus = menus;
		config.context = {
			preloadPosts,
			preloadCategories,
		};

		const releaseDate = new Date().toLocaleString("it-IT");

		await Promise.all([
			saveConfig(config),
			updateStyleMeta({
				name: themeName,
				description: themeDescription,
				textDomain,
				author: themeAuthor,
				version: themeVersion,
				releaseDate,
			}),
			updatePackageJson({
				slug: themeSlug,
				description: themeDescription,
				version: themeVersion,
				author: themeAuthor,
			}),
			updateComposerJson({
				slug: themeSlug,
				description: themeDescription,
				version: themeVersion,
				author: themeAuthor,
				authorEmail: themeAuthorEmail,
			}),
		]);

		console.log("\n✅ Theme configuration updated. Happy building!\n");
	} finally {
		rl.close();
	}
}

main().catch((error) => {
	console.error("\n🚨 Installer failed:", error);
	process.exitCode = 1;
});
