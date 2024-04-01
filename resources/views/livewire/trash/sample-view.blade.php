<div>
    <input type="text" wire:model.lazy="search" class="input input-sm" id="myInput">
    <div class="flex justify-between">
        <table class="flex flex-col gap-3" id="myTable">
            <thead>
                <tr>
                    <th>dmdcomb</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stocks as $stock)
                    <tr>
                        <td>
                            {{ $stock->dmdcomb }}
                        </td>
                        <td>
                            {{ $stock->drug_concat }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            $("#myInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#myTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
    </script>
@endpush
