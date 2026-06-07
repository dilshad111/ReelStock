<?php

namespace App\Services;

use App\Models\JobCard;
use App\Models\JobCardChangeLog;
use App\Models\JobCardVersion;
use Illuminate\Support\Facades\Auth;

class JobCardVersionService
{
    public function ensureInitialVersion(JobCard $jobCard): JobCardVersion
    {
        $existing = $jobCard->versions()->where('version_no', 1)->first();
        if ($existing) {
            if (!$jobCard->active_version_id) {
                $jobCard->forceFill([
                    'current_version_no' => max((int) ($jobCard->current_version_no ?: 1), (int) $existing->version_no),
                    'active_version_id' => $existing->id,
                ])->save();
            }
            return $existing;
        }

        $version = JobCardVersion::create([
            'job_card_id' => $jobCard->id,
            'version_no' => 1,
            'change_request_no' => null,
            'change_reason' => 'Initial Version',
            'effective_date' => optional($jobCard->created_at)->toDateString() ?? now()->toDateString(),
            'approval_status' => 'Approved',
            'version_status' => 'Active',
            'snapshot_data' => $this->snapshot($jobCard),
            'created_by' => $jobCard->created_by ?: (Auth::id() ?: null),
        ]);

        $jobCard->forceFill([
            'current_version_no' => max((int) ($jobCard->current_version_no ?: 1), 1),
            'active_version_id' => $jobCard->active_version_id ?: $version->id,
        ])->save();

        return $version;
    }

    public function createNextVersion(JobCard $jobCard, array $beforeSnapshot, string $reason = null): JobCardVersion
    {
        $jobCard->load(['customer', 'product', 'items.item', 'pieces.layers', 'layers']);
        $versionNo = ((int) ($jobCard->versions()->max('version_no') ?: 0)) + 1;

        $jobCard->versions()->update(['version_status' => 'Superseded']);

        $version = JobCardVersion::create([
            'job_card_id' => $jobCard->id,
            'version_no' => $versionNo,
            'change_request_no' => $this->nextChangeRequestNo(),
            'change_reason' => $reason ?: 'Customer requested specification change',
            'effective_date' => now()->toDateString(),
            'approval_status' => 'Approved',
            'version_status' => 'Active',
            'snapshot_data' => $this->snapshot($jobCard),
            'created_by' => Auth::id() ?: null,
        ]);

        foreach ($this->diffSnapshots($beforeSnapshot, $version->snapshot_data) as $change) {
            JobCardChangeLog::create([
                'job_card_version_id' => $version->id,
                'field_name' => $change['field_name'],
                'old_value' => $change['old_value'],
                'new_value' => $change['new_value'],
                'modified_by' => Auth::id() ?: null,
                'modified_at' => now(),
            ]);
        }

        $jobCard->forceFill([
            'current_version_no' => $versionNo,
            'active_version_id' => $version->id,
        ])->save();

        return $version;
    }

