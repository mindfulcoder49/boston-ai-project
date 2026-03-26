# Pages and UX Inventory

This document catalogs every user-facing page in BostonScope/PublicDataWatch: its route, controller, Vue component, purpose, data flow, and notes on current UX state and improvement goals.

---

## Navigation Structure (Current)

Top-level navigation exposes: Home, Map, Data Metrics, Trends, Yearly Comparisons, Scoring Reports, News, Saved Maps. Auth-gated items: Profile, Reports (email), Subscription. Admin panel at `/admin`.

---

## Public Pages

### Home
- **Route**: `GET /`
- **Controller**: `HomeController::index()`
- **Vue Page**: `Pages/Home.vue`
- **Data passed**: `cities[]`, `dataCategories[]`, `stats{totalRecords, cityCount, dataCategoryCount}`
- **Purpose**: Landing page. Shows city cards (Boston, Cambridge, Everett, Chicago) with data type counts and a link to the relevant map. Shows data category cards. Shows aggregate stats.
- **Data source**: `config/cities.php`, `metrics_snapshots` table (homepage response cached for 1 hour)
- **UX notes**: Entry point for all users. City cards link to `/data-map/{dataType}` or `/combined-map?types=...`. Needs clear CTAs and a coherent visual hierarchy.

---

### Radial Map (General Area Search)
- **Route**: `GET /map/{lat?}/{lng?}`
- **Controller**: Route closure → `Inertia::render('RadialMap')`
- **Vue Page**: `Pages/RadialMap.vue`
- **API calls**: `POST /api/map-data` → `GenericMapController::getRadialMapData()` — returns data points from the `data_points` table within a radius of given coordinates
- **Purpose**: Address-centered radial search across all data types. User enters an address, map shows nearby incidents/events as dots.
- **UX notes**: Core discovery feature. Depends on `data_points` aggregation table. `AddressSearch`/`GoogleAddressSearch` components drive address input. Map: Leaflet via `BostonMap`/`MapDisplay` components.

---

### Data Map (Single Data Type)
- **Route**: `GET /data-map/{dataType}`
- **Controller**: `DataMapController::index()`
- **Vue Page**: `Pages/DataMap.vue`
- **API calls**: `POST /api/data/{dataType}` — filtered data for map display
- **Purpose**: Full map filtered to a single data type (e.g., crime, 311 cases, building permits). Supports natural language querying via `POST /api/natural-language-query/{dataType}`.
- **Components**: `DataMapComponent.vue`, `GenericFiltersControl.vue`, `GenericDataList.vue`, `GenericDataDisplay.vue`
- **UX notes**: Data-type-specific filters, list/map toggle. AI filter assistant via `AiAssistant.vue` / `ChatInput.vue`.

---

### Combined Data Map
- **Route**: `GET /combined-map?types=...`
- **Controller**: `DataMapController::combinedIndex()`
- **Vue Page**: `Pages/CombinedDataMap.vue`
- **API calls**: `POST /api/data/{dataType}` per type
- **Purpose**: Multi-layer map showing multiple data types simultaneously. Types passed via query string.
- **Components**: `CombinedDataMapComponent.vue`
- **UX notes**: Used when a city area has multiple data map keys. Layer toggling needed.

---

### Crime Map (Legacy / Boston-specific)
- **Route**: `GET /crime-map`
- **Controller**: `CrimeMapController::index()`
- **Vue Page**: `Pages/CrimeMap.vue`
- **API calls**: `POST /api/crime-data`, `POST /api/natural-language-query`
- **Purpose**: Boston-specific crime map (predates the generic DataMap). Supports natural language querying.
- **Components**: `CrimeMapComponent.vue`, `CrimeDataList.vue`, `CrimeData.vue`
- **UX notes**: Potentially redundant with `/data-map/crime` — worth evaluating consolidation.

---

