<template>
    <form method="POST" action="/admin/settings/general">
        <input type="hidden" name="_token" :value="csrf" />

        <div class="mb-6 max-w-2xl rounded bg-white p-4 shadow-md dark:bg-gray-700">
            <h2 class="mb-4 font-semibold">Registration</h2>
            <div class="flex items-center gap-3">
                <input
                    type="hidden"
                    name="registration_enabled"
                    value="0"
                />
                <input
                    id="registration_enabled"
                    name="registration_enabled"
                    type="checkbox"
                    value="1"
                    :checked="form.registration_enabled"
                    class="h-4 w-4 rounded text-blue-600"
                />
                <div>
                    <pwdsafe-label for="registration_enabled" class="mb-0">
                        Allow public registration
                    </pwdsafe-label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        When disabled, only administrators can create new accounts.
                        Only applies when using internal authentication.
                    </p>
                </div>
            </div>
        </div>

        <div class="max-w-2xl">
            <pwdsafe-button type="submit">Save settings</pwdsafe-button>
        </div>
    </form>
</template>
<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    csrf: {
        type: String,
        required: true,
    },
})

const form = ref({ ...props.settings })
</script>
