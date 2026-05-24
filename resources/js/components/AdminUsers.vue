<template>
    <div>
        <!-- Create user panel -->
        <div class="mb-4 flex justify-end">
            <pwdsafe-button type="button" @click="toggleCreateForm">
                {{ showCreateForm ? 'Cancel' : 'Create user' }}
            </pwdsafe-button>
        </div>

        <div v-if="showCreateForm" class="mb-6 rounded-md bg-white p-5 shadow dark:bg-gray-700">
            <h3 class="mb-4 text-base font-semibold">Create local account</h3>
            <form action="/admin/users" method="post" class="space-y-4">
                <input type="hidden" name="_token" :value="csrf" />
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-500">*</span></label>
                    <input
                        type="email"
                        name="email"
                        required
                        autocomplete="off"
                        class="block w-full max-w-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:text-gray-200"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display name</label>
                    <input
                        type="text"
                        name="name"
                        autocomplete="off"
                        placeholder="Optional"
                        class="block w-full max-w-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:text-gray-200"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Temporary password <span class="text-red-500">*</span></label>
                    <input
                        type="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        class="block w-full max-w-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:text-gray-200"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        The user will replace this with their own password when setting up their vault on first login.
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm password <span class="text-red-500">*</span></label>
                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        class="block w-full max-w-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:text-gray-200"
                    />
                </div>
                <pwdsafe-button type="submit">Create account</pwdsafe-button>
            </form>
        </div>

        <!-- Toolbar -->
        <div class="mb-3 flex items-center gap-4">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300 select-none">
                <input
                    type="checkbox"
                    v-model="onlyAdmins"
                    class="h-4 w-4 rounded text-indigo-600"
                />
                Only show admins
            </label>
            <span class="text-sm text-gray-400 dark:text-gray-500">
                {{ visibleUsers.length }} user{{ visibleUsers.length === 1 ? '' : 's' }}
            </span>
        </div>

        <div class="overflow-hidden rounded-md bg-white shadow dark:bg-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            <button
                                @click="setSort('user')"
                                class="flex items-center gap-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                            >
                                User
                                <SortIcon column="user" />
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Source
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            <button
                                @click="setSort('last_login')"
                                class="flex items-center gap-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                            >
                                Last login
                                <SortIcon column="last_login" />
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    <template v-for="user in visibleUsers" :key="user.id">
                        <tr class="dark:text-gray-300">
                            <td class="px-6 py-4">
                                <div class="font-medium">{{ user.name ?? user.email }}</div>
                                <div v-if="user.name" class="text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                                <span v-if="user.is_admin" class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 mt-1">
                                    Admin
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="sourceBadgeClass(user.auth_source)" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ user.auth_source ?? 'local' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ user.last_login_at ? formatDate(user.last_login_at) : 'Never' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-3">
                                    <button
                                        @click="toggleAction(user.id, 'edit-name')"
                                        class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        Edit name
                                    </button>
                                    <button
                                        @click="toggleAction(user.id, 'reset-password')"
                                        class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        Reset password
                                    </button>
                                    <button
                                        @click="toggleAction(user.id, 'delete')"
                                        class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit name row -->
                        <tr v-if="isExpanded(user.id, 'edit-name')" class="bg-gray-50 dark:bg-gray-800">
                            <td colspan="4" class="px-6 py-4">
                                <form :action="nameUrl(user.id)" method="post" class="flex items-end gap-3 flex-wrap">
                                    <input type="hidden" name="_token" :value="csrf" />
                                    <input type="hidden" name="_method" value="PATCH" />
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                        <input
                                            type="text"
                                            name="name"
                                            :value="user.name ?? ''"
                                            placeholder="Display name (optional)"
                                            class="block w-64 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:text-gray-200"
                                        />
                                    </div>
                                    <div class="flex gap-2">
                                        <pwdsafe-button type="submit" theme="secondary">Save name</pwdsafe-button>
                                        <pwdsafe-button type="button" theme="secondary" @click="toggleAction(null, null)">Cancel</pwdsafe-button>
                                    </div>
                                </form>
                            </td>
                        </tr>

                        <!-- Reset password row -->
                        <tr v-if="isExpanded(user.id, 'reset-password')" class="bg-amber-50 dark:bg-amber-950">
                            <td colspan="4" class="px-6 py-4">
                                <pwdsafe-alert theme="warning" classes="mb-3">
                                    <strong>Warning:</strong> This will require the user to reset their safe on next login.
                                    Because the safe password is unknown, all stored credentials will be permanently destroyed and cannot be recovered.
                                </pwdsafe-alert>
                                <form :action="resetUrl(user.id)" method="post" class="flex items-end gap-3 flex-wrap">
                                    <input type="hidden" name="_token" :value="csrf" />
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New password</label>
                                        <input
                                            type="password"
                                            name="password"
                                            minlength="8"
                                            required
                                            class="block w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:text-gray-200"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm</label>
                                        <input
                                            type="password"
                                            name="password_confirmation"
                                            minlength="8"
                                            required
                                            class="block w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:text-gray-200"
                                        />
                                    </div>
                                    <div class="flex gap-2">
                                        <pwdsafe-button type="submit" theme="secondary">Confirm reset</pwdsafe-button>
                                        <pwdsafe-button type="button" theme="secondary" @click="toggleAction(null, null)">Cancel</pwdsafe-button>
                                    </div>
                                </form>
                            </td>
                        </tr>

                        <!-- Delete row -->
                        <tr v-if="isExpanded(user.id, 'delete')" class="bg-red-50 dark:bg-red-950">
                            <td colspan="4" class="px-6 py-4">
                                <pwdsafe-alert theme="danger" classes="mb-3">
                                    <strong>Warning:</strong> This will permanently delete the account for <strong>{{ user.email }}</strong> and all associated data. This cannot be undone.
                                </pwdsafe-alert>
                                <form :action="deleteUrl(user.id)" method="post" class="flex items-center gap-3">
                                    <input type="hidden" name="_token" :value="csrf" />
                                    <input type="hidden" name="_method" value="DELETE" />
                                    <pwdsafe-button type="submit" theme="danger">Yes, delete account</pwdsafe-button>
                                    <pwdsafe-button type="button" theme="secondary" @click="toggleAction(null, null)">Cancel</pwdsafe-button>
                                </form>
                            </td>
                        </tr>
                    </template>

                    <tr v-if="visibleUsers.length === 0">
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No users match the current filter.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, h } from 'vue'

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    csrf: {
        type: String,
        required: true,
    },
})

