@extends('layouts.templateowner')
@section('cafeorders')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Cafe - Orders</h4>
            <ul class="breadcrumbs">
                <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="/cafe">Cafe</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#">Orders</a></li>
            </ul>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="cafe-orders-table" class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>ID</th><th>Booking</th><th>Pelanggan</th><th>Total</th><th>Item</th><th>Waktu</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @foreach($orders as $o)
                                <tr>
                                    <td>#{{ $o->id }}</td>
                                    <td>@if($o->booking) <a href="/booking?booking_id={{ $o->booking_id }}">#{{ $o->booking_id }}</a> @endif</td>
                                    <td>{{ $o->booking?->pelanggan?->nama }}</td>
                                    <td class="text-end">{{ number_format($o->total,0,',','.') }}</td>
                                    <td style="font-size:.7rem;">
                                        @foreach($o->items as $it)
                                            <div>{{ $it->product?->nama }} x {{ $it->qty }}</div>
                                        @endforeach
                                    </td>
                                    <td>{{ $o->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <form action="{{ route('cafe.order.destroy', $o->id) }}" method="POST" onsubmit="return confirm('Hapus order cafe ini? Stok akan dikembalikan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
    </div>
</div>
@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        if(window.jQuery && jQuery.fn.DataTable){
            jQuery('#cafe-orders-table').DataTable({
                pageLength: 10,
                lengthMenu: [[10,25,50,-1],[10,25,50,'Semua']],
                order: [[0,'desc']],
                columnDefs: [
                    { targets: [4,6], orderable: false } // Item & Aksi tidak perlu sorting
                ]
            });
        }
    });
</script>
@endsection
@endsection