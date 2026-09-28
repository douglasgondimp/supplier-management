<?php

namespace App\Http\Requests;

use App\Enums\EmailType;
use App\Enums\PersonType;
use App\Enums\PhoneType;
use App\Enums\Remittance;
use App\Enums\StateRegistrationIndicator;
use App\Models\Supplier;
use App\Rules\UniqueCNPJ;
use App\Rules\UniqueCPF;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $supplier = $this->isMethod('PUT') || $this->isMethod('PATCH')
            ? $this->route('supplier')
            : null;
        $supplier = $supplier instanceof Supplier ? $supplier : null;

        $rules = [
            'active' => ['required', 'boolean'],
            'type_person' => [
                'required',
                Rule::enum(PersonType::class),
                function (string $attribute, mixed $value, Closure $fail) use ($supplier): void {
                    if ($supplier !== null && $value !== $supplier->type_person->value) {
                        $fail('O tipo de pessoa não pode ser alterado após o cadastro.');
                    }
                },
            ],
            'phone_number' => ['required', 'celular_com_ddd'],
            'phone_type' => ['required', Rule::enum(PhoneType::class)],
            'zip_address' => ['required', 'formato_cep'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:50'],
            'neighborhood' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:50'],
            'state' => ['required', 'max:2'],
            'reference_point' => ['nullable', 'string', 'max:255'],
            'has_condominium' => ['required', 'boolean'],
            'condominium_address' => ['nullable', 'string', 'max:255'],
            'condominium_number' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string'],
            'individual' => ['exclude_unless:type_person,fisica', 'required', 'array'],
            'corporate' => ['exclude_unless:type_person,juridica', 'required', 'array'],
            'emails' => ['present', 'array'],
            'phones' => ['present', 'array'],
            'contacts' => ['present', 'array'],
            'emails.*.email' => ['nullable', 'email', 'max:150'],
            'emails.*.email_type' => ['required_with:emails.*.email', 'nullable', Rule::enum(EmailType::class)],
            'phones.*.phone_number' => ['nullable', 'celular_com_ddd'],
            'phones.*.phone_type' => ['required_with:phones.*.phone_number', 'nullable', Rule::enum(PhoneType::class)],
            'contacts.*.name' => ['required', 'string', 'max:255'],
            'contacts.*.company' => ['nullable', 'string', 'max:150'],
            'contacts.*.position' => ['nullable', 'string', 'max:30'],
            'contacts.*.phone_number' => ['required', 'celular_com_ddd'],
            'contacts.*.phone_type' => ['required', Rule::enum(PhoneType::class)],
            'contacts.*.email' => ['nullable', 'email', 'max:150'],
            'contacts.*.email_type' => ['nullable', Rule::enum(EmailType::class)],
        ];

        $fields = $this->input('type_person') === 'fisica' ? [
            'individual.cpf' => ['bail', 'required', 'cpf', new UniqueCPF($supplier?->individual?->cpf)],
            'individual.name' => ['required', 'string', 'max:150'],
            'individual.surname' => ['nullable', 'string', 'max:255'],
            'individual.document_number' => ['required', 'string', 'max:15'],
        ] : [
            'corporate.cnpj' => ['bail', 'required', 'cnpj', new UniqueCNPJ($supplier?->corporate?->cnpj)],
            'corporate.company_name' => ['required', 'string', 'max:255'],
            'corporate.fantasy_name' => ['required', 'string', 'max:255'],
            'corporate.state_registration_indicator' => ['required', Rule::enum(StateRegistrationIndicator::class)],
            'corporate.state_registration' => ['nullable', 'string', 'max:50'],
            'corporate.municipal_registration' => ['nullable', 'string', 'max:50'],
            'corporate.cnpj_status' => ['nullable', 'string', 'max:30'],
            'corporate.remittance' => ['required', Rule::enum(Remittance::class)],
        ];

        return [...$rules, ...$fields];
    }
}