const showCreateForm = ref(false)
const onlyAdmins = ref(false)
const sortColumn = ref('user')
const sortAsc = ref(true)
const expandedUserId = ref(null)
const expandedAction = ref(null)

const toggleCreateForm = () => {
    showCreateForm.value = !showCreateForm.value
}

const setSort = (column) => {
    if (sortColumn.value === column) {
        sortAsc.value = !sortAsc.value
    } else {
        sortColumn.value = column
        sortAsc.value = true
    }
}

const userSortKey = (user) => (user.name ?? user.email).toLowerCase()

const visibleUsers = computed(() => {
    let list = onlyAdmins.value ? props.users.filter((u) => u.is_admin) : [...props.users]

    list.sort((a, b) => {
        let cmp = 0
        if (sortColumn.value === 'user') {
            cmp = userSortKey(a).localeCompare(userSortKey(b))
        } else if (sortColumn.value === 'last_login') {
            const ta = a.last_login_at ? new Date(a.last_login_at).getTime() : -Infinity
            const tb = b.last_login_at ? new Date(b.last_login_at).getTime() : -Infinity
            cmp = ta - tb
        }
        return sortAsc.value ? cmp : -cmp
    })

    return list
})

// Inline sort-icon component
const SortIcon = {
    props: ['column'],
    setup(p) {
        return () => {
            const active = sortColumn.value === p.column
            const asc = sortAsc.value
            const base = 'h-3.5 w-3.5 transition-opacity'
            const cls = active ? `${base} text-indigo-500` : `${base} opacity-30`
            const path = active && !asc
                ? 'M3 4h13M3 8h9m-9 4h9m5-4v12m0 0-3-3m3 3 3-3'
                : 'M3 4h13M3 8h9m-9 4h6m4 0 3-3m-3 3 3 3'
            return h('svg', { class: cls, fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', strokeWidth: 2 },
                h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: path })
            )
        }
    },
}

const toggleAction = (userId, action) => {
    if (expandedUserId.value === userId && expandedAction.value === action) {
        expandedUserId.value = null
        expandedAction.value = null
    } else {
        expandedUserId.value = userId
        expandedAction.value = action
    }
}

const isExpanded = (userId, action) => {
    return expandedUserId.value === userId && expandedAction.value === action
}

const resetUrl = (userId) => `/admin/users/${userId}/reset-password`
const nameUrl = (userId) => `/admin/users/${userId}/name`
const deleteUrl = (userId) => `/admin/users/${userId}`

const formatDate = (dateStr) => {
    if (!dateStr) return 'Never'
    const d = new Date(dateStr)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

const sourceBadgeClass = (source) => {
    const classes = {
        local: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        ldap: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        oidc: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    }
    return classes[source] ?? classes.local
}
</script>
