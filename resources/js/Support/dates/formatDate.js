import { currentLocale, trans } from "laravel-vue-i18n";
import { CONFIG } from "@/helpers/config";

const FORMAT_OPTIONS = {
    date: {
        short: { dateStyle: "short" },
        medium: { dateStyle: "medium" },
        long: { dateStyle: "long" },
    },
    time: {
        short: { timeStyle: "short" },
        medium: { timeStyle: "medium" },
        long: { timeStyle: "long" },
    },
    datetime: {
        short: { dateStyle: "short", timeStyle: "short" },
        medium: { dateStyle: "medium", timeStyle: "short" },
        long: { dateStyle: "long", timeStyle: "short" },
    },
};

const normalizeDate = (value) => {
    if (!value) {
        return null;
    }

    const date = value instanceof Date ? value : new Date(value);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return date;
};

const pad = (value) => String(value).padStart(2, "0");

const DEFAULT_PATTERNS = {
    date: CONFIG.DATE_FORMAT,
    time: CONFIG.TIME_FORMAT,
    datetime: CONFIG.DATETIME_FORMAT,
};

const formatWithPattern = (date, pattern) =>
    pattern
        .replaceAll("yyyy", String(date.getFullYear()))
        .replaceAll("mm", pad(date.getMonth() + 1))
        .replaceAll("dd", pad(date.getDate()))
        .replaceAll("HH", pad(date.getHours()))
        .replaceAll("MM", pad(date.getMinutes()))
        .replaceAll("DD", pad(date.getDate()));

export const formatDateValue = (
    value,
    options = {},
) => {
    const {
        type = "datetime",
        appearance = "medium",
        pattern = null,
        locale = currentLocale.value,
        fallback = trans("N/A"),
    } = options;
    const normalizedType = FORMAT_OPTIONS[type] ? type : "datetime";
    const normalizedAppearance = FORMAT_OPTIONS[normalizedType][appearance]
        ? appearance
        : "medium";
    const hasExplicitAppearance = Object.prototype.hasOwnProperty.call(options, "appearance");
    const resolvedPattern = pattern ?? (!hasExplicitAppearance ? DEFAULT_PATTERNS[normalizedType] : null);
    const date = normalizeDate(value);

    if (!date) {
        return fallback;
    }

    if (resolvedPattern) {
        return formatWithPattern(date, resolvedPattern);
    }

    return new Intl.DateTimeFormat(
        locale,
        FORMAT_OPTIONS[normalizedType][normalizedAppearance],
    ).format(date);
};

export const formatDate = (value, options = {}) =>
    formatDateValue(value, { ...options, type: "date" });

export const formatTime = (value, options = {}) =>
    formatDateValue(value, { ...options, type: "time" });

export const formatDateTime = (value, options = {}) =>
    formatDateValue(value, { ...options, type: "datetime" });
