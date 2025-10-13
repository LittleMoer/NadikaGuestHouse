<?php

use App\Models\User;
use App\Models\Pelanggan;
use App\Models\Kamar;
use App\Models\BookingOrder;
use App\Models\BookingOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authDetailedUser() {
    $user = User::factory()->create();
    test()->actingAs($user);
    return $user;
}

function makeDetKamar($attrs = []){
    static $num = 300;
    $num++;
    return Kamar::create(array_merge([
        'nomor_kamar' => (string)$num,
        'tipe' => 'STD',
        'kapasitas' => 2,
        'harga' => 200000,
        'deskripsi' => null,
    ], $attrs));
}

function makeDetPelanggan(){
    return Pelanggan::create([
        'nama' => 'Jane Doe',
        'telepon' => '08111111111',
        'alamat' => 'Alamat X',
    ]);
}

function detPayload($pelangganId, $kamarIds, $overrides = []){
    $checkin = now()->startOfDay()->addDays(3)->setTime(14,0);
    $checkout = now()->startOfDay()->addDays(5)->setTime(12,0); // 2 malam
    return array_merge([
        'pelanggan_id' => $pelangganId,
        'kamar_ids' => $kamarIds,
        'tanggal_checkin' => $checkin->toDateTimeString(),
        'tanggal_checkout' => $checkout->toDateTimeString(),
        'jumlah_tamu' => 2,
        'pemesanan' => 0,
        'status' => 2,
        'payment_status' => 'dp',
        'catatan' => 'Det',
        'biaya_tambahan' => 0,
    ], $overrides);
}

it('calculates multi-night totals correctly and ignores extra_time price multiplier', function(){
    authDetailedUser();
    $pelanggan = makeDetPelanggan();
    $k = makeDetKamar(['harga'=>150000]);

    // 2 malam expected = 300k; extra_time should not change price
    $checkin = now()->startOfDay()->addDays(2)->setTime(14,0);
    $checkout = now()->startOfDay()->addDays(4)->setTime(12,0);

    $res = test()->post(route('booking.store'), detPayload($pelanggan->id, [$k->id], [
        'tanggal_checkin'=>$checkin->toDateTimeString(),
        'tanggal_checkout'=>$checkout->toDateTimeString(),
        'extra_time' => 'h6',
    ]));
    $res->assertRedirect();

    $order = BookingOrder::latest('id')->first();
    expect((int)$order->total_harga)->toBe(300000);
});

it('enforces minimum total 100k for very cheap room and short stay', function(){
    authDetailedUser();
    $pelanggan = makeDetPelanggan();
    $k = makeDetKamar(['harga'=>40000]);

    $start = now()->startOfDay()->addDay()->setTime(8,0);
    $end = $start->copy()->setTime(10,0); // 2 hours, half-day rule applies but still min 100k

    test()->post(route('booking.store'), detPayload($pelanggan->id, [$k->id], [
        'tanggal_checkin'=>$start->toDateTimeString(),
        'tanggal_checkout'=>$end->toDateTimeString(),
    ]))->assertRedirect();

    $order = BookingOrder::latest('id')->first();
    expect((int)$order->total_harga)->toBe(100000);
});

it('applies per-head charge after half-day properly', function(){
    authDetailedUser();
    $pelanggan = makeDetPelanggan();
    $k = makeDetKamar(['harga'=>100000]);

    $start = now()->startOfDay()->addDay()->setTime(9,0);
    $end = $start->copy()->setTime(14,0); // 5 hours => half-day

    test()->post(route('booking.store'), detPayload($pelanggan->id, [$k->id], [
        'tanggal_checkin'=>$start->toDateTimeString(),
        'tanggal_checkout'=>$end->toDateTimeString(),
        'jumlah_tamu'=>3,
        'per_head_mode'=>true,
    ]))->assertRedirect();

    $order = BookingOrder::latest('id')->first();
    // base 100k -> half 50k -> +50k (1 extra guest) = 100k
    expect((int)$order->total_harga)->toBe(100000);
});

it('recalculates correctly on update() date change preserving kamar prices and discounts', function(){
    authDetailedUser();
    $pelanggan = makeDetPelanggan();
    $k = makeDetKamar(['harga'=>120000]);

    test()->post(route('booking.store'), detPayload($pelanggan->id, [$k->id], [
        'discount_review'=>true,
    ]))->assertRedirect();

    $order = BookingOrder::with('items')->latest('id')->first();

    // Move to 3 nights
    $newCheckin = now()->startOfDay()->addDays(1)->setTime(14,0);
    $newCheckout = now()->startOfDay()->addDays(4)->setTime(12,0);

    test()->post(route('booking.update', $order->id), [
        'tanggal_checkin'=>$newCheckin->toDateTimeString(),
        'tanggal_checkout'=>$newCheckout->toDateTimeString(),
        'pemesanan'=>0,
    ])->assertRedirect();

    $order->refresh();
    // base 3 * 120k = 360k, review -10% => 324k
    expect((int)$order->total_harga)->toBe(324000);
});

