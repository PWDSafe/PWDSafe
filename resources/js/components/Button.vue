<template>
    <button :class="allclasses" v-if="btntype === 'button'">
        <slot></slot>
    </button>
    <a :class="allclasses" v-else>
        <slot></slot>
    </a>
</template>
<script>
export default {
    props: {
        theme: {
            type: String,
            default: 'primary',
        },
        classes: {
            type: String,
            default: '',
        },
        btntype: {
            type: String,
            default: 'button'
        }
    },
    data() {
        return {
            colors: {
                primary: 'text-indigo-500 border-indigo-500 hover:bg-indigo-500 hover:text-white',
                secondary: 'text-gray-600 border-gray-600 hover:bg-gray-600 hover:text-gray-100',
                danger: 'text-red-600 border-red-600 hover:bg-red-600 hover:text-red-100',
                warning: 'text-orange-600 border-orange-600 hover:bg-orange-600 hover:text-orange-100',
            }
        }
    },
    computed: {
        allclasses: function() {
            return 'border px-4 py-1 rounded transition duration-200 ' + this.colors[this.theme] + ' ' + this.classes;
        }
    }
}
</script>
