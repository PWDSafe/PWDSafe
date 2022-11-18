<template>
    <div class="fixed bottom-0 w-full">
        <transition name="slide">
            <div
                v-show="show"
                class="border-orange-100 bg-orange-200 text-orange-800 px-4 py-4 mx-4 lg:mx-14 mb-4 lg:mb-8 rounded-md shadow"
            >
                <div class="flex justify-between items-end lg:items-center flex-col gap-y-4 lg:flex-row">
                    <p>
                        <strong>Make sure to remember your password!</strong>
                        Saved credentials can't be recovered if you lose it.
                        <span v-if="ldap" class="block mt-2">
                            You are using an external identity provider to log in. If you change your password you will be prompted to enter your old password to re-encrypt your secrets.
                        </span>
                    </p>
                    <pwdsafe-button @click="messageSeen" theme="warning" classes="whitespace-nowrap lg:ml-2">
                        Got it!
                    </pwdsafe-button>
                </div>
            </div>
        </transition>
    </div>
</template>
<script setup>
import {ref} from 'vue'

defineProps({
    ldap: {
        type: Boolean,
        required: true
    }
})
const show = ref(true)
const messageSeen = function () {
    axios.post('/settings/warningmessage', {
        accept: true
    }).then(() => {
        show.value = false
    })
}
</script>
<style scoped>
.slide-enter-active,
.slide-leave-active {
    @apply translate-y-0 opacity-100 duration-200 ease-out;
}

.slide-enter,
.slide-leave-active {
    @apply translate-y-full opacity-0;
}
</style>
