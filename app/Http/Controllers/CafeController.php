<?php
namespace App\Http\Controllers;

use App\Models\CafeProduct;
use App\Models\CafeStockMovement;
use App\Models\CafeOrder;
use App\Models\CafeOrderItem;
use App\Models\BookingOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CafeController extends Controller
{
    public function index()
    {
        $products = CafeProduct::orderBy('nama')->get();
        $movements = CafeStockMovement::with('product')->latest()->limit(25)->get();
        // Active bookings = status check-in (2)
        $activeBookings = BookingOrder::activeCheckin()->with('pelanggan')->orderByDesc('id')->get();
    // View utama menggunakan resources/views/cafe.blade.php
    return view('cafe', compact('products','movements','activeBookings'));
    }

    public function storeProduct(Request $request)
    {
        $data = $request->validate([
            'nama'=>'required|string|max:150',
            'kategori'=>'nullable|string|max:100',
            'satuan'=>'nullable|string|max:30',
            'harga_jual'=>'required|numeric|min:0',
            'stok_awal'=>'nullable|integer|min:0',
            'minimal_stok'=>'nullable|integer|min:0'
        ]);
        $prod = CafeProduct::create([
            'nama'=>$data['nama'],
            'kategori'=>$data['kategori'] ?? null,
            'satuan'=>$data['satuan'] ?? 'porsi',
            'harga_jual'=>$data['harga_jual'],
            'stok'=>$data['stok_awal'] ?? 0,
            'minimal_stok'=>$data['minimal_stok'] ?? 0,
            'aktif'=>true,
        ]);
        if(($data['stok_awal'] ?? 0) > 0){
            CafeStockMovement::create([
                'cafe_product_id'=>$prod->id,
                'tipe'=>'in',
                'qty'=>$data['stok_awal'],
                'keterangan'=>'Stok awal'
            ]);
        }
        return redirect()->route('cafe.index')->with('success','Produk cafe ditambahkan');
    }

    public function adjustStock(Request $request, $id)
    {
        $product = CafeProduct::findOrFail($id);
        $data = $request->validate([
            'tipe'=>'required|in:in,out,adjust',
            'qty'=>'required|integer|min:1',
            'keterangan'=>'nullable|string|max:200'
        ]);
        DB::transaction(function() use ($data,$product){
            $qty = (int)$data['qty'];
            if($data['tipe']==='in'){
                $product->stok += $qty;
            } elseif($data['tipe']==='out'){
                if($product->stok < $qty) throw new \Exception('Stok tidak cukup');
                $product->stok -= $qty;
            } else { // adjust -> set absolute (keterangan berisi penjelasan perubahan)
                $product->stok = $qty; // interpret adjust qty as new absolute level
            }
            $product->save();
            CafeStockMovement::create([
                'cafe_product_id'=>$product->id,
                'tipe'=>$data['tipe']==='adjust' ? 'adjust' : $data['tipe'],
                'qty'=>$qty,
                'keterangan'=>$data['keterangan'] ?? null,
            ]);
        });
        return redirect()->route('cafe.index')->with('success','Stok diperbarui');
    }

    public function storeOrder(Request $request)
    {
        $data = $request->validate([
            'booking_id'=>'required|exists:booking,id',
            'items'=>'required|array|min:1',
            'items.*.product_id'=>'required|exists:cafe_products,id',
            'items.*.qty'=>'required|integer|min:1'
        ]);
        $booking = BookingOrder::where('status',2)->findOrFail($data['booking_id']); // hanya booking check-in
        $itemsInput = $data['items'];
        $products = CafeProduct::whereIn('id', collect($itemsInput)->pluck('product_id'))->get()->keyBy('id');
        $orderTotal = 0;
        DB::transaction(function() use (&$orderTotal,$itemsInput,$products,$booking){
            // Validasi stok cukup
            foreach($itemsInput as $row){
                $p = $products[$row['product_id']] ?? null;
                if(!$p) throw new \Exception('Produk tidak ditemukan');
                if($p->stok < $row['qty']) throw new \Exception('Stok tidak cukup untuk '.$p->nama);
            }
            $order = CafeOrder::create([
                'booking_id'=>$booking->id,
                'total'=>0,
            ]);
            foreach($itemsInput as $row){
                $p = $products[$row['product_id']];
                $qty = (int)$row['qty'];
                $subtotal = $qty * (float)$p->harga_jual;
                $orderTotal += $subtotal;
                CafeOrderItem::create([
                    'cafe_order_id'=>$order->id,
                    'cafe_product_id'=>$p->id,
                    'qty'=>$qty,
                    'harga_satuan'=>$p->harga_jual,
                    'subtotal'=>$subtotal,
                ]);
                // Kurangi stok
                $p->stok -= $qty; $p->save();
                CafeStockMovement::create([
                    'cafe_product_id'=>$p->id,
                    'tipe'=>'out',
                    'qty'=>$qty,
                    'keterangan'=>'Order booking #'.$booking->id
                ]);
            }
            $order->total = $orderTotal; $order->save();
            // Tambah ke booking.total_cafe
            $booking->total_cafe = ($booking->total_cafe ?? 0) + $orderTotal;
            $booking->save();
        });
        if($request->wantsJson()) return response()->json(['success'=>true,'total_order'=>$orderTotal]);
        return redirect()->route('cafe.index')->with('success','Order cafe tersimpan');
    }

    public function ordersList(Request $request)
    {
        // Client-side pagination with DataTables on the view
        $orders = CafeOrder::with(['booking.pelanggan','items.product'])->latest()->get();
        return view('cafeorders', compact('orders'));
    }

    /**
     * Batalkan (hapus) 1 cafe order: kembalikan stok produk, kurangi total_cafe pada booking, lalu hapus order & items.
     */
    public function destroyOrder(Request $request, $id)
    {
        $order = CafeOrder::with(['items','booking'])->findOrFail($id);
        DB::transaction(function() use ($order){
            // Kembalikan stok untuk setiap item
            foreach($order->items as $it){
                $prod = CafeProduct::find($it->cafe_product_id);
                if($prod){ $prod->stok += (int)$it->qty; $prod->save(); }
                // Opsional: catat movement balik (in) karena pembatalan order
                CafeStockMovement::create([
                    'cafe_product_id'=>$it->cafe_product_id,
                    'tipe'=>'in',
                    'qty'=>$it->qty,
                    'keterangan'=>'Undo order cafe #'.$order->id
                ]);
            }
            // Kurangi total_cafe pada booking
            if($order->booking){
                $order->booking->total_cafe = max(0, (float)($order->booking->total_cafe ?? 0) - (float)$order->total);
                $order->booking->save();
            }
            // Hapus items lalu order
            $order->items()->delete();
            $order->delete();
        });
        if($request->wantsJson()) return response()->json(['success'=>true]);
        return redirect()->route('cafe.orders')->with('success','Order cafe dihapus dan stok dikembalikan');
    }
}
