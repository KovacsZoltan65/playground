<script setup>
import SidebarUsageTips from '@/Components/SidebarUsageTips.vue';
import { useColorScheme } from '@/Composables/useColorScheme';
import { flushQueuedToast } from '@/Support/toast/toastHelpers';
import { computed, onMounted, ref, watch } from 'vue';
import { currentLocale, trans } from 'laravel-vue-i18n';
import { Link, usePage } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const page = usePage();
const sidebarOpen = ref(false);
const toast = useToast();
const { isDarkScheme, toggleColorScheme } = useColorScheme();

const user = computed(() => page.props.auth.user);
const userPermissions = computed(() => page.props.auth.permissions ?? []);
const sidebarTipConfig = computed(() => page.props.sidebar_tips ?? {
    visible: false,
    rotationIntervalMs: 60 * 1000,
    tips: [],
});

const navigationSections = computed(() => {
    currentLocale.value;

    return [
        {
            label: trans('Workspace'),
            items: [
                {
                    label: trans('Dashboard'),
                    icon: 'pi pi-home',
                    route: 'dashboard',
                    activeRoute: 'dashboard',
                },
                {
                    label: trans('Profile'),
                    icon: 'pi pi-user',
                    route: 'profile.edit',
                    activeRoute: 'profile.*',
                },
            ],
        },
        {
            label: trans('Operations'),
            items: [
                {
                    label: trans('Activity logs'),
                    icon: 'pi pi-history',
                    route: 'activity-logs.index',
                    activeRoute: 'activity-logs.*',
                    permission: 'activityLogs.viewAny',
                },
                {
                    label: trans('Companies'),
                    icon: 'pi pi-building',
                    route: 'companies.index',
                    activeRoute: 'companies.*',
                    permission: 'companies.viewAny',
                },
                {
                    label: trans('Employees'),
                    icon: 'pi pi-users',
                    route: 'employees.index',
                    activeRoute: 'employees.*',
                    permission: 'employees.viewAny',
                },
                {
                    label: trans('Usage tips'),
                    icon: 'pi pi-lightbulb',
                    route: 'usage-tips.index',
                    activeRoute: 'usage-tips.*',
                    permission: 'sidebarTipPages.viewAny',
                },
            ],
        },
        {
            label: trans('Access Control'),
            items: [
                {
                    label: trans('Roles'),
                    icon: 'pi pi-shield',
                    route: 'roles.index',
                    activeRoute: 'roles.*',
                    permission: 'roles.viewAny',
                },
                {
                    label: trans('Users'),
                    icon: 'pi pi-id-card',
                    route: 'users.index',
                    activeRoute: 'users.*',
                    permission: 'users.viewAny',
                },
                {
                    label: trans('Permissions'),
                    icon: 'pi pi-key',
                    route: 'permissions.index',
                    activeRoute: 'permissions.*',
                    permission: 'permissions.viewAny',
                },
                {
                    label: trans('Temporary permissions'),
                    icon: 'pi pi-clock',
                    route: 'user-temporary-permissions.index',
                    activeRoute: 'user-temporary-permissions.*',
                    permission: 'userTemporaryPermissions.viewAny',
                },
            ],
        },
    ]
        .map((section) => ({
            ...section,
            items: section.items.filter(
                (item) => !item.permission || userPermissions.value.includes(item.permission),
            ),
        }))
        .filter((section) => section.items.length > 0);
});

const isActive = (name) => route().current(name);

const syncQueuedToast = () => {
    flushQueuedToast(toast);
};

onMounted(syncQueuedToast);

watch(() => page.url, syncQueuedToast);
</script>

