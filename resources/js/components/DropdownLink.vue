<template>
    <a :href="href" :class="allclasses" role="menuitem">
        <slot></slot>
    </a>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({
    userClasses: {
        type: String,
        default: '',
    },
    href: {
        type: String
    }
})

const allclasses = computed(() => {
    return classes + ' ' + props.userClasses;
})

const classes = 'block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out'
</script>