    public function snapshot(JobCard $jobCard): array
    {
        $jobCard->loadMissing(['customer', 'product', 'items.item', 'pieces.layers', 'layers']);

        return [
            'job_card' => [
                'job_card_no' => $jobCard->job_card_no,
                'customer_id' => $jobCard->customer_id,
                'customer_name' => $jobCard->customer?->name,
                'fg_product_id' => $jobCard->fg_product_id,
                'item_code' => $jobCard->product?->item_code,
                'item_name' => $jobCard->product?->item_name,
                'status' => $jobCard->status,
                'specifications' => $jobCard->specifications,
                'notes' => $jobCard->notes,
                'length_mm' => $this->number($jobCard->length_mm),
                'width_mm' => $this->number($jobCard->width_mm),
                'height_mm' => $this->number($jobCard->height_mm),
                'uom' => $jobCard->uom,
                'deckle_size' => $this->number($jobCard->deckle_size),
                'sheet_length' => $this->number($jobCard->sheet_length),
                'ups' => (int) $jobCard->ups,
                'carton_type' => $jobCard->carton_type,
                'machine_name' => $jobCard->machine_name,
                'target_speed' => (int) $jobCard->target_speed,
                'printing_process' => $jobCard->printing_process,
                'pasting_closure' => $jobCard->pasting_closure,
                'printing_colors_count' => (int) $jobCard->printing_colors_count,
                'pantone_colors' => $jobCard->pantone_colors ?: [],
                'special_details' => $jobCard->special_details ?: [],
                'pieces_count' => (int) $jobCard->pieces_count,
                'est_unit_weight' => $this->number($jobCard->est_unit_weight),
            ],
            'layers' => $jobCard->layers->map(fn ($layer) => [
                'layer_type' => $layer->layer_type,
                'paper_name' => $layer->paper_name,
                'gsm' => (int) $layer->gsm,
                'flute_profile' => $layer->flute_profile,
                'sequence' => (int) $layer->sequence,
            ])->values()->all(),
            'pieces' => $jobCard->pieces->map(fn ($piece) => [
                'piece_name' => $piece->piece_name,
                'length_mm' => $this->number($piece->length_mm),
                'width_mm' => $this->number($piece->width_mm),
                'height_mm' => $this->number($piece->height_mm),
                'deckle_size' => $this->number($piece->deckle_size),
                'sheet_length' => $this->number($piece->sheet_length),
                'ups' => (int) $piece->ups,
                'machine_name' => $piece->machine_name,
                'target_speed' => (int) $piece->target_speed,
                'est_unit_weight' => $this->number($piece->est_unit_weight),
                'instructions' => $piece->instructions,
                'layers' => $piece->layers->map(fn ($layer) => [
                    'layer_type' => $layer->layer_type,
                    'paper_name' => $layer->paper_name,
                    'gsm' => (int) $layer->gsm,
                    'flute_profile' => $layer->flute_profile,
                    'sequence' => (int) $layer->sequence,
                ])->values()->all(),
            ])->values()->all(),
            'items' => $jobCard->items->map(fn ($item) => [
                'rm_item_id' => $item->rm_item_id,
                'item_name' => $item->item?->rm_name ?? $item->item?->name,
                'required_qty' => $this->number($item->required_qty),
                'unit' => $item->unit,
            ])->values()->all(),
        ];
    }

    public function diffSnapshots(array $before, array $after): array
    {
        $labels = [
            'job_card.length_mm' => 'Length',
            'job_card.width_mm' => 'Width',
            'job_card.height_mm' => 'Height',
            'job_card.deckle_size' => 'Deckle Size',
            'job_card.sheet_length' => 'Sheet Length',
            'job_card.ups' => 'Units Per Sheet',
            'job_card.carton_type' => 'Carton Type',
            'job_card.printing_colors_count' => 'Printing Colors',
            'job_card.pantone_colors' => 'Ink / Pantone Colors',
            'job_card.pasting_closure' => 'Joinery Technique',
            'job_card.printing_process' => 'Printing Process',
            'job_card.pieces_count' => 'Pieces Count',
            'job_card.est_unit_weight' => 'Estimated Unit Weight',
            'job_card.special_details' => 'Special / Process Details',
            'layers' => 'Paper Layer Construction',
            'pieces' => 'Multi-Piece Configuration',
            'items' => 'Raw Material Requirements',
        ];

        $changes = [];
        foreach ($labels as $path => $label) {
            $old = $this->getPath($before, $path);
            $new = $this->getPath($after, $path);
            if ($this->normalize($old) !== $this->normalize($new)) {
                $changes[] = [
                    'field_name' => $label,
                    'old_value' => $this->stringify($old),
                    'new_value' => $this->stringify($new),
                ];
            }
        }

        return $changes;
    }

    private function nextChangeRequestNo(): string
    {
        $last = JobCardVersion::whereNotNull('change_request_no')->orderByDesc('id')->value('change_request_no');
        $number = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;
        return 'CR-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    private function getPath(array $data, string $path): mixed
    {
        $value = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    private function normalize(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function stringify(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if ($value === null || $value === '') {
            return '-';
        }
        return (string) $value;
    }

    private function number(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
