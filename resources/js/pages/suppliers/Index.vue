<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import { Ellipsis } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
type Row = {
    id: number;
    name: string;
    alias: string | null;
    document: string;
    active: boolean;
};
const props = defineProps<{
    suppliers: {
        data: Row[];
        current_page: number;
        last_page: number;
        total: number;
        from: number | null;
        to: number | null;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: { search: string; per_page: number };
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Fornecedores', href: '/suppliers' }] },
});
const search = ref(props.filters.search);
const perPage = ref(props.filters.per_page);
const deleting = ref<Row | null>(null);
const busy = ref(false);
let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(search, (value) => {
    clearTimeout(searchTimer);
    const term = value.trim();
    if (term.length > 0 && term.length < 3) return;
    searchTimer = setTimeout(filter, 300);
});
onBeforeUnmount(() => clearTimeout(searchTimer));
function filter() {
    clearTimeout(searchTimer);
    const term = search.value.trim();
    router.get(
        '/suppliers',
        { search: term.length >= 3 ? term : '', per_page: perPage.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
function remove() {
    if (!deleting.value) return;
    busy.value = true;
    router.delete(`/suppliers/${deleting.value.id}`, {
        onSuccess: () => {
            deleting.value = null;
        },
        onFinish: () => {
            busy.value = false;
        },
    });
}
</script>
<template>
    <Head title="Fornecedores" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">Fornecedores</h1>
            <Button as-child
                ><Link href="/suppliers/create">Novo fornecedor</Link></Button
            >
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-60 flex-1">
                <label for="search" class="mb-2 block text-sm font-medium"
                    >Buscar fornecedor</label
                ><Input
                    id="search"
                    aria-describedby="search-hint"
                    v-model="search"
                    placeholder="Razão social, nome, nome fantasia, apelido, CPF ou CNPJ"
                />
                <p id="search-hint" class="mt-1 text-xs text-muted-foreground">
                    Digite pelo menos 3 caracteres para buscar.
                </p>
            </div>
            <div>
                <label for="per-page" class="mb-2 block text-sm font-medium"
                    >Por página</label
                ><select
                    id="per-page"
                    v-model="perPage"
                    class="h-9 rounded-md border bg-background px-3"
                    @change="filter"
                >
                    <option
                        v-for="size in [10, 20, 30, 50]"
                        :key="size"
                        :value="size"
                    >
                        {{ size }}
                    </option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted">
                    <tr>
                        <th class="p-3">Razão Social/Nome</th>
                        <th class="p-3">Nome Fantasia/Apelido</th>
                        <th class="p-3">CPF/CNPJ</th>
                        <th class="p-3">Ativo</th>
                        <th class="p-3">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="supplier in suppliers.data"
                        :key="supplier.id"
                        class="border-t"
                    >
                        <td class="p-3 font-medium">{{ supplier.name }}</td>
                        <td class="p-3">{{ supplier.alias || '—' }}</td>
                        <td class="p-3 whitespace-nowrap">
                            {{ supplier.document }}
                        </td>
                        <td class="p-3">
                            {{ supplier.active ? 'Sim' : 'Não' }}
                        </td>
                        <td class="p-3">
                            <DropdownMenu
                                ><DropdownMenuTrigger as-child
                                    ><Button
                                        variant="ghost"
                                        size="icon"
                                        :aria-label="`Ações de ${supplier.name}`"
                                        ><Ellipsis
                                            class="size-4" /></Button></DropdownMenuTrigger
                                ><DropdownMenuContent align="end"
                                    ><DropdownMenuItem as-child
                                        ><Link
                                            :href="`/suppliers/${supplier.id}`"
                                            >Ver</Link
                                        ></DropdownMenuItem
                                    ><DropdownMenuItem as-child
                                        ><Link
                                            :href="`/suppliers/${supplier.id}/edit`"
                                            >Editar</Link
                                        ></DropdownMenuItem
                                    ><DropdownMenuItem
                                        class="text-destructive"
                                        @select="deleting = supplier"
                                        >Excluir</DropdownMenuItem
                                    ></DropdownMenuContent
                                ></DropdownMenu
                            >
                        </td>
                    </tr>
                    <tr v-if="!suppliers.data.length">
                        <td
                            colspan="5"
                            class="p-8 text-center text-muted-foreground"
                        >
                            Nenhum fornecedor encontrado.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <nav
            aria-label="Paginação"
            class="flex flex-wrap items-center justify-between gap-3 text-sm"
        >
            <span
                >{{ suppliers.from ?? 0 }}–{{ suppliers.to ?? 0 }} de
                {{ suppliers.total }} fornecedores</span
            >
            <div class="flex items-center gap-3">
                <Button
                    variant="outline"
                    :disabled="!suppliers.prev_page_url"
                    @click="
                        suppliers.prev_page_url &&
                        router.get(suppliers.prev_page_url)
                    "
                    >Anterior</Button
                ><span
                    >Página {{ suppliers.current_page }} de
                    {{ suppliers.last_page }}</span
                ><Button
                    variant="outline"
                    :disabled="!suppliers.next_page_url"
                    @click="
                        suppliers.next_page_url &&
                        router.get(suppliers.next_page_url)
                    "
                    >Próxima</Button
                >
            </div>
        </nav>
    </div>
    <Dialog
        :open="!!deleting"
        @update:open="!$event && !busy && (deleting = null)"
        ><DialogContent
            ><DialogHeader
                ><DialogTitle>Excluir fornecedor</DialogTitle
                ><DialogDescription
                    >Deseja excluir {{ deleting?.name }}?</DialogDescription
                ></DialogHeader
            ><DialogFooter
                ><Button
                    variant="outline"
                    :disabled="busy"
                    @click="deleting = null"
                    >Cancelar</Button
                ><Button variant="destructive" :disabled="busy" @click="remove"
                    >Excluir</Button
                ></DialogFooter
            ></DialogContent
        ></Dialog
    >
</template>
