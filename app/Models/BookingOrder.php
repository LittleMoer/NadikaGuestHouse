<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Pelanggan;
use App\Models\BookingRoomTransfer;

class BookingOrder extends Model
{
    // Menggunakan tabel 'booking' sebagai header multi-kamar
    protected $table = 'booking';
    protected $fillable = [
        'pelanggan_id',
        'tanggal_checkin',
        'tanggal_checkout',
        'status',
        'payment_status',
        'payment_method',
        'pemesanan',
        'catatan',
        'total_harga',
        'jumlah_tamu_total',
        'total_cafe',
        // legacy percentage kept for BC, but we now use nominal dp_amount
        'dp_percentage',
        // new fields
        'dp_amount',
        'diskon',
        'discount_review',
        'discount_follow',
        'extra_time', // values: none, h3(+35%), h6(+50%), h9(+85%), d1(+100%)
        'per_head_mode', // boolean-like 0/1
        'biaya_tambahan',
        // new: human-friendly booking number that can reset per period
        'booking_number'
    ];

    protected $casts = [
        'tanggal_checkin' => 'datetime',
        'tanggal_checkout' => 'datetime',
        'discount_review' => 'boolean',
        'discount_follow' => 'boolean',
        'per_head_mode' => 'boolean',
        'biaya_tambahan' => 'integer',
    ];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class,'pelanggan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingOrderItem::class,'booking_order_id');
    }

    public function cafeOrders(): HasMany
    {
        return $this->hasMany(CafeOrder::class,'booking_id');
    }

    public function roomTransfers(): HasMany
    {
        return $this->hasMany(BookingRoomTransfer::class, 'booking_id');
    }

    /**
     * Scope: only bookings that are currently checked-in (active for addons like Cafe orders)
     */
    public function scopeActiveCheckin($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Display meta based on payment_status (dp/lunas), pemesanan (0/1), and status lifecycle (1..4)
     * - status: 1=dipesan, 2=checkin, 3=checkout, 4=dibatalkan
     * - pemesanan: 0=walkin, 1=online (traveloka)
     * - payment_status: 'dp' or 'lunas'
     */
    public function getStatusMetaAttribute(): array
    {
        $isCancel = ((int)$this->status) === 4;
        $pay = in_array($this->payment_status, ['lunas','dp','dp_cancel']) ? $this->payment_status : 'dp';
        // Map pemesanan to channel: 0=walkin, 1=traveloka(online), 2=agent1, 3=agent2
        $channel = 'walkin';
        if($isCancel){
            $channel = 'cancel';
        } else {
            $map = [0=>'walkin', 1=>'traveloka', 2=>'agent1', 3=>'agent2'];
            $channel = $map[(int)$this->pemesanan] ?? 'traveloka';
        }
        $bgMap = [
            'walkin'=>'#dc3545',
            'agent1'=>'#6f42c1',
            'agent2'=>'#198754',
            'traveloka'=>'#0d6efd',
            'cancel'=>'#555'
        ];
        $textColor = $pay==='dp'? '#faed00':'#ffffff';
        $label = $this->buildStatusLabelFromParts($pay, $channel, $isCancel);
        return [
            'code'=>null, // legacy removed
            'payment'=>$pay,
            'channel'=>$channel,
            'background'=>$bgMap[$channel] ?? '#999',
            'text_color'=>$textColor,
            'is_cancel'=>$isCancel,
            'label'=>$label
        ];
    }

    private function buildStatusLabelFromParts(string $payment, string $channel, bool $isCancel): string
    {
        if($isCancel) return 'Dibatalkan';
        $payLabel = $payment==='lunas' ? 'Lunas' : ($payment==='dp_cancel' ? 'DP Batal' : 'DP');
        $channelLabelMap = [
            'walkin'=>'Walk-In',
            'traveloka'=>'Traveloka',
            'agent1'=>'Agent 1',
            'agent2'=>'Agent 2',
            'cancel'=>'-'
        ];
        $ch = $channelLabelMap[$channel] ?? ucfirst($channel);
        return $payLabel.' '.$ch;
    }

    /**
     * Computed attribute: order_code in format YYYYMM + zero-padded order id (at least 2 digits)
     * Example: October 2025 with id=1 => 20251001
     */
    public function getOrderCodeAttribute(): string
    {
        // Prefer stored booking_number when available for stability and reset capability
        if (!empty($this->booking_number)) {
            return (string)$this->booking_number;
        }
        // Fallback to legacy format based on id to preserve BC on older rows
        $ym = $this->tanggal_checkin ? $this->tanggal_checkin->format('Ym') : now()->format('Ym');
        $idPart = str_pad((string)($this->id ?? 0), 2, '0', STR_PAD_LEFT);
        return $ym . $idPart;
    }

    /**
     * Custom formatted ID for display on notas.
     * Format: (pemesanan)(yy)(mm)(padded_id)
     */
    public function getFormattedIdAttribute(): string
    {
        $prefixMap = [
            0 => 'WLK', // Walk-in
            1 => 'TRV', // Traveloka
            2 => 'ANA', // Agent 1
            3 => 'ANB', // Agent 2
        ];
        $prefix = $prefixMap[(int)($this->pemesanan ?? 0)] ?? 'XXX';
        $year = $this->tanggal_checkin ? $this->tanggal_checkin->format('y') : now()->format('y');
        $month = $this->tanggal_checkin ? $this->tanggal_checkin->format('m') : now()->format('m');
        $idPart = substr((string)($this->order_code ?? '000'), -3);

        return "{$prefix}{$year}{$month}-{$idPart}";
    }
}
