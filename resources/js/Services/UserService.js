import BaseService from "./BaseService";

class UserService extends BaseService {
    constructor() {
        super();

        this.url = "users";
    }

    async list(params = {}) {
        const response = await this.get(route(`${this.url}.list`), {
            params,
        });

        return response.data;
    }

    async show(userId) {
        const response = await this.get(route(`${this.url}.show`, userId));

        return response.data;
    }

    async store(payload) {
        const response = await this.post(route(`${this.url}.store`), payload);

        return response.data;
    }

    async update(userId, payload) {
        const response = await this.put(route(`${this.url}.update`, userId), payload);

        return response.data;
    }

    async destroy(userId) {
        const response = await this.delete(route(`${this.url}.destroy`, userId));

        return response.data;
    }

    async sendVerificationEmail(userId) {
        const response = await this.post(
            route(`${this.url}.send-verification-email`, userId),
        );

        return response.data;
    }

    async bulkDestroy(ids) {
        const response = await this.delete(route(`${this.url}.bulk-destroy`), {
            data: { ids },
        });

        return response.data;
    }
}

export default new UserService();
