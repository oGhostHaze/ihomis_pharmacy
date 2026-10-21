<?php
// Standalone fixtures only: no autoloader, Laravel bootstrap, or database access.
require __DIR__ . '/../../app/Services/Pharmacy/UdddsService.php';
require __DIR__ . '/../../app/Services/Pharmacy/UdddsStockAllocator.php';
require __DIR__ . '/../../app/Services/Pharmacy/UdddsReplacementSelection.php';

use App\Services\Pharmacy\UdddsReplacementSelection;
use App\Services\Pharmacy\UdddsStockAllocator;

function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function order($key, $drug = 'A') {
    return (object) ['docointkey' => $key, 'enccode' => $key, 'dmdcomb' => $drug, 'dmdctr' => '1',
        'orderfrom' => 'REG', 'pchrgqty' => 2, 'pchrgup' => 10, 'remarks' => '',
        'current_available' => 0, 'alternate_funds' => [], 'is_actionable' => true,
        'pcchrgcod' => null, 'uddds_source_docointkey' => 'SOURCE'];
}
function rejects($callback, $message) {
    try { $callback(); } catch (InvalidArgumentException $e) { return; }
    throw new RuntimeException($message);
}
$selector = new UdddsReplacementSelection;
$stock = (object) ['id' => 9, 'dmdcomb' => 'B', 'dmdctr' => '1', 'chrgcode' => 'REG',
    'replacement_price' => 25, 'stock_bal' => 4, 'exp_date' => '2099-01-01'];
$a = order('ONE');
$key = UdddsStockAllocator::groupKey($a);
$original = clone $a;
$result = $selector->apply([clone $a, order('TWO')], [$key => '9'], [$stock]);
check($result[0]->dmdcomb === 'B' && $result[1]->dmdcomb === 'B', 'Replace all selected patients in the group');
check($result[0]->pchrgqty === 2 && $result[0]->pchrgup === 25, 'Keep quantity and use replacement price');
check(strpos($result[0]->remarks, 'A/1 (fund REG)') !== false, 'Preserve original medicine and fund in remarks');
check($a == $original, 'Standing source fixture remains unchanged');
$plan = (new UdddsStockAllocator)->allocate($result, [$stock], []);
check($plan['ok'], 'Allocate replacement stock for selected patients');
$stock->stock_bal = 3;
check(!(new UdddsStockAllocator)->allocate($result, [$stock], [])['ok'], 'Reject shared stock shortage');
rejects(fn () => $selector->apply([clone $a], [$key => '999'], [$stock]), 'Reject unavailable stock ID');
rejects(fn () => $selector->apply([clone $a], [$key => ['9']], [$stock]), 'Reject malformed stock ID');
$charged = clone $a; $charged->pcchrgcod = 'CHARGED';
rejects(fn () => $selector->apply([$charged], [$key => '9'], [$stock]), 'Reject charged daily order');
$funded = clone $a; $funded->alternate_funds = [['code' => 'OTHER', 'available' => 2]];
rejects(fn () => $selector->apply([$funded], [$key => '9'], [$stock]), 'Reject replacement when alternate funds become available');
$covered = clone $a; $covered->current_available = 2;
rejects(fn () => $selector->apply([$covered], [$key => '9'], [$stock]), 'Reject replacement when current stock becomes sufficient');
$same = clone $stock; $same->dmdcomb = 'A';
rejects(fn () => $selector->apply([clone $a], [$key => '9'], [$same]), 'Reject original medicine as replacement');
$enrollment = clone $a; $enrollment->uddds_source_docointkey = null; $enrollment->pcchrgcod = 'OLD-SLIP';
check($selector->apply([$enrollment], [$key => '9'], [$stock])[0]->dmdcomb === 'B', 'Previous enrollment charge permits next dose replacement preview');
// A replacement matching a later original group must not be replaced a second time.
$b = order('OTHER', 'B');
$c = clone $stock; $c->id = 10; $c->dmdcomb = 'C';
$result = $selector->apply([clone $a, $b], [$key => '9', UdddsStockAllocator::groupKey($b) => '10'], [$stock, $c]);
check($result[0]->dmdcomb === 'B' && $result[1]->dmdcomb === 'C', 'Match replacements to original groups');
$group = ['item' => $a, 'qty' => 4, 'patients' => ['E1' => true, 'E2' => true]];
$a->drug_concat = 'ORIGINAL_MEDICINE';
$stock->drug_concat = 'REPLACEMENT_MEDICINE';
$summary = $selector->summary([$key => $group], [$key => '9'], [$stock]);
check(count($summary) === 1 && strpos($summary[0], 'ORIGINALMEDICINE -> REPLACEMENTMEDICINE') !== false, 'Confirmation identifies original and replacement medicines');
check(strpos($summary[0], '4 units; 2 patients; 25 per unit') !== false, 'Confirmation identifies quantities, patients and price');
check($selector->summary([$key => $group], [], [$stock]) === [], 'Unchanged items do not request replacement confirmation');
$token = UdddsReplacementSelection::confirmationKey(['ONE', 'TWO'], [$key => '9'], [], '2026-10-21');
check($token === UdddsReplacementSelection::confirmationKey(['TWO', 'ONE'], [$key => '9'], [], '2026-10-21'), 'Selection order does not invalidate confirmation');
check($token !== UdddsReplacementSelection::confirmationKey(['ONE'], [$key => '9'], [], '2026-10-21'), 'Changed selected orders require confirmation');
check($token !== UdddsReplacementSelection::confirmationKey(['ONE', 'TWO'], [$key => '10'], [], '2026-10-21'), 'Changed replacement requires confirmation');
check($token !== UdddsReplacementSelection::confirmationKey(['ONE', 'TWO'], [$key => '9'], [$key => ['OTHER']], '2026-10-21'), 'Changed funds require confirmation');
check($token !== UdddsReplacementSelection::confirmationKey(['ONE', 'TWO'], [$key => '9'], [], '2026-10-22'), 'Changed service date requires confirmation');

$viewPath = __DIR__ . '/../../resources/views/livewire/records/uddds-ward.blade.php';
check(preg_match('//u', file_get_contents($viewPath)) === 1, 'UDDDS view must be valid UTF-8 for Livewire JSON responses');

echo "UDDDS replacement selection checks passed.\n";
