#!/usr/bin/env node

/**
 * CLI bridge: converts a PPTX file to PDF using pptx-to-pdf.
 *
 * Usage: node bin/convert-pptx.js --input <path> --output <path>
 *
 * Exit codes:
 *   0 — success
 *   1 — invalid arguments
 *   2 — conversion failed
 */

const { convert } = require('pptx-to-pdf');
const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
const inputFlag = args.indexOf('--input');
const outputFlag = args.indexOf('--output');

if (inputFlag === -1 || outputFlag === -1 || !args[inputFlag + 1] || !args[outputFlag + 1]) {
    process.stderr.write('Usage: node bin/convert-pptx.js --input <path> --output <path>\n');
    process.exit(1);
}

const inputPath = path.resolve(args[inputFlag + 1]);
const outputPath = path.resolve(args[outputFlag + 1]);

if (!fs.existsSync(inputPath)) {
    process.stderr.write(`Input file not found: ${inputPath}\n`);
    process.exit(2);
}

(async () => {
    try {
        const pptx = fs.readFileSync(inputPath);
        const pdf = await convert(pptx);
        fs.writeFileSync(outputPath, pdf);
        process.exit(0);
    } catch (err) {
        process.stderr.write(`Conversion error: ${err.message}\n`);
        process.exit(2);
    }
})();
