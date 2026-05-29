<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Valida i filtri della lista ordini ed espone la mappatura ai parametri WooCommerce.
 * Accesso gestito dal middleware ip.whitelist.
 */
class ListaOrdiniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // accesso gestito da middleware ip.whitelist
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', Rule::in([
                'any', 'pending', 'processing', 'on-hold', 'completed',
                'cancelled', 'refunded', 'failed', 'trash',
            ])],
            'after' => ['nullable', 'date'],
            'before' => ['nullable', 'date'],
            'modified_after' => ['nullable', 'date'],
            'modified_before' => ['nullable', 'date'],
            'customer' => ['nullable', 'integer', 'min:0'],
            'search' => ['nullable', 'string', 'max:255'],
            'orderby' => ['nullable', Rule::in(['date', 'id', 'title', 'slug', 'modified', 'include'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'include' => ['nullable', 'string'], // CSV di id → include[]
            'exclude' => ['nullable', 'string'], // CSV di id → exclude[]
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Stato ordine non valido.',
            'per_page.max' => 'per_page non può superare 100 (limite WooCommerce).',
        ];
    }

    /**
     * Costruisce l'array di parametri da inviare a WooCommerce a partire dai dati validati:
     * date in ISO8601, include/exclude da CSV a array di interi, valori nulli rimossi.
     *
     * @return array<string,mixed>
     */
    public function filtriWooCommerce(): array
    {
        $v = $this->validated();

        foreach (['after', 'before', 'modified_after', 'modified_before'] as $campoData) {
            if (! empty($v[$campoData])) {
                $v[$campoData] = Carbon::parse($v[$campoData])->toIso8601String();
            }
        }

        foreach (['include', 'exclude'] as $csv) {
            if (! empty($v[$csv])) {
                $v[$csv] = array_values(array_filter(array_map(
                    'intval',
                    explode(',', $v[$csv])
                )));
            }
        }

        return array_filter($v, static fn ($valore): bool => $valore !== null && $valore !== '');
    }
}
