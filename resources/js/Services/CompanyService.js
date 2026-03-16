import BaseService from "./BaseService";

class CompanyService extends BaseService {
    constructor() {
        super();

        this.url = "companies";
    }

    async list(params = {}) {
        const response = await this.get(route(`${this.url}.list`), {
            params,
        });

        return response.data;
    }

    async show(companyId) {
        const response = await this.get(route(`${this.url}.show`, companyId));

        return response.data;
    }

    async store(payload) {
        const response = await this.post(route(`${this.url}.store`), payload);

        return response.data;
    }

    async update(companyId, payload) {
        const response = await this.put(route(`${this.url}.update`, companyId), payload);

        return response.data;
    }

    async destroy(companyId) {
        const response = await this.delete(route(`${this.url}.destroy`, companyId));

        return response.data;
    }

    async bulkDestroy(ids) {
        const response = await this.delete(route(`${this.url}.bulk-destroy`), {
            data: { ids },
        });

        return response.data;
    }

    async bulkActivate(ids) {
        const response = await this.patch(route(`${this.url}.bulk-activate`), {
            ids,
        });

        return response.data;
    }

    async bulkDeactivate(ids) {
        const response = await this.patch(route(`${this.url}.bulk-deactivate`), {
            ids,
        });

        return response.data;
    }

    async toggleActiveStatus(companyId) {
        const response = await this.patch(route(`${this.url}.toggle-active`, companyId));

        return response.data;
    }
}

export default new CompanyService();
