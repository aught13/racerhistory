import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function* walkSync(dir) {
    const files = fs.readdirSync(dir, { withFileTypes: true });
    for (const file of files) {
        if (file.isDirectory()) {
            yield* walkSync(path.join(dir, file.name));
        } else {
            yield path.join(dir, file.name);
        }
    }
}

const testDir = path.join(__dirname, 'webroot/js/tests');
const files = [...walkSync(testDir)].filter(f => f.endsWith('.test.js') || f.endsWith('.test.mjs'));

// List of failing tests to fix
const failingTests = [
    'admin.branches2.test.js',
    'admin.branches.more.test.js',
    'admin.branches.test.js',
    'admin.find-source-throws.test.js',
    'admin.requestsubmit.test.js',
    'admin.test.js',
    'crop-selector.branches.more.test.js',
    'crop-selector.branches.test.js',
    'crop-selector.test.js',
    'crop-selector.uncovered-branches.test.js',
    'games_sport_dynamic.branches2.test.js',
    'games_sport_dynamic.branches3.test.js',
    'games_sport_dynamic.branches4.test.js',
    'games_sport_dynamic.branches.test.js',
    'games_sport_dynamic.more.test.js',
    'image-selector.branches.test.js',
    'image-selector.crop.branches.test.js',
    'image-selector.test.js',
];

let fixed = 0;

for (const file of files) {
    const filename = path.basename(file);
    if (!failingTests.includes(filename)) continue;

    let content = fs.readFileSync(file, 'utf8');
    const original = content;

    // Check if this test uses this.admin or this.moduleVar pattern in beforeEach
    const thisModulePattern = /\bbeforeEach\s*\(\s*async\s*\(\)\s*=>\s*\{[\s\S]*?this\.(\w+)\s*=\s*await\s*import\(/;

    if (thisModulePattern.test(content)) {
        // Code to fix tests using this.varName pattern
        // We need to:
        // 1. Extract the variable name (e.g., "admin" from "this.admin")
        // 2. Replace this.admin with a module-level let declaration
        // 3. Update all references from this.admin to admin

        const match = content.match(/\bbeforeEach\s*\(\s*async\s*\(\)\s*=>\s*\{[\s\S]*?this\.(\w+)\s*=\s*await\s*import\(/);
        if (match) {
            const varName = match[1]; // e.g., "admin"

            // Add module-level variable declaration after imports
            const importsEnd = content.lastIndexOf("import ") + content.substring(content.lastIndexOf("import ")).indexOf(";") + 1;
            if (importsEnd > 6) {
                const beforeImports = content.substring(0, importsEnd);
                const afterImports = content.substring(importsEnd);

                if (!afterImports.includes(`let ${varName};`)) {
                    content = beforeImports + `\n\nlet ${varName};` + afterImports;
                }
            } else {
                // No imports found, add at top
                const firstNonComment = content.match(/^(\s*(\/\/.*\n)*\s*(\/\*[\s\S]*?\*\/\s)*)/);
                const insertPos = firstNonComment ? firstNonComment[0].length : 0;
                content = content.substring(0, insertPos) + `let ${varName};\n` + content.substring(insertPos);
            }

            // Replace this.admin = await import(...) with admin = await import(...)
            content = content.replace(
                new RegExp(`this\\.${varName}\\s*=\\s*await\\s*import`, 'g'),
                `${varName} = await import`
            );

            // Replace references from this.admin. to admin.
            content = content.replace(
                new RegExp(`this\\.${varName}\\.`, 'g'),
                `${varName}.`
            );

            // Replace const admin = this.admin; with const admin = admin; (redundant, but safe to cleanup after)
            // But more likely: const admin = this.admin; should be removed since admin is now module-level
            content = content.replace(
                new RegExp(`const\\s+${varName}\\s*=\\s*this\\.${varName};?`, 'g'),
                ''
            );

            if (content !== original) {
                fs.writeFileSync(file, content, 'utf8');
                console.log(`Fixed: ${filename}`);
                fixed++;
            }
        }
    } else if (content.includes(`modImport = await import`) && content.includes(`const mod = modImport.default || modImport`)) {
        // Fix pattern where function variables aren't extracted
        // Example: const modImport = await import(...); const mod = modImport.default || modImport;
        // But then using fetchMeta() directly without extracting from mod

        // Find all function calls that aren't defined in the test context
        const functionCallPattern = /\b(fetchMeta|renderEav|groupFields|buildFieldControl|cropImage|setCrop|loadPersonImage|loadPersonImagePreview|setPreviewFromId|initPersonImageSelector|initPersonImageUploadHandler|addSportAwareFieldControl|loadGameView|initSeasonScorecardTableau|initSearchBuilder|initGameSearch|initGameForm|initGameAddHandler)\s*\(/g;

        const matches = [...content.matchAll(functionCallPattern)];
        if (matches.length > 0) {
            // These functions need to be extracted from the module
            const functionNames = [...new Set(matches.map(m => m[1]))];

            // Find the import line and extract functions from mod
            const importMatch = content.match(/const\s+(\w+)\s*=\s*\w+\.default\s*\|\|\s*\w+;/);
            if (importMatch) {
                const modVarName = importMatch[1]; // e.g., "mod"

                // Add extraction of functions after the mod assignment
                const extractLine = `const { ${functionNames.join(', ')} } = ${modVarName};`;
                content = content.replace(
                    importMatch[0],
                    importMatch[0] + `\n        ${extractLine}`
                );

                if (content !== original) {
                    fs.writeFileSync(file, content, 'utf8');
                    console.log(`Fixed: ${filename}`);
                    fixed++;
                }
            }
        }
    }
}

console.log(`\nFixed ${fixed} test files`);
