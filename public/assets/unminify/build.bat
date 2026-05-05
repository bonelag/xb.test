@echo off
echo Building XBoard Admin...
cd /d "%~dp0"
npx esbuild index.unminify.js --minify --loader:.js=jsx --jsx-factory=__ReactInstance.createElement --jsx-fragment=__ReactInstance.Fragment --format=esm --outfile=..\admin\assets\index.min.js
echo Build complete!
pause
