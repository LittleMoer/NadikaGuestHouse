<?php

use App\Models\User;
use App\Models\Pelanggan;
use App\Models\Kamar;
use App\Models\BookingOrder;
use App\Models\BookingOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authUser() {
    $user = User::factory()->create();
    test()->actingAs($user);
    return $user;
}

function makeKamar($attrs = []){
    static $num = 100;
    $num++;
    return Kamar::create(array_merge([
        'nomor_kamar' => (string)$num,
        'tipe' => 'STD',
        'kapasitas' => 2,
        'harga' => 200000,
        'deskripsi' => null,
    ], $attrs));
}

function makePelanggan(){
    return Pelanggan::create([
        'nama' => 'John Doe',
        'telepon' => '08123456789',
        'alamat' => 'Alamat',
    ]);
}

function baseBookingPayload($pelangganId, $kamarIds, $overrides = []){
    $checkin = now()->startOfDay()->addDay();
    $checkout = now()->startOfDay()->addDays(2); // 1 malam
    return array_merge([
        'pelanggan_id' => $pelangganId,
        'kamar_ids' => $kamarIds,
        'tanggal_checkin' => $checkin->toDateTimeString(),
        'tanggal_checkout' => $checkout->toDateTimeString(),
        'jumlah_tamu' => 2,
        'pemesanan' => 0, // walk-in
        'status' => 2, // check-in default
        'payment_status' => 'dp',
        'catatan' => 'Catatan',
        'biaya_tambahan' => 0,
    ], $overrides);
}

it('can create a booking with correct totals for 1 night', function(){
    authUser();
    $pelanggan = makePelanggan();
    $k1 = makeKamar(['harga'=>150000]);
    $k2 = makeKamar(['harga'=>200000]);

    $payload = baseBookingPayload($pelanggan->id, [$k1->id, $k2->id]);

    $res = test()->post(route('booking.store'), $payload);
    $res->assertRedirect(route('booking.index'));

    $order = BookingOrder::with('items')->latest('id')->first();
    expect($order)->not->toBeNull();
    // 1 malam: 150k + 200k = 350k
    expect((int)$order->total_harga)->toBe(350000);
    expect($order->items)->toHaveCount(2);
});

it('rejects overlapping booking on the same room and dates', function(){
    authUser();
    $pelanggan = makePelanggan();
    $k = makeKamar(['harga'=>100000]);

    // First booking
    $payload1 = baseBookingPayload($pelanggan->id, [$k->id]);
    test()->post(route('booking.store'), $payload1)->assertRedirect();

    // Second booking overlaps same dates, same room
    $payload2 = baseBookingPayload($pelanggan->id, [$k->id]);
    $res = test()->post(route('booking.store'), $payload2);
    $res->assertSessionHasErrors('kamar_ids', null, 'booking_create');
});

it('applies half-day rate when duration <= 6 hours in same day', function(){
    authUser();
    $pelanggan = makePelanggan();
    $k = makeKamar(['harga'=>200000]);

    $checkin = now()->startOfDay()->addDay()->setTime(8,0);
    $checkout = $checkin->copy()->setTime(12,0); // 4 hours

    $payload = baseBookingPayload($pelanggan->id, [$k->id], [
        'tanggal_checkin' => $checkin->toDateTimeString(),
        'tanggal_checkout' => $checkout->toDateTimeString(),
    ]);

    $res = test()->post(route('booking.store'), $payload);
    $res->assertRedirect();

    $order = BookingOrder::latest('id')->first();
    // base 1 malam 200k, half-day: 100k
    expect((int)$order->total_harga)->toBe(100000);
});

it('adds per-head charge for guests > 2 when per_head_mode is true', function(){
    authUser();
    $pelanggan = makePelanggan();
    $k = makeKamar(['harga'=>100000]);

    $payload = baseBookingPayload($pelanggan->id, [$k->id], [
        'jumlah_tamu' => 4,
        'per_head_mode' => true,
    ]);

    $res = test()->post(route('booking.store'), $payload);
    $res->assertRedirect();

    $order = BookingOrder::latest('id')->first();
    // 1 malam 100k + (4-2)*50k = 200k
    expect((int)$order->total_harga)->toBe(200000);
});

