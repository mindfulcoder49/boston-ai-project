import test from 'node:test';
import assert from 'node:assert/strict';

import { cloneResults, normalizeViewerReport } from '../../resources/js/Pages/Reports/Scoring/viewerState.js';

test('normalizeViewerReport detaches scoring payloads from the source report', () => {
  const initialReport = {
    job_id: 'job-123',
    artifact_name: 'scoring_results_resolution_8.json',
    resolution: 8,
    scoring_data: {
      parameters: {
        group_weights: {
          crimes: 2,
        },
      },
      results: [
        {
          h3_index: '882a100d2dfffff',
          score: 4.5,
          score_composition: [
            {
              secondary_group: 'crimes',
              avg_weekly_count: 2.25,
            },
          ],
        },
      ],
    },
  };

  const processed = normalizeViewerReport(initialReport);

  assert.notStrictEqual(processed.scoring_data, initialReport.scoring_data);
  assert.notStrictEqual(processed.scoring_data.results, initialReport.scoring_data.results);
  assert.notStrictEqual(processed.scoring_data.parameters, initialReport.scoring_data.parameters);

  processed.scoring_data.results[0].score = 9.75;
  processed.scoring_data.parameters.group_weights.crimes = 7;

  assert.equal(initialReport.scoring_data.results[0].score, 4.5);
  assert.equal(initialReport.scoring_data.parameters.group_weights.crimes, 2);
  assert.equal(processed.original_results[0].score, 4.5);
});

test('cloneResults returns a detached copy of the score array', () => {
  const results = [
    {
      h3_index: '882a100d2dfffff',
      score: 1.25,
      score_composition: [
        {
          secondary_group: 'noise',
          avg_weekly_count: 1.25,
        },
      ],
    },
  ];

  const cloned = cloneResults(results);
  cloned[0].score = 99;
  cloned[0].score_composition[0].avg_weekly_count = 0;

  assert.equal(results[0].score, 1.25);
  assert.equal(results[0].score_composition[0].avg_weekly_count, 1.25);
});
