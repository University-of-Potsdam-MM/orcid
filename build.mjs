/**
 * Standalone asset build for the ORCID TYPO3 extension.
 *
 * Replaces the former project-level gulp pipeline so the extension can be
 * built independently of the surrounding TYPO3 project. Sources live in
 * Resources/Private/Build, compiled output goes to Resources/Public and is
 * committed to the repository (TYPO3 serves those files directly; consumers
 * installing via composer do not run a build step).
 *
 * Usage: npm ci && npm run build
 */

import * as esbuild from 'esbuild'
import * as sass from 'sass'
import { mkdirSync, writeFileSync } from 'node:fs'
import { dirname } from 'node:path'

const BUILD = 'Resources/Private/Build'
const PUBLIC = 'Resources/Public'

// Shared esbuild options for browser-facing bundles that expose a global.
// format:iife matches the former gulp `bundleCitationJs` task and keeps the
// window.* globals the frontend scripts rely on.
const browserBundle = {
  bundle: true,
  platform: 'browser',
  format: 'iife',
  target: ['es2019'],
  minify: true,
}

function writeFile(file, contents) {
  mkdirSync(dirname(file), { recursive: true })
  writeFileSync(file, contents)
}

// 1. Stylesheet: SCSS -> compressed CSS
//    The ORCID SCSS is self-contained (no @import, no project variables) and
//    uses no properties that need vendor prefixes for the target browsers,
//    so plain sass compressed output matches the former gulp result.
const css = sass.compile(`${BUILD}/StyleSheets/OrcidStyles.scss`, {
  style: 'compressed',
})
writeFile(`${PUBLIC}/StyleSheets/OrcidStyles.css`, css.css)
console.log('✓ CSS  -> StyleSheets/OrcidStyles.css')

// 2. citation-js bundle (exposes window.Cite) — used for BibTeX -> CSL-JSON
await esbuild.build({
  ...browserBundle,
  entryPoints: [`${BUILD}/JavaScript/Vendor/citationjs-entry.js`],
  outfile: `${PUBLIC}/JavaScript/Vendor/citation-js.bundle.min.js`,
  sourcemap: true,
})
console.log('✓ JS   -> JavaScript/Vendor/citation-js.bundle.min.js')

// 3. citeproc-js (exposes window.CSL) — the CSL rendering engine.
//    Kept as a vendored source file (NOT npm): a newer citeproc release
//    caused rendering regressions, so this specific build is pinned in-tree.
//    Minify only — the file already defines the global CSL on load.
await esbuild.build({
  entryPoints: [`${BUILD}/JavaScript/citeproc.js`],
  outfile: `${PUBLIC}/JavaScript/citeproc.min.js`,
  minify: true,
})
console.log('✓ JS   -> JavaScript/citeproc.min.js')

// 4. Own frontend controller — minify only (no bundle, has no imports)
await esbuild.build({
  entryPoints: [`${BUILD}/JavaScript/orcid-citations.js`],
  outfile: `${PUBLIC}/JavaScript/orcid-citations.min.js`,
  target: ['es2019'],
  minify: true,
})
console.log('✓ JS   -> JavaScript/orcid-citations.min.js')

console.log('\nBuild complete.')
