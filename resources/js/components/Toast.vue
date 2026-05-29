<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div
                v-if="message"
                class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 flex items-center gap-x-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-lg dark:bg-green-700"
            >
                <CheckIcon class="h-4 w-4 flex-shrink-0" />
                {{ message }}
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { CheckIcon } from '@heroicons/vue/24/outline'
import { useToast } from '../composables/useToast.js'
const { message } = useToast()
</script>
