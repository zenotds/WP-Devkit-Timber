import tailwindcssPostcss from "@tailwindcss/postcss";
import browserSync from "browser-sync";
import chalk from "chalk";
import chokidar from "chokidar";
import esbuild from "esbuild";
import fs from "node:fs";
import path from "node:path";
import postcss from "postcss";

// Suppress deprecation warnings while keeping visibility for others.
// Preserve support for the various process.emitWarning invocation signatures.
const originalEmitWarning = process.emitWarning.bind(process);
process.emitWarning = (warning, ...args) => {
	const type =
		typeof warning === "string"
			? typeof args[0] === "string"
				? args[0]
				: typeof args[0] === "object" && args[0] !== null
					? args[0].type
					: undefined
			: warning && typeof warning === "object"
				? warning.name || warning.type
				: undefined;

	if (type === "DeprecationWarning") {
		return;
	}

	return originalEmitWarning(warning, ...args);
};

// ============================================
// CONFIGURAZIONE
// ============================================
// I valori per-progetto stanno in devkit.config.json: questo file resta identico
// tra devkit e progetti, così si aggiorna senza conflitti.

const CONFIG_FILE = "./devkit.config.json";

const CONFIG_DEFAULTS = {
	proxy: "https://your-site.test",
	browser: ["default"],
	watch: [
		"./templates/",
		"./blocks/",
		"./woocommerce/",
		"./dev/css/",
		"./dev/js/",
	],
};

function loadConfig() {
	if (!fs.existsSync(CONFIG_FILE)) {
		console.warn(`⚠️  ${CONFIG_FILE} non trovato: uso i default.`);
		return CONFIG_DEFAULTS;
	}

	try {
		const parsed = JSON.parse(fs.readFileSync(CONFIG_FILE, "utf8"));
		return { ...CONFIG_DEFAULTS, ...parsed };
	} catch (error) {
		console.error(`🚨 ${CONFIG_FILE} non valido: ${error.message}`);
		return CONFIG_DEFAULTS;
	}
}

const config = loadConfig();

// ============================================

// Determine if the environment is production
const isProduction = process.env.NODE_ENV === "production";

// BrowserSync instance for live reload
const bs = browserSync.create();

// Function to update CSS version in the style.css file (only in production)
function updateVersion() {
	const styleFilePath = "./style.css";
	const currentDate = new Date();
	const dateStr = currentDate.toLocaleString("it-IT");

	let content = fs.readFileSync(styleFilePath, { encoding: "utf8" });

	const versionPattern =
		/(^\s*\*\s*Version:\s*)(\d+)\.(\d+)([a-z]+)(\d+)(?:\s*\(.*\))?\s*$/im;
	const match = content.match(versionPattern);

	if (!match) {
		console.warn(
			"⚠️ Version line not found or does not match the expected format.",
		);
		return;
	}

	const [, prefix, major, minor, state, build] = match;
	const newBuildNumber = String(parseInt(build, 10) + 1);
	const newVersion = `${major}.${minor}${state}${newBuildNumber}`;

	content = content.replace(versionPattern, `${prefix}${newVersion}`);

	const releaseDatePattern = /(^\s*\*\s*Release Date:\s*).*/im;

	if (releaseDatePattern.test(content)) {
		content = content.replace(
			releaseDatePattern,
			(_, releasePrefix) => `${releasePrefix}${dateStr}`,
		);
	} else {
		content = content.replace(
			`${prefix}${newVersion}`,
			`${prefix}${newVersion}\n * Release Date: ${dateStr}`,
		);
	}

	fs.writeFileSync(styleFilePath, content, { encoding: "utf8" });
	console.log(`📦 Version updated to ${newVersion} — Release Date: ${dateStr}`);
}

