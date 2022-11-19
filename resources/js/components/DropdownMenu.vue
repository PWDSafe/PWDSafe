<template>
    <div class="relative h-full flex-shrink-0">
        <div v-if="open" @click="open = false" class="fixed inset-0"></div>
        <div class="h-full">
            <button
                class="flex h-full items-center text-sm transition duration-150 ease-in-out focus:outline-none"
                id="user-menu"
                aria-label="User menu"
                aria-haspopup="true"
                @click="open = !open"
            >
                <slot name="trigger" v-bind:open="open"></slot>
            </button>
        </div>
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-to-class="transform opacity-100 scale-100"
            enter-class="transform opacity-0 scale-95"
            leave-active-class="transition ease-in duration-75"
            leave-to-class="transform opacity-0 scale-95"
            leave-class="transform opacity-100 scale-100"
        >
            <div
                class="absolute right-0 z-10 -mt-1 w-48 origin-top-right rounded-md shadow-lg"
                v-if="open"
            >
                <div
                    class="shadow-xs rounded-md bg-white py-1 dark:bg-gray-600"
                    role="menu"
                    aria-orientation="vertical"
                    aria-labelledby="user-menu"
                >
                    <slot></slot>
                </div>
            </div>
        </transition>
    </div>
</template>
<script setup>
import { ref, onUnmounted } from 'vue'

const open = ref(false)

const onEscape = (e) => {
    if (open.value && e.keyCode === 27) {
        open.value = false
    }
}

document.addEventListener('keydown', onEscape)

onUnmounted(() => {
    document.removeEventListener('keydown', onEscape)
})
</script>
