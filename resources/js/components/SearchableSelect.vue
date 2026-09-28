<script setup lang="ts">
import { computed, ref } from 'vue';
import {
    ComboboxRoot,
    ComboboxAnchor,
    ComboboxInput,
    ComboboxTrigger,
    ComboboxContent,
    ComboboxViewport,
    ComboboxItem,
    ComboboxEmpty,
} from 'reka-ui';

const props = defineProps<{
    id: string;
    options: { value: string; label: string }[];
    placeholder?: string;
    disabled?: boolean;
    invalid?: boolean;
}>();
const model = defineModel<string>({ required: true });
const search = ref('');
const normalize = (value: string) =>
    value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('pt-BR');
const filtered = computed(() =>
    props.options.filter((option) =>
        normalize(option.label).includes(normalize(search.value)),
    ),
);
const displayValue = (value: unknown) =>
    props.options.find((option) => option.value === value)?.label ??
    String(value ?? '');
</script>

<template>
    <ComboboxRoot
        v-model="model"
        :disabled="disabled"
        ignore-filter
        @update:open="search = ''"
    >
        <ComboboxAnchor
            class="flex h-9 w-full items-center rounded-md border bg-background shadow-xs"
        >
            <ComboboxInput
                :id="id"
                :display-value="displayValue"
                :placeholder="placeholder ?? 'Selecione ou pesquise'"
                :aria-invalid="invalid"
                class="min-w-0 flex-1 bg-transparent px-3 py-1 text-sm outline-none disabled:opacity-50"
                @update:model-value="search = $event"
            />
            <ComboboxTrigger class="px-3" aria-label="Exibir opções"
                >⌄</ComboboxTrigger
            >
        </ComboboxAnchor>
        <ComboboxContent
            position="popper"
            class="z-50 w-[var(--reka-combobox-trigger-width)] rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
        >
            <ComboboxViewport class="max-h-60 overflow-y-auto">
                <ComboboxEmpty class="p-2 text-sm text-muted-foreground"
                    >Nenhum resultado encontrado.</ComboboxEmpty
                >
                <ComboboxItem
                    v-for="option in filtered"
                    :key="option.value"
                    :value="option.value"
                    class="cursor-pointer rounded-sm px-2 py-1.5 text-sm outline-none data-highlighted:bg-accent data-highlighted:text-accent-foreground"
                    >{{ option.label }}</ComboboxItem
                >
            </ComboboxViewport>
        </ComboboxContent>
    </ComboboxRoot>
</template>
