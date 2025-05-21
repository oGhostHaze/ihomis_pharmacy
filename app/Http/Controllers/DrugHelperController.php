<?php

namespace App\Http\Controllers;

use App\Models\Pharmacy\Drug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrugHelperController extends Controller
{
    /**
     * Get statistics about drug associations
     */
    public function getDrugAssociationStats()
    {
        $totalItems = DB::connection('pims')
            ->table('tbl_items')
            ->count();

        $associatedItems = DB::connection('pims')
            ->table('tbl_items')
            ->whereNotNull('pdims_itemcode')
            ->count();

        $percentageAssociated = $totalItems > 0 ? round(($associatedItems / $totalItems) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'totalItems' => $totalItems,
                'associatedItems' => $associatedItems,
                'percentageAssociated' => $percentageAssociated
            ],
            'message' => $associatedItems . ' out of ' . $totalItems . ' items (' . $percentageAssociated . '%) have drug associations.'
        ]);
    }

    /**
     * Search for drugs by term (API endpoint)
     */
    public function searchDrugs(Request $request)
    {
        $searchTerm = $request->input('term');

        if (strlen($searchTerm) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Search term must be at least 3 characters long.'
            ]);
        }

        $results = Drug::query()
            ->where(function ($query) use ($searchTerm) {
                $query->whereRaw("brandname LIKE ?", ['%' . $searchTerm . '%'])
                    ->orWhereRaw("drug_concat LIKE ?", ['%' . $searchTerm . '%']);
            })
            ->limit(15)
            ->get(['dmdcomb', 'dmdctr', 'brandname', 'drug_concat'])
            ->map(function ($drug) {
                return [
                    'id' => $drug->dmdcomb . '.' . $drug->dmdctr,
                    'name' => $drug->drug_concat(),
                    'brandname' => $drug->brandname,
                    'dmdcomb' => $drug->dmdcomb,
                    'dmdctr' => $drug->dmdctr
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Associate a drug with an item
     */
    public function associateDrug(Request $request)
    {
        $request->validate([
            'itemId' => 'required|numeric',
            'drugId' => 'required|string'
        ]);

        $itemId = $request->input('itemId');
        $drugId = $request->input('drugId');

        // Split the drugId into dmdcomb and dmdctr parts
        list($dmdcomb, $dmdctr) = explode('.', $drugId);

        // Fetch the drug from SQL Server
        $drug = Drug::where('dmdcomb', $dmdcomb)
            ->where('dmdctr', $dmdctr)
            ->first();

        if (!$drug) {
            return response()->json([
                'success' => false,
                'message' => 'Drug not found.'
            ], 404);
        }

        // Get the drug description
        $drugDescription = $drug->drug_concat();

        // Update the item in MySQL (PIMS connection)
        $updated = DB::connection('pims')
            ->table('tbl_items')
            ->where('itemID', $itemId)
            ->update([
                'pdims_itemcode' => $drugId,
                'pdims_drugdesc' => $drugDescription
            ]);

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'Drug successfully associated with item.',
                'data' => [
                    'itemId' => $itemId,
                    'drugId' => $drugId,
                    'drugDescription' => $drugDescription
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to associate drug with item.'
            ], 500);
        }
    }

    /**
     * Remove drug association from an item
     */
    public function removeDrugAssociation(Request $request)
    {
        $request->validate([
            'itemId' => 'required|numeric'
        ]);

        $itemId = $request->input('itemId');

        // Update the item in MySQL (PIMS connection)
        $updated = DB::connection('pims')
            ->table('tbl_items')
            ->where('itemID', $itemId)
            ->update([
                'pdims_itemcode' => null,
                'pdims_drugdesc' => null
            ]);

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'Drug association successfully removed.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove drug association.'
            ], 500);
        }
    }
}
