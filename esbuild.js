import esbuild from 'esbuild';
import postcss from 'postcss';
import autoprefixer from 'autoprefixer';
import tailwindcssPostcss from '@tailwindcss/postcss';
import chokidar from 'chokidar';
import chalk from 'chalk';
import browserSync from 'browser-sync';
import fs from 'fs';
import path from 'path';

// Suppress deprecation warnings
process.emitWarning = (warning, type) => {
    if (type !== 'DeprecationWarning') {
        console.warn(warning);
    }
};

// Determine if the environment is production
const isProduction = process.env.NODE_ENV === 'production';

// BrowserSync instance for live reload
const bs = browserSync.create();

// Function to update CSS version in the style.css file (only in production)
function updateVersion() {
    const styleFilePath = './style.css';
    const currentDate = new Date();
    const dateStr = currentDate.toLocaleString('it-IT');

    let content = fs.readFileSync(styleFilePath, { encoding: 'utf8' });
    let newVersion = '';

    content = content.replace(
        /(Version: \d+\.\d+)([a-z]+)(\d+) \((\d{2}\/\d{2}\/\d{4}), (\d{2}:\d{2}:\d{2})\)/i,
        (match, versionPrefix, versionSuffix, buildNumber) => {
            const newBuildNumber = parseInt(buildNumber, 10) + 1;
            newVersion = `${versionPrefix}${versionSuffix}${newBuildNumber} (${dateStr})`;
            return newVersion;
        }
    );

    fs.writeFileSync(styleFilePath, content, { encoding: 'utf8' });
    console.log(`📦 ${newVersion}`);
}

// Function to log file sizes of generated assets
function logFileSizes(files) {
    files.forEach((file) => {
        if (fs.existsSync(file)) {
            const stats = fs.statSync(file);
            const size = (stats.size / 1024).toFixed(2) + ' KB';
            console.log(`   ${file}    ${chalk.cyan(size)}`);
        } else {
            console.log(`   ${file}    ${chalk.red('File not found')}`);
        }
    });
}

// Entry points for JavaScript and CSS
function entryPoints() {
    const entryPoints = {};
    const jsDir = './dev/js';
    const cssDir = './dev/css';

    // Add JS files
    fs.readdirSync(jsDir).forEach((file) => {
        if (file.endsWith('.js')) {
            const name = path.basename(file, '.js');
            entryPoints[`js/${name}.min`] = path.join(jsDir, file);
        }
    });

    // Add CSS files
    fs.readdirSync(cssDir).forEach((file) => {
        if (file.endsWith('.css')) {
            const name = path.basename(file, '.css');
            entryPoints[`css/${name}.min`] = path.join(cssDir, file);
        }
    });

    return entryPoints;
}

// Build options for esbuild
const buildOptions = {
    entryPoints: entryPoints(),
    outdir: './assets',
    bundle: true,
    sourcemap: true,
    minify: isProduction,
    logLevel: 'silent',
    plugins: [
        {
            name: 'postcss',
            setup(build) {
                build.onLoad({ filter: /\.css$/ }, async (args) => {
                    const source = await fs.promises.readFile(args.path, 'utf8');
                    const result = await postcss([tailwindcssPostcss(), autoprefixer()]).process(source, {
                        from: args.path,
                    });
                    return { contents: result.css, loader: 'css' };
                });
            },
        },
    ],
    target: ['esnext'],
    external: ['*.woff', '*.woff2', '*.ttf', '*.eot', '*.png', '*.svg', '*.jpg', '*.webp'],
};

// Function to run esbuild
async function build() {
    const startTime = Date.now();

    try {
        if (isProduction) {
            updateVersion(); // Update version in production
        }

        await esbuild.build(buildOptions);

        const entries = entryPoints();
        const scripts = [];
        const styles = [];

        for (const entry in entries) {
            if (entry.startsWith('js/')) {
                scripts.push(`./assets/${entry}.js`);
                scripts.push(`./assets/${entry}.js.map`); // Include source map
            } else if (entry.startsWith('css/')) {
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

// Watch for file changes during development
if (!isProduction) {
    console.log(`🚀 Starting development server...`);

    build().then(() => {
        console.log(`🔭 Watching for changes...\n`);

        bs.init({
            proxy: 'https://www.domain.com',
            open: true,
            browser: ['firefox developer edition'],
        });

        chokidar.watch(['./templates/', './dev/css/', './dev/js/'], { ignoreInitial: true }).on('all', async (event, filePath) => {
            console.log(`\n🚧 ${filePath} ${event}, rebuilding and reloading...`);
            await build();
            bs.reload();
        });
    });
} else {
    console.log(`🚀 Building for production...`);
    build();
}
