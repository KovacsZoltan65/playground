import { defineComponent, h, ref, watch } from "vue";

const cloneValue = (value) => JSON.parse(JSON.stringify(value ?? {}));

export const AuthenticatedLayoutStub = defineComponent({
    name: "AuthenticatedLayoutStub",
    setup(_, { slots }) {
        return () =>
            h("div", { "data-test-id": "authenticated-layout" }, [
                h("header", { "data-test-id": "layout-header" }, slots.header?.()),
                h("main", { "data-test-id": "layout-content" }, slots.default?.()),
            ]);
    },
});

export const HeadStub = defineComponent({
    name: "HeadStub",
    props: {
        title: { type: String, default: "" },
    },
    setup(props) {
        return () => h("div", { "data-test-id": "head", "data-title": props.title });
    },
});

export const LinkStub = defineComponent({
    name: "LinkStub",
    props: {
        href: { type: String, default: "#" },
    },
    setup(props, { slots }) {
        return () => h("a", { href: props.href }, slots.default?.());
    },
});

export const ButtonStub = defineComponent({
    name: "ButtonStub",
    props: {
        label: { type: String, default: "" },
        disabled: { type: Boolean, default: false },
        loading: { type: Boolean, default: false },
        type: { type: String, default: "button" },
    },
    emits: ["click"],
    setup(props, { emit, slots, attrs }) {
        return () =>
            h(
                "button",
                {
                    ...attrs,
                    type: props.type,
                    disabled: props.disabled || props.loading,
                    onClick: (event) => emit("click", event),
                },
                slots.default?.() ?? props.label,
            );
    },
});

export const CardStub = defineComponent({
    name: "CardStub",
    setup(_, { slots }) {
        return () =>
            h("section", { "data-test-id": "card" }, slots.content?.() ?? slots.default?.());
    },
});

export const ColumnStub = defineComponent({
    name: "ColumnStub",
    props: {
        field: { type: String, default: null },
        header: { type: String, default: null },
        sortable: { type: Boolean, default: false },
        selectionMode: { type: String, default: null },
    },
    setup() {
        return () => null;
    },
});

export const DataTableStub = defineComponent({
    name: "DataTable",
    props: {
        value: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({}) },
        selection: { type: Array, default: () => [] },
        sortField: { type: String, default: null },
        sortOrder: { type: Number, default: null },
        first: { type: Number, default: 0 },
        rows: { type: Number, default: 10 },
        totalRecords: { type: Number, default: 0 },
        loading: { type: Boolean, default: false },
    },
    emits: ["update:filters", "update:selection", "page", "filter", "sort"],
    setup(props, { emit, slots }) {
        const localFilters = ref(cloneValue(props.filters));

        watch(
            () => props.filters,
            (nextFilters) => {
                localFilters.value = cloneValue(nextFilters);
            },
            { deep: true },
        );

        const getColumns = () =>
            (slots.default?.() ?? []).filter(
                (vnode) => vnode?.type?.name === "ColumnStub",
            );

        const applyFilters = () => {
            emit("update:filters", localFilters.value);
            emit("filter", { filters: localFilters.value });
        };

        const toggleSort = (field) => {
            const nextSortOrder =
                props.sortField === field && props.sortOrder === 1 ? -1 : 1;

            emit("sort", {
                sortField: field,
                sortOrder: nextSortOrder,
            });
        };

        const emitPage = (rows, first = 0) => {
            emit("page", { rows, first });
        };

        const updateSelection = (selection) => {
            emit("update:selection", selection);
        };

        return () => {
            const columns = getColumns();

            if (props.loading && slots.loading) {
                return h("div", { "data-test-id": "datatable-loading" }, slots.loading());
            }

            return h("div", { "data-test-id": "datatable" }, [
                h("div", { "data-test-id": "datatable-header" }, slots.header?.()),
                h(
                    "div",
                    { "data-test-id": "datatable-controls" },
                    [
                        h(
                            "button",
                            {
                                type: "button",
                                "data-test-id": "datatable-select-all",
                                onClick: () => updateSelection(props.value),
                            },
                            "select-all",
                        ),
                        h(
                            "button",
                            {
                                type: "button",
                                "data-test-id": "datatable-page-next",
                                onClick: () => emitPage(props.rows, props.first + props.rows),
                            },
                            "page-next",
                        ),
                        h(
                            "button",
                            {
                                type: "button",
                                "data-test-id": "datatable-rows-25",
                                onClick: () => emitPage(25, 0),
                            },
                            "rows-25",
                        ),
                    ],
                ),
                ...columns
                    .filter((column) => column.props?.sortable && column.props?.field)
                    .map((column) =>
                        h(
                            "button",
                            {
                                type: "button",
                                "data-test-id": `sort-${column.props.field}`,
                                onClick: () => toggleSort(column.props.field),
                            },
                            `sort-${column.props.field}`,
                        ),
                    ),
                ...columns
                    .filter((column) => column.props?.field && column.children?.filter)
                    .map((column) => {
                        const field = column.props.field;

                        if (!localFilters.value[field]) {
                            localFilters.value[field] = { value: null, matchMode: "contains" };
                        }

                        return h(
                            "div",
                            { "data-test-id": `filter-${field}` },
                            column.children.filter({
                                filterModel: localFilters.value[field],
                                filterCallback: applyFilters,
                            }),
                        );
                    }),
                props.value.length === 0 && slots.empty
                    ? h("div", { "data-test-id": "datatable-empty" }, slots.empty())
                    : null,
                ...props.value.map((row, rowIndex) =>
                    h(
                        "div",
                        {
                            key: row.id ?? rowIndex,
                            "data-test-id": `datatable-row-${rowIndex}`,
                        },
                        columns
                            .filter((column) => column.props?.selectionMode !== "multiple")
                            .map((column, columnIndex) =>
                                h(
                                    "div",
                                    {
                                        key: `${row.id ?? rowIndex}-${column.props?.field ?? columnIndex}`,
                                        "data-test-id": `cell-${rowIndex}-${column.props?.field ?? columnIndex}`,
                                    },
                                    column.children?.body
                                        ? column.children.body({ data: row })
                                        : row[column.props?.field] ?? "",
                                ),
                            ),
                    ),
                ),
            ]);
        };
    },
});

