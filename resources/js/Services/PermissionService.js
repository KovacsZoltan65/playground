import BaseService from "./BaseService";

class PermissionService extends BaseService {
    constructor() {
        super();

        this.url = "permissions";
    }

    async list(params = {}) {
        const response = await this.get(route(`${this.url}.list`), {
            params,
        });

        return response.data;
    }

    async show(permissionId) {
        const response = await this.get(route(`${this.url}.show`, permissionId));

        return response.data;
    }

    async store(payload) {
        const response = await this.post(route(`${this.url}.store`), payload);

        return response.data;
    }

    async update(permissionId, payload) {
        const response = await this.put(route(`${this.url}.update`, permissionId), payload);

        return response.data;
    }

    async destroy(permissionId) {
        const response = await this.delete(route(`${this.url}.destroy`, permissionId));

        return response.data;
    }

    async bulkDestroy(ids) {
        const response = await this.delete(route(`${this.url}.bulk-destroy`), {
            data: { ids },
        });

        return response.data;
    }
}

export default new PermissionService();