### 311 Scatter Plot
- **Route**: `GET /scatter`
- **Controller**: `ThreeOneOneCaseController::indexnofilter()`
- **Vue Page**: `Pages/ThreeOneOneProject.vue`
- **Purpose**: Scatter plot visualization of 311 cases. Likely exploratory/legacy.
- **UX notes**: Unclear current role in navigation. Assess whether this is still a featured page or internal/legacy.

---

### Data Metrics
- **Route**: `GET /data-metrics`
- **Controller**: `MetricsController::index()`
- **Vue Page**: `Pages/DataMetrics.vue`
- **Data passed**: `metricsData[]` and `lastUpdated` from the current row in `metrics_snapshots` (falls back to `config/metrics.php` only if the table is unavailable or empty)
- **Purpose**: Dashboard showing dataset statistics (record counts, date ranges) per model/city.
- **Components**: `Components/Metrics/` subdirectory
- **UX notes**: Static/cached data. Useful for transparency. Should communicate how often data is refreshed.

---

## Analysis & Reporting Pages

### Trends Index
- **Route**: `GET /trends`
- **Controller**: `TrendsController::index()`
- **Vue Page**: `Pages/Trends/Index.vue`
- **Data passed**: `reportsByModel[]` — each entry has `model_name`, `model_key`, `analyses[]` (trend_id, column_name, h3_resolution, analysis_weeks_trend)
- **Purpose**: Directory listing of all available statistical analysis reports, grouped by data model.
- **Data source**: `Trend` Eloquent model (populated by `app:dispatch-statistical-analysis-jobs` Artisan command)
- **UX notes**: Entry point to individual analysis reports. Needs to communicate what each report shows (H3 resolution, time windows, grouping column) clearly.

---

### Statistical Analysis Viewer
- **Route**: `GET /reports/statistical-analysis/{trendId}`
- **Controller**: `StatisticalAnalysisReportController::show()`
- **Vue Page**: `Pages/Reports/StatisticalAnalysisViewer.vue`
- **Data passed**: `jobId`, `apiBaseUrl`, `reportData` (full `stage4_h3_anomaly.json` from S3), `reportTitle`
- **Purpose**: Displays Stage 4 H3 anomaly analysis results for a specific trend record. Interactive H3 hex map showing anomalies and trend data per cell.
- **Data source**: `Trend` model → `job_id` → S3 `{job_id}/stage4_h3_anomaly.json`
- **UX notes**: Heavy data page. Key UX questions: how does user navigate between cells? How is the anomaly significance communicated? Does it link to the Scoring page for the same job?

---

### Yearly Count Comparison Index
- **Route**: `GET /yearly-comparisons`
- **Controller**: `YearlyCountComparisonController::index()`
- **Vue Page**: `Pages/YearlyCountComparison/Index.vue`
- **Data passed**: `reportsByModel[]`
- **Purpose**: Directory of year-over-year comparison reports grouped by model.
- **Data source**: `YearlyCountComparison` Eloquent model
- **UX notes**: Similar structure to Trends Index. Ensure consistent nav and formatting between the two directories.

---

### Yearly Count Comparison Viewer
- **Route**: `GET /reports/yearly-comparison/{reportId}`
- **Controller**: `YearlyCountComparisonController::show()`
- **Vue Page**: `Pages/Reports/YearlyCountComparisonViewer.vue`
- **Data passed**: `jobId`, `apiBaseUrl`, `reportData` (fetched live from analysis API), `reportTitle`, `newsArticle?`
- **Purpose**: Shows year-over-year count comparison results. If a related published `NewsArticle` exists, links to it.
- **Data source**: Analysis API artifact served live via `Http::get("{ANALYSIS_API_URL}/api/v1/jobs/{job_id}/results/stage2_yearly_count_comparison.json")`
- **UX notes**: Unlike Stage 4 (which reads from S3), this fetches live from the API. If API is down, the page fails. Note the `newsArticle` link — this is a key cross-page connection.

---

