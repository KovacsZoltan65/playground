import { mount } from "@vue/test-utils";
import { vi } from "vitest";

import {
    AuthenticatedLayoutStub,
    ButtonStub,
    CardStub,
    CheckboxStub,
    ColumnStub,
    ConfirmDialogStub,
    DataTableStub,
    DialogStub,
    HeadStub,
    IconFieldStub,
    InputIconStub,
    InputErrorStub,
    InputNumberStub,
    InputTextStub,
    LinkStub,
    MultiSelectStub,
    PassiveModalStub,
    PasswordStub,
    RowActionMenuStub,
    SelectStub,
    TagStub,
    TextareaStub,
} from "./primevueStubs";

export const routerMock = {
    get: vi.fn(),
};

export const toastAddMock = vi.fn();
export const confirmRequireMock = vi.fn();

export const flushPromises = async () => {
    await Promise.resolve();
    await Promise.resolve();
};

export const defaultListResponse = (data = []) => ({
    data,
    meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: data.length,
    },
});

export const installBrowserGlobals = () => {
    const storage = new Map();

    Object.defineProperty(window, "localStorage", {
        configurable: true,
        value: {
            getItem: (key) => (storage.has(key) ? storage.get(key) : null),
            setItem: (key, value) => {
                storage.set(String(key), String(value));
            },
            removeItem: (key) => {
                storage.delete(String(key));
            },
            clear: () => {
                storage.clear();
            },
        },
    });

    global.route = vi.fn((name, ...params) => `${name}:${params.join(",")}`);
};

export const resetBrowserState = () => {
    if (typeof window.localStorage?.clear === "function") {
        window.localStorage.clear();
    }
    document.cookie = "";
    vi.clearAllMocks();
};

export const mountPage = (component, options = {}) =>
    mount(component, {
        ...options,
        global: {
            ...(options.global ?? {}),
            stubs: {
                AuthenticatedLayout: AuthenticatedLayoutStub,
                Head: HeadStub,
                Link: LinkStub,
                Button: ButtonStub,
                Card: CardStub,
                Checkbox: CheckboxStub,
                Column: ColumnStub,
                ConfirmDialog: ConfirmDialogStub,
                DataTable: DataTableStub,
                Dialog: DialogStub,
                IconField: IconFieldStub,
                InputIcon: InputIconStub,
                InputError: InputErrorStub,
                InputNumber: InputNumberStub,
                InputText: InputTextStub,
                MultiSelect: MultiSelectStub,
                Password: PasswordStub,
                RowActionMenu: RowActionMenuStub,
                Select: SelectStub,
                Tag: TagStub,
                Textarea: TextareaStub,
                CreateModal: PassiveModalStub,
                EditModal: PassiveModalStub,
                ...(options.global?.stubs ?? {}),
            },
            mocks: {
                $t: (value, params = {}) =>
                    Object.entries(params).reduce(
                        (result, [key, paramValue]) =>
                            result.replace(`:${key}`, String(paramValue)),
                        value,
                    ),
                route: (...args) => global.route?.(...args),
                ...(options.global?.mocks ?? {}),
            },
        },
    });
