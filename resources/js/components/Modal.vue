<template>
    <div>
        <button @click="open = true">
            <slot name="trigger"></slot>
        </button>
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-to-class="transform opacity-100"
            enter-class="transform opacity-0"
            leave-active-class="transition ease-in duration-75"
            leave-to-class="transform opacity-0"
            leave-class="transform opacity-100"
        >
            <div class="fixed z-10 inset-0 overflow-y-auto" v-if="open">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity">
                        <div class="absolute inset-0 bg-gray-500 opacity-75" @click="open = false"></div>
                    </div>

                    <!-- This element is to trick the browser into centering the modal contents. -->
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;

                    <div v-if="open"
                         class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm md:max-w-md sm:w-full sm:p-6"
                         role="dialog" aria-modal="true" aria-labelledby="modal-headline">
                        <slot></slot>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
<script setup>
import { ref, onUnmounted, watch, defineEmits } from "vue";

const open = ref(false)
const emit = defineEmits()

const onEscape = (e) => {
    if (open.value && e.keyCode === 27) {
        open.value = false;
    }
}

document.addEventListener('keydown', onEscape)

onUnmounted(() => {
    document.removeEventListener('keydown', onEscape)
})

watch(open, (value) => {
    if (value) {
        emit('modal-open');
    } else {
        emit('modal-close');
    }
})
</script>
