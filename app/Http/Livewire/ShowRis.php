<?php

namespace App\Http\Livewire;

use App\Helpers\DateHelper;
use App\Models\Pharmacy\DeliveryDetail;
use App\Models\Pharmacy\DeliveryItems;
use App\Models\Pharmacy\Drug;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use App\Models\References\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;

class ShowRis extends Component
{
    // Safe to make public - simple types
    public $risId;
    public $drugSearchTerm = '';
    public $selectedItemId = null;
    public $isModalOpen = false;
    public $searchResults = [];
    public $loading = true;

    // Serializable public properties for essential data
    public $risNo = null;
    public $risDate = null;
    public $officeName = null;
    public $rcc = null;
    public $purpose = null;

    // For tracking state
    public $dataLoaded = false;

    // Add this for tracking association status
    public $associationStatus = [
        'total' => 0,
        'associated' => 0,
        'percentage' => 0,
        'allAssociated' => false
    ];

    // Protected properties for complex objects
    protected $ris = null;
    protected $risDetails = null;
    protected $relatedIar = null;
    // New properties for the transfer modal
    public $isTransferModalOpen = false;
    public $deliveryData = [
        'suppcode' => '',
        'delivery_type' => 'RIS',
        'charge_code' => '',
        'pharm_location_id' => '',
        'delivery_date' => '',
        'si_no' => '',
        'po_no' => '' // Will be set to RIS number
    ];

    // Add validation rules
    protected function rules()
    {
        return [
            'deliveryData.suppcode' => 'required',
            'deliveryData.delivery_type' => 'required',
            'deliveryData.charge_code' => 'required',
            'deliveryData.pharm_location_id' => 'required',
            'deliveryData.delivery_date' => 'required|date',
        ];
    }

    // Add listeners
    protected $listeners = [
        'refreshRisDetails' => 'loadRis',
        'transferComplete' => 'handleTransferComplete'
    ];

    public function mount($id)
    {
        $this->risId = $id;
        $this->loadRis();
    }

    public function hydrate()
    {
        // If we have essential data but the full objects are null, reload them
        if ($this->dataLoaded && ($this->ris === null || $this->risDetails === null)) {
            $this->loadRisData();
        }
    }

