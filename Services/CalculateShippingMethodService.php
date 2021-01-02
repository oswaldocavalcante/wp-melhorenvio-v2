<?php

namespace Services;

use Helpers\MoneyHelper;
use Helpers\TimeHelper;
use Models\ShippingService;

class CalculateShippingMethodService
{
    /**
     * Constant for delivery class of any class
     */
    const ANY_DELIVERY = -1;

    /**
     * Constant for no delivery class
     */

    const WITHOUT_DELIVERY = 0;

    /**
     * Function to carry out the freight quote in the Melhor Envio api.
     *
     * @param array $package
     * @param int $code
     * @param int $id
     * @param string $company
     * @param string $title
     * @param float $taxExtra
     * @param int $timeExtra
     * @param int $percent
     * @return false
     */
    public function calculateShipping($package = [], $code, $id, $company, $title, $taxExtra, $timeExtra, $percent)
    {
        $to = preg_replace('/\D/', '', $package['destination']['postcode']);

        $products = (isset($package['contents']))
            ? $package['contents']
            : (new CartWooCommerceService())->getProducts();

        $result = (new QuotationService())->calculateQuotationByProducts(
            $products,
            $to,
            $code
        );

        if (is_array($result)) {
            $result = $this->extractOnlyQuotationByService($result, $code);
        }

        if ($result) {
            if (isset($result->price) && isset($result->name)) {
                if ($this->isCorreios($code) && $this->hasMultipleVolumes($result)) {
                    return false;
                }

                $rate = [
                    'id' => $id,
                    'label' => $title . TimeHelper::label(
                        $result->delivery_range,
                        $timeExtra
                    ),
                    'cost' => MoneyHelper::cost(
                        $result->price,
                        $taxExtra,
                        $percent
                    ),
                    'calc_tax' => 'per_item',
                    'meta_data' => [
                        'delivery_time' => TimeHelper::label(
                            $result->delivery_range,
                            $timeExtra
                        ),
                        'price' => MoneyHelper::price(
                            $result->price,
                            $taxExtra,
                            $percent
                        ),
                        'company' => $company
                    ]
                ];
            }

            if (!empty($rate)) {
                return $rate;
            }
        }

        return false;
    }

    /**
     * Check if it has more than one volume
     *
     * @param stdClass $quotation
     * @return boolean
     */
    public function hasMultipleVolumes($quotation)
    {
        if (!isset($quotation->packages)) {
            return false;
        }

        return (count($quotation->packages) >= 2) ? true : false;
    }

    /**
     * Check if it is "Correios"
     *
     * @param int $code
     * @return boolean
     */
    public function isCorreios($code)
    {
        return in_array($code, ShippingService::SERVICES_CORREIOS);
    }

    /**
     * Check if it is "Jadlog"
     *
     * @param int $code
     * @return boolean
     */
    public function isJadlog($code)
    {
        return in_array($code, ShippingService::SERVICES_JADLOG);
    }

    /**
     * Check if it is "Azul Cargo"
     *
     * @param int $code
     * @return boolean
     */
    public function isAzulCargo($code)
    {
        return in_array($code, ShippingService::SERVICES_AZUL);
    }

    /**
     * Function to extract the quotation by the shipping method
     *
     * @param array $quotations
     * @param int $service
     * @return object
     */
    public function extractOnlyQuotationByService($quotations, $service)
    {
        $quotationByService = array_filter(
            $quotations,
            function ($item) use ($service) {
                if ($item->id == $service) {
                    return $item;
                }
            }
        );

        if (!is_array($quotationByService)) {
            return false;
        }

        return end($quotationByService);
    }

    /**
     * Get shipping classes options.
     *
     * @return array
     */
    public function getShippingClassesOptions()
    {
        $shippingClasses = WC()->shipping->get_shipping_classes();
        $options = array(
            self::WITHOUT_DELIVERY  => 'Sem classe de entrega',
        );

        if (!empty($shippingClasses)) {
            $options += wp_list_pluck($shippingClasses, 'name', 'term_id');
        }

        return $options;
    }

    /**
     * Check if package uses only the selected shipping class.
     *
     * @param  array $package Cart package.
     * @param int $shippingClassId
     * @return bool
     */
    public function needShowShippginMethod($package, $shippingClassId)
    {
        $show = false;

        if (!empty($package['cotationProduct'])) {
            foreach ($package['cotationProduct'] as $product) {

                if ($this->isProductWithouShippingClass($product->shipping_class_id, $shippingClassId)) {
                    $show = true;
                    break;
                }

                $show = ($product->shipping_class_id == $shippingClassId);
            }
            return $show;
        }

        foreach ($package['contents'] as $values) {
            $product = $values['data'];
            $qty     = $values['quantity'];
            if ($qty > 0 && $product->needs_shipping()) {
                if ($this->isProductWithouShippingClass($product->get_shipping_class_id(), $shippingClassId)) {
                    $show = true;
                    break;
                }
                $show = ($product->get_shipping_class_id() == $shippingClassId);
            }
        }

        return $show;
    }

    /**
     * Function to check if product not has shipping class.
     *
     * @param int $productShippingClassId
     * @param int $shippingClassId
     * @return boolean
     */
    private function isProductWithouShippingClass($productShippingClassId, $shippingClassId)
    {
        $shippingsMehodsWithoutClass = [
            self::ANY_DELIVERY,
            self::WITHOUT_DELIVERY
        ];

        return (in_array($productShippingClassId, $shippingsMehodsWithoutClass) && in_array($shippingClassId, $shippingsMehodsWithoutClass));
    }

    /**
     * Function to check if the insured amount is mandatory
     *
     * @param bool $optionalInsuredAmount
     * @param string $serviceId
     * @return bool
     */
    public function insuranceValueIsRequired($optionalInsuredAmount, $serviceId)
    {
        if ($optionalInsuredAmount && is_null($serviceId)) {
            return true;
        }

        if (!$this->isCorreios($serviceId)) {
            return true;
        }

        return $optionalInsuredAmount;
    }
}
