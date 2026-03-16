import BaseService from "@/Services/BaseService.js";

class EmployeeService extends BaseService {
    constructor() {
        super();
        this.url = "/employees";
    }

    async list(params = {}) {
        const response = await this.get(`${this.url}/list`, { params });

        return response.data;
    }

    async show(employeeId) {
        const response = await this.get(`${this.url}/${employeeId}`);

        return response.data;
    }

    async store(payload) {
        const response = await this.post(`${this.url}`, payload);

        return response.data;
    }

    async update(employeeId, payload) {
        const response = await this.put(`${this.url}/${employeeId}`, payload);

        return response.data;
    }

    async toggleActiveStatus(employeeId) {
        const response = await this.apiClient.patch(
            `${this.url}/${employeeId}/toggle-active`
        );

        return response.data;
    }

    async destroy(employeeId) {
        const response = await this.delete(`${this.url}/${employeeId}`);

        return response.data;
    }

    async bulkDestroy(ids) {
        const response = await this.delete(`${this.url}`, {
            data: { ids },
        });

        return response.data;
    }
}

export default new EmployeeService();
