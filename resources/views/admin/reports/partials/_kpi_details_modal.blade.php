<div class="modal fade" id="kpiDetailsModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header p-3 bg-light">
                <h5 class="modal-title" id="kpiModalTitle">{{ $kpiModalTitle ?? 'KPI Details' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($kpiModalRows && count($kpiModalRows) > 0)
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    @foreach($kpiModalHeaders as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kpiModalRows as $row)
                                    <tr>
                                        @foreach($row as $cell)
                                            <td>{!! $cell !!}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            @if(isset($kpiModalFooter))
                                <tfoot class="table-light text-end fw-semibold">
                                    <tr>
                                        <td colspan="{{ count($kpiModalHeaders) }}">{!! $kpiModalFooter !!}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title bg-light text-primary rounded-circle fs-2">
                                <i class="ri-search-eye-line"></i>
                            </div>
                        </div>
                        <h5 class="text-muted">No breakdown data available for this selection.</h5>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                @if(isset($kpiModalAction))
                    <a href="{{ $kpiModalAction['link'] }}" class="btn btn-primary">{{ $kpiModalAction['label'] }}</a>
                @endif
            </div>
        </div>
    </div>
</div>
