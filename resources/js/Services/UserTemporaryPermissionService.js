import BaseService from "./BaseService";

class UserTemporaryPermissionService extends BaseService {
    constructor() {
        super();

        this.url = "user-temporary-permissions";
    }

    async list(params = {}) {
        const response = await this.get(route(`${this.url}.list`), {
            params,
        });

        return response.data;
    }

    async show(id) {
        const response = await this.get(route(`${this.url}.show`, id));

        return response.data;
    }

    async store(payload) {
        const response = await this.post(route(`${this.url}.store`), payload);

        return response.data;
    }

    async update(id, payload) {
        const response = await this.put(route(`${this.url}.update`, id), payload);

        return response.data;
    }

    async destroy(id) {
        const response = await this.delete(route(`${this.url}.destroy`, id));

        return response.data;
    }

    async bulkDestroy(ids) {
        const response = await this.delete(route(`${this.url}.bulk-destroy`), {
            data: { ids },
        });

        return response.data;
    }
}

export default new UserTemporaryPermissionService();
