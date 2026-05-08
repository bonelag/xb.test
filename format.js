const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'public', 'assets', 'unminify', 'index.unminify.js');
let content = fs.readFileSync(filePath, 'utf8');

// Replace multiline ["path", { d: "...", key: "..." }] arrays with a single-line format
const regex = /\[\s*"path"\s*,\s*\{\s*d\s*:\s*("[^"]+")\s*,\s*key\s*:\s*("[^"]+")\s*,?\s*\}\s*,?\s*\]/g;

const newContent = content.replace(regex, '["path", { d: $1, key: $2 }]');

if (content !== newContent) {
    fs.writeFileSync(filePath, newContent, 'utf8');
    console.log('Formatted successfully!');
} else {
    console.log('No matches found or already formatted.');
}
