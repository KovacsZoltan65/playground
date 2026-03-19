/**
 * Kulcsonként debounce-olt kérésütemező listaoldalakhoz.
 *
 * A különböző keresők és oszlopszűrők így nem írják felül egymás időzítőit,
 * mégis egységesen kezelhető a teljes oldal kérésritmusa.
 */
export const createDebouncedRequestManager = (delay) => {
    const timers = new Map();

    const clear = (key) => {
        const timerId = timers.get(key);

        if (timerId !== undefined) {
            window.clearTimeout(timerId);
            timers.delete(key);
        }
    };

    const clearAll = () => {
        timers.forEach((timerId) => window.clearTimeout(timerId));
        timers.clear();
    };

    const schedule = (key, callback) => {
        clear(key);

        const timerId = window.setTimeout(async () => {
            timers.delete(key);
            await callback();
        }, delay);

        timers.set(key, timerId);
    };

    return {
        clear,
        clearAll,
        schedule,
    };
};
