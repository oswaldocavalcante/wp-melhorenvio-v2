<?php

namespace MelhorEnvio\Services\Products;

use MelhorEnvio\Models\Product;

class CompositeService extends ProductsService
{
	const PRODUCT_COMPOSITE_TYPE = 'composite';
	const PRODUCT_COMPOSITE_PRICING = 'wooco_pricing';
	const PRODUCT_COMPOSITE_SHIPPING_FEE = 'wooco_shipping_fee';

	public function getDataByProductCart( $productCart , $items ): Product
	{
		$data = parent::normalize(
			$productCart['data'],
			! empty($productCart['wooco_price']) ? $productCart['wooco_price'] : $productCart['line_total'],
			$productCart['quantity']
		);

		$data->pricing = self::getPricingType( $productCart['data']->get_id() );
		$data->shipping_fee = self::getShippingFeeType( $productCart['data']->get_id() );

		if ($data->type == self::PRODUCT_COMPOSITE_TYPE) {
			if (isset($productCart['wooco_keys'])) {
				foreach ($productCart['wooco_keys'] as $key) {
					$data->components[] = parent::normalize(
						$items[$key]['data'],
						$items[$key]['line_total'] / $items[$key]['quantity'],
						$items[$key]['quantity']);
				}

				if (($data->pricing == 'include' || $data->pricing == 'only') && $data->shipping_fee == 'each') {
					array_filter($data->components, function($component) {
						$component->setValues(0);
					});

					$data->components[0]->setValues(($data->unitary_value/$data->components[0]->quantity));
				}
			}
		}

		return $data;
	}

	public function getDataByProductOrder( $productOrder, $items ): Product
	{
		$data = parent::normalize(
			$productOrder->get_product(),
			$productOrder->get_meta('wooco_price', true),
			$productOrder->get_quantity()
		);

		$data->pricing = self::getPricingType( $productOrder->get_product()->get_id() );
		$data->shipping_fee = self::getShippingFeeType( $productOrder->get_product()->get_id() );

		if ($data->type == self::PRODUCT_COMPOSITE_TYPE) {
			foreach ($items as $item) {
				$woocoParentId = $item->get_meta('wooco_parent_id', true);
				if ($woocoParentId !== null && $woocoParentId == $productOrder->get_product()->get_id()) {
					$data->components[] = parent::normalize(
						$item->get_product(),
						$item->get_total() / $item->get_quantity(),
						$item->get_quantity()
					);
				}
			}

			if (($data->pricing == 'include' || $data->pricing == 'only') && $data->shipping_fee == 'each') {
				array_filter($data->components, function($component) {
					$component->setValues(0);
				});

				$data->components[0]->setValues(($data->unitary_value/$data->components[0]->quantity));
			}
		}

		return $data;
	}

	/**
	 * Function to get type pricing
	 *
	 * @return string
	 */
	public function getPricingType( $productId ): string
	{
		return get_post_meta( $productId, self::PRODUCT_COMPOSITE_PRICING, true );
	}

	/**
	 * Function to get type shipping fee
	 *
	 * @return string
	 */
	public function getShippingFeeType( $productId ): string
	{
		return get_post_meta(  $productId, self::PRODUCT_COMPOSITE_SHIPPING_FEE, true );
	}
}
