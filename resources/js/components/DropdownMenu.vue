<template>
        <div class="relative flex-shrink-0 h-full">
            <div v-if="open" @click="open = false" class="fixed inset-0"></div>
            <div class="h-full">
                <button class="h-full flex items-center text-sm focus:outline-none transition duration-150 ease-in-out" id="user-menu" aria-label="User menu" aria-haspopup="true" @click="open = !open">
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
                <div class="origin-top-right absolute right-0 -mt-1 w-48 rounded-md shadow-lg" v-if="open">
                    <div class="py-1 rounded-md bg-white shadow-xs" role="menu" aria-orientation="vertical" aria-labelledby="user-menu">
                        <slot></slot>
                    </div>
                </div>
            </transition>
        </div>
</template>
<script>
export default {
    data() {
        return {
            open: false
        }
    },
    created() {
        const onEscape = (e) => {
            if (this.open && e.keyCode === 27) {
                this.open = false;
            }
        }
        document.addEventListener('keydown', onEscape)
        this.$once('hook:destroyed', () => {
            document.removeEventListener('keydown', onEscape)
        })
    },
}
</script>
