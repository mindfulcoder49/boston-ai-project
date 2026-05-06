# 2026-05-06 Local Ingestion Feasibility

## Goal

Test whether PublicDataWatch can move the Boston and Everett acquisition flow into Laravel/PHP and stop depending on the external scraper/PDF helper API.

Scope tested:

- Boston dataset download
- Everett page scraping for PDF discovery
- Everett PDF download
- Everett PDF conversion into `.md` text that the existing parser can still consume
- Node package based PDF extraction as a Hostinger-friendly replacement for `pdftotext`

## Probe Command

Implemented local probe command:

```bash
php artisan app:test-local-ingestion-feasibility \
  --boston-dataset=crime-incident-reports \
  --everett-year=2025
```

The command writes artifacts and a machine-readable summary under:

```bash
storage/app/feasibility/local_ingestion/<timestamp>/
```

For the final May 6, 2026 run, the output directory was:

```bash
storage/app/feasibility/local_ingestion/20260506_164427/
```

The probe writes:

- native `pdftotext` conversion artifacts
- Node `pdf-parse@1.1.4` conversion artifacts
- Everett page HTML captured via both plain HTTP and Playwright

## Findings

### Boston

- Direct in-app HTTP download worked without the external scraper service.
- The probe downloaded `crime-incident-reports` successfully:
  - status `200`
  - content type `application/octet-stream`
  - bytes `49,898,668`
- Playwright browser navigation to the same Boston URL consistently raised:
  - `page.goto: Download is starting`
- That confirms browser reachability, but the current PHP binding did not give a clean saved-file path through the tested download flow.
- Playwright PHP `APIRequestContext` is not usable in the currently installed vendor/runtime combination for this work:
  - probe result: `Unknown action: api.fetch`

Practical interpretation:

- Boston does not appear to need the external Python helper.
- Boston does not appear to need Playwright for the file bytes.
- The cleanest in-app replacement for Boston is plain Laravel HTTP download.

### Everett page discovery

- The populated Everett log listings are on the `2025` pages, which currently contain late `2025` plus `2026` PDFs.
- The `2026` page URLs looked incomplete in ad hoc inspection, so the probe intentionally used `2025`.
- Plain HTTP and Playwright returned the same PDF link counts in the final probe.

Probe results:

- daily page `https://www.everettpolicema.com/daily_log_2025/daily_log.php`
  - HTTP status `200`, extracted PDF links `491`
  - Playwright status `200`, extracted PDF links `491`
- arrest page `https://www.everettpolicema.com/arrest_log_2025/arrest_log.php`
  - HTTP status `200`, extracted PDF links `70`
  - Playwright status `200`, extracted PDF links `70`

Practical interpretation:

- Everett page discovery can run inside Laravel.
- Everett does not appear to need Playwright for page discovery on the tested pages.
- Plain HTTP is the cleaner default, with Playwright only as fallback if the source becomes browser-sensitive later.

### Everett PDF download and conversion

- Direct in-app HTTP download of Everett PDFs worked.
- Local `pdftotext` is installed on this machine and produced stable text output.
- The repo Node extractor `scripts/extract_pdf_text.cjs` with `pdf-parse@1.1.4` also produced parser-compatible text output.

Probe results:

- daily PDF `call_log_20260505.pdf`
  - status `200`
  - bytes `42,929`
  - native `pdftotext` parsed daily entries: `58`
  - repo Node extractor parsed daily entries: `58`
- arrest PDF `arr_log_20260503.pdf`
  - status `200`
  - bytes `44,285`
  - native `pdftotext` parsed arrest entries: `7`
  - repo Node extractor parsed arrest entries: `7`

Additional runtime validation:

- the repo script `scripts/extract_pdf_text.cjs` plus `pdf-parse@1.1.4` also worked in a Node `20.2.0` smoke test
- Everett daily under Node `20.2.0`: `58/58` parser-compatible entries
- Everett arrest under Node `20.2.0`: `7/7` parser-compatible entries

Practical interpretation:

- Everett no longer appears to need the external PDF-to-Markdown helper.
- The current Everett downstream parser mainly depends on line layout, not rich Markdown semantics.
- A lightweight conversion path of:
  - download PDF
  - run the repo Node extractor `scripts/extract_pdf_text.cjs`
  - save normalized text as `.md`
  is sufficient for feasibility.
- For the tested Everett PDFs, `pdf-parse@1.1.4` is a cleaner Hostinger path than installing Poppler binaries.

## Recommended Migration Shape

1. Replace Boston acquisition with plain Laravel HTTP download.
   - No Playwright required unless future Boston endpoints become browser-only.

2. Replace Everett acquisition with:
   - in-app page fetch for PDF link extraction
   - direct Laravel HTTP PDF download
   - local Node script `scripts/extract_pdf_text.cjs`
   - existing Everett parser and CSV pipeline unchanged

3. Do not base the migration on Playwright PHP `APIRequestContext`.
   - In this environment it failed with `Unknown action: api.fetch`.

4. Treat Playwright browser usage as optional and targeted.
   - not currently necessary for Boston bytes
   - not currently necessary for Everett page discovery
   - not currently necessary for Everett PDF download
   - browser download handling in the current PHP binding needs more work before using it as the main file-transfer path

## Risk Notes

- Node/npm do exist on Hostinger through `nvm`, but the current production version observed was `v20.2.0`.
- The committed repo target `pdf-parse@1.1.4` worked under Node `20.2.0`, but newer `pdf-parse` releases did not.
- The production deploy script currently runs `npm run build` but does not install newly added npm dependencies during deploy.
- Laravel production commands that invoke Node from PHP will need a stable Node binary path outside login-shell PATH assumptions.
- Everett `2026` listing pages should be checked before production cutover because the live content appears to remain on the `2025` pages for now.

## Conclusion

Moving Boston and Everett ingestion into this app looks feasible.

Most important conclusions:

- the external Python scraper/PDF backend is not structurally required for the tested Boston and Everett paths
- Playwright is not required for the tested Boston dataset download, Everett page discovery, or Everett PDF download paths
- Everett PDF text extraction can run from this repo with `pdf-parse@1.1.4`, including under Node `20.2.0`

Best next implementation sequence:

1. ship Boston local HTTP download replacement
2. ship Everett local HTTP page fetch + PDF download + Node extractor behind a dry-run or alternate command path
3. update Hostinger deploy/runtime so `pdf-parse@1.1.4` is installed and Node is addressable from Laravel before making it the default production path
