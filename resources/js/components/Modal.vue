<template>
    <slot name="trigger" :openModal="openModal"></slot>
    <Teleport to="#modals">
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-to-class="transform opacity-100"
            enter-class="transform opacity-0"
            leave-active-class="transition ease-in duration-75"
            leave-to-class="transform opacity-0"
            leave-class="transform opacity-100"
        >
            <div
                class="fixed inset-0 overflow-y-auto"
                :class="props.zindexclass"
                v-if="open"
            >
                <div
                    class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0"
                >
                    <div class="fixed inset-0 transition-opacity">
                        <div
                            class="absolute inset-0 bg-gray-500 opacity-75"
                            @click="open = false"
                        ></div>
                    </div>

                    <!-- This element is to trick the browser into centering the modal contents. -->
                    <span
                        class="hidden sm:inline-block sm:h-screen sm:align-middle"
                    ></span
                    >&#8203;

                    <div
                        v-if="open"
                        class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left align-bottom shadow-xl transition-all dark:bg-gray-700 sm:my-8 sm:w-full sm:max-w-sm sm:p-6 sm:align-middle md:max-w-md"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="modal-headline"
                    >
                        <slot></slot>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>
<script setup lang="ts">
import { ref, onUnmounted, watch } from 'vue'

const props = defineProps({
    zindexclass: {
        type: String,
        default: 'z-10',
    },
})

const open = ref(false)
const emit = defineEmits(['modal-open', 'modal-close'])

const onEscape = (e) => {
    if (open.value && e.keyCode === 27) {
        open.value = false
    }
}

const openModal = function () {
    open.value = true
}

const closeModal = function () {
    open.value = false
}

defineExpose({ closeModal })

document.addEventListener('keydown', onEscape)

onUnmounted(() => {
    document.removeEventListener('keydown', onEscape)
})

watch(open, (value) => {
    if (value) {
        emit('modal-open')
    } else {
        emit('modal-close')
    }
})
</script>
