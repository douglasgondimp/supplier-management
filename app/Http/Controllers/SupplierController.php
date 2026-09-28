<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierFiltersRequest;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(SupplierFiltersRequest $request): Response
    {
        $filters = $request->validated();
        $search = trim($filters['search'] ?? '');
        $perPage = (int) ($filters['per_page'] ?? 10);
        $query = Supplier::with(['individual', 'corporate', 'emails', 'phones', 'contacts'])
            ->when($search !== '', fn($query) => $query->textSearch($search));

        $suppliers = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        $suppliers->through(fn(Supplier $supplier) => (new SupplierResource($supplier))->resolve($request));

        return Inertia::render('suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => ['search' => $search, 'per_page' => $perPage],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('suppliers/Form', ['supplier' => null, 'readonly' => false]);
    }

    public function show(Supplier $supplier): Response
    {
        $supplier->load(['individual', 'corporate', 'emails', 'phones', 'contacts']);

        return Inertia::render('suppliers/Form', [
            'supplier' => (new SupplierResource($supplier))->resolve(),
            'readonly' => true,
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        $supplier->load(['individual', 'corporate', 'emails', 'phones', 'contacts']);

        return Inertia::render('suppliers/Form', [
            'supplier' => (new SupplierResource($supplier))->resolve(),
            'readonly' => false,
        ]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $this->save(new Supplier, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fornecedor cadastrado.']);

        return to_route('suppliers.index');
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->save($supplier, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fornecedor atualizado.']);

        return to_route('suppliers.index');
    }

    /** @param array<string, mixed> $data */
    private function save(Supplier $supplier, array $data): void
    {
        DB::transaction(function () use ($supplier, $data) {
            $supplier->fill(Arr::except($data, ['individual', 'corporate', 'emails', 'phones', 'contacts']))->save();
            $individual = $data['type_person'] === 'fisica';
            $relation = $individual ? 'individual' : 'corporate';
            $supplier->{$relation}()->updateOrCreate([], $data[$relation]);
            foreach (['emails', 'phones', 'contacts'] as $relation) {
                $supplier->{$relation}()->delete();
                $rows = $data[$relation];
                if ($relation !== 'contacts') {
                    $field = $relation === 'emails' ? 'email' : 'phone_number';
                    $rows = array_filter($rows, fn(array $row) => filled($row[$field] ?? null));
                }
                $supplier->{$relation}()->createMany($rows);
            }
        });
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fornecedor excluído.']);

        return to_route('suppliers.index');
    }
}
