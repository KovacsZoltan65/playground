import { flushPromises, mountPage } from "./pageTestUtils";

export async function mountAdminIndexPage(loadComponent, props = {}) {
    const module = await loadComponent();

    const wrapper = mountPage(module.default, { props });

    await flushPromises();

    return wrapper;
}

export async function triggerSort(wrapper, field) {
    await wrapper.get(`[data-test-id="sort-${field}"]`).trigger("click");
    await flushPromises();
}

export async function selectAllRows(wrapper) {
    await wrapper.get('[data-test-id="datatable-select-all"]').trigger("click");
    await flushPromises();
}

export async function goToNextPage(wrapper) {
    await wrapper.get('[data-test-id="datatable-page-next"]').trigger("click");
    await flushPromises();
}

export async function changeRowsPerPage(wrapper) {
    await wrapper.get('[data-test-id="datatable-rows-25"]').trigger("click");
    await flushPromises();
}
