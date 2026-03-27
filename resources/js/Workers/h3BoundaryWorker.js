import * as h3 from 'h3-js';

const boundaryCache = new Map();

self.onmessage = ({ data }) => {
    const { requestId, cells = [] } = data ?? {};

    try {
        const boundaries = {};

        for (const cell of cells) {
            if (!boundaryCache.has(cell)) {
                boundaryCache.set(cell, h3.cellToBoundary(cell, true));
            }
            boundaries[cell] = boundaryCache.get(cell);
        }

        self.postMessage({ requestId, boundaries });
    } catch (error) {
        self.postMessage({
            requestId,
            error: error instanceof Error ? error.message : 'Unknown H3 worker error.',
        });
    }
};