export const DialogStub = defineComponent({
    name: "DialogStub",
    props: {
        visible: { type: Boolean, default: false },
        header: { type: String, default: "" },
    },
    emits: ["update:visible", "hide"],
    setup(props, { emit, slots }) {
        return () =>
            props.visible
                ? h("div", { "data-test-id": "dialog" }, [
                      h("div", { "data-test-id": "dialog-header" }, props.header),
                      h("div", { "data-test-id": "dialog-body" }, slots.default?.()),
                      h(
                          "button",
                          {
                              type: "button",
                              "data-test-id": "dialog-close",
                              onClick: () => {
                                  emit("update:visible", false);
                                  emit("hide");
                              },
                          },
                          "close",
                      ),
                  ])
                : null;
    },
});

export const SelectStub = defineComponent({
    name: "SelectStub",
    props: {
        modelValue: { type: [String, Number, Boolean, null], default: null },
        options: { type: Array, default: () => [] },
        optionLabel: { type: String, default: "label" },
        optionValue: { type: String, default: "value" },
        placeholder: { type: String, default: "" },
    },
    emits: ["update:modelValue", "change"],
    setup(props, { emit, attrs }) {
        const normalizeValue = (value) => {
            if (value === "") {
                return null;
            }

            const matchedOption = props.options.find(
                (option) => String(option?.[props.optionValue]) === value,
            );

            return matchedOption ? matchedOption[props.optionValue] : value;
        };

        return () =>
            h(
                "select",
                {
                    ...attrs,
                    value: props.modelValue ?? "",
                    onChange: (event) => {
                        const nextValue = normalizeValue(event.target.value);
                        emit("update:modelValue", nextValue);
                        emit("change", { value: nextValue });
                    },
                },
                [
                    h("option", { value: "" }, props.placeholder || "select"),
                    ...props.options.map((option) =>
                        h(
                            "option",
                            {
                                key: String(option?.[props.optionValue]),
                                value: option?.[props.optionValue],
                            },
                            option?.[props.optionLabel],
                        ),
                    ),
                ],
            );
    },
});

export const MultiSelectStub = defineComponent({
    name: "MultiSelectStub",
    props: {
        modelValue: { type: Array, default: () => [] },
        options: { type: Array, default: () => [] },
        optionLabel: { type: String, default: "label" },
        optionValue: { type: String, default: "value" },
    },
    emits: ["update:modelValue"],
    setup(props, { emit, attrs }) {
        return () =>
            h(
                "select",
                {
                    ...attrs,
                    multiple: true,
                    value: props.modelValue,
                    onChange: (event) => {
                        const nextValues = Array.from(event.target.selectedOptions).map(
                            (option) => option.value,
                        );
                        emit("update:modelValue", nextValues);
                    },
                },
                props.options.map((option) =>
                    h(
                        "option",
                        {
                            key: String(option?.[props.optionValue]),
                            value: option?.[props.optionValue],
                            selected: props.modelValue.includes(option?.[props.optionValue]),
                        },
                        option?.[props.optionLabel],
                    ),
                ),
            );
    },
});

