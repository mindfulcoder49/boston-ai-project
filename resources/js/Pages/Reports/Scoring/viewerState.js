function cloneJsonPayload(value, fallback) {
  if (value === undefined) {
    return fallback;
  }

  return JSON.parse(JSON.stringify(value));
}

export function cloneResults(results) {
  return cloneJsonPayload(results ?? [], []);
}

export function normalizeViewerReport(report) {
  const normalizedReport = cloneJsonPayload(report ?? {}, {});
  const originalResults = cloneResults(normalizedReport.scoring_data?.results);
  const originalLookup = Object.fromEntries(originalResults.map((result) => [result.h3_index, result]));

  return {
    ...normalizedReport,
    original_results: originalResults,
    original_lookup: originalLookup,
    result_lookup: Object.fromEntries(originalResults.map((result) => [result.h3_index, result])),
  };
}
