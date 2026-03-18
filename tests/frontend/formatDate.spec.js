import { describe, expect, it, vi } from "vitest";

import {
    formatDate,
    formatDateTime,
    formatDateValue,
    formatTime,
} from "@/Support/dates/formatDate";

vi.mock("laravel-vue-i18n", () => ({
    currentLocale: { value: "en-US" },
    trans: (value) => value,
}));

vi.mock("@/helpers/config", () => ({
    CONFIG: {
        DATE_FORMAT: "yyyy-mm-dd",
        TIME_FORMAT: "HH:MM",
        DATETIME_FORMAT: "yyyy-mm-dd HH:MM",
    },
}));

describe("formatDate", () => {
    it("formats date, time and datetime values with dedicated helpers", () => {
        const value = new Date(2026, 2, 18, 14, 5, 0);

        expect(formatDate(value)).toBe("2026-03-18");
        expect(formatTime(value)).toBe("14:05");
        expect(formatDateTime(value)).toBe("2026-03-18 14:05");
    });

    it("accepts appearance as a parameter", () => {
        const value = "2026-03-18T14:05:00Z";
        const date = new Date(value);

        expect(formatDateValue(value, { type: "date", appearance: "short" })).toBe(
            new Intl.DateTimeFormat("en-US", { dateStyle: "short" }).format(date),
        );
        expect(formatDateValue(value, { type: "datetime", appearance: "long" })).toBe(
            new Intl.DateTimeFormat("en-US", {
                dateStyle: "long",
                timeStyle: "short",
            }).format(date),
        );
    });

    it("accepts a custom pattern parameter", () => {
        const value = new Date(2026, 2, 18, 14, 5, 9);

        expect(
            formatDateTime(value, {
                pattern: "yyyy-mm-dd HH:MM:DD",
            }),
        ).toBe("2026-03-18 14:05:18");
    });

    it("returns the fallback for empty or invalid values", () => {
        expect(formatDate(null)).toBe("N/A");
        expect(formatDateValue("invalid-value", { fallback: "-" })).toBe("-");
    });
});
