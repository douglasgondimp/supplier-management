<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import NoteEditor from '@/components/NoteEditor.vue';
import MaskedInput from '@/components/MaskedInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
const defaults = {
    active: true,
    type_person: 'fisica',
    has_condominium: false,
    phone_type: 'celular',
    phone_number: '',
    zip_address: '',
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: '',
    reference_point: '',
    condominium_address: '',
    condominium_number: '',
    note: '',
    individual: { name: '', surname: '', cpf: '', document_number: '' },
    corporate: {
        company_name: '',
        fantasy_name: '',
        cnpj: '',
        state_registration: '',
        municipal_registration: '',
        cnpj_status: '',
        state_registration_indicator: 'nao_contribuinte',
        remittance: 'a_recolher',
    },
    emails: [] as { email: string; email_type: string }[],
    phones: [] as { phone_number: string; phone_type: string }[],
    contacts: [] as {
        name: string;
        company: string;
        position: string;
        phone_number: string;
        phone_type: string;
        email: string;
        email_type: string;
    }[],
};
const props = defineProps<{
    supplier: (typeof defaults & { id: number }) | null;
    readonly: boolean;
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Fornecedores', href: '/suppliers' }] },
});
const form = useForm({
    ...defaults,
    ...props.supplier,
    emails: props.supplier?.emails.length
        ? props.supplier.emails
        : [{ email: '', email_type: 'pessoal' }],
    individual: props.supplier?.individual ?? defaults.individual,
    corporate: props.supplier?.corporate ?? defaults.corporate,
});
const errors = computed(() => form.errors as Record<string, string>);
const zipCodeLoading = ref(false);
const zipCodeError = ref('');
const cnpjLoading = ref(false);
const cnpjError = ref('');

