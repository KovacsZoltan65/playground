import { beforeEach, describe, expect, it, vi } from "vitest";

const deleteMock = vi.fn();

vi.stubGlobal("route", vi.fn((name) => name));

vi.mock("@/Services/BaseService", () => ({
    default: class BaseService {
        delete(...args) {
            return deleteMock(...args);
        }
    },
}));

describe("PermissionService bulk actions", () => {
    beforeEach(() => {
        deleteMock.mockReset();
    });

    it("keeps bulk delete behavior aligned with the admin modules", async () => {
        deleteMock.mockResolvedValue({ data: { deleted: 2 } });

        const { default: permissionService } = await import("@/Services/PermissionService");

        await permissionService.bulkDestroy([5, 6]);

        expect(route).toHaveBeenCalledWith("permissions.bulk-destroy");
        expect(deleteMock).toHaveBeenCalledWith("permissions.bulk-destroy", {
            data: { ids: [5, 6] },
        });
    });
});
