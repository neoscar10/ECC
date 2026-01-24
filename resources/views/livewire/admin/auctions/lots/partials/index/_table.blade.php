<div class="table-card mb-1">
    <table class="table align-middle" id="auctionTable">
        <thead class="table-light text-muted">
            <tr>
                <th>Lot No</th>
                <th>Item</th>
                <th>Status</th>
                <th>Current Bid</th>
                <th>Schedule</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody class="list form-check-all">
            @forelse($lots as $lot)
                @include('livewire.admin.auctions.lots.partials.index._row', ['lot' => $lot])
            @empty
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="noresult">
                            <div class="text-center">
                                <h5 class="mt-2">No lots found</h5>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-end">
    {{ $lots->links() }}
</div>
