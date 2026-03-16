import { describe, expect, it, vi } from "vitest";
import { requestConfirmation } from "@/Support/confirm/requestConfirmation";

describe("requestConfirmation", () => {
    it("resolves true when the dialog is accepted", async () => {
        const requireMock = vi.fn((options) => options.accept());

        await expect(
            requestConfirmation({ require: requireMock }, { message: "Confirm?" })
        ).resolves.toBe(true);

        expect(requireMock).toHaveBeenCalled();
    });

    it("resolves false when the dialog is rejected", async () => {
        const requireMock = vi.fn((options) => options.reject());

        await expect(
            requestConfirmation({ require: requireMock }, { message: "Confirm?" })
        ).resolves.toBe(false);

        expect(requireMock).toHaveBeenCalled();
    });
});
