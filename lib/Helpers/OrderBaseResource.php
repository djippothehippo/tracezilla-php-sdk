<?php

namespace TracezillaConnector\Helpers;

use TracezillaConnector\BaseResource;
use TracezillaConnector\Exceptions\PartnerRoleNotAllowedForOrderType;
use TracezillaConnector\Exceptions\PartnerRoleRequiredForOrderType;
use TracezillaConnector\Resources\PartnerLocation;
use TracezillaConnector\Resources\Sku;

class OrderBaseResource extends BaseResource
{
    /**
     * Partner roles that are allowed
     */
    protected $orderType = '';

    /**
     * Partner roles that are required
     */
    protected $primaryPartnerRole = '';

    /**
     * Partner roles that are allowed
     */
    protected $allowedPartnerRoles = [
        'deliver_to',
        'pickup_from'
    ];

    /**
     * Order header
     */
    protected $orderHeader = [];

    /**
     * Order inbound lines
     */
    protected $inboundLotLines = [];

    /**
     * Order outbound lines
     */
    protected $outboundLotLines = [];

    /**
     * Partners on the order
     */
    protected $partners = [];

    /**
     * Add new partner location to order
     */
    public function addPartnerLocation(string $role, string $partnerId, string $partnerLocationId)
    {
        if (!in_array($role, $this->allowedPartnerRoles)) {
            $allowedPartnerRoles = implode(', ', $this->allowedPartnerRoles);
            throw new PartnerRoleNotAllowedForOrderType("The role $role is not allowed on {$this->orderType} orders. Allowed order types are: $allowedPartnerRoles");
        }

        $this->partners[$role] = [
            'partner_id' => $partnerId,
            'location_id' => $partnerLocationId
        ];

        return $this;
    }

    /**
     * Get partner roles array
     */
    public function buildPartnerLocationsForRequest()
    {
        /**
         * Check if all required partner roles have been set
         */
        $return = [];

        if (!isset($this->partners[$this->primaryPartnerRole])) {
            throw new PartnerRoleRequiredForOrderType("The role {$this->primaryPartnerRole} is required on {$this->orderType} orders.");
        }

        foreach ($this->partners as $role => $partnerLocation) {
            $return[$role] = [
                'partner_id' => $partnerLocation['partner_id'],
                'location_id' => $partnerLocation['location_id'],
            ];
        }

        return $return;
    }

    /**
     * Set order header
     */
    public function setOrderHeader($data) {
        $this->orderHeader = $data;
        return $this;
    }

    /**
     * Helper function to build order header array for request
     */
    public function buildOrderHeaderForRequest() {
        $this->orderHeader['partners'] = $this->buildPartnerLocationsForRequest();
        return $this->orderHeader;
    }

    /**
     * Add inbound lot line to order
     */
    public function addInboundLotLine(string $skuId, int $quantity, float $unitPrice = 0, array $additional = []) {
        $additional['sku_id'] = $skuId;
        $additional['quantity'] = $quantity;
        $additional['unit_price'] = $unitPrice;
        
        $this->inboundLotLines[] = $additional;

        return $this;
    }

    /**
     * Add outbound lot line to order
     */
    public function addOutboundLotLine(string $skuId, int $quantity, float $unitPrice = 0, array $additional = []) {
        $additional['sku_id'] = $skuId;
        $additional['quantity'] = $quantity;
        $additional['unit_price'] = $unitPrice;
        
        $this->outboundLotLines[] = $additional;

        return $this;
    }
}
