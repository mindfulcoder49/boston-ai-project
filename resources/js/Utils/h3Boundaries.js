import H3BoundaryWorker from '@/Workers/h3BoundaryWorker.js?worker';

const boundaryCache = new Map();
let requestCounter = 0;
let worker = null;
const pending = new Map();

function ensureWorker() {
    if (typeof window === 'undefined') {
        return null;
    }

    if (!worker) {
        worker = new H3BoundaryWorker();
        worker.onmessage = ({ data }) => {
            const { requestId, boundaries = {}, error } = data ?? {};
            const request = pending.get(requestId);
            if (!request) {
                return;
            }

            pending.delete(requestId);

            if (error) {
                request.reject(new Error(error));
                return;
            }

            Object.entries(boundaries).forEach(([cell, boundary]) => {
                boundaryCache.set(cell, boundary);
            });

            request.resolve(boundaries);
        };
    }

    return worker;
}

export async function ensureGeoJsonBoundaries(cells) {
    const uniqueCells = [...new Set((cells ?? []).filter(Boolean))];
    const missingCells = uniqueCells.filter(cell => !boundaryCache.has(cell));

    if (missingCells.length === 0) {
        return uniqueCells.reduce((acc, cell) => {
            acc[cell] = boundaryCache.get(cell);
            return acc;
        }, {});
    }

    const activeWorker = ensureWorker();
    if (!activeWorker) {
        return {};
    }

    const requestId = ++requestCounter;

    const result = await new Promise((resolve, reject) => {
        pending.set(requestId, { resolve, reject });
        activeWorker.postMessage({ requestId, cells: missingCells });
    });

    return uniqueCells.reduce((acc, cell) => {
        acc[cell] = boundaryCache.get(cell) ?? result[cell];
        return acc;
    }, {});
}

export function getGeoJsonBoundary(cell) {
    return boundaryCache.get(cell) ?? null;
}
