<?php

namespace App;

use App\Domain\Order\ValueObjects\OrderStatusSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    protected $fillable=['restaurant_id','user_id','product_id','qty','price','total_items','latitude','longitude','offer_discount','tax','delivery_charges','sub_total','total','admin_commission','restaurant_commission','driver_tip','delivery_address','scheduled_date','ordered_time','delivered_time','order_no','d_lat','d_lng','payment_method','payment_status','status','business_status','technical_status','accepted_at','preparation_started_at','ready_at','cancelled_at','driver_id','fulfillment_mode','pickup_code','customer_arrived_at','customer_picked_up_at'];

    protected $casts = [
        'ordered_time' => 'datetime',
        'delivered_time' => 'datetime',
        'scheduled_date' => 'datetime',
        'accepted_at' => 'datetime',
        'preparation_started_at' => 'datetime',
        'ready_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'customer_arrived_at' => 'datetime',
        'customer_picked_up_at' => 'datetime',
    ];
    
   public function restaurant()
   {
       return $this->belongsTo(Restaurant::class);
   }
   public function product()
   {
       return $this->belongsTo(Product::class);
   }
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function rating()
    {
        return $this->hasOne(Rating::class);
    }
    
    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }
    
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Toutes les lignes de commande partageant le même order_no.
     * La table orders stocke 1 ligne par produit avec le même order_no.
     */
    public function cartDetails()
    {
        return $this->hasMany(self::class, 'order_no', 'order_no')
                    ->with('product');
    }

    public function resolveEffectiveBusinessStatus(): string
    {
        if (!$this->isPickup() && $this->relationLoaded('delivery') && $this->delivery) {
            $mapped = $this->mapDeliveryStatusToBusiness($this->delivery->status);
            if ($mapped !== null && $mapped !== 'dispatching') {
                return $mapped;
            }
        }

        if (!empty($this->business_status)) {
            return $this->business_status;
        }

        return $this->mapLegacyStatusToBusiness($this->status);
    }

    public function resolveTrackingStatus(): string
    {
        if ($this->isPickup()) {
            return match ($this->resolveEffectiveBusinessStatus()) {
                'pending_restaurant_acceptance' => 'pending',
                'accepted', 'in_kitchen' => 'prepairing',
                'ready_for_pickup', 'customer_arrived' => 'assign',
                'picked_up_by_customer', 'closed' => 'completed',
                'no_show', 'cancelled' => 'cancelled',
                default => 'pending',
            };
        }

        return match ($this->resolveEffectiveBusinessStatus()) {
            'pending_restaurant_acceptance' => 'pending',
            'accepted', 'in_kitchen' => 'prepairing',
            'ready_for_pickup', 'dispatching', 'driver_assigned', 'driver_arrived_at_restaurant' => 'assign',
            'picked_up' => 'pickup',
            'out_for_delivery', 'delivery_attempt_failed', 'incident_open' => 'onway',
            'delivered', 'closed' => 'completed',
            'cancelled', 'refunded' => 'cancelled',
            default => 'pending',
        };
    }

    public function resolveTrackingProgress(): int
    {
        return [
            'pending' => 0,
            'prepairing' => 25,
            'assign' => 50,
            'pickup' => 75,
            'onway' => 90,
            'completed' => 100,
            'cancelled' => 0,
        ][$this->resolveTrackingStatus()] ?? 0;
    }

    protected function mapLegacyStatusToBusiness(?string $legacyStatus): string
    {
        if ($this->isPickup()) {
            return match (strtolower((string) $legacyStatus)) {
                'pending' => 'pending_restaurant_acceptance',
                'prepairing' => 'in_kitchen',
                'assign' => 'ready_for_pickup',
                'pickup' => 'customer_arrived',
                'completed' => 'picked_up_by_customer',
                'cancelled' => 'cancelled',
                default => 'pending_restaurant_acceptance',
            };
        }

        return match (strtolower((string) $legacyStatus)) {
            'pending' => 'pending_restaurant_acceptance',
            'prepairing' => 'in_kitchen',
            'assign' => 'dispatching',
            'pickup' => 'picked_up',
            'onway' => 'out_for_delivery',
            'completed' => 'delivered',
            'cancelled' => 'cancelled',
            default => 'pending_restaurant_acceptance',
        };
    }

    protected function mapDeliveryStatusToBusiness(?string $deliveryStatus): ?string
    {
        return match (strtoupper((string) $deliveryStatus)) {
            'PENDING' => 'dispatching',
            'ASSIGNED' => 'driver_assigned',
            'PICKED_UP' => 'picked_up',
            'ON_THE_WAY' => 'out_for_delivery',
            'DELIVERED' => 'delivered',
            'CANCELLED' => 'cancelled',
            default => null,
        };
    }

    public function isPickup(): bool
    {
        return strtolower((string) ($this->fulfillment_mode ?? 'delivery')) === 'pickup';
    }

    public function canBeModified(): bool
    {
        $businessStatus = $this->resolveEffectiveBusinessStatus();

        if (!in_array($businessStatus, ['pending_restaurant_acceptance', 'accepted'], true)) {
            return false;
        }

        if ($this->relationLoaded('delivery') && $this->delivery) {
            $deliveryStatus = strtoupper((string) $this->delivery->status);

            if (in_array($deliveryStatus, ['PICKED_UP', 'ON_THE_WAY', 'DELIVERED', 'CANCELLED'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Lecture unifiée du statut de la commande.
     *
     * Délègue aux méthodes existantes pour rester la source canonique.
     * Charge la relation delivery si elle ne l'est pas encore.
     */
    public function statusSnapshot(): OrderStatusSnapshot
    {
        if (! $this->relationLoaded('delivery') && $this->exists) {
            $this->load('delivery');
        }

        $delivery = $this->relationLoaded('delivery') ? $this->getRelation('delivery') : null;

        return new OrderStatusSnapshot(
            effectiveBusinessStatus: $this->resolveEffectiveBusinessStatus(),
            trackingStatus:          $this->resolveTrackingStatus(),
            trackingProgress:        $this->resolveTrackingProgress(),
            technicalStatus:         $this->technical_status,
            deliveryStatus:          $delivery?->status,
            paymentStatus:           (string) ($this->payment_status ?? 'pending'),
            isPickup:                $this->isPickup(),
            canBeModified:           $this->canBeModified(),
        );
    }

}