### Scoring Reports Index
- **Route**: `GET /scoring-reports`
- **Controller**: `ScoringReportController::index()`
- **Vue Page**: `Pages/Reports/Scoring/Index.vue`
- **Data passed**: `reportGroups` — nested: city → date_range → sorted reports
- **Purpose**: Directory of neighborhood scoring reports (Stage 5 and Stage 6 artifacts), grouped by city and date range.
- **Data source**: S3 — scans all job directories for files starting with `scoring_results` or `stage6`. Cached forever (manual refresh via admin).
- **UX notes**: Cache-heavy page. Admin refresh route at `POST /admin/scoring-reports/refresh`. Grouping by city+date_range is the main organizational logic. Needs clear UI for what "resolution" means and why multiple resolutions exist for the same city/period.

---

### Scoring Report Viewer
- **Route**: `GET /scoring-reports/{jobId}/{artifactName}`
- **Controller**: `ScoringReportController::show()`
- **Vue Page**: `Pages/Reports/Scoring/Viewer.vue`
- **Data passed**: `reportGroup[]` (all reports in same city+date group, with scoring_data embedded), `initialReport`, `reportTitle`
- **API calls (client-side)**:
  - `POST /api/scoring-reports/score-for-location` — given an H3 index, returns score details + source analysis data
  - `GET /api/scoring-reports/source-analysis/{jobId}` — returns full Stage 4 source JSON
- **Purpose**: Interactive H3 map colored by neighborhood score. Clicking a cell shows its score breakdown and underlying anomaly analysis. Supports multiple resolutions in a single view.
- **Data source**: S3 scoring artifacts + S3 Stage 4 source artifact (cached in Laravel)
- **UX notes**: Most complex page. Key integration point: scoring data from Stage 5/6 + analysis detail from Stage 4. The `source_job_id` field in the scoring JSON links back to the Stage 4 job. Resolution switcher UI is important.

---

## News Pages

### News Index
- **Route**: `GET /news`
- **Controller**: `NewsArticleController::index()`
- **Vue Page**: `Pages/News/` (index)
- **Purpose**: Lists published AI-generated news articles derived from statistical analysis jobs.
- **Data source**: `NewsArticle` model (generated by `app:dispatch-news-article-generation-jobs`)
- **UX notes**: Articles are linked from YearlyCountComparison reports. Ensure consistent slug-based routing.

---

### News Article
- **Route**: `GET /news/{newsArticle:slug}`
- **Controller**: `NewsArticleController::show()`
- **Vue Page**: `Pages/News/` (show)
- **Purpose**: Individual AI-generated news article.
- **UX notes**: Cross-linked from `YearlyCountComparisonViewer`. Consider backlinks to source report.

---

## Saved Maps

### Saved Maps Index
- **Route**: `GET /saved-maps`
- **Controller**: `SavedMapController::index()`
- **Vue Page**: `Pages/UserSavedMapsPage.vue`
- **Purpose**: Lists user-saved map configurations and publicly featured maps.
- **UX notes**: `FeaturedUserMapsBanner.vue` used. Saved maps can be approved/featured by admin.

---

### View Saved Map
- **Route**: `GET /saved-maps/{savedMap}/view`
- **Controller**: `SavedMapController::view()`
- **Vue Page**: `Pages/ViewSavedMapPage.vue`
- **Purpose**: Publicly shareable view of a saved map configuration.
- **UX notes**: Public-facing — no auth required. Should clearly identify it as a community map.

---

## Auth-Gated Pages

### Profile
- **Route**: `GET /profile` (auth)
- **Controller**: `ProfileController::edit()`
- **Vue Page**: `Pages/Profile/` (edit)
- **Purpose**: Edit name, email, password. Redeem subscription codes.

---

### Email Reports History
- **Route**: `GET /reports` (auth)
- **Controller**: `ReportController::index()`
- **Vue Page**: `Pages/ReportIndex.vue`
- **Purpose**: History of location-based email reports generated for the user's saved locations.

---

### Email Report Detail
- **Route**: `GET /reports/{report}` (auth)
- **Controller**: `ReportController::show()`
- **Vue Page**: `Pages/Reports/Show.vue`
- **Purpose**: View/download a specific report.

