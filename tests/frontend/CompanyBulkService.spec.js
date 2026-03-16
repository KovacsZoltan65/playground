import { beforeEach, describe, expect, it, vi } from "vitest";

const patchMock = vi.fn();
const deleteMock = vi.fn();

vi.stubGlobal("route", vi.fn((name) => name));

vi.mock("@/Services/BaseService", () => ({
    default: class BaseService {
        patch(...args) {
            return patchMock(...args);
        }

        delete(...args) {
            return deleteMock(...args);
        }
    },
}));

describe("CompanyService bulk actions", () => {
    beforeEach(() => {
        patchMock.mockReset();
        deleteMock.mockReset();
    });

    it("calls the bulk activate route with ids", async () => {
        patchMock.mockResolvedValue({ data: { count: 2 } });

        const { default: companyService } = await import("@/Services/CompanyService");

        await companyService.bulkActivate([1, 2]);

        expect(route).toHaveBeenCalledWith("companies.bulk-activate");
        expect(patchMock).toHaveBeenCalledWith("companies.bulk-activate", {
            ids: [1, 2],
        });
    });

    it("calls the bulk deactivate route with ids", async () => {
        patchMock.mockResolvedValue({ data: { count: 2 } });

        const { default: companyService } = await import("@/Services/CompanyService");

        await companyService.bulkDeactivate([3, 4]);

        expect(route).toHaveBeenCalledWith("companies.bulk-deactivate");
        expect(patchMock).toHaveBeenCalledWith("companies.bulk-deactivate", {
            ids: [3, 4],
        });
    });

    it("keeps bulk delete behavior unchanged", async () => {
        deleteMock.mockResolvedValue({ data: { count: 2 } });

        const { default: companyService } = await import("@/Services/CompanyService");

        await companyService.bulkDestroy([5, 6]);

        expect(route).toHaveBeenCalledWith("companies.bulk-destroy");
        expect(deleteMock).toHaveBeenCalledWith("companies.bulk-destroy", {
            data: { ids: [5, 6] },
        });
    });
});
