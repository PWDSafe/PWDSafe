<template>
    <form method="post">
        <div class="px-8 py-4">
            <h3 class="text-2xl mb-4">Add credentials</h3>
            <input type="hidden" name="_token" :value="csrftoken" />
            <div class="mb-4">
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="site">Site</pwdsafe-label>
                    <pwdsafe-input
                        type="text"
                        name="site"
                        id="site"
                        autocomplete="off"
                        required
                        autofocus
                    ></pwdsafe-input>
                </div>
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="user"
                        >Username</pwdsafe-label
                    >
                    <pwdsafe-input
                        type="text"
                        name="user"
                        id="user"
                        class="form-control"
                        autocomplete="off"
                        required
                    ></pwdsafe-input>
                </div>
                <div class="mb-2">
                    <div class="flex gap-x-2 justify-between items-end mb-2">
                        <pwdsafe-label class="mb-1" for="pass">
                            Password
                        </pwdsafe-label>
                        <pwdsafe-passwordgen
                            @generated="updatePassword"
                        ></pwdsafe-passwordgen>
                    </div>
                    <TextareaVue
                        v-model="password"
                        name="pass"
                        id="pass"
                        rows="5"
                        required
                    ></TextareaVue>
                </div>
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="notes"
                        >Notes</pwdsafe-label
                    >
                    <pwdsafe-textarea
                        name="notes"
                        id="notes"
                        rows="5"
                    ></pwdsafe-textarea>
                </div>
            </div>
        </div>
        <div
            class="bg-gray-50 dark:bg-gray-700 dark:border-t dark:border-gray-800"
        >
            <div class="flex justify-end gap-x-2 px-8 py-4">
                <pwdsafe-button theme="secondary" :href="backlink">
                    Back
                </pwdsafe-button>
                <pwdsafe-button type="submit">Add credential</pwdsafe-button>
            </div>
        </div>
    </form>
</template>
<script setup>
import { ref } from 'vue'
import TextareaVue from './TextareaVue.vue'

const props = defineProps({
    backlink: {
        type: String,
        required: true,
    },
})

const csrftoken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute('content')

const password = ref('')
const updatePassword = (event) => {
    password.value = event
}
</script>
