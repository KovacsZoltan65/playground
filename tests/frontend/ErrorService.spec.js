import { beforeEach, describe, expect, it, vi } from "vitest";

const getMock = vi.fn();
const postMock = vi.fn();
const uuidMock = vi.fn(() => "uuid-test");

vi.mock("uuid", () => ({
    v4: uuidMock,
}));

vi.mock("@/Services/HttpClient.js", () => ({
    apiClient: {
        get: getMock,
        post: postMock,
    },
}));

describe("ErrorService", () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();

        global.route = vi.fn((name) => {
            if (typeof name === "undefined") {
                return {
                    has: (routeName) => routeName === "client-errors.store",
                };
            }

            return `/${name}`;
        });
    });

    it("loads activity logs through the shared route helper", async () => {
        getMock.mockResolvedValue({ data: [] });

        const { default: ErrorService } = await import("@/Services/ErrorService.js");

        await ErrorService.getLogs({ event: "frontend-error" });

        expect(getMock).toHaveBeenCalledWith("/activity-logs.list", {
            params: { event: "frontend-error" },
        });
    });

    it("posts a structured client error payload when the route exists", async () => {
        postMock.mockResolvedValue({ data: { ok: true } });

        const { default: ErrorService } = await import("@/Services/ErrorService.js");

        await ErrorService.logClientError(
            {
                message: "Something failed",
                stack: "stack-trace",
                component: "CompanyIndex",
                info: "during refresh",
            },
            {
                category: "frontend_error",
                priority: "high",
                data: { companyId: 1 },
            },
        );

        expect(postMock).toHaveBeenCalledWith(
            "/client-errors.store",
            expect.objectContaining({
                message: "Something failed",
                component: "CompanyIndex",
                category: "frontend_error",
                priority: "high",
                uniqueErrorId: "uuid-test",
                data: { companyId: 1 },
            }),
        );
    });

    it("returns null when the client error route is unavailable", async () => {
        global.route = vi.fn(() => ({
            has: () => false,
        }));

        const { default: ErrorService } = await import("@/Services/ErrorService.js");

        const result = await ErrorService.logClientError(new Error("No route"));

        expect(result).toBeNull();
        expect(postMock).not.toHaveBeenCalled();
    });
});

