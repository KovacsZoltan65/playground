import { apiClient } from "@/Services/HttpClient.js";
import ErrorService from "@/Services/ErrorService.js";

/**
 * Közös API service alaposztály.
 *
 * Egységesen kezeli a response interceptor telepítését, a validációs hibák
 * normalizálását és a kliensoldali API hibák naplózását.
 */
class BaseService {
    static _interceptorInstalled = false;

    constructor() {
        this.apiClient = apiClient;

        if (!BaseService._interceptorInstalled) {
            this.apiClient.interceptors.response.use(
                (response) => response,
                (error) => {
                    const status = error?.response?.status;
                    const data = error?.response?.data;

                    if (data?.errors && status === 422) {
                        // A komponensek egységesen ezen a mezőn keresztül olvassák a backend validációs hibákat.
                        error.normalizedErrors = data.errors;
                    }

                    if (error.response) {
                        ErrorService.logClientError(error, {
                            category: "api_error",
                            data: {
                                method: error.config?.method,
                                url: error.config?.url,
                                params: error.config?.params,
                                data: error.config?.data,
                                status,
                            },
                        });
                    }

                    return Promise.reject(error);
                },
            );
            BaseService._interceptorInstalled = true;
        }
    }

    extractErrors(error) {
        return error?.normalizedErrors || error?.response?.data?.errors || null;
    }

    get(url, config = {}) {
        return this.apiClient.get(url, config);
    }
    post(url, data, config = {}) {
        return this.apiClient.post(url, data, config);
    }
    patch(url, data, config = {}) {
        return this.apiClient.patch(url, data, config);
    }
    put(url, data, config = {}) {
        return this.apiClient.put(url, data, config);
    }

    delete(url, config = {}) {
        return this.apiClient.delete(url, {
            ...config,
            data: config.data || {},
        });
    }
}

export default BaseService;
