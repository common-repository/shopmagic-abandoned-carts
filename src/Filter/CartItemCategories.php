<?php

namespace WPDesk\ShopMagicCart\Filter;

use WPDesk\ShopMagic\Workflow\Filter\ComparisonType\ComparisonType;
use WPDesk\ShopMagic\Workflow\Filter\ComparisonType\SelectManyToManyType;


final class CartItemCategories extends CartBasedFilter {
	public function get_name(): string {
		return __( 'Cart - Item Categories', 'shopmagic-abandoned-carts' );
	}

	public function get_description(): string {
		return esc_html__('Run automation if products in cart matches the rule.', 'shopmagic-abandoned-carts');
	}

	public function passed(): bool {
		$terms = [];
		foreach ( $this->get_cart()->get_items() as $item ) {
			$terms[] = wp_get_object_terms( $item->get_product_id(), 'product_cat', [ 'fields' => 'ids' ] );
		}
		$category_ids = array_filter( array_merge( ...$terms ) );

		return $this->get_type()->passed(
			$this->fields_data->get( SelectManyToManyType::VALUE_KEY ),
			$this->fields_data->get( SelectManyToManyType::CONDITION_KEY ),
			$category_ids
		);
	}

	protected function get_type(): ComparisonType {
		return new SelectManyToManyType( $this->get_categories() );
	}

	/**
	 * @return array<string, string>
	 */
	private function get_categories(): array {
		$list = [];

		$categories = get_terms(
			'product_cat',
			[
				'orderby'    => 'name',
				'hide_empty' => false,
			]
		);

		foreach ( $categories as $category ) {
			$list[ $category->term_id ] = $category->name;
		}

		return $list;
	}
}
