import { beforeEach, describe, expect, it, vi } from "vitest";

const patchMock = vi.fn();
const deleteMock = vi.fn();

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

describe("EmployeeService bulk actions", () => {
    beforeEach(() => {
        patchMock.mockReset();
        deleteMock.mockReset();
    });

    it("calls the bulk activate endpoint with ids", async () => {
        patchMock.mockResolvedValue({ data: { count: 2 } });

        const { default: employeeService } = await import("@/Services/EmployeeService");

        await employeeService.bulkActivate([1, 2]);

        expect(patchMock).toHaveBeenCalledWith("/employees/bulk-activate", {
            ids: [1, 2],
        });
    });

    it("calls the bulk deactivate endpoint with ids", async () => {
        patchMock.mockResolvedValue({ data: { count: 2 } });

        const { default: employeeService } = await import("@/Services/EmployeeService");

        await employeeService.bulkDeactivate([3, 4]);

        expect(patchMock).toHaveBeenCalledWith("/employees/bulk-deactivate", {
            ids: [3, 4],
        });
    });

    it("keeps bulk delete behavior unchanged", async () => {
        deleteMock.mockResolvedValue({ data: { deleted: 2 } });

        const { default: employeeService } = await import("@/Services/EmployeeService");

        await employeeService.bulkDestroy([5, 6]);

        expect(deleteMock).toHaveBeenCalledWith("/employees", {
            data: { ids: [5, 6] },
        });
    });
});
