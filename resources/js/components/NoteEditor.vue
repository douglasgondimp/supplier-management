<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelValue: string | null;
    readonly?: boolean;
    disabled?: boolean;
}>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const editor = ref<HTMLDivElement>();
const tools = [
    { label: 'Negrito', command: 'bold' },
    { label: 'Itálico', command: 'italic' },
    { label: 'Sublinhado', command: 'underline' },
    { label: 'Lista numerada', command: 'insertOrderedList' },
    { label: 'Lista com marcadores', command: 'insertUnorderedList' },
    { label: 'Limpar formatação', command: 'removeFormat' },
];
// Rebuild only supported formatting elements, without attributes or executable HTML.
function safeContent(value: string): DocumentFragment {
    const parsed = new DOMParser().parseFromString(value, 'text/html');
    const fragment = document.createDocumentFragment();
    const allowed = new Set([
        'B',
        'STRONG',
        'I',
        'EM',
        'U',
        'P',
        'DIV',
        'BR',
        'OL',
        'UL',
        'LI',
    ]);
    function append(source: Node, target: Node) {
        if (source.nodeType === Node.TEXT_NODE) {
            target.appendChild(
                document.createTextNode(source.textContent ?? ''),
            );
            return;
        }
        if (!(source instanceof Element)) return;
        if (['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT'].includes(source.tagName))
            return;
        const destination = allowed.has(source.tagName)
            ? document.createElement(source.tagName.toLowerCase())
            : target;
        if (destination !== target) target.appendChild(destination);
        source.childNodes.forEach((child) => append(child, destination));
    }
    parsed.body.childNodes.forEach((child) => append(child, fragment));
    return fragment;
}
function render() {
    editor.value?.replaceChildren(safeContent(props.modelValue ?? ''));
}
function update() {
    if (!editor.value || props.readonly || props.disabled) return;
    const clean = document.createElement('div');
    clean.append(safeContent(editor.value.innerHTML));
    emit('update:modelValue', clean.textContent?.trim() ? clean.innerHTML : '');
}
function format(command: string) {
    if (props.readonly || props.disabled) return;
    editor.value?.focus();
    document.execCommand(command);
    update();
}
function paste(event: ClipboardEvent) {
    event.preventDefault();
    if (props.readonly || props.disabled) return;
    document.execCommand(
        'insertText',
        false,
        event.clipboardData?.getData('text/plain') ?? '',
    );
    update();
}
onMounted(render);
watch(
    () => props.modelValue,
    () => {
        if (document.activeElement !== editor.value) render();
    },
);
</script>
<template>
    <div class="space-y-3">
        <div
            v-if="!readonly"
            role="toolbar"
            aria-label="Formatação da observação"
            class="flex flex-wrap gap-1"
        >
            <Button
                v-for="tool in tools"
                :key="tool.command"
                type="button"
                variant="outline"
                size="sm"
                :disabled="disabled"
                @mousedown.prevent
                @click="format(tool.command)"
                >{{ tool.label }}</Button
            >
        </div>
        <div
            ref="editor"
            role="textbox"
            aria-label="Observação"
            aria-multiline="true"
            :aria-readonly="readonly || disabled"
            :contenteditable="!readonly && !disabled"
            :tabindex="readonly ? -1 : 0"
            class="min-h-48 rounded-md border bg-background p-3 whitespace-pre-wrap outline-none focus:ring-2 focus:ring-ring [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6"
            @input="update"
            @paste="paste"
            @drop.prevent
        />
    </div>
</template>
