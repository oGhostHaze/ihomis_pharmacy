<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Models\Pharmacy\Drugs\StockReclassification;
use App\Models\References\ChargeCode;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ChargeCodeReclassification extends Component
{
    public $source_chrgcode = '';
    public $destination_chrgcode = '';
    public $confirmation = '';
    public $command_output = '';
    public $output_type = '';
    public $preview_source = '';
    public $preview_destination = '';
    public $preview_ready = false;

    public function mount()
    {
        $this->authorizeAccess();
    }

    public function updatedSourceChrgcode()
    {
        $this->invalidatePreview();
    }

    public function updatedDestinationChrgcode()
    {
        $this->invalidatePreview();
    }

    public function preview()
    {
        $this->authorizeAccess();
        $this->validateSelection();

        $exitCode = Artisan::call('pharmacy:reclassify-charge-code', [
            'source' => $this->source_chrgcode,
            'destination' => $this->destination_chrgcode,
            '--user-id' => (string) Auth::id(),
        ]);

        $this->command_output = trim(Artisan::output());
        $this->output_type = $exitCode === 0 ? 'success' : 'error';
        $this->preview_ready = $exitCode === 0;
        $this->preview_source = $exitCode === 0 ? $this->source_chrgcode : '';
        $this->preview_destination = $exitCode === 0 ? $this->destination_chrgcode : '';
        $this->confirmation = '';
    }

    public function commit()
    {
        $this->authorizeAccess();
        $this->validateSelection();

        if (
            !$this->preview_ready
            || $this->preview_source !== $this->source_chrgcode
            || $this->preview_destination !== $this->destination_chrgcode
        ) {
            $this->addError('confirmation', 'Run a successful preview for these charge codes before committing.');

            return;
        }

        if ($this->confirmation !== $this->confirmationPhrase()) {
            $this->addError('confirmation', 'The confirmation phrase does not match.');

            return;
        }

        set_time_limit(0);
        $exitCode = Artisan::call('pharmacy:reclassify-charge-code', [
            'source' => $this->source_chrgcode,
            'destination' => $this->destination_chrgcode,
            '--user-id' => (string) Auth::id(),
            '--commit' => true,
        ]);

        $this->command_output = trim(Artisan::output());
        $this->output_type = $exitCode === 0 ? 'success' : 'error';
        $this->preview_ready = false;
        $this->preview_source = '';
        $this->preview_destination = '';
        $this->confirmation = '';
    }

    public function render()
    {
        $this->authorizeAccess();

        $chargeCodes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->orderBy('chrgcode')
            ->get();

        $history = StockReclassification::select(
            'reference_no',
            'source_chrgcode',
            'destination_chrgcode',
            'user_id',
            DB::raw('COUNT(*) as batch_count'),
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('MAX(executed_at) as executed_at')
        )
            ->groupBy('reference_no', 'source_chrgcode', 'destination_chrgcode', 'user_id')
            ->orderByRaw('MAX(executed_at) DESC')
            ->limit(10)
            ->get();

        return view('livewire.pharmacy.drugs.charge-code-reclassification', [
            'chargeCodes' => $chargeCodes,
            'confirmationPhrase' => $this->confirmationPhrase(),
            'history' => $history,
        ]);
    }

    private function validateSelection()
    {
        $this->source_chrgcode = strtoupper(trim((string) $this->source_chrgcode));
        $this->destination_chrgcode = strtoupper(trim((string) $this->destination_chrgcode));

        $this->validate([
            'source_chrgcode' => ['required', 'string', 'max:30', 'different:destination_chrgcode'],
            'destination_chrgcode' => ['required', 'string', 'max:30', 'different:source_chrgcode'],
        ]);
    }

    private function confirmationPhrase()
    {
        return "RECLASSIFY {$this->source_chrgcode} TO {$this->destination_chrgcode}";
    }

    private function invalidatePreview()
    {
        $this->preview_ready = false;
        $this->preview_source = '';
        $this->preview_destination = '';
        $this->confirmation = '';
    }

    private function authorizeAccess()
    {
        abort_unless(Auth::check() && Auth::user()->can('adjust-stock-qty'), 403);
    }
}