// Normalizza i percorsi webfont nei CSS compilati: da assets/css/ è sempre ../webfonts/
function normalizeFontPaths(files) {
	for (const file of files) {
		if (!file.endsWith(".css") || !fs.existsSync(file)) continue;
		const css = fs.readFileSync(file, "utf8");
		const fixed = css.replace(
			/url\((["']?)(?:\.{1,2}\/)*webfonts\//g,
			"url($1../webfonts/",
		);
		if (fixed !== css) fs.writeFileSync(file, fixed);
	}
}

// I sourcemap non si generano in produzione (esporrebbero i sorgenti): rimuove quelli
// lasciati da build di sviluppo precedenti, altrimenti restano orfani in assets/.
function cleanupSourcemaps() {
	for (const dir of ["./assets/css", "./assets/js"]) {
		if (!fs.existsSync(dir)) continue;
		for (const file of fs.readdirSync(dir)) {
			if (file.endsWith(".map")) fs.unlinkSync(path.join(dir, file));
		}
	}
}

// Function to log file sizes of generated assets
function logFileSizes(outputs) {
	for (const [file, bytes] of outputs) {
		const size = `${(bytes / 1024).toFixed(2)} KB`;
		console.log(`   ${file}    ${chalk.cyan(size)}`);
	}
}

// Entry points for JavaScript and CSS
let warnedMissingJsDir = false;
let warnedMissingCssDir = false;

function entryPoints() {
	const entryPoints = {};
	const jsDir = "./dev/js";
	const cssDir = "./dev/css";

	// Add JS files
	if (fs.existsSync(jsDir)) {
		fs.readdirSync(jsDir).forEach((file) => {
			if (file.endsWith(".js")) {
				const name = path.basename(file, ".js");
				entryPoints[`js/${name}.min`] = path.join(jsDir, file);
			}
		});
	} else {
		if (!warnedMissingJsDir) {
			console.warn(`⚠️ JavaScript directory not found: ${jsDir}`);
			warnedMissingJsDir = true;
		}
	}

	// Add CSS files
	if (fs.existsSync(cssDir)) {
		fs.readdirSync(cssDir).forEach((file) => {
			if (file.endsWith(".css")) {
				const name = path.basename(file, ".css");
				entryPoints[`css/${name}.min`] = path.join(cssDir, file);
			}
		});
	} else {
		if (!warnedMissingCssDir) {
			console.warn(`⚠️ CSS directory not found: ${cssDir}`);
			warnedMissingCssDir = true;
		}
	}

	return entryPoints;
}

// Tailwind 4 risolve da sé gli @import e il prefixing (via Lightning CSS): niente
// postcss-import né autoprefixer. Solo styles.css passa di qui, i file importati no.
const postcssPlugin = {
	name: "postcss",
	setup(build) {
		build.onLoad({ filter: /\.css$/ }, async (args) => {
			const source = await fs.promises.readFile(args.path, "utf8");
			const result = await postcss([tailwindcssPostcss()]).process(source, {
				from: args.path,
			});
			return { contents: result.css, loader: "css" };
		});
	},
};

function createBuildOptions(entries) {
	return {
		entryPoints: entries,
		outdir: "./assets",
		bundle: true,
		sourcemap: !isProduction,
		minify: isProduction,
		metafile: true,
		logLevel: isProduction ? "silent" : "info",
		plugins: [postcssPlugin],
		target: ["esnext"],
		define: {
			"process.env.NODE_ENV": JSON.stringify(
				isProduction ? "production" : "development",
			),
		},
		external: [
			"*.woff",
			"*.woff2",
			"*.ttf",
			"*.eot",
			"*.png",
			"*.svg",
			"*.jpg",
			"*.webp",
		],
	};
}

// Context esbuild riusato tra le build: il rebuild incrementale è ~10x più veloce.
// Si ricrea solo se cambia l'elenco degli entrypoint (un file in più in dev/js o dev/css).
let context = null;
let contextKey = "";

async function getContext() {
	const entries = entryPoints();
	const key = Object.keys(entries).sort().join("|");

	if (context && key === contextKey) return context;

	if (context) await context.dispose();

	contextKey = key;
	context = await esbuild.context(createBuildOptions(entries));

	return context;
}

// Function to run esbuild
async function build() {
	const startTime = Date.now();

	try {
		if (isProduction) {
			updateVersion(); // Update version in production
		}

		const ctx = await getContext();
		const result = await ctx.rebuild();

		const outputs = Object.entries(result.metafile.outputs)
			.filter(([file]) => !file.endsWith(".map"))
			.map(([file, meta]) => [file, meta.bytes]);

		const styles = outputs.filter(([file]) => file.endsWith(".css"));
		const scripts = outputs.filter(([file]) => file.endsWith(".js"));

		normalizeFontPaths(styles.map(([file]) => file));

		if (isProduction) {
			cleanupSourcemaps();
		}

		// Log file sizes for styles
		if (styles.length > 0) {
			console.log(`\n🟪 Styles compiled with Tailwind CSS!`);
			logFileSizes(styles);
		}

		// Log file sizes for scripts
		if (scripts.length > 0) {
			console.log(`\n🟨 Scripts compiled!`);
			logFileSizes(scripts);
		}

		const totalBuildTime = ((Date.now() - startTime) / 1000).toFixed(2);
		console.log(`⏱️  Total build time: ${chalk.green(`${totalBuildTime}s`)}`);
	} catch (error) {
		console.error(`🚨 Build failed:`, error);
	}
}

let buildQueue = Promise.resolve();

function queueBuild({ reload = false, cssOnly = false } = {}) {
	buildQueue = buildQueue
		.catch(() => {})
		.then(async () => {
			try {
				await build();
				if (!isProduction && reload) {
					// Sui cambi CSS BrowserSync inietta il foglio di stile senza ricaricare:
					// la pagina non perde lo stato (menu aperti, modali, posizione di scroll).
					if (cssOnly) {
						bs.reload("*.css");
					} else {
						bs.reload();
					}
				}
			} catch (error) {
				console.error("🚨 Queued build failed:", error);
			}
		});
	return buildQueue;
}

// Watch for file changes during development
if (!isProduction) {
	console.log(`🚀 Starting development server...`);

	queueBuild()
		.then(() => {
			console.log(`🔭 Watching for changes...\n`);

			bs.init({
				proxy: config.proxy,
				open: true,
				browser: config.browser,
			});

			chokidar
				.watch(config.watch, {
					ignoreInitial: true,
				})
				.on("all", (event, filePath) => {
					const cssOnly = filePath.endsWith(".css");
					const action = cssOnly ? "injecting" : "reloading";
					console.log(`\n🚧 ${filePath} ${event}, rebuilding and ${action}...`);
					queueBuild({ reload: true, cssOnly });
				});
		})
		.catch((error) => {
			console.error("🚨 Initial build failed:", error);
		});
} else {
	console.log(`🚀 Building for production...`);
	queueBuild()
		.then(async () => {
			if (context) await context.dispose();
		})
		.catch((error) => {
			console.error("🚨 Production build failed:", error);
		});
}
