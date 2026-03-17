import { trans } from "laravel-vue-i18n";

const PENDING_TOAST_STORAGE_KEY = "app.pending-toast";

function defaultSummary(severity) {
    return severity === "error" ? trans("Error") : trans("Success");
}

function defaultLife(severity) {
    return severity === "error" ? 4000 : 3000;
}

export function addToast(toast, { severity = "success", summary, detail, life } = {}) {
    if (!toast || !detail) {
        return;
    }

    toast.add({
        severity,
        summary: summary ?? defaultSummary(severity),
        detail,
        life: life ?? defaultLife(severity),
    });
}

export function showErrorToast(toast, detail = trans("Action failed.")) {
    addToast(toast, {
        severity: "error",
        detail,
    });
}

export function queueToast({ severity = "success", summary, detail, life } = {}) {
    if (typeof window === "undefined" || !detail) {
        return;
    }

    window.sessionStorage.setItem(
        PENDING_TOAST_STORAGE_KEY,
        JSON.stringify({
            severity,
            summary: summary ?? defaultSummary(severity),
            detail,
            life: life ?? defaultLife(severity),
        })
    );
}

export function queueSuccessToast(detail) {
    queueToast({
        severity: "success",
        detail,
    });
}

export function flushQueuedToast(toast) {
    if (typeof window === "undefined") {
        return false;
    }

    const payload = window.sessionStorage.getItem(PENDING_TOAST_STORAGE_KEY);

    if (!payload) {
        return false;
    }

    window.sessionStorage.removeItem(PENDING_TOAST_STORAGE_KEY);

    try {
        addToast(toast, JSON.parse(payload));

        return true;
    } catch {
        return false;
    }
}
