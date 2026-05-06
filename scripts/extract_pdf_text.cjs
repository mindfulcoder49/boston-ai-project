#!/usr/bin/env node

const fs = require('fs');
const pdf = require('pdf-parse');

async function main() {
  const [inputPath, outputPath] = process.argv.slice(2);

  if (!inputPath || !outputPath) {
    console.error('Usage: node scripts/extract_pdf_text.cjs <input-pdf> <output-text>');
    process.exit(1);
  }

  const dataBuffer = fs.readFileSync(inputPath);
  const result = await pdf(dataBuffer);

  fs.writeFileSync(outputPath, result.text, 'utf8');
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
