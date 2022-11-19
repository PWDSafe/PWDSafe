<template>
    <div class="fixed bottom-0 w-full">
        <transition
            enter-active-class="duration-300 ease-out translate-y-0 opacity-100"
            leave-active-class="duration-300 ease-out translate-y-full opacity-0"
        >
            <div
                v-show="show"
                class="mx-4 mb-4 rounded-md border-orange-100 bg-orange-200 px-4 py-4 text-orange-800 shadow dark:bg-orange-900 dark:text-orange-200 lg:mx-14 lg:mb-8"
            >
                <div
                    class="flex flex-col items-end justify-between gap-y-4 lg:flex-row lg:items-center"
                >
                    <p>
                        <strong>Make sure to remember your password!</strong>
                        Saved credentials can't be recovered if you lose it.
                        <span v-if="ldap" class="mt-2 block">
                            You are using an external identity provider to log
                            in. If you change your password you will be prompted
                            to enter your old password to re-encrypt your
                            secrets.
                        </span>
                    </p>
                    <pwdsafe-button
                        @click="messageSeen"
                        theme="warning"
                        classes="whitespace-nowrap lg:ml-2"
                    >
                        Got it!
                    </pwdsafe-button>
                </div>
            </div>
        </transition>
    </div>
</template>
<script setup>
import { ref } from 'vue'

defineProps({
    ldap: {
        type: Boolean,
        required: true,
    },
})
const show = ref(true)
const messageSeen = function () {
    axios
        .post('/settings/warningmessage', {
            accept: true,
        })
        .then(() => {
            show.value = false
        })
}
</script>