it('applies review and follow discounts sequentially', function(){
    authUser();
    $pelanggan = makePelanggan();
    $k = makeKamar(['harga'=>200000]); // base 200k

    $payload = baseBookingPayload($pelanggan->id, [$k->id], [
        'discount_review' => true,
        'discount_follow' => true,
    ]);

    $res = test()->post(route('booking.store'), $payload);
    $res->assertRedirect();

    $order = BookingOrder::latest('id')->first();
    // base 200k -> review 10% => 180k -> follow 10% => 162k
    expect((int)$order->total_harga)->toBe(162000);
});

it('can update payment to lunas and record pelunasan in ledger', function(){
    authUser();
    $pelanggan = makePelanggan();
    $k = makeKamar(['harga'=>150000]);

    $payload = baseBookingPayload($pelanggan->id, [$k->id], [
        'dp_amount' => 50000,
        'payment_method' => 'cash',
    ]);
    test()->post(route('booking.store'), $payload)->assertRedirect();

    $order = BookingOrder::latest('id')->first();
    // Toggle payment to lunas
    $res = test()->post(route('booking.payment', $order->id), [ 'payment_status' => 'lunas' ]);
    $res->assertRedirect();

    $order->refresh();
    expect($order->payment_status)->toBe('lunas');
    // Ledger should have pelunasan for remaining 100k
    $sum = DB::table('cash_ledger')->where('booking_id',$order->id)->sum('amount');
    expect($sum)->toBeGreaterThanOrEqual(150000);
});

it('can move a room item to another available room and recalc totals', function(){
    authUser();
    $pelanggan = makePelanggan();
    $k1 = makeKamar(['harga'=>100000]);
    $k2 = makeKamar(['harga'=>200000]);

    test()->post(route('booking.store'), baseBookingPayload($pelanggan->id, [$k1->id]))->assertRedirect();
    $order = BookingOrder::with('items')->latest('id')->first();
    $item = $order->items->first();

    // move to k2
    $res = test()->post(route('booking.move_room', $order->id), [
        'item_id' => $item->id,
        'new_kamar_id' => $k2->id,
    ]);
    $res->assertRedirect();

    $order->refresh();
    expect((int)$order->total_harga)->toBe(200000);
});

it('rejects upgrade to cheaper room using upgradeRoom', function(){
    authUser();
    $pelanggan = makePelanggan();
    $kCheap = makeKamar(['harga'=>100000]);
    $kExp = makeKamar(['harga'=>250000]);

    test()->post(route('booking.store'), baseBookingPayload($pelanggan->id, [$kExp->id]))->assertRedirect();
    $order = BookingOrder::with('items')->latest('id')->first();
    $item = $order->items->first();

    $res = test()->post(route('booking.upgrade_room', $order->id), [
        'item_id' => $item->id,
        'new_kamar_id' => $kCheap->id,
    ]);
    $res->assertSessionHas('error');
});

it('records top-up in ledger when already lunas and total increases after move', function(){
    authUser();
    $pelanggan = makePelanggan();
    $k1 = makeKamar(['harga'=>100000]);
    $k2 = makeKamar(['harga'=>200000]);

    // Make order lunas at 100k
    test()->post(route('booking.store'), baseBookingPayload($pelanggan->id, [$k1->id], [
        'dp_amount' => 100000,
    ]))->assertRedirect();
    $order = BookingOrder::with('items')->latest('id')->first();
    test()->post(route('booking.payment', $order->id), [ 'payment_status' => 'lunas' ])->assertRedirect();

    // Move to higher price room 200k
    test()->post(route('booking.move_room', $order->id), [
        'item_id' => $order->items->first()->id,
        'new_kamar_id' => $k2->id,
    ])->assertRedirect();

    $order->refresh();
    expect((int)$order->total_harga)->toBe(200000);
    $dp = (int)$order->dp_amount; // should be topped up to 200k
    expect($dp)->toBe(200000);
});
