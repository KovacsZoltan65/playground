import BaseService from "./BaseService";

class RoleService extends BaseService {
    constructor() {
        super();

        this.url = "roles";
    }

    async list(params = {}) {
        const response = await this.get(route(`${this.url}.list`), {
            params,
        });

        return response.data;
    }

    async show(roleId) {
        const response = await this.get(route(`${this.url}.show`, roleId));

        return response.data;
    }

    async store(payload) {
        const response = await this.post(route(`${this.url}.store`), payload);

        return response.data;
    }

    async update(roleId, payload) {
        const response = await this.put(route(`${this.url}.update`, roleId), payload);

        return response.data;
    }

    async destroy(roleId) {
        const response = await this.delete(route(`${this.url}.destroy`, roleId));

        return response.data;
    }

    async bulkDestroy(ids) {
        const response = await this.delete(route(`${this.url}.bulk-destroy`), {
            data: { ids },
        });

        return response.data;
    }
}

export default new RoleService();
