<template>
    <Modal ref="modal" zindexclass="z-20">
        <template v-slot:trigger="{ openModal }">
            <Button
                type="button"
                theme="secondary"
                :size="props.buttonSize"
                @click="openModal"
                >Generate</Button
            >
        </template>
        <template #default>
            <Button
                theme="secondary"
                type="button"
                @click="$refs.modal.closeModal()"
                class="absolute top-0 right-0 mr-4 mt-4"
            >
                X
            </Button>
            <h2 class="text-xl mb-4">Generate password</h2>
            <div class="grid grid-cols-2 gap-0.5">
                <label
                    class="flex items-center gap-x-2 bg-gray-800 px-2 py-2 rounded-tl-md"
                >
                    <input
                        v-model="uppercase"
                        type="checkbox"
                        :disabled="uppercaseDisabled"
                    />
                    Uppercase
                </label>
                <label
                    class="flex items-center gap-x-2 bg-gray-800 px-2 py-2 rounded-tr-md"
                >
                    <input
                        v-model="lowercase"
                        type="checkbox"
                        :disabled="lowercaseDisabled"
                    />
                    Lowercase
                </label>
                <label
                    class="flex items-center gap-x-2 bg-gray-800 px-2 py-2 rounded-bl-md"
                >
                    <input
                        v-model="numeric"
                        type="checkbox"
                        :disabled="numericDisabled"
                    />
                    Numeric
                </label>
                <label
                    class="flex items-center gap-x-2 bg-gray-800 px-2 py-2 rounded-br-md"
                >
                    <input
                        v-model="symbols"
                        type="checkbox"
                        :disabled="symbolsDisabled"
                    />
                    Symbols
                </label>
            </div>
            <label class="flex flex-col mt-2">
                Password length: {{ length }}
                <Input
                    v-model.number="length"
                    type="range"
                    min="1"
                    max="64"
                    class="w-full"
                />
            </label>

            <div class="mt-4">
                <strong>Generated</strong>
                <div class="flex gap-x-2">
                    <Input
                        v-model="generated"
                        readonly
                        :type="showPassword ? 'text' : 'password'"
                    />
                    <Button
                        theme="secondary"
                        type="button"
                        @click="generated = generateNewPassword()"
                    >
                        <ArrowPathIcon class="h-5 w-5" />
                    </Button>
                    <Button
                        theme="secondary"
                        type="button"
                        @click="showPassword = !showPassword"
                    >
                        <EyeIcon v-if="!showPassword" class="h-5 w-5" />
                        <EyeSlashIcon v-else class="h-5 w-5" />
                    </Button>
                </div>
                <div class="flex justify-between gap-x-4">
                    <Button
                        type="button"
                        theme="secondary"
                        @click.native="toClipboard(generated)"
                        class="mt-4 flex-1"
                    >
                        Copy
                    </Button>
                    <Button
                        type="button"
                        @click="emitGenerated"
                        class="mt-4 flex-1"
                    >
                        Use
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
<script setup>
import Modal from './Modal.vue'
import Button from './Button.vue'
import Input from './Input.vue'
import { ref, watch, computed } from 'vue'
import {
    ArrowPathIcon,
    EyeIcon,
    EyeSlashIcon,
} from '@heroicons/vue/24/outline/index.js'
import { toClipboard } from '@soerenmartius/vue3-clipboard'

const props = defineProps({
    buttonSize: {
        type: String,
        default: 'medium',
    },
})

const emit = defineEmits(['generated'])

const uppercase = ref(true)
const lowercase = ref(true)
const numeric = ref(true)
const symbols = ref(false)
const length = ref(16)

const generated = ref('')
const generateNewPassword = () => {
    let all = uppercase.value ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : ''
    all += lowercase.value ? 'abcdefghijklmnopqrstuvwxyz' : ''
    all += numeric.value ? '0123456789' : ''
    all += symbols.value ? '~`!@#$%^&*()_-+={[}]|\\:;"\'<,>.?/' : ''

    let generatedpw = ''

    for (let i = 0; i < length.value; i++) {
        let randompos = Math.floor(Math.random() * all.length)
        generatedpw += all.substring(randompos, randompos + 1)
    }

    return generatedpw
}

watch(uppercase, () => {
    generated.value = generateNewPassword()
})
watch(lowercase, () => {
    generated.value = generateNewPassword()
})
watch(numeric, () => {
    generated.value = generateNewPassword()
})
watch(symbols, () => {
    generated.value = generateNewPassword()
})
watch(length, () => {
    generated.value = generateNewPassword()
})

generated.value = generateNewPassword()

const showPassword = ref(false)

const modal = ref(null)

const uppercaseDisabled = computed(() => {
    return !lowercase.value && !symbols.value && !numeric.value
})
const lowercaseDisabled = computed(() => {
    return !uppercase.value && !symbols.value && !numeric.value
})
const numericDisabled = computed(() => {
    return !lowercase.value && !symbols.value && !uppercase.value
})
const symbolsDisabled = computed(() => {
    return !lowercase.value && !uppercase.value && !numeric.value
})

const emitGenerated = () => {
    emit('generated', generated.value)
    modal.value.closeModal()
}
</script>
