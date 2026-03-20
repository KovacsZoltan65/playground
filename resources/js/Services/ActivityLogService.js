import BaseService from "./BaseService";

class ActivityLogService extends BaseService {
    constructor() {
        super();

        this.url = "activity-logs";
    }

    async list(params = {}) {
        const response = await this.get(route(`${this.url}.list`), {
            params,
        });

        return response.data;
    }

    async analysis(params = {}) {
        const response = await this.get(route(`${this.url}.analysis`), {
            params,
        });

        return response.data;
    }
}

export default new ActivityLogService();
