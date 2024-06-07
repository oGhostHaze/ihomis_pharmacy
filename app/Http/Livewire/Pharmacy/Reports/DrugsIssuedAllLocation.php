<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DrugsIssuedAllLocation extends Component
{
    use WithPagination;

    public $date_from, $date_to;

    public function render()
    {
        $date_from = Carbon::parse($this->date_from)->format('Y-m-d H:i');
        $date_to = Carbon::parse($this->date_to)->format('Y-m-d H:i');

        $opd_issued = DB::select("SELECT hdmhdr.drug_concat, SUM(pchrgqty) total_issue, CASE henctr.toecode
                                                                                                    WHEN 'OPD' THEN 'Outpatient'
                                                                                                    WHEN 'OPDAD' THEN 'Admitted'
                                                                                                END
                                                                                                 encounter, serv2.tsdesc tsdesc
                                    FROM henctr
                                        LEFT JOIN hopdlog ON henctr.enccode = hopdlog.enccode
                                        INNER JOIN hrxo ON henctr.enccode = hrxo.enccode
                                        INNER JOIN hdmhdr ON hrxo.dmdcomb = hdmhdr.dmdcomb AND hrxo.dmdctr = hdmhdr.dmdctr
                                        LEFT JOIN htypser serv2 ON hopdlog.tscode = serv2.tscode
                                    WHERE hrxo.estatus = 'S' AND hrxo.dodate BETWEEN '".$date_from."' AND '".$date_to."' AND (henctr.toecode = 'OPD' OR henctr.toecode = 'OPDAD')
                                    GROUP BY serv2.tsdesc, hrxo.dmdcomb, hrxo.dmdctr, hdmhdr.drug_concat, henctr.toecode
                                    ORDER BY hdmhdr.drug_concat");

        $er_issued = DB::select("SELECT hdmhdr.drug_concat, SUM(pchrgqty) total_issue,  CASE henctr.toecode
                                                                                                    WHEN 'ER' THEN 'Emergency Room'
                                                                                                    WHEN 'ERADM' THEN 'Admitted'
                                                                                                END
                                                                                                encounter, serv3.tsdesc tsdesc
                            FROM henctr
                                LEFT JOIN herlog ON henctr.enccode = herlog.enccode
                                INNER JOIN hrxo ON henctr.enccode = hrxo.enccode
                                INNER JOIN hdmhdr ON hrxo.dmdcomb = hdmhdr.dmdcomb AND hrxo.dmdctr = hdmhdr.dmdctr
                                LEFT JOIN htypser serv3 ON herlog.tscode = serv3.tscode
                            WHERE hrxo.estatus = 'S' AND hrxo.dodate BETWEEN '".$date_from."' AND '".$date_to."' AND (henctr.toecode = 'ER' OR henctr.toecode = 'ERADM')
                            GROUP BY serv3.tsdesc, hrxo.dmdcomb, hrxo.dmdctr, hdmhdr.drug_concat, henctr.toecode
                            ORDER BY hdmhdr.drug_concat");

        $adm_issued = DB::select("SELECT hdmhdr.drug_concat, SUM(pchrgqty) total_issue, 'Admitted' encounter, serv.tsdesc tsdesc
                            FROM henctr
                                LEFT JOIN hadmlog ON henctr.enccode = hadmlog.enccode
                                INNER JOIN hrxo ON henctr.enccode = hrxo.enccode
                                INNER JOIN hdmhdr ON hrxo.dmdcomb = hdmhdr.dmdcomb AND hrxo.dmdctr = hdmhdr.dmdctr
                                LEFT JOIN htypser serv ON hadmlog.tscode = serv.tscode
                            WHERE hrxo.estatus = 'S' AND hrxo.dodate BETWEEN '".$date_from."' AND '".$date_to."' AND henctr.toecode = 'ADM'
                            GROUP BY serv.tsdesc, hrxo.dmdcomb, hrxo.dmdctr, hdmhdr.drug_concat, henctr.toecode
                            ORDER BY hdmhdr.drug_concat");

        $walkn_issued = DB::select("SELECT hdmhdr.drug_concat, SUM(pchrgqty) total_issue, 'Walk In' encounter, hdept.deptname tsdesc
                            FROM henctr
                                INNER JOIN hrxo ON henctr.enccode = hrxo.enccode
                                INNER JOIN hdmhdr ON hrxo.dmdcomb = hdmhdr.dmdcomb AND hrxo.dmdctr = hdmhdr.dmdctr
                                LEFT JOIN hdept ON hrxo.deptcode = hdept.deptcode
                            WHERE hrxo.estatus = 'S' AND hrxo.dodate BETWEEN '".$date_from."' AND '".$date_to."' AND henctr.toecode = 'WALKN'
                            GROUP BY hrxo.dmdcomb, hrxo.dmdctr, hdmhdr.drug_concat, henctr.toecode, hrxo.deptcode, hdept.deptname
                            ORDER BY hdmhdr.drug_concat");

        $departments = DB::select("SELECT hdept.deptname FROM hdept WHERE deptstat = 'A' UNION SELECT tsdesc FROM htypser WHERE tsstat = 'A'");

        return view('livewire.pharmacy.reports.drugs-issued-all-location', [
            'opd_issued' => $opd_issued,
            'er_issued' => $er_issued,
            'adm_issued' => $adm_issued,
            'walkn_issued' => $walkn_issued,
            'departments' => $departments,
        ]);
    }

    public function mount()
    {
        if (isset($_GET['from']) and isset($_GET['to'])) {
            $this->date_from = Carbon::parse($_GET['from'])->format('Y-m-d H:i');
            $this->date_to = Carbon::parse($_GET['to'])->format('Y-m-d H:i');
        } else {
            $this->date_from = Carbon::parse(now())->startOfWeek()->format('Y-m-d H:i');
            $this->date_to = Carbon::parse(now())->endOfWeek()->format('Y-m-d H:i');
        }
    }
}