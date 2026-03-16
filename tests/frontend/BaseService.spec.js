import { describe, expect, it, vi } from "vitest";

const patchSpy = vi.fn();

vi.mock("@/Services/HttpClient.js", () => ({
    apiClient: {
        interceptors: {
            response: {
                use: vi.fn(),
            },
        },
        patch: patchSpy,
    },
}));

vi.mock("@/Services/ErrorService.js", () => ({
    default: {
        logClientError: vi.fn(),
    },
}));

describe("BaseService", () => {
    it("forwards patch requests to the shared api client", async () => {
        patchSpy.mockResolvedValue({ data: { ok: true } });

        const { default: BaseService } = await import("@/Services/BaseService");
        const service = new BaseService();

        await service.patch("/companies/bulk-activate", { ids: [1, 2] });

        expect(patchSpy).toHaveBeenCalledWith("/companies/bulk-activate", {
            ids: [1, 2],
        }, {});
    });
});
