<x-slot name="header">
    <div class="text-sm breadcrumbs">
        <ul>
            <li class="font-bold">
                <i class="mr-1 las la-map-marked la-lg"></i> {{session('pharm_location_name')}}
            </li>
            <li>
                <i class="mr-1 las la-file-excel la-lg"></i> Report
            </li>
            <li>
                <i class="mr-1 las la-clone la-lg"></i> Drug Issuance
            </li>
        </ul>
    </div>
</x-slot>

<div class="max-w-screen">
    <div class="flex flex-col px-2 py-5">
        <div class="flex justify-end my-2">
            <select class="w-3/12 select select-bordered select-sm" wire:model="charge_code">
                <option></option>
                @foreach ($charges as $charge)
                <option value="{{$charge->chrgcode}}">{{$charge->chrgdesc}}</option>
                @endforeach
            </select>
        </div>
        <table class="text-xs bg-white shadow-md table-fixed table-compact">
            <thead class="font-bold bg-gray-200">
                <tr class="text-center uppercase">
                    <td class="w-2/12 text-xs border border-black">Source of Fund</td>
                    <td class="text-xs border border-black" colspan="2">Beg. Bal.</td>
                    <td class="text-xs border border-black" colspan="3">Total Avail. For Sale</td>
                    <td class="text-xs border border-black" colspan="14">Issuances</td>
                    <td class="text-xs border border-black" colspan="2">Ending Bal.</td>
                </tr>
                <tr class="text-center">
                    <td class="text-xs border border-black"></td>
                    <td class="text-xs border border-black">QTY.</td>
                    <td class="text-xs border border-black">Amount</td>
                    <td class="text-xs border border-black">QTY.</td>
                    <td class="text-xs border border-black">Unit <br> Cost</td>
                    <td class="text-xs border border-black">Total <br> Cost</td>
                    <td class="text-xs border border-black">SC/PWD</td>
                    <td class="text-xs border border-black">EMS</td>
                    <td class="text-xs border border-black">MAIP</td>
                    <td class="text-xs border border-black">W.S.</td>
                    <td class="text-xs border border-black">Pay</td>
                    <td class="text-xs border border-black">Medicare</td>
                    <td class="text-xs border border-black">Service</td>
                    <td class="text-xs border border-black">CAF</td>
                    <td class="text-xs border border-black">Gov't <br> Emp.</td>
                    <td class="text-xs border border-black">Issued <br> Total</td>
                    <td class="text-xs border border-black">Selling <br> Price</td>
                    <td class="text-xs border border-black">Total <br> Sales</td>
                    <td class="text-xs border border-black">COGS</td>
                    <td class="text-xs border border-black">Profit</td>
                    <td class="text-xs border border-black">QTY.</td>
                    <td class="text-xs border border-black">Amount</td>
                </tr>
            </thead>
            <tbody>
                @forelse ($charges as $charge)
                <tr class="border border-black bg-slate-400">
                    <th colspan="22" class="text-left">{{$charge->chrgdesc}}</th>
                </tr>
                @foreach ($charge->issued_stock->all() as $rxi)
                @php
                $sc_pwd = $rxi->issued_drugs->sum('sc_pwd');
                $ems = $rxi->issued_drugs->sum('ems');
                $maip = $rxi->issued_drugs->sum('maip');
                $wholesale = $rxi->issued_drugs->sum('wholesale');
                $pay = $rxi->issued_drugs->sum('pay');
                $medicare = $rxi->issued_drugs->sum('medicare');
                $service = $rxi->issued_drugs->sum('service');
                $caf = $rxi->issued_drugs->sum('caf');
                $govt_emp = $rxi->issued_drugs->sum('govt_emp');
                $total_qty = $rxi->issued_drugs->sum('qty');
                $total_sales = $rxi->issued_drugs->sum('pcchrgamt');
                $total_cogs = $total_sales * (100 / 130);
                $profit = $total_sales - $total_cogs;
                $ending_bal = $rxi->beg_bal - $total_qty;
                $ending_amount = $ending_bal * $rxi->retail_price;
                @endphp
                <tr classs="border border-black">
                    <td class="text-xs border border-black">
                        <div class="flex flex-col">
                            <div class="text-sm font-bold">{{$rxi->drug->generic->gendesc}}</div>
                            <div class="ml-10 text-xs text-slate-800">{{$rxi->drug->dmdnost}}{{$rxi->drug->strength->stredesc ?? ''}} {{$rxi->drug->form->formdesc ?? ''}}</div>
                            <div class="text-xs font-light">Exp. Date: {{$rxi->exp_date}}</div>
                        </div>
                    </td>
                    <td class="text-xs text-right border border-black">{{$rxi->beg_bal}}</td>
                    <td class="text-xs text-right border border-black">{{number_format($rxi->beg_bal * ($rxi->retail_price * (100 / 130)),2)}}</td>
                    <td class="text-xs text-right border border-black">{{$rxi->stock_bal}}</td>
                    <td class="text-xs text-right border border-black">{{number_format($rxi->retail_price * (100 / 130),2)}}</td>
                    <td class="text-xs text-right border border-black">{{number_format($rxi->stock_bal * ($rxi->retail_price * (100 / 130)),2)}}</td>
                    <td class="text-xs text-right border border-black">{{$sc_pwd}}</td>
                    <td class="text-xs text-right border border-black">{{$ems}}</td>
                    <td class="text-xs text-right border border-black">{{$maip}}</td>
                    <td class="text-xs text-right border border-black">{{$wholesale}}</td>
                    <td class="text-xs text-right border border-black">{{$pay}}</td>
                    <td class="text-xs text-right border border-black">{{$medicare}}</td>
                    <td class="text-xs text-right border border-black">{{$service}}</td>
                    <td class="text-xs text-right border border-black">{{$caf}}</td>
                    <td class="text-xs text-right border border-black">{{$govt_emp}}</td>
                    <td class="text-xs text-right border border-black">{{$total_qty}}</td>
                    <td class="text-xs text-right border border-black">{{$rxi->retail_price}}</td>
                    <td class="text-xs text-right border border-black">{{number_format($total_sales, 2)}}</td>
                    <td class="text-xs text-right border border-black">{{number_format($total_cogs, 2)}}</td>
                    <td class="text-xs text-right border border-black">{{number_format($profit, 2)}}</td>
                    <td class="text-xs text-right border border-black">{{$ending_bal}}</td>
                    <td class="text-xs text-right border border-black">{{number_format($ending_amount, 2)}}</td>
                </tr>
                @endforeach
                @empty
                <tr>
                    <th class="text-center" colspan="22">No record found!</th>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>