<?php

namespace TracezillaConnector\Resources;

use TracezillaConnector\Helpers\OrderBaseResource;

class SalesOrder extends OrderBaseResource {
    /**
     * Resource name to be used in URLs
     */
    const BASE_ENDPOINT = 'orders/sales';

    /**
     * Function to store sold sku lines for put sales order requests
     */
    protected $soldSkuLines = [];

    /**
     * Primary partner role, this is always required
     */
    protected $primaryPartnerRole = 'customer';

    /**
     * Partner roles that are allowed
     */
    protected $allowedPartnerRoles = [
        'customer',
        'invoice_to',
        'forwarder',
        'deliver_to',
        'pickup_from'
    ];

    /**
     * This function tries to find an existing order by ext_ref and update this order.
     * If no existing order exists a new one will automatically be created.
     */
    public function putSalesOrder($priceLogic = 'none', $actionOnMissingLotSelection = 'none', $actionOnMissingInventory = 'none', $postSaveAction = 'none', bool $ignoreOrderState = false, bool $ignoreMissingSkus = false) {
        $endpoint = 'orders/sales';

        $resource = $this->connector->putRequest($endpoint, [
            'order_header' => $this->buildOrderHeaderForRequest(),
            'outbound_skus' => $this->buildSoldSkuLinesForRequest(),
            'price_logic' => $priceLogic,
            'action_on_missing_lot_selection' => $actionOnMissingLotSelection,
            'action_on_missing_inventory' => $actionOnMissingInventory,
            'post_save_action' => $postSaveAction,
            'ignore_order_state' => $ignoreOrderState,
            'ignore_missing_skus' => $ignoreMissingSkus,
        ]);

        $data = $resource['data'];

        return $this->setActiveResource($data, ['tags', 'remark', 'partners']);
    }

    /**
     * 
     */
    public function addSoldSkuLine(string $skuIdentifierType = 'sku_code', string $skuIdentifier, int $quantity, float $unitPrice = null, bool $traceable = null) {
        $data = [
            $skuIdentifierType => $skuIdentifier,
            'quantity' => $quantity
        ];

        if (!is_null($unitPrice)) {
            $data['unit_price'] = $unitPrice;
        }

        if (!is_null($traceable)) {
            $data['traceable'] = $traceable;
        }

        $this->soldSkuLines[] = $data;
    }

    public function buildSoldSkuLinesForRequest() {
        return $this->soldSkuLines;
    }
}