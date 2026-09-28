<script setup lang="ts">
import { computed, nextTick } from 'vue';
import { formatInput } from '@/lib/inputMasks';
import type { InputMask } from '@/lib/inputMasks';

const props = defineProps<{ modelValue: string | null; mask: InputMask }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const formatted = computed(() => formatInput(props.modelValue, props.mask));

function update(event: Event) {
    const input = event.target as HTMLInputElement;
    const digitsBeforeCursor = input.value
        .slice(0, input.selectionStart ?? input.value.length)
        .replace(/\D/g, '').length;
    const value = formatInput(input.value, props.mask);
    emit('update:modelValue', value);
    input.value = value;
    let cursor = 0;
    let digits = 0;
    while (cursor < value.length && digits < digitsBeforeCursor) {
        if (/\d/.test(value[cursor])) digits++;
        cursor++;
    }
    nextTick(() => input.setSelectionRange(cursor, cursor));
}

function backspace(event: KeyboardEvent) {
    const input = event.target as HTMLInputElement;
    const cursor = input.selectionStart ?? 0;
    if (
        event.key !== 'Backspace' ||
        cursor !== input.selectionEnd ||
        !cursor ||
        /\d/.test(input.value[cursor - 1])
    )
        return;
    event.preventDefault();
    let start = cursor - 1;
    while (start > 0 && /\D/.test(input.value[start])) start--;
    input.value = input.value.slice(0, start) + input.value.slice(cursor);
    input.setSelectionRange(start, start);
    update(event);
}
</script>
<template>
    <input
        :value="formatted"
        :inputmode="mask === 'phone' ? 'tel' : 'numeric'"
        type="text"
        data-slot="input"
        class="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none selection:bg-primary selection:text-primary-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
        @input="update"
        @keydown="backspace"
    />
</template>
