import tailwindcssPostcss from "@tailwindcss/postcss";
import autoprefixer from "autoprefixer";
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

// Function to log file sizes of generated assets
function logFileSizes(files) {
	files.forEach((file) => {
		if (fs.existsSync(file)) {
			const stats = fs.statSync(file);
			const size = `${(stats.size / 1024).toFixed(2)} KB`;
			console.log(`   ${file}    ${chalk.cyan(size)}`);
		} else {
			console.log(`   ${file}    ${chalk.red("File not found")}`);
		}
	});
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

const postcssPlugin = {
	name: "postcss",
	setup(build) {
		build.onLoad({ filter: /\.css$/ }, async (args) => {
			const source = await fs.promises.readFile(args.path, "utf8");
			const result = await postcss([
				tailwindcssPostcss(),
				autoprefixer(),
			]).process(source, {
				from: args.path,
			});
			return { contents: result.css, loader: "css" };
		});
	},
};

function createBuildOptions() {
	return {
		entryPoints: entryPoints(),
		outdir: "./assets",
		bundle: true,
		sourcemap: true,
		minify: isProduction,
		logLevel: isProduction ? "silent" : "info",
		plugins: [postcssPlugin],
		target: ["esnext"],
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

// Function to run esbuild
async function build() {
	const startTime = Date.now();

	try {
		if (isProduction) {
			updateVersion(); // Update version in production
		}

		await esbuild.build(createBuildOptions());

		const entries = entryPoints();
		const scripts = [];
		const styles = [];

		for (const entry in entries) {
			if (entry.startsWith("js/")) {
				scripts.push(`./assets/${entry}.js`);
				scripts.push(`./assets/${entry}.js.map`); // Include source map
			} else if (entry.startsWith("css/")) {
				styles.push(`./assets/${entry}.css`);
				styles.push(`./assets/${entry}.css.map`); // Include source map
			}
		}

		// Log file sizes for styles
		if (styles.length > 0) {
			console.log(`\n🟪 Styles compiled with Tailwind CSS and Autoprefixer!`);
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

function queueBuild({ reload = false } = {}) {
	buildQueue = buildQueue
		.catch(() => {})
		.then(async () => {
			try {
				await build();
				if (!isProduction && reload) {
					bs.reload();
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
				proxy: "https://your-site.test",
				open: true,
				browser: ["firefox developer edition"],
			});

			chokidar
				.watch(["./templates/", "./woocommerce/", "./dev/css/", "./dev/js/"], {
					ignoreInitial: true,
				})
				.on("all", (event, filePath) => {
					console.log(`\n🚧 ${filePath} ${event}, rebuilding and reloading...`);
					queueBuild({ reload: true });
				});
		})
		.catch((error) => {
			console.error("🚨 Initial build failed:", error);
		});
} else {
	console.log(`🚀 Building for production...`);
	queueBuild().catch((error) => {
		console.error("🚨 Production build failed:", error);
	});
}
