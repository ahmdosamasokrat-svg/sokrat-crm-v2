<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Quotation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        $query = Quotation::query()
            ->accessibleTo($request->user());

        if ($term !== '') {
            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('quotation_no', 'like', '%'.$term.'%')
                    ->orWhere('client_name', 'like', '%'.$term.'%')
                    ->orWhere('prepared_by', 'like', '%'.$term.'%')
                    ->orWhere('system_title', 'like', '%'.$term.'%');
            });
        }

        $quotations = $query
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('quotations.index', compact('quotations', 'term'));
    }

    public function create(Request $request): View
    {
        $prefill = [
            'clientName' => '',
            'quotationNo' => '',
            'quoteDate' => now()->format('Y-m-d'),
        ];

        $leadId = $request->query('lead_id') ?? $request->query('lead');
        if ($leadId) {
            $lead = Lead::query()
                ->accessibleTo($request->user())
                ->find($leadId);
            if ($lead) {
                $prefill['clientName'] = $lead->company_name ?: ($lead->name ?: '');
            }
        }

        if ($request->query('client_name')) {
            $prefill['clientName'] = trim((string) $request->query('client_name'));
        }

        return view('quotations.builder', [
            'prefill' => $prefill,
            'isLegacy' => false,
        ]);
    }

    public function show(Quotation $quotation, Request $request): View
    {
        abort_unless($quotation->isAccessibleTo($request->user()), 404);

        $payload = $quotation->payload;
        $isLegacy = ! is_array($payload)
            || empty($payload['generator'])
            || $payload['generator'] !== 'mpc'
            || (($payload['schema_version'] ?? null) !== 4);

        return view('quotations.builder', [
            'quotation' => $quotation,
            'isLegacy' => $isLegacy,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $calculated = $this->recalculateAndBuildV4Payload($validated);

        $quotation = Quotation::create([
            'quotation_no' => $validated['quotationNo'],
            'client_name' => $validated['clientName'],
            'quote_date' => $validated['quoteDate'],
            'system_title' => $validated['offerTitle'] ?? null,
            'prepared_by' => $request->user()->name,
            'grand_total' => $calculated['grandTotal'],
            'payload' => $calculated['payload'],
            'created_by' => $request->user()->name,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'ok' => true,
            'id' => $quotation->id,
            'message' => __('crm.quotation_saved_success'),
            'redirect' => route('v2.quotations.show', $quotation),
        ], 201);
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        abort_unless($quotation->isAccessibleTo($request->user()), 404);

        $validated = $this->validatePayload($request);
        $calculated = $this->recalculateAndBuildV4Payload($validated);

        $quotation->update([
            'quotation_no' => $validated['quotationNo'],
            'client_name' => $validated['clientName'],
            'quote_date' => $validated['quoteDate'],
            'system_title' => $validated['offerTitle'] ?? null,
            'grand_total' => $calculated['grandTotal'],
            'payload' => $calculated['payload'],
        ]);

        return response()->json([
            'ok' => true,
            'id' => $quotation->id,
            'message' => __('crm.quotation_updated_success'),
            'redirect' => route('v2.quotations.show', $quotation),
        ]);
    }

    /**
     * Validate incoming MPC v4 quotation payload.
     *
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'offerType' => ['required', 'string', 'in:other,iso,inspection'],
            'clientName' => ['required', 'string', 'max:255'],
            'quotationNo' => ['required', 'string', 'max:100'],
            'quoteDate' => ['required', 'date'],
            'vatRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'offerTitle' => ['nullable', 'string', 'max:500'],
            'introText' => ['nullable', 'string', 'max:5000'],
            'leadText' => ['nullable', 'string', 'max:500'],
            'rows' => ['required', 'array', 'min:1', 'max:100'],
            'rows.*.item' => ['nullable', 'string', 'max:500'],
            'rows.*.values' => ['nullable', 'array'],
            'rows.*.remarks' => ['nullable', 'string', 'max:500'],
            'haccpValues' => ['nullable', 'array'],
            'showVatRow' => ['nullable', 'boolean'],
            'formatNumbers' => ['nullable', 'boolean'],
            'paymentTimes' => ['nullable', 'string', 'max:50'],
            'paymentYear' => ['nullable', 'string', 'max:50'],
            'autoPaymentAmounts' => ['nullable', 'boolean'],
            'payments' => ['nullable', 'array', 'max:30'],
            'payments.*.percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payments.*.amount' => ['nullable'],
            'payments.*.due' => ['nullable', 'string', 'max:255'],
            'transportNote' => ['nullable', 'string', 'max:2000'],
            'closingText' => ['nullable', 'string', 'max:500'],
            'showTransportNote' => ['nullable', 'boolean'],
            'showClosing' => ['nullable', 'boolean'],
            'typography' => ['nullable', 'array'],
            'typography.quoteFontFamily' => ['nullable', 'string', 'max:50'],
            'typography.quoteFontSize' => ['nullable', 'integer', 'min:50', 'max:200'],
            'typography.enableAccentColor' => ['nullable', 'boolean'],
            'typography.quoteAccentColor' => ['nullable', 'string', 'max:20'],
            'typography.enableTextColor' => ['nullable', 'boolean'],
            'typography.quoteTextColor' => ['nullable', 'string', 'max:20'],
        ]);
    }

    /**
     * Authoritative server-side financial recalculation for MPC v4.
     * Computes column subtotals, VAT, column totals, HACCP totals, grand total, and payment schedule.
     *
     * @param  array<string, mixed>  $data
     * @return array{grandTotal: float, payload: array<string, mixed>}
     */
    private function recalculateAndBuildV4Payload(array $data): array
    {
        $offerType = $data['offerType'];
        $vatRate = max(0.0, (float) ($data['vatRate'] ?? 0.0));
        $showVatRow = (bool) ($data['showVatRow'] ?? true);
        $formatNumbers = (bool) ($data['formatNumbers'] ?? true);
        $autoPaymentAmounts = (bool) ($data['autoPaymentAmounts'] ?? false);

        // Column counts by offer type: other=3, iso=4, inspection=2
        $colCount = match ($offerType) {
            'other' => 3,
            'iso' => 4,
            'inspection' => 2,
            default => 3,
        };

        $rawRows = $data['rows'] ?? [];
        $sanitizedRows = [];
        $subtotals = array_fill(0, $colCount, 0.0);
        $hasValue = array_fill(0, $colCount, false);

        foreach ($rawRows as $row) {
            $item = trim((string) ($row['item'] ?? ''));
            $remarks = trim((string) ($row['remarks'] ?? ''));
            $values = [];
            $rawValues = (array) ($row['values'] ?? []);

            for ($i = 0; $i < $colCount; $i++) {
                $rawVal = $rawValues[$i] ?? '';
                $num = $this->numericValue($rawVal);
                if ($num !== null) {
                    $subtotals[$i] += $num;
                    $hasValue[$i] = true;
                    $values[$i] = (string) $rawVal;
                } else {
                    $values[$i] = (string) $rawVal;
                }
            }

            $sanitizedRows[] = [
                'item' => $item,
                'values' => $values,
                'remarks' => $remarks,
            ];
        }

        // Primary VAT & totals
        $primaryVat = [];
        $primaryTotals = [];
        for ($i = 0; $i < $colCount; $i++) {
            $primaryVat[$i] = ($hasValue[$i] && $showVatRow)
                ? ($subtotals[$i] * $vatRate / 100.0)
                : 0.0;
            $primaryTotals[$i] = $hasValue[$i]
                ? ($subtotals[$i] + $primaryVat[$i])
                : 0.0;
        }

        $primaryGrandTotal = array_sum($primaryTotals);

        // HACCP calculation (only for inspection offers)
        $haccpValues = array_pad(array_slice((array) ($data['haccpValues'] ?? []), 0, 3), 3, '');
        $haccpSubtotals = [0.0, 0.0, 0.0];
        $haccpHas = [false, false, false];
        $haccpVat = [0.0, 0.0, 0.0];
        $haccpTotals = [0.0, 0.0, 0.0];

        if ($offerType === 'inspection') {
            for ($i = 0; $i < 3; $i++) {
                $num = $this->numericValue($haccpValues[$i]);
                if ($num !== null) {
                    $haccpSubtotals[$i] = $num;
                    $haccpHas[$i] = true;
                    if ($showVatRow) {
                        $haccpVat[$i] = $num * $vatRate / 100.0;
                    }
                    $haccpTotals[$i] = $num + $haccpVat[$i];
                }
            }
        }

        $haccpGrandTotal = $offerType === 'inspection' ? array_sum($haccpTotals) : 0.0;
        $authoritativeGrandTotal = round($primaryGrandTotal + $haccpGrandTotal, 2);

        // Payments recalculation
        $rawPayments = (array) ($data['payments'] ?? []);
        $sanitizedPayments = [];
        foreach ($rawPayments as $payment) {
            $percent = max(0.0, (float) ($payment['percent'] ?? 0.0));
            $due = trim((string) ($payment['due'] ?? ''));
            if ($autoPaymentAmounts) {
                $amount = $authoritativeGrandTotal > 0 ? (string) round($authoritativeGrandTotal * $percent / 100.0, 2) : '';
            } else {
                $amount = trim((string) ($payment['amount'] ?? ''));
            }
            $sanitizedPayments[] = [
                'percent' => $percent,
                'amount' => $amount,
                'due' => $due,
            ];
        }

        // Typography
        $typography = (array) ($data['typography'] ?? []);
        $cleanTypography = [
            'quoteFontFamily' => (string) ($typography['quoteFontFamily'] ?? 'times'),
            'quoteFontSize' => max(85, min(115, (int) ($typography['quoteFontSize'] ?? 100))),
            'enableAccentColor' => (bool) ($typography['enableAccentColor'] ?? false),
            'quoteAccentColor' => (string) ($typography['quoteAccentColor'] ?? '#4472c4'),
            'enableTextColor' => (bool) ($typography['enableTextColor'] ?? false),
            'quoteTextColor' => (string) ($typography['quoteTextColor'] ?? '#000000'),
        ];

        $payload = [
            'schema_version' => 4,
            'generator' => 'mpc',
            'generator_version' => '4.0',
            'offerType' => $offerType,
            'clientName' => $data['clientName'],
            'quotationNo' => $data['quotationNo'],
            'quoteDate' => $data['quoteDate'],
            'vatRate' => $vatRate,
            'offerTitle' => (string) ($data['offerTitle'] ?? ''),
            'introText' => (string) ($data['introText'] ?? ''),
            'leadText' => (string) ($data['leadText'] ?? ''),
            'rows' => $sanitizedRows,
            'haccpValues' => $haccpValues,
            'showVatRow' => $showVatRow,
            'formatNumbers' => $formatNumbers,
            'paymentTimes' => (string) ($data['paymentTimes'] ?? '02'),
            'paymentYear' => (string) ($data['paymentYear'] ?? ''),
            'autoPaymentAmounts' => $autoPaymentAmounts,
            'payments' => $sanitizedPayments,
            'transportNote' => (string) ($data['transportNote'] ?? ''),
            'closingText' => (string) ($data['closingText'] ?? ''),
            'showTransportNote' => (bool) ($data['showTransportNote'] ?? true),
            'showClosing' => (bool) ($data['showClosing'] ?? true),
            'typography' => $cleanTypography,
            'financials' => [
                'primarySubtotals' => array_map(fn ($v) => round($v, 2), $subtotals),
                'primaryVat' => array_map(fn ($v) => round($v, 2), $primaryVat),
                'primaryTotals' => array_map(fn ($v) => round($v, 2), $primaryTotals),
                'haccpSubtotals' => array_map(fn ($v) => round($v, 2), $haccpSubtotals),
                'haccpVat' => array_map(fn ($v) => round($v, 2), $haccpVat),
                'haccpTotals' => array_map(fn ($v) => round($v, 2), $haccpTotals),
                'grandTotal' => $authoritativeGrandTotal,
            ],
        ];

        return [
            'grandTotal' => $authoritativeGrandTotal,
            'payload' => $payload,
        ];
    }

    /**
     * Parse numeric float value matching MPC v4 frontend rules.
     */
    private function numericValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : null;
        }

        $cleaned = trim((string) ($value ?? ''));
        $cleaned = str_replace([',', ' '], '', $cleaned);
        if ($cleaned === '') {
            return null;
        }

        if (! preg_match('/^[-+]?\d*\.?\d+$/', $cleaned)) {
            return null;
        }

        $n = (float) $cleaned;

        return is_finite($n) ? $n : null;
    }
}