    public function loadRis()
    {
        $this->loading = true;
        $this->dataLoaded = false;

        try {
            $this->loadRisData();
            $this->dataLoaded = true;

            // Ensure the calculateAssociationStatus gets called
            $this->calculateAssociationStatus();
        } catch (\Exception $e) {
            session()->flash('error', 'Error loading RIS data: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    protected function loadRisData()
    {
        // Get RIS details
        $this->ris = DB::connection('pims')
            ->table('tbl_ris')
            ->from(DB::raw('tbl_ris'))
            ->select([
                'tbl_ris.risid',
                'tbl_ris.risno',
                'tbl_ris.purpose',
                DB::raw("DATE_FORMAT(tbl_ris.risdate, '%b-%d-%Y') AS formatted_risdate"),
                'tbl_ris.officeID',
                DB::raw("DATE_FORMAT(tbl_ris.requestdate, '%b-%d-%Y') AS formatted_requestdate"),
                'tbl_ris.apprvdby',
                'tbl_ris.apprvdby_desig',
                DB::raw("DATE_FORMAT(tbl_ris.apprvddate, '%b-%d-%Y') AS formatted_approveddate"),
                DB::raw("DATE_FORMAT(tbl_ris.issueddate, '%b-%d-%Y') AS formatted_issueddate"),
                'tbl_ris.receivedby',
                'tbl_ris.receivedby_desig',
                DB::raw("DATE_FORMAT(tbl_ris.receiveddate, '%b-%d-%Y') AS formatted_receiveddate"),
                'tbl_ris.apprvstat',
                'tbl_ris.issuedstat',
                'tbl_ris.status',
                'tbl_ris.ris_in_iar',
                'tbl_ris.iarid',
                'tbl_ris.transferred_to_pdims',
                'tbl_ris.transferred_at',
                'tbl_office.officeName',
                'tbl_office.rcc',
                'req.fullName AS requested_by_name',
                'req.designation AS requested_by_desig',
                'issue.fullName AS issued_by_name',
                'issue.designation AS issued_by_desig'
            ])
            ->join(DB::raw('tbl_ris_details'), 'tbl_ris.risid', '=', 'tbl_ris_details.risid')
            ->join(DB::raw('tbl_items'), 'tbl_items.itemid', '=', 'tbl_ris_details.itemid')
            ->leftJoin(DB::raw('tbl_user AS req'), 'req.userID', '=', 'tbl_ris.requestby')
            ->leftJoin(DB::raw('tbl_user AS issue'), 'issue.userID', '=', 'tbl_ris.issuedby')
            ->join(DB::raw('tbl_office'), 'tbl_office.officeID', '=', 'tbl_ris.officeID')
            ->where('tbl_ris.risid', $this->risId)
            ->where('tbl_items.catid', 9)
            ->first();

        if (!$this->ris) {
            session()->flash('error', 'RIS not found');
            return redirect()->route('ris.index');
        }

        // Save essential data to public properties for state preservation
        $this->risNo = $this->ris->risno;
        $this->risDate = $this->ris->formatted_risdate;
        $this->officeName = $this->ris->officeName;
        $this->rcc = $this->ris->rcc;
        $this->purpose = $this->ris->purpose;

        // Get RIS items with drug association information and batch/expiry details
        $this->risDetails = DB::connection('pims')
            ->table('tbl_ris_details')
            ->from(DB::raw('tbl_ris_details'))
            ->select([
                'tbl_ris_details.risdetid',
                'tbl_ris_details.stockno',
                'tbl_ris_details.onhand',
                'tbl_ris_details.itmqty',
                'tbl_items.itemID',
                'tbl_items.description',
                'tbl_items.unit',
                'tbl_items.pdims_itemcode',
                'tbl_items.pdims_drugdesc'
            ])
            ->join(DB::raw('tbl_items'), 'tbl_items.itemID', '=', 'tbl_ris_details.itemID')
            ->where('tbl_ris_details.risid', $this->risId)
            ->where('tbl_ris_details.status', 'A')
            ->get();

        // Get fund source information for each item if available
        foreach ($this->risDetails as $detail) {
            $detail->fundSources = DB::connection('pims')
                ->table('tbl_ris_release')
                ->from(DB::raw('tbl_ris_release'))
                ->select([
                    'tbl_ris_release.slcID',
                    'tbl_ris_release.releaseqty',
                    'tbl_ris_release.fsid',
                    'tbl_ris_release.unitprice',
                    'tbl_ris_release.risreleaseid', // Added risreleaseid field
                    'tbl_fund_source.fsname'
                ])
                ->leftJoin(DB::raw('tbl_fund_source'), 'tbl_fund_source.fsid', '=', 'tbl_ris_release.fsid')
                ->where('tbl_ris_release.risdetid', $detail->risdetid)
                ->where('tbl_ris_release.status', 'A')
                ->get();

            // Get batch and expiry information from tbl_supply_slc and tbl_iar_details
            if (count($detail->fundSources) > 0) {
                $releaseIds = $detail->fundSources->pluck('risreleaseid')->toArray();

                $batchAndExpiryInfo = DB::connection('pims')
                    ->table('tbl_supply_slc')
                    ->from(DB::raw('tbl_supply_slc'))
                    ->select([
                        'tbl_supply_slc.lotno',
                        'tbl_supply_slc.expiredate',
                        'tbl_iar_details.batch_no',
                        'tbl_iar_details.expire_date'
                    ])
                    ->leftJoin(DB::raw('tbl_iar_details'), 'tbl_iar_details.iardetailsid', '=', 'tbl_supply_slc.iardetid')
                    ->whereIn('tbl_supply_slc.risreleaseid', $releaseIds)
                    ->where('tbl_supply_slc.status', 'A')
                    ->first();

                if ($batchAndExpiryInfo) {
                    $detail->batch_no = $batchAndExpiryInfo->batch_no ?? $batchAndExpiryInfo->lotno ?? null;

                    // Parse the expiry date using our new function
                    $rawExpiryDate = $batchAndExpiryInfo->expire_date ?? $batchAndExpiryInfo->expiredate ?? null;
                    $parsedExpiry = DateHelper::parseExpiryDate($rawExpiryDate);

                    $detail->expire_date = $parsedExpiry['raw'];
                    $detail->formatted_expire_date = $parsedExpiry['formatted'];
                    $detail->sql_formatted_expire_date = $parsedExpiry['sql_format'];
                } else {
                    $detail->batch_no = null;
                    $detail->expire_date = null;
                    $detail->formatted_expire_date = 'N/A';
                    $detail->sql_formatted_expire_date = null;
                }
            } else {
                $detail->batch_no = null;
                $detail->expire_date = null;
                $detail->formatted_expire_date = 'N/A';
                $detail->sql_formatted_expire_date = null;
            }
        }

        // Get related IAR if exists
        if ($this->ris && $this->ris->iarid) {
            $this->relatedIar = DB::connection('pims')
                ->table('tbl_iar')
                ->from(DB::raw('tbl_iar'))
                ->select([
                    'tbl_iar.iarID',
                    'tbl_iar.iarNo',
                    'tbl_iar.invoiceNo',
                    DB::raw("DATE_FORMAT(tbl_iar.iardate, '%b-%d-%Y') AS formatted_iardate"),
                    'tbl_iar.supplier'
                ])
                ->where('tbl_iar.iarID', $this->ris->iarid)
                ->first();
        }

        // Calculate association statistics
        $this->calculateAssociationStatus();
    }

    /**
     * Calculate the drug association status for this RIS
     */
    protected function calculateAssociationStatus()
    {
        if (!$this->risDetails) {
            $this->associationStatus = [
                'total' => 0,
                'associated' => 0,
                'percentage' => 0,
                'allAssociated' => false
            ];
            return;
        }

        $total = count($this->risDetails);
        $associated = 0;

        foreach ($this->risDetails as $detail) {
            if (!empty($detail->pdims_itemcode)) {
                $associated++;
            }
        }

        $percentage = $total > 0 ? round(($associated / $total) * 100) : 0;

        $this->associationStatus = [
            'total' => $total,
            'associated' => $associated,
            'percentage' => $percentage,
            'allAssociated' => ($total > 0 && $associated === $total)
        ];
    }

    /**
     * Get all unassociated items
     */
    public function getUnassociatedItems()
    {
        if (!$this->risDetails) {
            return [];
        }

        return collect($this->risDetails)
            ->filter(function ($detail) {
                return empty($detail->pdims_itemcode);
            })
            ->values()
            ->all();
    }

    /**
     * Open modal to associate all remaining items
     */
    public function batchAssociateItems()
    {
        $unassociated = $this->getUnassociatedItems();

        if (count($unassociated) === 0) {
            session()->flash('message', 'All items are already associated with drugs.');
            return;
        }

        // Select the first unassociated item to start with
        $this->openDrugModal($unassociated[0]->itemID);
    }

    public function openDrugModal($itemId)
    {
        $this->selectedItemId = $itemId;

        // Find the selected item's description from risDetails
        $selectedItem = collect($this->risDetails)->firstWhere('itemID', $itemId);

        if ($selectedItem && isset($selectedItem->description)) {
            // Get the first word from the description
            $firstWord = explode(' ', trim($selectedItem->description))[0];

            // Remove any non-alphanumeric characters except spaces
            $cleanWord = preg_replace('/[^A-Za-z0-9 ]/', '', $firstWord);

            // Use it as the initial search term
            $this->drugSearchTerm = $cleanWord;

            // Pre-populate search results
            $this->searchDrugs();
        } else {
            $this->drugSearchTerm = '';
            $this->searchResults = [];
        }

        $this->isModalOpen = true;
    }

    public function searchDrugs()
    {
        if (strlen($this->drugSearchTerm) >= 2) { // Reduced minimum to 2 characters
            try {
                // Search for drugs in the SQL Server database
                $drugs = Drug::query()
                    ->where(function ($query) {
                        $query->whereRaw("brandname LIKE ?", ['%' . $this->drugSearchTerm . '%'])
                            ->orWhereRaw("drug_concat LIKE ?", ['%' . $this->drugSearchTerm . '%']);

                        // If the search term contains a space, also try searching for parts
                        if (strpos($this->drugSearchTerm, ' ') !== false) {
                            $terms = explode(' ', $this->drugSearchTerm);
                            foreach ($terms as $term) {
                                if (strlen($term) >= 3) {
                                    $query->orWhereRaw("brandname LIKE ?", ['%' . $term . '%']);
                                }
                            }
                        }
                    })
                    ->limit(15) // Increased from 10 to 15
                    ->get(['dmdcomb', 'dmdctr', 'brandname', 'drug_concat']);

                // Format and highlight results
                $searchTermLower = strtolower($this->drugSearchTerm);
                $this->searchResults = $drugs->map(function ($drug) use ($searchTermLower) {
                    $drugName = $drug->drug_concat();

                    // Add highlighted version for display
                    $highlightedName = $this->highlightMatch($drugName, $searchTermLower);

                    return [
                        'id' => $drug->dmdcomb . '.' . $drug->dmdctr,
                        'name' => $drugName,
                        'highlighted_name' => $highlightedName,
                        'dmdcomb' => $drug->dmdcomb,
                        'dmdctr' => $drug->dmdctr
                    ];
                });

                $this->emit('drugsSearched');
            } catch (\Exception $e) {
                session()->flash('error', 'Error searching drugs: ' . $e->getMessage());
                $this->searchResults = [];
            }
        } else {
            $this->searchResults = [];
        }
    }

    /**
     * Highlight the matching part of a text
     */
    protected function highlightMatch($text, $searchTerm)
    {
        if (empty($searchTerm)) {
            return $text;
        }

        $textLower = strtolower($text);

        // If search term has spaces, check for each part
        if (strpos($searchTerm, ' ') !== false) {
            $terms = explode(' ', $searchTerm);

            foreach ($terms as $term) {
                if (strlen($term) < 3) continue;

                $pos = strpos($textLower, strtolower($term));
                if ($pos !== false) {
                    $replacement = '<span class="font-medium bg-yellow-100">' . substr($text, $pos, strlen($term)) . '</span>';
                    $text = substr_replace($text, $replacement, $pos, strlen($term));

                    // Adjust textLower to account for the added HTML tags
                    $textLower = strtolower(strip_tags($text));
                }
            }

            return $text;
        }

        // Single term search
        $pos = strpos($textLower, $searchTerm);
        if ($pos !== false) {
            $replacement = '<span class="font-medium bg-yellow-100">' . substr($text, $pos, strlen($searchTerm)) . '</span>';
            $text = substr_replace($text, $replacement, $pos, strlen($searchTerm));
        }

        return $text;
    }

    public function associateDrug($drugData)
    {
        try {
            // Get the drug ID
            $pdims_itemcode = $drugData;
            $pdims_drugdesc = $drugData; // Temporary placeholder

            // Try to get drug details if needed
            if (strpos($drugData, '.') !== false) {
                list($dmdcomb, $dmdctr) = explode('.', $drugData);

                // Optionally fetch drug name if needed
                $drug = Drug::where('dmdcomb', $dmdcomb)
                    ->where('dmdctr', $dmdctr)
                    ->first();

                if ($drug) {
                    $pdims_drugdesc = $drug->drug_concat();
                }
            }

            // Update the database
            DB::connection('pims')
                ->table('tbl_items')
                ->where('itemID', $this->selectedItemId)
                ->update([
                    'pdims_itemcode' => $pdims_itemcode,
                    'pdims_drugdesc' => $pdims_drugdesc
                ]);

            // Update the local data state directly
            // This is critical for UI updates without a full reload
            foreach ($this->risDetails as $key => $detail) {
                if ($detail->itemID == $this->selectedItemId) {
                    $this->risDetails[$key]->pdims_itemcode = $pdims_itemcode;
                    $this->risDetails[$key]->pdims_drugdesc = $pdims_drugdesc;
                    break;
                }
            }

            // Recalculate association status now that we've updated the detail
            $this->calculateAssociationStatus();

            // Close the modal
            $this->isModalOpen = false;
            $this->drugSearchTerm = '';
            $this->searchResults = [];

            // Show success message
            session()->flash('message', 'Drug successfully associated with item.');

            // CRITICAL FIX: Don't call loadRis() here - that's what's causing the issue
            // The local state is already updated above, and loadRis() is breaking it
        } catch (\Exception $e) {
            session()->flash('error', 'Error associating drug: ' . $e->getMessage());
        }
    }

    public function removeDrugAssociation($itemId)
    {
        try {
            // Remove the drug association
            DB::connection('pims')
                ->table('tbl_items')
                ->where('itemID', $itemId)
                ->update([
                    'pdims_itemcode' => null,
                    'pdims_drugdesc' => null
                ]);

            $this->loadRis();

            session()->flash('message', 'Drug association removed.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error removing drug association: ' . $e->getMessage());
        }
    }

    /**
     * Open the transfer modal
     */
    public function openTransferModal()
    {

        // Check if RIS is already transferred
        if ($this->ris && $this->ris->transferred_to_pdims) {
            session()->flash('error', 'This RIS has already been transferred to delivery system.');
            return;
        }

        // Check if all items are associated with drugs
        if (!$this->associationStatus['allAssociated']) {
            session()->flash('error', 'All items must be linked to drugs before transfer.');
            return;
        }


        // Set default values
        $this->deliveryData['po_no'] = $this->risNo; // Use RIS number as PO number
        $this->deliveryData['delivery_date'] = date('Y-m-d'); // Set today's date as default

        $this->isTransferModalOpen = true;
    }

    /**
     * Transfer RIS items to the pharmacy delivery system
     */
    public function transferToDelivery()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $invoiceNo = null;
            if ($this->ris && $this->ris->iarid) {
                $iar = DB::connection('pims')
                    ->table('tbl_iar')
                    ->from(DB::raw('tbl_iar'))
                    ->select('invoiceNo')
                    ->where('iarID', $this->ris->iarid)
                    ->first();

                $invoiceNo = $iar->invoiceNo ?? null;
            }

            // Create new delivery detail
            $delivery = new DeliveryDetail();
            $delivery->po_no = $this->deliveryData['po_no'] ?? $this->risNo;
            $delivery->si_no = $invoiceNo ?? $this->deliveryData['si_no']; // Use IAR invoice number if available
            $delivery->pharm_location_id = $this->deliveryData['pharm_location_id'];
            $delivery->user_id = Auth::id() ?? session('user_id');
            $delivery->delivery_date = $this->deliveryData['delivery_date'];
            $delivery->suppcode = $this->deliveryData['suppcode'];
            $delivery->delivery_type = $this->deliveryData['delivery_type'];
            $delivery->charge_code = $this->deliveryData['charge_code'];
            $delivery->save();

            // Transfer each RIS item to delivery items
            foreach ($this->risDetails as $detail) {
                // Skip items without drug association
                if (empty($detail->pdims_itemcode)) {
                    continue;
                }

                // Parse the drug code (format: dmdcomb.dmdctr)
                list($dmdcomb, $dmdctr) = explode('.', $detail->pdims_itemcode);

                // Get total amount from fund sources if available
                $unit_cost = 0;
                $total_amount = 0;

                if (isset($detail->fundSources) && count($detail->fundSources) > 0) {
                    // Use the first fund source for simplicity
                    $unit_cost = $detail->fundSources[0]->unitprice ?? 0;
                    $total_amount = $detail->itmqty * $unit_cost;
                }

                // Calculate retail price with markup (same logic as add_item)
                $excess = 0;
                if ($unit_cost >= 10000.01) {
                    $excess = $unit_cost - 10000;
                    $markup_price = 1115 + ($excess * 0.05);
                    $retail_price = $unit_cost + $markup_price;
                } elseif ($unit_cost >= 1000.01 && $unit_cost <= 10000.00) {
                    $excess = $unit_cost - 1000;
                    $markup_price = 215 + ($excess * 0.10);
                    $retail_price = $unit_cost + $markup_price;
                } elseif ($unit_cost >= 100.01 && $unit_cost <= 1000.00) {
                    $excess = $unit_cost - 100;
                    $markup_price = 35 + ($excess * 0.20);
                    $retail_price = $unit_cost + $markup_price;
                } elseif ($unit_cost >= 50.01 && $unit_cost <= 100.00) {
                    $excess = $unit_cost - 50;
                    $markup_price = 20 + ($excess * 0.30);
                    $retail_price = $unit_cost + $markup_price;
                } elseif ($unit_cost >= 0.01 && $unit_cost <= 50.00) {
                    $markup_price = $unit_cost * 0.40;
                    $retail_price = $unit_cost + $markup_price;
                } else {
                    $retail_price = 0;
                    $markup_price = 0;
                }

                // Create new delivery item
                $deliveryItem = new DeliveryItems();
                $deliveryItem->delivery_id = $delivery->id;
                $deliveryItem->dmdcomb = $dmdcomb;
                $deliveryItem->dmdctr = $dmdctr;
                $deliveryItem->qty = $detail->itmqty;
                $deliveryItem->unit_price = $unit_cost;
                $deliveryItem->total_amount = $total_amount;
                $deliveryItem->retail_price = $retail_price;
                $deliveryItem->lot_no = $detail->batch_no ?? ''; // Use batch_no for lot_no

                // Use the SQL-formatted expiry date
                if (isset($detail->sql_formatted_expire_date) && !empty($detail->sql_formatted_expire_date)) {
                    $deliveryItem->expiry_date = $detail->sql_formatted_expire_date;
                } else {
                    // Default to 1 year if no expiry date is available
                    $deliveryItem->expiry_date = date('Y-m-d', strtotime('+1 year'));
                }

                $deliveryItem->pharm_location_id = $this->deliveryData['pharm_location_id'];
                $deliveryItem->charge_code = $this->deliveryData['charge_code'];
                $deliveryItem->save();

                // Create/Update DrugPrice (same as add_item logic)
                $attributes = [
                    'dmdcomb' => $deliveryItem->dmdcomb,
                    'dmdctr' => $deliveryItem->dmdctr,
                    'dmhdrsub' => $delivery->charge_code,
                    'dmduprice' => $unit_cost,
                    'dmselprice' => $deliveryItem->retail_price,
                    'expdate' => $deliveryItem->expiry_date,
                    'stock_id' => $deliveryItem->id,
                    'mark_up' => $markup_price,
                    'acquisition_cost' => $unit_cost,
                    'has_compounding' => false,
                    'retail_price' => $retail_price
                ];

                $values = [
                    'dmdprdte' => now()
                ];

                // Create or find existing price record
                $new_price = \App\Models\Pharmacy\DrugPrice::firstOrCreate($attributes, $values);

                $dmdprdte = $new_price->dmdprdte;
                $deliveryItem->dmdprdte = $dmdprdte;
                $deliveryItem->save();

                // Update RIS detail to mark as transferred with NOLOCK for read
                $risDetailInfo = DB::connection('pims')
                    ->table('tbl_ris_details')
                    ->from(DB::raw('tbl_ris_details'))
                    ->select('risdetid')
                    ->where('risdetid', $detail->risdetid)
                    ->first();

                if ($risDetailInfo) {
                    DB::connection('pims')
                        ->table('tbl_ris_details')
                        ->where('risdetid', $detail->risdetid)
                        ->update([
                            'transferred_to_pdims' => $delivery->id,
                            'transferred_at' => now()
                        ]);
                }
            }

            // Update RIS header to mark as transferred with NOLOCK for read
            $risHeaderInfo = DB::connection('pims')
                ->table('tbl_ris')
                ->from(DB::raw('tbl_ris'))
                ->select('risid')
                ->where('risid', $this->risId)
                ->first();

            if ($risHeaderInfo) {
                DB::connection('pims')
                    ->table('tbl_ris')
                    ->where('risid', $this->risId)
                    ->update([
                        'transferred_to_pdims' => $delivery->id,
                        'transferred_at' => now()
                    ]);
            }

            DB::commit();

            session()->flash('message', 'RIS items successfully transferred to delivery system.');
            $this->isTransferModalOpen = false;

            // Redirect to the delivery view page
            return redirect()->route('delivery.view', [$delivery->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error transferring items: ' . $e->getMessage());
            \Log::error('Transfer to delivery error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle transfer complete event
     */
    public function handleTransferComplete()
    {
        $this->loadRis();
    }

    public function render()
    {
        // Fetch suppliers, charge codes, and pharmacy locations for the transfer modal
        $suppliers = Supplier::all();

        $chargeCodes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', app('chargetable'))
            ->get();

        // Use the correct PharmLocation model
        $pharmacyLocations = PharmLocation::where('description', 'like', '%warehouse%')->where('deleted_at', null)->get();
        $this->deliveryData['pharm_location_id'] = $pharmacyLocations->first()->id ?? null;
        return view('livewire.show-ris', [
            'ris' => $this->ris,
            'risDetails' => $this->risDetails,
            'relatedIar' => $this->relatedIar,
            'loading' => $this->loading,
            'suppliers' => $suppliers,
            'chargeCodes' => $chargeCodes,
            'pharmacyLocations' => $pharmacyLocations,
        ]);
    }
}
