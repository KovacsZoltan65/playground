import { beforeEach, describe, expect, it, vi } from "vitest";

let createConfig;
let requestInterceptor;

vi.mock("@/helpers/config.js", () => ({
    CONFIG: {
        BASE_URL: "/api",
        TIMEOUT: 15000,
    },
}));

vi.mock("axios", () => ({
    default: {
        create: vi.fn((config) => {
            createConfig = config;

            return {
                interceptors: {
                    request: {
                        use: vi.fn((handler) => {
                            requestInterceptor = handler;
                        }),
                    },
                },
            };
        }),
    },
}));

describe("HttpClient", () => {
    beforeEach(() => {
        vi.resetModules();
        createConfig = undefined;
        requestInterceptor = undefined;
        document.head.innerHTML = "";
        document.cookie = "";
    });

    it("creates the shared axios client with the expected defaults", async () => {
        await import("@/Services/HttpClient.js");

        expect(createConfig).toEqual(
            expect.objectContaining({
                baseURL: "/api",
                timeout: 15000,
                withCredentials: true,
                headers: expect.objectContaining({
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                }),
            }),
        );
        expect(requestInterceptor).toEqual(expect.any(Function));
    });

    it("injects CSRF and XSRF headers in the request interceptor", async () => {
        document.head.innerHTML =
            '<meta name="csrf-token" content="csrf-token-value">';
        document.cookie = "XSRF-TOKEN=xsrf-token-value";

        await import("@/Services/HttpClient.js");

        const config = requestInterceptor({
            headers: {},
        });

        expect(config.headers["X-CSRF-TOKEN"]).toBe("csrf-token-value");
        expect(config.headers["X-XSRF-TOKEN"]).toBe("xsrf-token-value");
    });
});

