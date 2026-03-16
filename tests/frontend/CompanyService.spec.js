import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import companyService from '@/Services/CompanyService';

describe('CompanyService', () => {
    const originalRoute = globalThis.route;

    beforeEach(() => {
        globalThis.route = vi.fn((name, param) => {
            if (name === 'companies.list') {
                return '/companies/list';
            }

            if (name === 'companies.show') {
                return `/companies/${param}`;
            }

            return '/unknown';
        });
    });

    afterEach(() => {
        vi.restoreAllMocks();
        globalThis.route = originalRoute;
    });

    it('uses the named route for list requests', async () => {
        const getSpy = vi.spyOn(companyService, 'get').mockResolvedValue({
            data: { data: [], meta: {} },
        });

        await companyService.list({ page: 1 });

        expect(globalThis.route).toHaveBeenCalledWith('companies.list');
        expect(getSpy).toHaveBeenCalledWith('/companies/list', {
            params: { page: 1 },
        });
    });

    it('uses the named route for show requests', async () => {
        const getSpy = vi.spyOn(companyService, 'get').mockResolvedValue({
            data: { data: { id: 5 } },
        });

        await companyService.show(5);

        expect(globalThis.route).toHaveBeenCalledWith('companies.show', 5);
        expect(getSpy).toHaveBeenCalledWith('/companies/5');
    });
});
