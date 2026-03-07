import { usePage } from '@inertiajs/vue3';

/**
 * Returns a getName(h3Index) function that resolves an H3 index to a
 * human-readable location name from the globally-shared h3LocationNames map.
 *
 * Falls back to the h3Index itself if no name is found, or to the optional
 * second argument if provided.
 *
 * Usage:
 *   import { useH3Names } from '@/composables/useH3Names';
 *   const { getName } = useH3Names();
 *   getName('8928a3b3fffffff')           // → "Beacon Hill, Boston"
 *   getName('8928a3b3fffffff', 'Area')   // → "Area" if not found
 */
export function useH3Names() {
    const page = usePage();

    const getName = (h3Index, fallback = null) => {
        return page.props.h3LocationNames?.[h3Index] ?? fallback ?? h3Index;
    };

    const hasName = (h3Index) => {
        return h3Index in (page.props.h3LocationNames ?? {});
    };

    return { getName, hasName };
}
