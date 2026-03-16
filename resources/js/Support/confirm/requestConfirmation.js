export const requestConfirmation = (confirm, options) =>
    new Promise((resolve) => {
        confirm.require({
            ...options,
            accept: () => resolve(true),
            reject: () => resolve(false),
        });
    });
