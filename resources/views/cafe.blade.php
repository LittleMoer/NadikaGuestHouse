@extends('layouts.templateowner')
@section('cafe')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Cafe - Inventory & Order</h4>
            <ul class="breadcrumbs">
                <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#">Cafe</a></li>
            </ul>
        </div>
        @if(session('success'))<div class="alert alert-success py-2 px-3">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2 px-3">{{ session('error') }}</div>@endif

        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header py-2"><strong>Tambah Produk</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('cafe.product.store') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label mb-0">Nama</label>
                                <input type="text" name="nama" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0">Kategori</label>
                                <input type="text" name="kategori" class="form-control form-control-sm">
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0">Satuan</label>
                                <input type="text" name="satuan" value="porsi" class="form-control form-control-sm">
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0">Harga Jual</label>
                                <input type="number" name="harga_jual" min="0" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0">Stok Awal</label>
                                <input type="number" name="stok_awal" min="0" class="form-control form-control-sm">
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0">Minimal Stok</label>
                                <input type="number" name="minimal_stok" min="0" class="form-control form-control-sm">
                            </div>
                            <div class="text-end">
                                <button class="btn btn-primary btn-sm">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header py-2"><strong>Penyesuaian Stok</strong></div>
                    <div class="card-body">
                        <form method="POST" id="formAdjustStock">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label mb-0">Produk</label>
                                <select name="product_id" id="adj_product" class="form-select form-select-sm" required>
                                    <option value="">-- pilih --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-nama="{{ $p->nama }}">{{ $p->nama }} (Stok: {{ $p->stok }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0">Tipe</label>
                                <select name="tipe" class="form-select form-select-sm" required>
                                    <option value="in">Masuk (IN)</option>
                                    <option value="out">Keluar (OUT)</option>
                                    <option value="adjust">Set Absolut</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0">Qty</label>
                                <input type="number" name="qty" min="1" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control form-control-sm">
                            </div>
                            <div class="text-end">
                                <button class="btn btn-warning btn-sm" type="submit">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header py-2"><strong>Order ke Booking</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('cafe.order.store') }}" id="formOrderCafe">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label mb-0">Booking (Check-In)</label>
                                <select name="booking_id" class="form-select form-select-sm" required>
                                    <option value="">-- pilih booking --</option>
                                    @foreach($activeBookings as $b)
                                        <option value="{{ $b->id }}">#{{ $b->id }} - {{ $b->pelanggan?->nama }} ({{ $b->tanggal_checkin->format('d/m H:i') }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="orderItemsWrap">
                                <div class="order-item-row mb-2">
                                    <select name="items[0][product_id]" class="form-select form-select-sm d-inline-block" style="width:60%;" required>
                                        <option value="">-- produk --</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-harga="{{ $p->harga_jual }}">{{ $p->nama }} (Stok: {{ $p->stok }})</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="items[0][qty]" placeholder="Qty" min="1" class="form-control form-control-sm d-inline-block" style="width:38%;" required>
                                </div>
                            </div>
                            <div class="mb-2 text-end">
                                <button type="button" id="btnAddRowOrder" class="btn btn-outline-secondary btn-xs">+</button>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-success btn-sm">Simpan Order</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header py-2"><strong>Daftar Produk</strong></div>
                    <div class="table-responsive" style="max-height:340px;overflow:auto;">
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Min</th></tr></thead>
                            <tbody>
                                @foreach($products as $p)
                                    <tr class="{{ $p->stok <= $p->minimal_stok ? 'table-warning' : '' }}">
                                        <td>{{ $p->nama }}</td>
                                        <td>{{ $p->kategori }}</td>
                                        <td class="text-end">{{ number_format($p->harga_jual,0,',','.') }}</td>
                                        <td class="text-end">{{ $p->stok }}</td>
                                        <td class="text-end">{{ $p->minimal_stok }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header py-2"><strong>Riwayat Stok Terbaru</strong></div>
                    <div class="table-responsive" style="max-height:340px;overflow:auto;">
                        <table class="table table-sm mb-0" style="font-size:.75rem;">
                            <thead class="table-light"><tr><th>Waktu</th><th>Produk</th><th>Tipe</th><th class="text-end">Qty</th><th>Keterangan</th></tr></thead>
                            <tbody>
                                @foreach($movements as $m)
                                    <tr>
                                        <td>{{ $m->created_at->format('d/m H:i') }}</td>
                                        <td>{{ $m->product?->nama }}</td>
                                        <td>{{ strtoupper($m->tipe) }}</td>
                                        <td class="text-end">{{ $m->qty }}</td>
                                        <td>{{ $m->keterangan }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        const addBtn = document.getElementById('btnAddRowOrder');
        const wrap = document.getElementById('orderItemsWrap');
        let idx = 1;
        addBtn?.addEventListener('click', ()=>{
            const div = document.createElement('div');
            div.className='order-item-row mb-2';
            div.innerHTML = `
                <select name="items[${idx}][product_id]" class="form-select form-select-sm d-inline-block" style="width:60%;" required>
                    <option value="">-- produk --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-harga="{{ $p->harga_jual }}">{{ $p->nama }} (Stok: {{ $p->stok }})</option>
                    @endforeach
                </select>
                <input type="number" name="items[${idx}][qty]" placeholder="Qty" min="1" class="form-control form-control-sm d-inline-block" style="width:38%;" required>
            `;
            wrap.appendChild(div);
            idx++;
        });
        const formAdjust = document.getElementById('formAdjustStock');
        formAdjust?.addEventListener('submit', function(e){
            const prodId = document.getElementById('adj_product').value;
            if(!prodId){ e.preventDefault(); alert('Pilih produk'); }
            this.action = '/cafe/products/'+prodId+'/adjust';
        });
    });
</script>
@endsection