---

### CSV Map Reports
- **Route**: `GET /csvreports/map`
- **Controller**: `ReportIndexController::index()`
- **Vue Page**: `Pages/ReportIndex.vue` (reused? confirm)
- **Purpose**: Index of CSV-based map export files.

---

### Subscription
- **Route**: `GET /subscription`
- **Controller**: `SubscriptionController::index()`
- **Vue Page**: `Pages/Subscription.vue`
- **Purpose**: Stripe subscription management (plans, success/cancel messages, billing portal).
- **UX notes**: Integrates with Stripe Cashier. Success/cancel status passed via query params.

---

## Help & Legal Pages

All rendered via Inertia route closures:

| Route | Vue Page | Notes |
|-------|----------|-------|
| `/help` | `Pages/Help/Index.vue` | Help landing |
| `/help/users` | `Pages/Help/ForUsers.vue` | |
| `/help/municipalities` | `Pages/Help/ForMunicipalities.vue` | |
| `/help/researchers` | `Pages/Help/ForResearchers.vue` | |
| `/help/investors` | `Pages/Help/ForInvestors.vue` | |
| `/help-contact` | `Pages/Support/HelpContact.vue` | Feedback form (POST /feedback → EmailController) |
| `/about-us` | `Pages/Company/AboutUs.vue` | |
| `/privacy-policy` | `Pages/Legal/PrivacyPolicy.vue` | |
| `/terms-of-use` | `Pages/Legal/TermsOfUse.vue` | |

---

## Admin Pages

All under `/admin` prefix, auth + verified middleware.

| Route | Controller Method | Purpose |
|-------|------------------|---------|
| `/admin` | `AdminController::index()` | Dashboard |
| `/admin/job-runs` | `AdminController::jobRunsIndex()` | Artisan job run history |
| `/admin/job-dispatcher` | `AdminController::jobDispatcherIndex()` | UI to dispatch analysis jobs to Python API |
| `/admin/maps` | `AdminMapController::index()` | Approve/feature saved maps |
| `/admin/users` | `AdminController::usersIndex()` | User management |
| `/admin/locations` | `AdminLocationController::index()` | Location management |
| `/admin/pipeline-file-logs` | `AdminController::pipelineFileLogsIndex()` | View data pipeline log runs |
| `/admin/pipeline-file-logs/{runId}` | `AdminController::showPipelineFileLogRun()` | Individual pipeline run log |
| `/admin/scoring-reports/refresh` | `ScoringReportController::refreshIndex()` | Bust scoring reports S3 cache |

---

## Key Shared Components

| Component | Used by |
|-----------|---------|
| `SharedNavMenu.vue` | All pages (main nav) |
| `Footer.vue` | All pages |
| `PageTemplate.vue` | Wrapper with nav/footer |
| `MapDisplay.vue` / `BostonMap.vue` | All map pages |
| `AddressSearch.vue` / `GoogleAddressSearch.vue` | Radial map, data maps |
| `AiAssistant.vue` / `ChatInput.vue` / `ChatHistory.vue` | Data Map, Crime Map |
| `GenericFiltersControl.vue` | DataMap, CrimeMap |
| `GenericDataList.vue` / `GenericDataDisplay.vue` | DataMap |
| `SubscriptionBanner.vue` | Various pages (upsell) |
| `DataVisibilityBanner.vue` | Map pages (data freshness) |
| `Pagination.vue` | List views |

---

## UX Flow: Analysis Report Discovery

The intended user flow for exploring analysis results currently requires knowing which URL to visit:

```
/trends (index) → /reports/statistical-analysis/{id} (Stage 4 viewer)
/yearly-comparisons (index) → /reports/yearly-comparison/{id} → /news/{slug}
/scoring-reports (index) → /scoring-reports/{jobId}/{artifact} (scoring viewer)
```

These three flows are currently separate. A key UX improvement goal is to cross-link them: the Stage 4 viewer should link to related scoring reports for the same job; scoring reports should link back to their source Stage 4 analysis.