<template>
    <div class="app-shell lg:flex">
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="app-sidebar fixed inset-y-0 left-0 z-40 flex w-80 flex-col overflow-y-auto px-6 py-6 transition-transform duration-300 lg:static lg:h-screen lg:translate-x-0"
        >
            <div class="mb-8 flex items-center justify-between">
                <Link :href="route('dashboard')" class="flex items-center gap-4">
                    <div
                        class="app-brand-badge flex h-12 w-12 items-center justify-center rounded-3xl text-xl font-bold text-white"
                    >
                        P
                    </div>
                    <div>
                        <div class="app-kicker text-xs uppercase tracking-[0.35em]">
                            PrimeVue
                        </div>
                        <div class="app-title text-2xl font-semibold tracking-tight">
                            Playground
                        </div>
                    </div>
                </Link>

                <button
                    type="button"
                    class="app-icon-button inline-flex h-10 w-10 items-center justify-center rounded-full transition lg:hidden"
                    @click="sidebarOpen = false"
                >
                    <i class="pi pi-times text-base"></i>
                </button>
            </div>

            <div class="app-sidebar-panel mb-8">
                <div class="app-panel-kicker mb-2 text-xs uppercase tracking-[0.3em]">
                    {{ $t('Sakai style') }}
                </div>
                <p class="app-copy text-sm leading-6">
                    {{ $t('PrimeVue components power this admin shell on Laravel 12 and Inertia.') }}
                </p>
            </div>

            <nav class="space-y-6">
                <section
                    v-for="section in navigationSections"
                    :key="section.label"
                    class="space-y-2"
                >
                    <div class="app-section-label px-3 text-xs uppercase tracking-[0.3em]">
                        {{ section.label }}
                    </div>

                    <div class="space-y-2">
                        <Link
                            v-for="item in section.items"
                            :key="item.route"
                            :href="route(item.route)"
                            :class="[
                                'app-sidebar-link',
                                isActive(item.activeRoute) ? 'app-sidebar-link-active' : '',
                            ]"
                            @click="sidebarOpen = false"
                        >
                            <i :class="item.icon" class="text-base"></i>
                            <span>{{ item.label }}</span>
                        </Link>
                    </div>
                </section>
            </nav>

            <SidebarUsageTips :config="sidebarTipConfig" />
        </aside>

        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-slate-900/20 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        />

        <div class="min-w-0 flex-1 overflow-hidden lg:h-screen">
            <div class="flex h-full min-h-0 flex-col overflow-y-auto">
            <Toast position="top-right" class="app-toast" />
            <header
                class="app-header sticky top-0 z-20 px-4 py-4 sm:px-6 lg:px-10"
            >
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="app-icon-button inline-flex h-10 w-10 items-center justify-center rounded-full transition lg:hidden"
                            @click="sidebarOpen = true"
                        >
                            <i class="pi pi-bars text-base"></i>
                        </button>

                        <div>
                            <div class="app-section-label text-xs uppercase tracking-[0.3em]">
                                {{ $t('Admin workspace') }}
                            </div>
                            <div
                                v-if="$slots.header"
                                class="app-title text-2xl font-semibold tracking-tight"
                            >
                                <slot name="header" />
                            </div>
                            <div
                                v-else
                                class="app-title text-2xl font-semibold tracking-tight"
                            >
                                {{ $t('Dashboard') }}
                            </div>
                        </div>
                    </div>

                    <div class="app-user-chip flex items-center gap-3 rounded-full px-3 py-2">
                        <button
                            type="button"
                            class="app-icon-button inline-flex h-10 w-10 items-center justify-center rounded-full transition"
                            @click="toggleColorScheme"
                        >
                            <i :class="isDarkScheme ? 'pi pi-sun' : 'pi pi-moon'" class="text-base"></i>
                        </button>
                        <Avatar
                            shape="circle"
                            :label="user?.name?.charAt(0)?.toUpperCase() ?? 'U'"
                            class="app-avatar text-white"
                        />
                        <div class="hidden text-sm sm:block">
                            <div class="app-user-name font-medium">{{ user?.name }}</div>
                            <div class="app-user-email">{{ user?.email }}</div>
                        </div>
                        <Link
                            :href="route('profile.edit')"
                            class="app-icon-link transition"
                        >
                            <i class="pi pi-cog text-lg"></i>
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="app-icon-link app-icon-link-danger transition"
                        >
                            <i class="pi pi-sign-out text-lg"></i>
                        </Link>
                    </div>
                </div>
            </header>

            <main class="app-main flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
                <slot />
            </main>
            </div>
        </div>
    </div>
</template>
