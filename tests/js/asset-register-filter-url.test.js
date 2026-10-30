import { describe, expect, it } from 'vitest';
import { withMultiSelectFilter } from '../../resources/js/asset-register-ui.js';

describe('withMultiSelectFilter', () => {
    it('replaces indexed array params from paginator URLs and preserves other query params', () => {
        const current = new URL(
            'https://hk.test/asset-register/registry?status%5B0%5D=available&status%5B1%5D=in_use&type%5B0%5D=4&page=2&per_page=30',
        );

        const next = withMultiSelectFilter(current, 'status[]', ['broken']);

        expect(next.searchParams.getAll('status[]')).toEqual(['broken']);
        expect(next.searchParams.getAll('status[0]')).toEqual([]);
        expect(next.searchParams.getAll('status[1]')).toEqual([]);
        expect(next.searchParams.get('type[0]')).toBe('4');
        expect(next.searchParams.get('per_page')).toBe('30');
        expect(next.searchParams.has('page')).toBe(false);
    });

    it('clears both bracketed and unbracketed forms when the selection is empty', () => {
        const current = new URL(
            'https://hk.test/asset-register/registry?status=available&status%5B0%5D=in_use',
        );

        const next = withMultiSelectFilter(current, 'status[]', []);

        expect(next.searchParams.has('status')).toBe(false);
        expect(next.searchParams.has('status[0]')).toBe(false);
        expect(next.searchParams.has('status[]')).toBe(false);
    });
});
