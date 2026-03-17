import { beforeEach, describe, expect, it, vi } from "vitest";

import {
    addToast,
    flushQueuedToast,
    queueSuccessToast,
    showErrorToast,
} from "@/Support/toast/toastHelpers";

vi.mock("laravel-vue-i18n", () => ({
    trans: (value) => value,
}));

describe("toastHelpers", () => {
    beforeEach(() => {
        window.sessionStorage.clear();
    });

    it("queues a success toast and flushes it once", () => {
        const toast = { add: vi.fn() };

        queueSuccessToast("Created successfully.");

        expect(flushQueuedToast(toast)).toBe(true);
        expect(toast.add).toHaveBeenCalledWith({
            severity: "success",
            summary: "Success",
            detail: "Created successfully.",
            life: 3000,
        });

        expect(flushQueuedToast(toast)).toBe(false);
    });

    it("shows an immediate error toast with fallback text", () => {
        const toast = { add: vi.fn() };

        showErrorToast(toast);

        expect(toast.add).toHaveBeenCalledWith({
            severity: "error",
            summary: "Error",
            detail: "Action failed.",
            life: 4000,
        });
    });

    it("ignores empty toast details", () => {
        const toast = { add: vi.fn() };

        addToast(toast, { severity: "success", detail: "" });

        expect(toast.add).not.toHaveBeenCalled();
    });
});
