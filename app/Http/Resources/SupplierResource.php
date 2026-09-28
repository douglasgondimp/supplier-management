<?php

namespace App\Http\Resources;

use App\Enums\PersonType;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Supplier */
class SupplierResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['individual', 'corporate', 'emails', 'phones', 'contacts']);
        $individual = $this->resource->type_person === PersonType::P_FISICA;
        $person = $individual ? $this->resource->individual : $this->resource->corporate;

        return [
            ...$this->resource->toArray(),
            'id' => $this->resource->id,
            'name' => $person?->getAttribute($individual ? 'name' : 'company_name'),
            'alias' => $person?->getAttribute($individual ? 'surname' : 'fantasy_name'),
            'document' => $person?->getAttribute($individual ? 'cpf' : 'cnpj'),
            'active' => $this->resource->active,
        ];
    }
}