export const InputTextStub = defineComponent({
    name: "InputTextStub",
    props: {
        modelValue: { type: [String, Number], default: "" },
    },
    emits: ["update:modelValue", "input", "blur"],
    setup(props, { emit, attrs }) {
        return () =>
            h("input", {
                ...attrs,
                value: props.modelValue ?? "",
                onInput: (event) => {
                    emit("update:modelValue", event.target.value);
                    emit("input", event);
                },
                onBlur: (event) => emit("blur", event),
            });
    },
});

export const TextareaStub = defineComponent({
    name: "TextareaStub",
    props: {
        modelValue: { type: String, default: "" },
    },
    emits: ["update:modelValue", "blur", "input"],
    setup(props, { emit, attrs }) {
        return () =>
            h("textarea", {
                ...attrs,
                value: props.modelValue ?? "",
                onInput: (event) => {
                    emit("update:modelValue", event.target.value);
                    emit("input", event);
                },
                onBlur: (event) => emit("blur", event),
            });
    },
});

export const CheckboxStub = defineComponent({
    name: "CheckboxStub",
    props: {
        modelValue: { type: [Boolean, Array], default: false },
        binary: { type: Boolean, default: false },
    },
    emits: ["update:modelValue", "change"],
    setup(props, { emit, attrs }) {
        return () =>
            h("input", {
                ...attrs,
                type: "checkbox",
                checked: Boolean(props.modelValue),
                onChange: (event) => {
                    const nextValue = event.target.checked;
                    emit("update:modelValue", nextValue);
                    emit("change", { checked: nextValue });
                },
            });
    },
});

export const InputNumberStub = defineComponent({
    name: "InputNumberStub",
    props: {
        modelValue: { type: [String, Number, null], default: null },
    },
    emits: ["update:modelValue"],
    setup(props, { emit, attrs }) {
        return () =>
            h("input", {
                ...attrs,
                type: "number",
                value: props.modelValue ?? "",
                onInput: (event) => {
                    const rawValue = event.target.value;
                    emit("update:modelValue", rawValue === "" ? null : Number(rawValue));
                },
            });
    },
});

export const PasswordStub = defineComponent({
    name: "PasswordStub",
    props: {
        modelValue: { type: String, default: "" },
    },
    emits: ["update:modelValue", "blur"],
    setup(props, { emit, attrs }) {
        return () =>
            h("input", {
                ...attrs,
                type: "password",
                value: props.modelValue,
                onInput: (event) => emit("update:modelValue", event.target.value),
                onBlur: (event) => emit("blur", event),
            });
    },
});

export const TagStub = defineComponent({
    name: "TagStub",
    props: {
        value: { type: [String, Number], default: "" },
    },
    setup(props) {
        return () => h("span", { "data-test-id": "tag" }, props.value);
    },
});

export const IconFieldStub = defineComponent({
    name: "IconFieldStub",
    setup(_, { slots }) {
        return () => h("div", { "data-test-id": "icon-field" }, slots.default?.());
    },
});

export const InputIconStub = defineComponent({
    name: "InputIconStub",
    setup(_, { slots }) {
        return () => h("span", { "data-test-id": "input-icon" }, slots.default?.());
    },
});

export const ConfirmDialogStub = defineComponent({
    name: "ConfirmDialogStub",
    setup() {
        return () => h("div", { "data-test-id": "confirm-dialog" });
    },
});

export const RowActionMenuStub = defineComponent({
    name: "RowActionMenuStub",
    props: {
        items: { type: Array, default: () => [] },
    },
    setup(props) {
        return () =>
            h(
                "div",
                { "data-test-id": "row-action-menu" },
                props.items.map((item) =>
                    h(
                        "button",
                        {
                            key: item.label,
                            type: "button",
                            "data-test-id": `row-action-${String(item.label)
                                .toLowerCase()
                                .replace(/\s+/g, "-")}`,
                            disabled: item.disabled,
                            onClick: () => item.command?.(),
                        },
                        item.label,
                    ),
                ),
            );
    },
});

export const PassiveModalStub = defineComponent({
    name: "PassiveModalStub",
    props: {
        modelValue: { type: Boolean, default: false },
    },
    emits: ["update:modelValue", "saved"],
    setup(props, { slots }) {
        return () =>
            props.modelValue
                ? h("div", { "data-test-id": "passive-modal" }, slots.default?.())
                : null;
    },
});

export const InputErrorStub = defineComponent({
    name: "InputErrorStub",
    props: {
        message: { type: [String, Array, null], default: null },
    },
    setup(props) {
        return () =>
            props.message
                ? h("small", { "data-test-id": "input-error" }, props.message)
                : null;
    },
});
