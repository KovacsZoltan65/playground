import BaseService from "@/Services/BaseService.js";

class SidebarTipPageService extends BaseService {
    constructor() {
        super();
        this.url = "/usage-tips";
    }

    async list(params = {}) {
        const response = await this.get(`${this.url}/list`, { params });

        return response.data;
    }

    async show(sidebarTipPageId) {
        const response = await this.get(`${this.url}/${sidebarTipPageId}`);

        return response.data;
    }

    async store(payload) {
        const response = await this.post(`${this.url}`, payload);

        return response.data;
    }

    async update(sidebarTipPageId, payload) {
        const response = await this.put(`${this.url}/${sidebarTipPageId}`, payload);

        return response.data;
    }

    async destroy(sidebarTipPageId) {
        const response = await this.delete(`${this.url}/${sidebarTipPageId}`);

        return response.data;
    }
}

export default new SidebarTipPageService();