it('prevents moving to occupied room on overlapping dates', function(){
    authDetailedUser();
    $pelanggan = makeDetPelanggan();
    $k1 = makeDetKamar(['harga'=>100000]);
    $k2 = makeDetKamar(['harga'=>200000]);

    // Occupy k2 with another order
    $pel2 = makeDetPelanggan();
    $start = now()->startOfDay()->addDays(2);
    $end = now()->startOfDay()->addDays(3);
    test()->post(route('booking.store'), detPayload($pel2->id, [$k2->id], [
        'tanggal_checkin'=>$start->toDateTimeString(),
        'tanggal_checkout'=>$end->toDateTimeString(),
    ]))->assertRedirect();

    // Create order on k1 for overlapping dates
    test()->post(route('booking.store'), detPayload($pelanggan->id, [$k1->id], [
        'tanggal_checkin'=>$start->toDateTimeString(),
        'tanggal_checkout'=>$end->toDateTimeString(),
    ]))->assertRedirect();
    $order = BookingOrder::with('items')->latest('id')->first();

    // Try moving k1 item to occupied k2 -> should error
    $res = test()->post(route('booking.move_room', $order->id), [
        'item_id' => $order->items->first()->id,
        'new_kamar_id' => $k2->id,
    ]);
    $res->assertSessionHas('error');
});

it('rejects moving to the same room id', function(){
    authDetailedUser();
    $pelanggan = makeDetPelanggan();
    $k = makeDetKamar(['harga'=>150000]);

    test()->post(route('booking.store'), detPayload($pelanggan->id, [$k->id]))->assertRedirect();
    $order = BookingOrder::with('items')->latest('id')->first();

    $res = test()->post(route('booking.move_room', $order->id), [
        'item_id' => $order->items->first()->id,
        'new_kamar_id' => $k->id,
    ]);
    $res->assertSessionHas('error');
});

it('allows upgrade to same-price room', function(){
    authDetailedUser();
    $pelanggan = makeDetPelanggan();
    $k1 = makeDetKamar(['harga'=>180000]);
    $k2 = makeDetKamar(['harga'=>180000]);

    // Use explicit 1-night stay so expected equals 1 * 180k
    $start = now()->startOfDay()->addDay()->setTime(14,0);
    $end = now()->startOfDay()->addDays(2)->setTime(12,0);
    test()->post(route('booking.store'), detPayload($pelanggan->id, [$k1->id], [
        'tanggal_checkin' => $start->toDateTimeString(),
        'tanggal_checkout' => $end->toDateTimeString(),
    ]))->assertRedirect();
    $order = BookingOrder::with('items')->latest('id')->first();
    $item = $order->items->first();

    // upgradeRoom should accept >= current price, same price is OK
    test()->post(route('booking.upgrade_room', $order->id), [
        'item_id' => $item->id,
        'new_kamar_id' => $k2->id,
    ])->assertRedirect();

    $order->refresh();
    expect((int)$order->total_harga)->toBe(180000);
});

it('logs refund when already lunas and total decreases after move', function(){
    authDetailedUser();
    $pelanggan = makeDetPelanggan();
    $kExp = makeDetKamar(['harga'=>250000]);
    $kCheap = makeDetKamar(['harga'=>100000]);

    // Make order lunas at 250k (1-night stay)
    $start = now()->startOfDay()->addDay()->setTime(14,0);
    $end = now()->startOfDay()->addDays(2)->setTime(12,0);
    test()->post(route('booking.store'), detPayload($pelanggan->id, [$kExp->id], [
        'tanggal_checkin' => $start->toDateTimeString(),
        'tanggal_checkout' => $end->toDateTimeString(),
        'dp_amount' => 250000,
    ]))->assertRedirect();
    $order = BookingOrder::with('items')->latest('id')->first();
    test()->post(route('booking.payment', $order->id), [ 'payment_status' => 'lunas' ])->assertRedirect();

    // Move to cheaper room 100k -> should log dp_canceled for 150k and reduce dp_amount
    test()->post(route('booking.move_room', $order->id), [
        'item_id' => $order->items->first()->id,
        'new_kamar_id' => $kCheap->id,
    ])->assertRedirect();

    $order->refresh();
    expect((int)$order->total_harga)->toBe(100000);
    expect((int)$order->dp_amount)->toBe(100000);
});
