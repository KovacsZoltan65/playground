import axios from 'axios';

export default {
    async list(params = {}) {
        const response = await axios.get('/companies/list', { params });

        return response.data;
    },

    async show(companyId) {
        const response = await axios.get(`/companies/${companyId}`);

        return response.data;
    },

    async store(payload) {
        const response = await axios.post('/companies', payload);

        return response.data;
    },

    async update(companyId, payload) {
        const response = await axios.put(`/companies/${companyId}`, payload);

        return response.data;
    },

    async destroy(companyId) {
        const response = await axios.delete(`/companies/${companyId}`);

        return response.data;
    },
};
