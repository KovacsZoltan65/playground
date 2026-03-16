import BaseService from "@/Services/BaseService.js";

class CompanyService extends BaseService {
    constructor() {
        super();
        this.url = "/companies";
    }

    async list(params = {}) {
        const response = await this.get(`${this.url}/list`, { params });

        return response.data;
    }

    async show(companyId) {
        const response = await this.get(`${this.url}/${companyId}`);

        return response.data;
    }

    async store(payload) {
        const response = await this.post(`${this.url}`, payload);

        return response.data;
    }

    async update(companyId, payload) {
        const response = await this.put(`${this.url}/${companyId}`, payload);

        return response.data;
    }

    async toggleActiveStatus(companyId) {
        const response = await this.apiClient.patch(
            `${this.url}/${companyId}/toggle-active`
        );

        return response.data;
    }

    async destroy(companyId) {
        const response = await this.delete(`${this.url}/${companyId}`);

        return response.data;
    }

    async bulkDestroy(ids) {
        const response = await this.delete(`${this.url}`, {
            data: { ids },
        });

        return response.data;
    }
}

export default new CompanyService();