watch(
    [
        () => (form.corporate.cnpj ?? '').replace(/\D/g, ''),
        () => form.type_person,
    ],
    async ([cnpj, personType], _previous, onCleanup) => {
        cnpjError.value = '';
        cnpjLoading.value = false;
        if (props.readonly || personType !== 'juridica' || cnpj.length !== 14)
            return;

        const controller = new AbortController();
        onCleanup(() => controller.abort());
        cnpjLoading.value = true;

        try {
            const response = await fetch(`/api-data/cnpj?cnpj=${cnpj}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) {
                throw new Error(
                    response.status === 404
                        ? 'CNPJ não encontrado.'
                        : 'Não foi possível consultar o CNPJ. Tente novamente ou preencha os dados manualmente.',
                );
            }
            const company = (await response.json()) as {
                company_name: string;
                fantasy_name: string;
                cnpj_status: string;
            };
            if (controller.signal.aborted) return;

            form.corporate.company_name = company.company_name;
            form.corporate.fantasy_name = company.fantasy_name;
            form.corporate.cnpj_status = company.cnpj_status;
        } catch (error) {
            if (!controller.signal.aborted) {
                cnpjError.value =
                    error instanceof Error &&
                    error.message === 'CNPJ não encontrado.'
                        ? error.message
                        : 'Não foi possível consultar o CNPJ. Tente novamente ou preencha os dados manualmente.';
            }
        } finally {
            if (!controller.signal.aborted) cnpjLoading.value = false;
        }
    },
);

watch(
    () => (form.zip_address ?? '').replace(/\D/g, ''),
    async (cep, _previous, onCleanup) => {
        zipCodeError.value = '';
        zipCodeLoading.value = false;
        if (props.readonly || cep.length !== 8) return;

        const controller = new AbortController();
        onCleanup(() => controller.abort());
        zipCodeLoading.value = true;

        try {
            const response = await fetch(`/api-data/zip-code?cep=${cep}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) {
                throw new Error(
                    response.status === 404
                        ? 'CEP não encontrado.'
                        : 'Não foi possível consultar o CEP. Tente novamente ou preencha o endereço manualmente.',
                );
            }
            const address = (await response.json()) as {
                street: string;
                complement: string;
                neighborhood: string;
                city: string;
                state: string;
            };
            if (controller.signal.aborted) return;

            form.street = address.street;
            form.complement = address.complement;
            form.neighborhood = address.neighborhood;
            form.city = address.city;
            form.state = address.state;
        } catch (error) {
            if (!controller.signal.aborted) {
                zipCodeError.value =
                    error instanceof Error
                        ? error.message
                        : 'Não foi possível consultar o CEP.';
            }
        } finally {
            if (!controller.signal.aborted) zipCodeLoading.value = false;
        }
    },
);
const title = computed(() =>
    props.readonly
        ? 'Ver fornecedor'
        : props.supplier
          ? 'Editar fornecedor'
          : 'Novo fornecedor',
);
const formElement = ref<HTMLFormElement>();
function revealErrors() {
    formElement.value?.querySelectorAll('details').forEach((block) => {
        block.open = true;
    });
    nextTick(() =>
        formElement.value
            ?.querySelector<HTMLElement>('[aria-invalid="true"]')
            ?.focus(),
    );
}
function submit() {
    if (props.readonly) return;
    if (props.supplier)
        form.put(`/suppliers/${props.supplier.id}`, { onError: revealErrors });
    else form.post('/suppliers', { onError: revealErrors });
}
</script>
<template>
    <Head :title="title" />
    <div class="mx-auto w-full max-w-7xl space-y-5 bg-muted/30 p-4 md:p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">{{ title }}</h1>
            <Button v-if="readonly && supplier" as-child
                ><Link :href="`/suppliers/${supplier.id}/edit`"
                    >Editar</Link
                ></Button
            >
        </div>
        <form ref="formElement" class="space-y-5" @submit.prevent="submit">
            <details
                open
                class="group rounded-md border border-t-4 bg-background"
            >
                <summary
                    class="flex cursor-pointer list-none items-center justify-between border-b px-4 py-3 text-lg font-medium"
                >
                    Dados do Fornecedor<span
                        aria-hidden="true"
                        class="group-open:hidden"
                        >+</span
                    ><span aria-hidden="true" class="hidden group-open:inline"
                        >−</span
                    >
                </summary>
                <fieldset
                    :disabled="readonly || form.processing"
                    class="space-y-4 p-4"
                >
                    <div class="flex flex-wrap gap-5">
                        <label class="flex items-center gap-2"
                            ><input
                                v-model="form.type_person"
                                type="radio"
                                name="type_person"
                                :disabled="!!supplier"
                                value="juridica"
                            />
                            Pessoa Jurídica</label
                        ><label class="flex items-center gap-2"
                            ><input
                                v-model="form.type_person"
                                type="radio"
                                name="type_person"
                                :disabled="!!supplier"
                                value="fisica"
                            />
                            Pessoa Física</label
                        >
                    </div>
                    <InputError :message="errors.type_person" />
                    <div
                        v-if="form.type_person === 'fisica'"
                        class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div class="space-y-2">
                            <label
                                :for="'individual.cpf'"
                                class="text-sm font-medium"
                                >CPF
                                <span class="text-destructive">*</span></label
                            ><MaskedInput
                                :id="'individual.cpf'"
                                mask="cpf"
                                placeholder="000.000.000-00"
                                v-model="form.individual.cpf"
                                :aria-invalid="!!errors['individual.cpf']"
                            /><InputError :message="errors['individual.cpf']" />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'individual.name'"
                                class="text-sm font-medium"
                                >Nome
                                <span class="text-destructive">*</span></label
                            ><Input
                                :id="'individual.name'"
                                v-model="form.individual.name"
                                :aria-invalid="!!errors['individual.name']"
                            /><InputError
                                :message="errors['individual.name']"
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'individual.surname'"
                                class="text-sm font-medium"
                                >Apelido</label
                            ><Input
                                :id="'individual.surname'"
                                v-model="form.individual.surname"
                                :aria-invalid="!!errors['individual.surname']"
                            /><InputError
                                :message="errors['individual.surname']"
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'individual.document_number'"
                                class="text-sm font-medium"
                                >Documento de identidade
                                <span class="text-destructive">*</span></label
                            ><Input
                                :id="'individual.document_number'"
                                v-model="form.individual.document_number"
                                :aria-invalid="
                                    !!errors['individual.document_number']
                                "
                            /><InputError
                                :message="errors['individual.document_number']"
                            />
                        </div>
                    </div>
                    <div
                        v-else
                        class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div class="space-y-2">
                            <label
                                :for="'corporate.cnpj'"
                                class="text-sm font-medium"
                                >CNPJ
                                <span class="text-destructive">*</span></label
                            ><MaskedInput
                                :id="'corporate.cnpj'"
                                mask="cnpj"
                                placeholder="00.000.000/0000-00"
                                v-model="form.corporate.cnpj"
                                :aria-invalid="
                                    !!errors['corporate.cnpj'] || !!cnpjError
                                "
                            /><InputError :message="errors['corporate.cnpj']" />
                            <p
                                v-if="cnpjLoading"
                                role="status"
                                class="text-sm text-muted-foreground"
                            >
                                Consultando CNPJ…
                            </p>
                            <InputError :message="cnpjError" />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'corporate.company_name'"
                                class="text-sm font-medium"
                                >Razão Social
                                <span class="text-destructive">*</span></label
                            ><Input
                                :id="'corporate.company_name'"
                                v-model="form.corporate.company_name"
                                :aria-invalid="
                                    !!errors['corporate.company_name']
                                "
                            /><InputError
                                :message="errors['corporate.company_name']"
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'corporate.fantasy_name'"
                                class="text-sm font-medium"
                                >Nome Fantasia
                                <span class="text-destructive">*</span></label
                            ><Input
                                :id="'corporate.fantasy_name'"
                                v-model="form.corporate.fantasy_name"
                                :aria-invalid="
                                    !!errors['corporate.fantasy_name']
                                "
                            /><InputError
                                :message="errors['corporate.fantasy_name']"
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'corporate.state_registration_indicator'"
                                class="text-sm font-medium"
                                >Indicador de Inscrição Estadual
                                <span class="text-destructive">*</span></label
                            ><select
                                :id="'corporate.state_registration_indicator'"
                                v-model="
                                    form.corporate.state_registration_indicator
                                "
                                :aria-invalid="
                                    !!errors[
                                        'corporate.state_registration_indicator'
                                    ]
                                "
                                class="h-9 w-full rounded-md border bg-background px-3"
                            >
                                <option value="contribuinte">
                                    Contribuinte
                                </option>
                                <option value="isento">Isento</option>
                                <option value="nao_contribuinte">
                                    Não contribuinte
                                </option></select
                            ><InputError
                                :message="
                                    errors[
                                        'corporate.state_registration_indicator'
                                    ]
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'corporate.state_registration'"
                                class="text-sm font-medium"
                                >Inscrição Estadual</label
                            ><Input
                                :id="'corporate.state_registration'"
                                v-model="form.corporate.state_registration"
                                :aria-invalid="
                                    !!errors['corporate.state_registration']
                                "
                            /><InputError
                                :message="
                                    errors['corporate.state_registration']
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'corporate.municipal_registration'"
                                class="text-sm font-medium"
                                >Inscrição Municipal</label
                            ><Input
                                :id="'corporate.municipal_registration'"
                                v-model="form.corporate.municipal_registration"
                                :aria-invalid="
                                    !!errors['corporate.municipal_registration']
                                "
                            /><InputError
                                :message="
                                    errors['corporate.municipal_registration']
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'corporate.cnpj_status'"
                                class="text-sm font-medium"
                                >Situação CNPJ</label
                            ><Input
                                :id="'corporate.cnpj_status'"
                                v-model="form.corporate.cnpj_status"
                                :aria-invalid="
                                    !!errors['corporate.cnpj_status']
                                "
                            /><InputError
                                :message="errors['corporate.cnpj_status']"
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'corporate.remittance'"
                                class="text-sm font-medium"
                                >Recolhimento
                                <span class="text-destructive">*</span></label
                            ><select
                                :id="'corporate.remittance'"
                                v-model="form.corporate.remittance"
                                :aria-invalid="!!errors['corporate.remittance']"
                                class="h-9 w-full rounded-md border bg-background px-3"
                            >
                                <option value="a_recolher">A recolher</option>
                                <option value="retido">Retido</option></select
                            ><InputError
                                :message="errors['corporate.remittance']"
                            />
                        </div>
                    </div>
                    <label class="flex items-center gap-2"
                        ><input v-model="form.active" type="checkbox" />
                        Ativo</label
                    >
                </fieldset>
            </details>
            <details
                open
                class="group rounded-md border border-t-4 bg-background"
            >
                <summary
                    class="flex cursor-pointer list-none items-center justify-between border-b px-4 py-3 text-lg font-medium"
                >
                    Contato Principal<span
                        aria-hidden="true"
                        class="group-open:hidden"
                        >+</span
                    ><span aria-hidden="true" class="hidden group-open:inline"
                        >−</span
                    >
                </summary>
                <fieldset
                    :disabled="readonly || form.processing"
                    class="space-y-4 p-4"
                >
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label
                                        :for="'phone_number'"
                                        class="text-sm font-medium"
                                        >Telefone
                                        <span class="text-destructive"
                                            >*</span
                                        ></label
                                    ><MaskedInput
                                        :id="'phone_number'"
                                        mask="phone"
                                        placeholder="(00) 00000-0000"
                                        v-model="form.phone_number"
                                        :aria-invalid="!!errors['phone_number']"
                                    /><InputError
                                        :message="errors['phone_number']"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label
                                        :for="'phone_type'"
                                        class="text-sm font-medium"
                                        >Tipo
                                        <span class="text-destructive"
                                            >*</span
                                        ></label
                                    ><select
                                        :id="'phone_type'"
                                        v-model="form.phone_type"
                                        :aria-invalid="!!errors['phone_type']"
                                        class="h-9 w-full rounded-md border bg-background px-3"
                                    >
                                        <option value="residencial">
                                            Residencial
                                        </option>
                                        <option value="comercial">
                                            Comercial
                                        </option>
                                        <option value="celular">
                                            Celular
                                        </option></select
                                    ><InputError
                                        :message="errors['phone_type']"
                                    />
                                </div>
                            </div>
                            <div
                                v-for="(row, index) in form.phones"
                                :key="index"
                                class="grid gap-4 border-b pb-4 sm:grid-cols-2"
                            >
                                <div class="space-y-2">
                                    <label
                                        :for="`phones-${index}-phone_number`"
                                        class="text-sm font-medium"
                                        >Telefone adicional</label
                                    ><MaskedInput
                                        :id="`phones-${index}-phone_number`"
                                        mask="phone"
                                        placeholder="(00) 00000-0000"
                                        v-model="row.phone_number"
                                        :aria-invalid="
                                            !!errors[
                                                `phones.${index}.phone_number`
                                            ]
                                        "
                                    /><InputError
                                        :message="
                                            errors[
                                                `phones.${index}.phone_number`
                                            ]
                                        "
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label
                                        :for="`phones-${index}-phone_type`"
                                        class="text-sm font-medium"
                                        >Tipo</label
                                    ><select
                                        :id="`phones-${index}-phone_type`"
                                        v-model="row.phone_type"
                                        :aria-invalid="
                                            !!errors[
                                                `phones.${index}.phone_type`
                                            ]
                                        "
                                        class="h-9 w-full rounded-md border bg-background px-3"
                                    >
                                        <option value="residencial">
                                            Residencial
                                        </option>
                                        <option value="comercial">
                                            Comercial
                                        </option>
                                        <option value="celular">
                                            Celular
                                        </option></select
                                    ><InputError
                                        :message="
                                            errors[`phones.${index}.phone_type`]
                                        "
                                    />
                                </div>
                                <Button
                                    v-if="!readonly"
                                    type="button"
                                    variant="outline"
                                    @click="form.phones.splice(index, 1)"
                                    >Remover telefone</Button
                                >
                            </div>
                            <Button
                                v-if="!readonly"
                                type="button"
                                variant="outline"
                                @click="
                                    form.phones.push({
                                        phone_number: '',
                                        phone_type: 'celular',
                                    })
                                "
                                >Adicionar telefone</Button
                            >
                        </div>
                        <div class="space-y-4">
                            <div
                                v-for="(row, index) in form.emails"
                                :key="index"
                                class="grid gap-4 border-b pb-4 sm:grid-cols-2"
                            >
                                <div class="space-y-2">
                                    <label
                                        :for="`emails-${index}-email`"
                                        class="text-sm font-medium"
                                        >E-mail</label
                                    ><Input
                                        :id="`emails-${index}-email`"
                                        v-model="row.email"
                                        :aria-invalid="
                                            !!errors[`emails.${index}.email`]
                                        "
                                    /><InputError
                                        :message="
                                            errors[`emails.${index}.email`]
                                        "
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label
                                        :for="`emails-${index}-email_type`"
                                        class="text-sm font-medium"
                                        >Tipo</label
                                    ><select
                                        :id="`emails-${index}-email_type`"
                                        v-model="row.email_type"
                                        :aria-invalid="
                                            !!errors[
                                                `emails.${index}.email_type`
                                            ]
                                        "
                                        class="h-9 w-full rounded-md border bg-background px-3"
                                    >
                                        <option value="pessoal">Pessoal</option>
                                        <option value="comercial">
                                            Comercial
                                        </option>
                                        <option value="outro">
                                            Outro
                                        </option></select
                                    ><InputError
                                        :message="
                                            errors[`emails.${index}.email_type`]
                                        "
                                    />
                                </div>
                                <Button
                                    v-if="!readonly && index > 0"
                                    type="button"
                                    variant="outline"
                                    @click="form.emails.splice(index, 1)"
                                    >Remover e-mail</Button
                                >
                            </div>
                            <Button
                                v-if="!readonly"
                                type="button"
                                variant="outline"
                                @click="
                                    form.emails.push({
                                        email: '',
                                        email_type: 'pessoal',
                                    })
                                "
                                >Adicionar e-mail</Button
                            >
                        </div>
                    </div>
                </fieldset>
            </details>
            <details
                open
                class="group rounded-md border border-t-4 bg-background"
            >
                <summary
                    class="flex cursor-pointer list-none items-center justify-between border-b px-4 py-3 text-lg font-medium"
                >
                    Contatos Adicionais<span
                        aria-hidden="true"
                        class="group-open:hidden"
                        >+</span
                    ><span aria-hidden="true" class="hidden group-open:inline"
                        >−</span
                    >
                </summary>
                <fieldset
                    :disabled="readonly || form.processing"
                    class="space-y-4 p-4"
                >
                    <p
                        v-if="!form.contacts.length"
                        class="py-2 text-center text-sm text-muted-foreground"
                    >
                        Não há contatos adicionais.
                    </p>
                    <div
                        v-for="(row, index) in form.contacts"
                        :key="index"
                        class="space-y-4 rounded-md border p-4"
                    >
                        <h3 class="font-medium">Contato {{ index + 1 }}</h3>
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="space-y-2">
                                <label
                                    :for="`contacts-${index}-name`"
                                    class="text-sm font-medium"
                                    >Nome
                                    <span class="text-destructive"
                                        >*</span
                                    ></label
                                ><Input
                                    :id="`contacts-${index}-name`"
                                    v-model="row.name"
                                    :aria-invalid="
                                        !!errors[`contacts.${index}.name`]
                                    "
                                /><InputError
                                    :message="errors[`contacts.${index}.name`]"
                                />
                            </div>
                            <div class="space-y-2">
                                <label
                                    :for="`contacts-${index}-company`"
                                    class="text-sm font-medium"
                                    >Empresa</label
                                ><Input
                                    :id="`contacts-${index}-company`"
                                    v-model="row.company"
                                    :aria-invalid="
                                        !!errors[`contacts.${index}.company`]
                                    "
                                /><InputError
                                    :message="
                                        errors[`contacts.${index}.company`]
                                    "
                                />
                            </div>
                            <div class="space-y-2">
                                <label
                                    :for="`contacts-${index}-position`"
                                    class="text-sm font-medium"
                                    >Cargo</label
                                ><Input
                                    :id="`contacts-${index}-position`"
                                    v-model="row.position"
                                    :aria-invalid="
                                        !!errors[`contacts.${index}.position`]
                                    "
                                /><InputError
                                    :message="
                                        errors[`contacts.${index}.position`]
                                    "
                                />
                            </div>
                            <div class="space-y-2">
                                <label
                                    :for="`contacts-${index}-phone_number`"
                                    class="text-sm font-medium"
                                    >Telefone
                                    <span class="text-destructive"
                                        >*</span
                                    ></label
                                ><MaskedInput
                                    :id="`contacts-${index}-phone_number`"
                                    mask="phone"
                                    placeholder="(00) 00000-0000"
                                    v-model="row.phone_number"
                                    :aria-invalid="
                                        !!errors[
                                            `contacts.${index}.phone_number`
                                        ]
                                    "
                                /><InputError
                                    :message="
                                        errors[`contacts.${index}.phone_number`]
                                    "
                                />
                            </div>
                            <div class="space-y-2">
                                <label
                                    :for="`contacts-${index}-phone_type`"
                                    class="text-sm font-medium"
                                    >Tipo de telefone
                                    <span class="text-destructive"
                                        >*</span
                                    ></label
                                ><select
                                    :id="`contacts-${index}-phone_type`"
                                    v-model="row.phone_type"
                                    :aria-invalid="
                                        !!errors[`contacts.${index}.phone_type`]
                                    "
                                    class="h-9 w-full rounded-md border bg-background px-3"
                                >
                                    <option value="residencial">
                                        Residencial
                                    </option>
                                    <option value="comercial">Comercial</option>
                                    <option value="celular">
                                        Celular
                                    </option></select
                                ><InputError
                                    :message="
                                        errors[`contacts.${index}.phone_type`]
                                    "
                                />
                            </div>
                            <div class="space-y-2">
                                <label
                                    :for="`contacts-${index}-email`"
                                    class="text-sm font-medium"
                                    >E-mail</label
                                ><Input
                                    :id="`contacts-${index}-email`"
                                    v-model="row.email"
                                    :aria-invalid="
                                        !!errors[`contacts.${index}.email`]
                                    "
                                /><InputError
                                    :message="errors[`contacts.${index}.email`]"
                                />
                            </div>
                            <div class="space-y-2">
                                <label
                                    :for="`contacts-${index}-email_type`"
                                    class="text-sm font-medium"
                                    >Tipo de e-mail</label
                                ><select
                                    :id="`contacts-${index}-email_type`"
                                    v-model="row.email_type"
                                    :aria-invalid="
                                        !!errors[`contacts.${index}.email_type`]
                                    "
                                    class="h-9 w-full rounded-md border bg-background px-3"
                                >
                                    <option value="">Selecione</option>
                                    <option value="pessoal">Pessoal</option>
                                    <option value="comercial">Comercial</option>
                                    <option value="outro">Outro</option></select
                                ><InputError
                                    :message="
                                        errors[`contacts.${index}.email_type`]
                                    "
                                />
                            </div>
                        </div>
                        <Button
                            v-if="!readonly"
                            type="button"
                            variant="outline"
                            @click="form.contacts.splice(index, 1)"
                            >Remover contato</Button
                        >
                    </div>
                    <Button
                        v-if="!readonly"
                        type="button"
                        variant="outline"
                        @click="
                            form.contacts.push({
                                name: '',
                                company: '',
                                position: '',
                                phone_number: '',
                                phone_type: 'celular',
                                email: '',
                                email_type: '',
                            })
                        "
                        >Adicionar contato</Button
                    >
                </fieldset>
            </details>
            <details
                open
                class="group rounded-md border border-t-4 bg-background"
            >
                <summary
                    class="flex cursor-pointer list-none items-center justify-between border-b px-4 py-3 text-lg font-medium"
                >
                    Dados de Endereço<span
                        aria-hidden="true"
                        class="group-open:hidden"
                        >+</span
                    ><span aria-hidden="true" class="hidden group-open:inline"
                        >−</span
                    >
                </summary>
                <fieldset
                    :disabled="readonly || form.processing"
                    class="space-y-4 p-4"
                >
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-2">
                            <label
                                :for="'zip_address'"
                                class="text-sm font-medium"
                                >CEP
                                <span class="text-destructive">*</span></label
                            ><MaskedInput
                                :id="'zip_address'"
                                mask="cep"
                                placeholder="00000-000"
                                v-model="form.zip_address"
                                :aria-invalid="
                                    !!errors['zip_address'] || !!zipCodeError
                                "
                            /><InputError :message="errors['zip_address']" />
                            <p
                                v-if="zipCodeLoading"
                                role="status"
                                class="text-sm text-muted-foreground"
                            >
                                Consultando CEP…
                            </p>
                            <InputError :message="zipCodeError" />
                        </div>
                        <div class="space-y-2">
                            <label :for="'street'" class="text-sm font-medium"
                                >Logradouro
                                <span class="text-destructive">*</span></label
                            ><Input
                                :id="'street'"
                                v-model="form.street"
                                :aria-invalid="!!errors['street']"
                            /><InputError :message="errors['street']" />
                        </div>
                        <div class="space-y-2">
                            <label :for="'number'" class="text-sm font-medium"
                                >Número
                                <span class="text-destructive">*</span></label
                            ><Input
                                :id="'number'"
                                v-model="form.number"
                                :aria-invalid="!!errors['number']"
                            /><InputError :message="errors['number']" />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'complement'"
                                class="text-sm font-medium"
                                >Complemento</label
                            ><Input
                                :id="'complement'"
                                v-model="form.complement"
                                :aria-invalid="!!errors['complement']"
                            /><InputError :message="errors['complement']" />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'neighborhood'"
                                class="text-sm font-medium"
                                >Bairro
                                <span class="text-destructive">*</span></label
                            ><Input
                                :id="'neighborhood'"
                                v-model="form.neighborhood"
                                :aria-invalid="!!errors['neighborhood']"
                            /><InputError :message="errors['neighborhood']" />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'reference_point'"
                                class="text-sm font-medium"
                                >Ponto de Referência</label
                            ><Input
                                :id="'reference_point'"
                                v-model="form.reference_point"
                                :aria-invalid="!!errors['reference_point']"
                            /><InputError
                                :message="errors['reference_point']"
                            />
                        </div>
                        <div class="space-y-2">
                            <label :for="'state'" class="text-sm font-medium"
                                >UF
                                <span class="text-destructive">*</span></label
                            ><select
                                :id="'state'"
                                v-model="form.state"
                                :aria-invalid="!!errors['state']"
                                class="h-9 w-full rounded-md border bg-background px-3"
                            >
                                <option value="">Selecione</option>
                                <option value="AC">AC</option>
                                <option value="AL">AL</option>
                                <option value="AP">AP</option>
                                <option value="AM">AM</option>
                                <option value="BA">BA</option>
                                <option value="CE">CE</option>
                                <option value="DF">DF</option>
                                <option value="ES">ES</option>
                                <option value="GO">GO</option>
                                <option value="MA">MA</option>
                                <option value="MT">MT</option>
                                <option value="MS">MS</option>
                                <option value="MG">MG</option>
                                <option value="PA">PA</option>
                                <option value="PB">PB</option>
                                <option value="PR">PR</option>
                                <option value="PE">PE</option>
                                <option value="PI">PI</option>
                                <option value="RJ">RJ</option>
                                <option value="RN">RN</option>
                                <option value="RS">RS</option>
                                <option value="RO">RO</option>
                                <option value="RR">RR</option>
                                <option value="SC">SC</option>
                                <option value="SP">SP</option>
                                <option value="SE">SE</option>
                                <option value="TO">TO</option></select
                            ><InputError :message="errors['state']" />
                        </div>
                        <div class="space-y-2">
                            <label :for="'city'" class="text-sm font-medium"
                                >Cidade
                                <span class="text-destructive">*</span></label
                            ><Input
                                :id="'city'"
                                v-model="form.city"
                                :aria-invalid="!!errors['city']"
                            /><InputError :message="errors['city']" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2"
                        ><input
                            v-model="form.has_condominium"
                            type="checkbox"
                        />
                        Condomínio?</label
                    >
                    <div
                        v-if="form.has_condominium"
                        class="grid gap-5 sm:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <label
                                :for="'condominium_address'"
                                class="text-sm font-medium"
                                >Endereço do condomínio</label
                            ><Input
                                :id="'condominium_address'"
                                v-model="form.condominium_address"
                                :aria-invalid="!!errors['condominium_address']"
                            /><InputError
                                :message="errors['condominium_address']"
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                :for="'condominium_number'"
                                class="text-sm font-medium"
                                >Número no condomínio</label
                            ><Input
                                :id="'condominium_number'"
                                v-model="form.condominium_number"
                                :aria-invalid="!!errors['condominium_number']"
                            /><InputError
                                :message="errors['condominium_number']"
                            />
                        </div>
                    </div>
                </fieldset>
            </details>
            <details
                open
                class="group rounded-md border border-t-4 bg-background"
            >
                <summary
                    class="flex cursor-pointer list-none items-center justify-between border-b px-4 py-3 text-lg font-medium"
                >
                    Observação<span aria-hidden="true" class="group-open:hidden"
                        >+</span
                    ><span aria-hidden="true" class="hidden group-open:inline"
                        >−</span
                    >
                </summary>
                <fieldset
                    :disabled="readonly || form.processing"
                    class="space-y-4 p-4"
                >
                    <NoteEditor
                        v-model="form.note"
                        :readonly="readonly"
                        :disabled="form.processing"
                    /><InputError :message="errors.note" />
                </fieldset>
            </details>
            <div
                v-if="Object.keys(errors).length"
                role="alert"
                class="text-sm text-destructive"
            >
                <p>Revise os campos informados.</p>
                <ul class="list-inside list-disc">
                    <li v-for="(error, key) in errors" :key="key">
                        {{ error }}
                    </li>
                </ul>
            </div>
            <div class="flex gap-3">
                <Button
                    v-if="!readonly"
                    type="submit"
                    :disabled="form.processing"
                    >{{ form.processing ? 'Salvando…' : 'Salvar' }}</Button
                ><Button as-child variant="outline"
                    ><Link href="/suppliers">Voltar</Link></Button
                >
            </div>
        </form>
    </div>
</template>
